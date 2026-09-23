<?php

namespace Tests\Feature;

use App\Models\Factura;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HistorialModificacionesTest extends TestCase
{
    use RefreshDatabase;

    private function recibo(string $folio, string $fecha, array $payload = []): Factura
    {
        $factura = new Factura([
            'reference_number' => $folio,
            'numero_servicio' => '1007',
            'periodo' => '2026-09',
            'fecha_contable' => '2026-08-15',
            'total' => 159,
            'created_by' => auth()->id(),
            'payload' => array_merge([
                'nombre' => 'Cliente de prueba',
                'manual_total_enabled' => true,
                'manual_total_reason' => 'Corrección autorizada',
                'cobro' => 'Cristopher',
            ], $payload),
        ]);
        $factura->created_at = $fecha;
        $factura->save();

        return $factura;
    }

    public function test_historial_y_excel_solo_incluyen_modificaciones_capturadas_en_el_mes(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $primera = $this->recibo('101', '2026-09-01 00:00:00');
        $this->recibo('102', '2026-09-30 23:59:59')->delete();
        $this->recibo('103', '2026-08-31 23:59:59');
        $this->recibo('104', '2026-10-01 00:00:00');
        $this->recibo('105', '2026-09-23 12:00:00', [
            'manual_total_enabled' => false, 'adeudo_monto_previo' => 900,
        ]);
        DB::table('audit_logs')->insert([
            'action' => 'factura_total_override', 'table_name' => 'facturas',
            'entity_id' => (string) $primera->id, 'actor_name' => 'Administrador original',
            'prev_values' => json_encode(['total' => 350]),
            'new_values' => json_encode(['total' => 159]),
        ]);

        $response = $this->get(route('admin.dashboard.modificaciones', ['mes' => '2026-09']))
            ->assertOk()->assertViewHas('cantidad', 2)
            ->assertSee('00000101')->assertSee('00000102')->assertSee('Quién cobró')->assertSee('Cristopher')
            ->assertSee('$350.00')->assertSee('$159.00')->assertSee('Administrador original')
            ->assertSee('Corrección autorizada')->assertSee('No registrado')
            ->assertDontSee('00000103')->assertDontSee('00000104')->assertDontSee('00000105');
        $this->assertNull($response->viewData('rows')->first()->total_anterior);

        $excel = $this->get(route('admin.dashboard.modificaciones', ['mes' => '2026-09', 'format' => 'excel']))
            ->assertOk()->assertDownload('historial_modificaciones_2026-09.xls');
        $contenido = $excel->streamedContent();
        foreach (['00000101', '00000102', 'Quién cobró', 'Cristopher', '$350.00', '$159.00', 'Corrección autorizada'] as $texto) {
            $this->assertStringContainsString($texto, $contenido);
        }
        foreach (['00000103', '00000104', '00000105'] as $folio) {
            $this->assertStringNotContainsString($folio, $contenido);
        }
    }

    public function test_total_anterior_ambiguo_en_recibo_viejo_se_muestra_como_no_registrado(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $factura = $this->recibo('101', '2026-09-23 12:00:00');
        DB::table('audit_logs')->insert([
            'action' => 'factura_total_override', 'table_name' => 'facturas',
            'entity_id' => (string) $factura->id,
            'prev_values' => json_encode(['total' => 159]),
        ]);
        $this->get(route('admin.dashboard.modificaciones', ['mes' => '2026-09']))
            ->assertOk()->assertViewHas('rows', fn ($rows) => $rows->first()->total_anterior === null);
    }

    public function test_excel_exporta_todas_las_paginas_y_escapa_el_motivo(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        for ($folio = 1; $folio <= 51; $folio++) {
            $this->recibo((string) $folio, '2026-09-23 12:00:00', [
                'manual_total_reason' => '<script>alert(1)</script>',
            ]);
        }
        $this->get(route('admin.dashboard.modificaciones', ['mes' => '2026-09']))
            ->assertOk()->assertViewHas('cantidad', 51)->assertDontSee('00000001');
        $response = $this->get(route('admin.dashboard.modificaciones', ['mes' => '2026-09', 'format' => 'excel', 'page' => 2]))
            ->assertOk();
        $contenido = $response->streamedContent();
        $this->assertStringContainsString('00000001', $contenido);
        $this->assertStringContainsString('00000051', $contenido);
        $this->assertStringContainsString('&lt;script&gt;', $contenido);
        $this->assertStringNotContainsString('<script>', $contenido);
    }

    public function test_mes_predeterminado_vacio_y_validacion(): void
    {
        $this->travelTo(now()->setDate(2026, 9, 23));
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->get(route('admin.dashboard.modificaciones'))->assertOk()
            ->assertViewHas('mes', '2026-09')->assertSee('No hay modificaciones registradas');
        $this->get(route('admin.dashboard.modificaciones', ['mes' => '2026-09', 'format' => 'excel']))
            ->assertOk()->assertDownload('historial_modificaciones_2026-09.xls');
        foreach (['2026-13', '2026-09-23', 'invalid', ''] as $mes) {
            $this->getJson(route('admin.dashboard.modificaciones', ['mes' => $mes]))
                ->assertUnprocessable()->assertJsonValidationErrors('mes');
        }
    }

    public function test_solo_administradores_pueden_consultar_y_exportar(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'rosalito']));
        $this->get(route('admin.dashboard.modificaciones'))->assertForbidden();
        $this->get(route('admin.dashboard.modificaciones', ['format' => 'excel']))->assertForbidden();
    }

    public function test_dashboard_muestra_acceso_junto_a_exportar_pdf(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->get(route('admin.index'))->assertOk()
            ->assertSeeInOrder(['Exportar PDF', 'Historial de modificaciones']);
    }
}
