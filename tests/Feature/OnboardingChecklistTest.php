<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\OnboardingChecklist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingChecklistTest extends TestCase
{
    use RefreshDatabase;

    public function test_steps_reflect_real_configuration(): void
    {
        $byKey = fn () => collect(OnboardingChecklist::steps())->pluck('done', 'key');

        $this->assertFalse($byKey()['company']);
        $this->assertFalse($byKey()['bcv']);

        Setting::set('company_name', 'Tienda Demo');
        Setting::set('company_tax_id', 'J-12345678-9');
        Setting::set('bcv_rate', '924.353400');

        $this->assertTrue($byKey()['company']);
        $this->assertTrue($byKey()['bcv']);
        $this->assertSame(4, OnboardingChecklist::pending());
    }
}
