<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class FriendlyErrorPagesTest extends TestCase
{
    public function test_unexpected_error_shows_reference_code_and_logs_it(): void
    {
        config(['app.debug' => false]);
        Route::get('/_boom', fn () => throw new \RuntimeException('secreto interno'));

        $response = $this->get('/_boom');

        $response->assertStatus(500);
        $response->assertSee('Algo salió mal');
        $response->assertDontSee('secreto interno');
        $this->assertMatchesRegularExpression('/ERR-\d{6}-[A-Z0-9]{5}/', $response->getContent());
    }

    public function test_not_found_page_is_friendly(): void
    {
        config(['app.debug' => false]);
        $this->get('/no-existe-esta-ruta')->assertStatus(404)->assertSee('No encontramos esa página');
    }
}
