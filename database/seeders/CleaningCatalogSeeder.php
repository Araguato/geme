<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class CleaningCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Limpieza - Terminados' => 'Productos terminados de limpieza para venta al público.',
            'Cuidado personal' => 'Productos de higiene y cuidado personal.',
            'Herramientas de limpieza' => 'Utensilios y herramientas de limpieza.',
            'Químicos / Insumos' => 'Materia prima e insumos químicos para fabricación.',
            'Envases y empaques' => 'Envases, tapas, atomizadores y empaques.',
        ];

        $categoryModels = [];
        foreach ($categories as $name => $description) {
            $categoryModels[$name] = Category::firstOrCreate(
                ['name' => $name],
                [
                    'description' => $description,
                    'is_active' => true,
                ]
            );
        }

        $products = [
            // Químicos / Insumos (agua y sal NO se controlan en inventario)
            [
                'category' => 'Químicos / Insumos',
                'name' => 'Agua',
                'sku' => 'INS-AGUA',
                'price' => 0.00,
                'is_stock_tracked' => false,
                'is_raw_material' => true,
                'default_unit' => 'l',
            ],
            [
                'category' => 'Químicos / Insumos',
                'name' => 'Sal',
                'sku' => 'INS-SAL',
                'price' => 0.00,
                'is_stock_tracked' => false,
                'is_raw_material' => true,
                'default_unit' => 'kg',
            ],
            [
                'category' => 'Químicos / Insumos',
                'name' => 'Soda cáustica (NaOH)',
                'sku' => 'INS-NAOH',
                'price' => 0.00,
                'is_stock_tracked' => true,
                'is_raw_material' => true,
                'default_unit' => 'kg',
            ],
            [
                'category' => 'Químicos / Insumos',
                'name' => 'Ácido sulfónico',
                'sku' => 'INS-SULF',
                'price' => 0.00,
                'is_stock_tracked' => true,
                'is_raw_material' => true,
                'default_unit' => 'kg',
            ],
            [
                'category' => 'Químicos / Insumos',
                'name' => 'Fragancia',
                'sku' => 'INS-FRAG',
                'price' => 0.00,
                'is_stock_tracked' => true,
                'is_raw_material' => true,
                'default_unit' => 'ml',
            ],
            [
                'category' => 'Químicos / Insumos',
                'name' => 'Colorante',
                'sku' => 'INS-COLOR',
                'price' => 0.00,
                'is_stock_tracked' => true,
                'is_raw_material' => true,
                'default_unit' => 'ml',
            ],

            // Limpieza - Terminados (presentaciones)
            [
                'category' => 'Limpieza - Terminados',
                'name' => 'Cloro 1L',
                'sku' => 'CLORO-1L',
                'price' => 0.00,
                'is_stock_tracked' => true,
                'is_prepared' => true,
                'default_unit' => 'l',
            ],
            [
                'category' => 'Limpieza - Terminados',
                'name' => 'Cloro 4L',
                'sku' => 'CLORO-4L',
                'price' => 0.00,
                'is_stock_tracked' => true,
                'is_prepared' => true,
                'default_unit' => 'l',
            ],
            [
                'category' => 'Limpieza - Terminados',
                'name' => 'Cloro 20L',
                'sku' => 'CLORO-20L',
                'price' => 0.00,
                'is_stock_tracked' => true,
                'is_prepared' => true,
                'default_unit' => 'l',
            ],
            [
                'category' => 'Limpieza - Terminados',
                'name' => 'Desengrasante 1L',
                'sku' => 'DESENG-1L',
                'price' => 0.00,
                'is_stock_tracked' => true,
                'is_prepared' => true,
                'default_unit' => 'l',
            ],
            [
                'category' => 'Limpieza - Terminados',
                'name' => 'Desengrasante 4L',
                'sku' => 'DESENG-4L',
                'price' => 0.00,
                'is_stock_tracked' => true,
                'is_prepared' => true,
                'default_unit' => 'l',
            ],
            [
                'category' => 'Limpieza - Terminados',
                'name' => 'Desengrasante 20L',
                'sku' => 'DESENG-20L',
                'price' => 0.00,
                'is_stock_tracked' => true,
                'is_prepared' => true,
                'default_unit' => 'l',
            ],
            [
                'category' => 'Limpieza - Terminados',
                'name' => 'Jabón líquido para manos 500ml',
                'sku' => 'JABMAN-500ML',
                'price' => 0.00,
                'is_stock_tracked' => true,
                'is_prepared' => true,
                'default_unit' => 'l',
            ],
            [
                'category' => 'Limpieza - Terminados',
                'name' => 'Jabón líquido para manos 1L',
                'sku' => 'JABMAN-1L',
                'price' => 0.00,
                'is_stock_tracked' => true,
                'is_prepared' => true,
                'default_unit' => 'l',
            ],
            [
                'category' => 'Limpieza - Terminados',
                'name' => 'Jabón líquido para manos 4L',
                'sku' => 'JABMAN-4L',
                'price' => 0.00,
                'is_stock_tracked' => true,
                'is_prepared' => true,
                'default_unit' => 'l',
            ],
            [
                'category' => 'Limpieza - Terminados',
                'name' => 'Jabón líquido para manos 20L',
                'sku' => 'JABMAN-20L',
                'price' => 0.00,
                'is_stock_tracked' => true,
                'is_prepared' => true,
                'default_unit' => 'l',
            ],
            [
                'category' => 'Limpieza - Terminados',
                'name' => 'Detergente en polvo 500g',
                'sku' => 'DETPOL-500G',
                'price' => 0.00,
                'is_stock_tracked' => true,
                'is_prepared' => false,
                'default_unit' => 'g',
            ],
            [
                'category' => 'Limpieza - Terminados',
                'name' => 'Detergente en polvo 1kg',
                'sku' => 'DETPOL-1KG',
                'price' => 0.00,
                'is_stock_tracked' => true,
                'is_prepared' => false,
                'default_unit' => 'kg',
            ],
            [
                'category' => 'Limpieza - Terminados',
                'name' => 'Detergente en polvo 5kg',
                'sku' => 'DETPOL-5KG',
                'price' => 0.00,
                'is_stock_tracked' => true,
                'is_prepared' => false,
                'default_unit' => 'kg',
            ],

            // Cuidado personal
            [
                'category' => 'Cuidado personal',
                'name' => 'Shampoo 400ml',
                'sku' => 'SHAMP-400ML',
                'price' => 0.00,
                'is_stock_tracked' => true,
                'is_prepared' => false,
                'default_unit' => 'ml',
            ],
            [
                'category' => 'Cuidado personal',
                'name' => 'Jabón corporal 120g',
                'sku' => 'JABCOR-120G',
                'price' => 0.00,
                'is_stock_tracked' => true,
                'is_prepared' => false,
                'default_unit' => 'g',
            ],

            // Herramientas de limpieza
            [
                'category' => 'Herramientas de limpieza',
                'name' => 'Atomizador 500ml',
                'sku' => 'HERR-ATOM-500',
                'price' => 0.00,
                'is_stock_tracked' => true,
                'is_prepared' => false,
                'default_unit' => 'unidad',
            ],
            [
                'category' => 'Herramientas de limpieza',
                'name' => 'Escoba',
                'sku' => 'HERR-ESCOBA',
                'price' => 0.00,
                'is_stock_tracked' => true,
                'is_prepared' => false,
                'default_unit' => 'unidad',
            ],
            [
                'category' => 'Herramientas de limpieza',
                'name' => 'Coleto / mopa',
                'sku' => 'HERR-COLETO',
                'price' => 0.00,
                'is_stock_tracked' => true,
                'is_prepared' => false,
                'default_unit' => 'unidad',
            ],

            // Envases y empaques
            [
                'category' => 'Envases y empaques',
                'name' => 'Envase PET 1L',
                'sku' => 'ENV-1L',
                'price' => 0.00,
                'is_stock_tracked' => true,
                'is_raw_material' => true,
                'default_unit' => 'unidad',
            ],
            [
                'category' => 'Envases y empaques',
                'name' => 'Envase 4L',
                'sku' => 'ENV-4L',
                'price' => 0.00,
                'is_stock_tracked' => true,
                'is_raw_material' => true,
                'default_unit' => 'unidad',
            ],
            [
                'category' => 'Envases y empaques',
                'name' => 'Bidón 20L',
                'sku' => 'ENV-20L',
                'price' => 0.00,
                'is_stock_tracked' => true,
                'is_raw_material' => true,
                'default_unit' => 'unidad',
            ],
        ];

        foreach ($products as $data) {
            $category = $categoryModels[$data['category']];

            Product::firstOrCreate(
                [
                    'category_id' => $category->id,
                    'name' => $data['name'],
                ],
                [
                    'sku' => $data['sku'] ?? null,
                    'description' => $data['description'] ?? null,
                    'price' => $data['price'],
                    'is_active' => true,
                    'is_stock_tracked' => (bool) ($data['is_stock_tracked'] ?? false),
                    'is_prepared' => (bool) ($data['is_prepared'] ?? false),
                    'is_raw_material' => (bool) ($data['is_raw_material'] ?? false),
                    'default_unit' => $data['default_unit'] ?? null,
                ]
            );
        }

        $liquidFinishedSkus = [
            'CLORO-1L',
            'CLORO-4L',
            'CLORO-20L',
            'DESENG-1L',
            'DESENG-4L',
            'DESENG-20L',
            'JABMAN-500ML',
            'JABMAN-1L',
            'JABMAN-4L',
            'JABMAN-20L',
        ];

        Product::whereIn('sku', $liquidFinishedSkus)->update(['default_unit' => 'l']);
    }
}
