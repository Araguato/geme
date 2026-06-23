<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $warehouse = Warehouse::firstOrCreate(
            ['code' => 'PRINCIPAL'],
            [
                'name' => 'Almacén principal',
                'description' => 'Depósito principal del negocio',
                'address' => 'Dirección principal',
                'is_active' => true,
                'sort_order' => 0,
            ]
        );

        $locations = [
            [
                'code' => 'GENERAL',
                'name' => 'General',
                'description' => 'Ubicación general sin sección específica',
                'is_active' => true,
                'sort_order' => 0,
            ],
            [
                'code' => 'A-3-12',
                'name' => 'Pasillo A - Estante 3 - Vitrina 12',
                'aisle' => 'A',
                'shelf' => '3',
                'section' => '12',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'code' => 'C-5',
                'name' => 'Cajón C-5',
                'bin' => 'C-5',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'code' => 'VITRINA-P',
                'name' => 'Vitrina principal',
                'section' => 'Vitrina principal',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'code' => 'B-2',
                'name' => 'Pasillo B - Estante 2',
                'aisle' => 'B',
                'shelf' => '2',
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'code' => 'A-1',
                'name' => 'Pasillo A - Estante 1',
                'aisle' => 'A',
                'shelf' => '1',
                'is_active' => true,
                'sort_order' => 5,
            ],
        ];

        foreach ($locations as $location) {
            Location::updateOrCreate(
                ['code' => $location['code'], 'warehouse_id' => $warehouse->id],
                array_merge($location, ['warehouse_id' => $warehouse->id])
            );
        }
    }
}
