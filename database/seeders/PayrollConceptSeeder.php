<?php

namespace Database\Seeders;

use App\Models\PayrollConcept;
use Illuminate\Database\Seeder;

class PayrollConceptSeeder extends Seeder
{
    public function run(): void
    {
        $concepts = [
            [
                'code' => 'SAL_BASE',
                'name' => 'Salario base',
                'type' => 'earning',
                'is_taxable' => true,
                'is_social_security_applicable' => true,
                'calculation_method' => 'base_salary',
                'config' => null,
            ],
            [
                'code' => 'BON_TRAN',
                'name' => 'Bono de transporte',
                'type' => 'earning',
                'is_taxable' => false,
                'is_social_security_applicable' => false,
                'calculation_method' => 'fixed_amount',
                'config' => ['amount' => 0],
            ],
            [
                'code' => 'HOR_EXT',
                'name' => 'Horas extra',
                'type' => 'earning',
                'is_taxable' => true,
                'is_social_security_applicable' => true,
                'calculation_method' => 'hours_rate',
                'config' => ['multiplier' => 1.5],
            ],
            [
                'code' => 'DED_ISR',
                'name' => 'Impuesto sobre la renta',
                'type' => 'deduction',
                'is_taxable' => false,
                'is_social_security_applicable' => false,
                'calculation_method' => 'tax_table',
                'config' => null,
            ],
            [
                'code' => 'DED_SSO',
                'name' => 'Seguro social',
                'type' => 'contribution',
                'is_taxable' => false,
                'is_social_security_applicable' => true,
                'calculation_method' => 'percentage',
                'config' => ['rate' => 0],
            ],
        ];

        foreach ($concepts as $concept) {
            PayrollConcept::updateOrCreate(
                ['code' => $concept['code']],
                [
                    'name' => $concept['name'],
                    'type' => $concept['type'],
                    'is_taxable' => $concept['is_taxable'],
                    'is_social_security_applicable' => $concept['is_social_security_applicable'],
                    'calculation_method' => $concept['calculation_method'],
                    'config' => $concept['config'],
                    'is_active' => true,
                ]
            );
        }
    }
}
