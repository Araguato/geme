<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        // Sin sesión, la raíz lleva al login; con sesión, a la pantalla de su rol
        $this->get('/')->assertRedirect('/login');
        $this->get('/login')->assertOk();
    }
}
