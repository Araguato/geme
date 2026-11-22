<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Hallazgo ISO 27001 8.10/8.12/7.10: el service worker no debe guardar
 * páginas autenticadas (clientes, nómina, informes) en la caché del navegador.
 */
class ServiceWorkerCacheSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $u = User::factory()->create();
        $u->roles()->attach(Role::firstOrCreate(['name' => 'admin'])->id);

        return $u;
    }

    public function test_respuestas_autenticadas_llevan_no_store(): void
    {
        $res = $this->actingAs($this->admin())->get('/marketing');

        $cc = (string) $res->headers->get('Cache-Control');
        $this->assertStringContainsString('no-store', $cc);
        $this->assertStringContainsString('private', $cc);
        $this->assertSame('no-cache', $res->headers->get('Pragma'));
    }

    public function test_login_anonimo_no_recibe_no_store_forzado(): void
    {
        $res = $this->get('/login');
        $res->assertOk();
        $this->assertStringNotContainsString('no-store', (string) $res->headers->get('Cache-Control'));
    }

    public function test_logout_limpia_cache_del_navegador(): void
    {
        $res = $this->actingAs($this->admin())->post('/logout');

        $res->assertRedirect('/');
        $this->assertSame('"cache"', $res->headers->get('Clear-Site-Data'));
    }

    public function test_service_worker_ya_no_cachea_paginas(): void
    {
        $sw = file_get_contents(public_path('sw.js'));

        $this->assertStringContainsString('geme-static-v3', $sw);
        $this->assertStringContainsString('text/html', $sw);       // HTML excluido de la caché
        $this->assertStringContainsString('geme:clear-cache', $sw); // limpieza al cerrar sesión
        $this->assertStringNotContainsString("CACHE_NAME = 'geme-pwa-v2'", $sw);
        $this->assertFileExists(public_path('offline.html'));
        $this->assertStringNotContainsString('<table', file_get_contents(public_path('offline.html')));
    }
}
