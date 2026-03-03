<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImpresionScriptsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_pagos_view_includes_awaited_layout_and_guard()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $resp = $this->actingAs($user)->get(route('admin.pagos.index'));
        $resp->assertOk();
        $html = $resp->getContent();
        $this->assertStringContainsString('async init()', $html);
        $this->assertStringContainsString('await this.loadServerLayout()', $html);
        $this->assertStringContainsString("print-sheet no está listo/visible", $html);
    }

    public function test_pagos_recibos_view_includes_awaited_layout_and_guard()
    {
        $user = User::factory()->create(['role' => 'pagos']);
        $resp = $this->actingAs($user)->get(route('pagos.recibos'));
        $resp->assertOk();
        $html = $resp->getContent();
        $this->assertStringContainsString('async init()', $html);
        $this->assertStringContainsString('await this.loadServerLayout()', $html);
        $this->assertStringContainsString("print-sheet no está listo/visible", $html);
    }
}

