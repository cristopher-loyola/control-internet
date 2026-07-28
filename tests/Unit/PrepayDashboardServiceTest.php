<?php

namespace Tests\Unit;

use App\Services\PrepayDashboardService;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PrepayDashboardServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_normalize_numero_valida_solo_digitos_y_longitud(): void
    {
        $this->assertNull(PrepayDashboardService::normalizeNumero(null));
        $this->assertNull(PrepayDashboardService::normalizeNumero(''));
        $this->assertNull(PrepayDashboardService::normalizeNumero('12'));
        $this->assertNull(PrepayDashboardService::normalizeNumero('abc'));
        $this->assertNull(PrepayDashboardService::normalizeNumero('123abc'));
        $this->assertNull(PrepayDashboardService::normalizeNumero(str_repeat('1', 11)));

        $this->assertSame('123', PrepayDashboardService::normalizeNumero('123'));
        $this->assertSame('00123', PrepayDashboardService::normalizeNumero('00123'));
    }

    public function test_normalize_nombre_valida_longitud_y_caracteres(): void
    {
        $this->assertNull(PrepayDashboardService::normalizeNombre(null));
        $this->assertNull(PrepayDashboardService::normalizeNombre(''));
        $this->assertNull(PrepayDashboardService::normalizeNombre('ab'));
        $this->assertNull(PrepayDashboardService::normalizeNombre(str_repeat('a', 81)));
        $this->assertNull(PrepayDashboardService::normalizeNombre('Nombre <script>'));

        $this->assertSame('Juan Perez', PrepayDashboardService::normalizeNombre(' Juan   Perez '));
        $this->assertSame('María-José 123', PrepayDashboardService::normalizeNombre('María-José 123'));
    }

    public function test_parse_query_distingue_numero_y_nombre(): void
    {
        $this->assertNull(PrepayDashboardService::parseQuery(null));
        $this->assertNull(PrepayDashboardService::parseQuery(''));

        $r = PrepayDashboardService::parseQuery('1001');
        $this->assertSame('numero', $r['type']);
        $this->assertSame('1001', $r['value']);

        $r = PrepayDashboardService::parseQuery('Cliente Uno');
        $this->assertSame('nombre', $r['type']);
        $this->assertSame('Cliente Uno', $r['value']);
    }

    public function test_estado_por_vencimiento_detecta_vencido_y_activo(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 8, 10, 10, 0, 0, 'UTC'));
        $now = now();

        $venceAyer = $now->copy()->subDay()->endOfDay();
        $res = PrepayDashboardService::estadoPorVencimiento($venceAyer, $now);
        $this->assertSame('Vencido', $res['estado']);
        $this->assertTrue($res['vencido']);
        $this->assertFalse($res['expira_pronto']);

        $venceHoy = $now->copy()->endOfDay();
        $res = PrepayDashboardService::estadoPorVencimiento($venceHoy, $now);
        $this->assertSame('Activo', $res['estado']);
        $this->assertFalse($res['vencido']);
        $this->assertTrue($res['expira_pronto']);
        $this->assertSame(0, $res['dias_para_vencer']);
    }

    public function test_estado_por_vencimiento_detecta_expira_pronto_7_dias(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 8, 10, 10, 0, 0, 'UTC'));
        $now = now();

        $venceEn7 = $now->copy()->addDays(7)->endOfDay();
        $res = PrepayDashboardService::estadoPorVencimiento($venceEn7, $now, 7);
        $this->assertSame('Activo', $res['estado']);
        $this->assertFalse($res['vencido']);
        $this->assertTrue($res['expira_pronto']);
        $this->assertSame(7, $res['dias_para_vencer']);

        $venceEn8 = $now->copy()->addDays(8)->endOfDay();
        $res = PrepayDashboardService::estadoPorVencimiento($venceEn8, $now, 7);
        $this->assertSame('Activo', $res['estado']);
        $this->assertFalse($res['vencido']);
        $this->assertFalse($res['expira_pronto']);
        $this->assertSame(8, $res['dias_para_vencer']);
    }
}
