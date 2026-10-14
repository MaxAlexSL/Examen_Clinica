<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Tras cerrar sesión, "Atrás" no debe mostrar páginas del usuario anterior.
class PreventBackHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_las_rutas_autenticadas_no_se_guardan_en_cache(): void
    {
        $response = $this->actingAs(User::factory()->create())->get('/dashboard');

        $response->assertOk();
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('must-revalidate', $response->headers->get('Cache-Control'));
        $response->assertHeader('Pragma', 'no-cache');
        $response->assertHeader('Expires', '0');
    }

    public function test_el_logout_pide_borrar_la_cache_del_navegador(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/logout')
            ->assertRedirect(route('login'))
            ->assertHeader('Clear-Site-Data', '"cache"');

        $this->assertGuest();
    }

    // Tras cerrar sesión, "Atrás" pide la página anterior y el servidor redirige al login.
    // Quien inicie sesión después debe llegar al dashboard, no a esa página del usuario anterior.
    public function test_tras_volver_atras_y_reingresar_se_llega_al_dashboard(): void
    {
        $anterior = User::factory()->create();
        $siguiente = User::factory()->create();

        $this->actingAs($anterior)->post('/logout');

        // "Atrás" del navegador: vuelve a pedir una página autenticada.
        $this->get('/ventas')->assertRedirect(route('login'));

        $this->post('/login', ['email' => $siguiente->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($siguiente);
    }

    public function test_el_layout_recarga_la_pagina_si_viene_de_la_cache(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertSee("window.addEventListener('pageshow'", false);
    }
}
