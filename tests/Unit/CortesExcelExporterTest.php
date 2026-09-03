<?php

namespace Tests\Unit;

use App\Models\Usuario;
use App\Services\CortesExcelExporter;
use Illuminate\Support\Collection;
use Tests\TestCase;

class CortesExcelExporterTest extends TestCase
{
    public function test_resalta_solo_usuarios_con_dos_o_mas_meses_de_adeudo(): void
    {
        $unMes = new Usuario(['numero_servicio' => '1001', 'nombre_cliente' => 'Solo agosto']);
        $unMes->meses_adeudo_corte = 1;

        $variosMeses = new Usuario(['numero_servicio' => '1002', 'nombre_cliente' => 'Julio y agosto']);
        $variosMeses->meses_adeudo_corte = 2;

        $response = (new CortesExcelExporter)->download(new Collection([$unMes, $variosMeses]));
        ob_start();
        $response->sendContent();
        $contenido = ob_get_clean();

        $this->assertStringContainsString('<tr><td>1001</td>', $contenido);
        $this->assertStringContainsString('<tr class="varios-meses"><td>1002</td>', $contenido);
        $this->assertSame('application/vnd.ms-excel; charset=UTF-8', $response->headers->get('Content-Type'));
    }
}
