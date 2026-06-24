<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use App\Models\UnitConversion;
use App\Models\Warehouse;
use App\Models\Location;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $roles = [
            'admin' => 'Administrador del sistema',
            'cajero' => 'Cajero',
        ];

        $roleModels = [];
        foreach ($roles as $name => $description) {
            $roleModels[$name] = Role::firstOrCreate(
                ['name' => $name],
                ['description' => $description]
            );
        }

        // Almacén y ubicación por defecto para que el sistema sea usable
        $defaultWarehouse = Warehouse::firstOrCreate(
            ['code' => 'MAIN'],
            ['name' => 'Almacén principal', 'is_active' => true]
        );
        Location::firstOrCreate(
            ['warehouse_id' => $defaultWarehouse->id, 'code' => 'GEN'],
            ['name' => 'General', 'is_active' => true]
        );

        $this->call(LocationSeeder::class);

        $admin = User::updateOrCreate(
            ['email' => 'admin@geme.com'],
            [
                'name' => 'admin',
                'password' => bcrypt('admin123'),
            ]
        );

        if (!$admin->roles()->where('roles.id', $roleModels['admin']->id)->exists()) {
            $admin->roles()->attach($roleModels['admin']->id);
        }

        $this->call(PayrollConceptSeeder::class);

        $seedCleaningCatalog = filter_var(env('GEME_SEED_CLEANING_CATALOG', false), FILTER_VALIDATE_BOOL);
        if ($seedCleaningCatalog) {
            $this->call(CleaningCatalogSeeder::class);
        }

        // Always create base categories so the app is usable out of the box
        $categories = [
            'General' => 'Categoría general',
            'Productos' => 'Productos varios',
            'Servicios' => 'Servicios',
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

        // Basic units usable in any installation
        $units = [
            ['code' => 'UND', 'name' => 'Unidad', 'category' => 'pieza'],
            ['code' => 'HUEVO', 'name' => 'Huevo', 'category' => 'pieza'],
            ['code' => 'KG', 'name' => 'Kilogramo', 'category' => 'peso'],
            ['code' => 'G', 'name' => 'Gramo', 'category' => 'peso'],
            ['code' => 'L', 'name' => 'Litro', 'category' => 'volumen'],
            ['code' => 'ML', 'name' => 'Mililitro', 'category' => 'volumen'],
        ];

        $unitModels = [];
        foreach ($units as $unit) {
            $unitModels[$unit['code']] = Unit::firstOrCreate(
                ['code' => $unit['code']],
                [
                    'name' => $unit['name'],
                    'category' => $unit['category'],
                    'is_active' => true,
                ]
            );
        }

        // En algunos entornos (por ejemplo, con config cache habilitado), env() puede devolver null
        // dentro de los seeders aunque la variable no exista. En ese caso queremos asumir que el
        // seed de datos demo está ACTIVADO por defecto y solo desactivarlo cuando exista un valor
        // explícito tipo "false", "0", "off", etc.
        $rawDemoFlag = env('GEME_SEED_DEMO_DATA');
        $seedDemo = $rawDemoFlag === null
            ? true
            : filter_var($rawDemoFlag, FILTER_VALIDATE_BOOL);

        if ($seedDemo) {
            // Demo categories para una bodega / pulpería típica en Venezuela
            $demoCategories = [
                'Abarrotes' => 'Secos: arroz, pasta, harina, azúcar, granos',
                'Panadería' => 'Pan, dulces y productos de panadería',
                'Refrigerados' => 'Huevos, lácteos y embutidos',
                'Bebidas' => 'Refrescos, jugos, agua, malta',
                'Limpieza del hogar' => 'Detergentes, desinfectantes, lavaplatos',
                'Insumos de limpieza' => 'Materias primas para fabricar limpiadores',
                'Higiene personal' => 'Jabón, champú, pasta dental, papel higiénico',
            ];

            foreach ($demoCategories as $name => $description) {
                $categoryModels[$name] = Category::firstOrCreate(
                    ['name' => $name],
                    [
                        'description' => $description,
                        'is_active' => true,
                    ]
                );
            }

            // Demo-specific units (packaging etc.) and conversions
            $demoUnits = [
                ['code' => 'CTN30H', 'name' => 'Cartón 30 huevos', 'category' => 'empaque'],
                ['code' => 'SACO45', 'name' => 'Saco harina 45 kg', 'category' => 'empaque'],
            ];

            foreach ($demoUnits as $unit) {
                $unitModels[$unit['code']] = Unit::firstOrCreate(
                    ['code' => $unit['code']],
                    [
                        'name' => $unit['name'],
                        'category' => $unit['category'],
                        'is_active' => true,
                    ]
                );
            }

            // 1 CTN30H = 30 HUEVO
            if (isset($unitModels['CTN30H'], $unitModels['HUEVO'])) {
                UnitConversion::firstOrCreate(
                    [
                        'from_unit_id' => $unitModels['CTN30H']->id,
                        'to_unit_id' => $unitModels['HUEVO']->id,
                    ],
                    [
                        'factor' => 30,
                        'is_active' => true,
                    ]
                );
            }

            // 1 KG = 1000 G
            if (isset($unitModels['KG'], $unitModels['G'])) {
                UnitConversion::firstOrCreate(
                    [
                        'from_unit_id' => $unitModels['KG']->id,
                        'to_unit_id' => $unitModels['G']->id,
                    ],
                    [
                        'factor' => 1000,
                        'is_active' => true,
                    ]
                );
            }

            // 1 L = 1000 ML
            if (isset($unitModels['L'], $unitModels['ML'])) {
                UnitConversion::firstOrCreate(
                    [
                        'from_unit_id' => $unitModels['L']->id,
                        'to_unit_id' => $unitModels['ML']->id,
                    ],
                    [
                        'factor' => 1000,
                        'is_active' => true,
                    ]
                );
            }

            $products = [
                // Bodega: abarrotes (secos)
                ['category' => 'Abarrotes', 'name' => 'Harina PAN 1kg', 'price' => 1.50],
                ['category' => 'Abarrotes', 'name' => 'Arroz blanco 1kg', 'price' => 1.20],
                ['category' => 'Abarrotes', 'name' => 'Azúcar refinada 1kg', 'price' => 1.10],
                ['category' => 'Abarrotes', 'name' => 'Pasta corta 1kg', 'price' => 1.30],
                ['category' => 'Abarrotes', 'name' => 'Lentejas 1kg', 'price' => 1.40],
                ['category' => 'Abarrotes', 'name' => 'Caraotas negras 1kg', 'price' => 1.60],
                ['category' => 'Abarrotes', 'name' => 'Sal fina 1kg', 'price' => 0.60],

                // Bodega: panadería
                ['category' => 'Panadería', 'name' => 'Pan campesino unidad', 'price' => 0.40],
                ['category' => 'Panadería', 'name' => 'Canilla', 'price' => 0.35],
                ['category' => 'Panadería', 'name' => 'Dulce de guayaba', 'price' => 0.70],

                // Bodega: refrigerados
                ['category' => 'Refrigerados', 'name' => 'Cartón 30 huevos', 'price' => 5.50],
                ['category' => 'Refrigerados', 'name' => 'Queso blanco duro 1kg', 'price' => 4.50],
                ['category' => 'Refrigerados', 'name' => 'Jamón de pierna 500g', 'price' => 3.80],
                ['category' => 'Refrigerados', 'name' => 'Margarina 500g', 'price' => 1.90],

                // Bodega: bebidas
                ['category' => 'Bebidas', 'name' => 'Refresco cola 2L', 'price' => 2.00],
                ['category' => 'Bebidas', 'name' => 'Malta 355ml', 'price' => 0.90],
                ['category' => 'Bebidas', 'name' => 'Agua mineral 1,5L', 'price' => 0.80],
                ['category' => 'Bebidas', 'name' => 'Jugo pasteurizado 1L', 'price' => 1.80],

                // Bodega: limpieza del hogar
                ['category' => 'Limpieza del hogar', 'name' => 'Detergente en polvo 1kg', 'price' => 2.50],
                ['category' => 'Limpieza del hogar', 'name' => 'Lavaplatos líquido 500ml', 'price' => 1.20],
                ['category' => 'Limpieza del hogar', 'name' => 'Cloro 1L', 'price' => 1.00],
                ['category' => 'Limpieza del hogar', 'name' => 'Desinfectante de pino 1L', 'price' => 1.60],

                // Bodega: insumos de limpieza (para fabricar productos)
                ['category' => 'Insumos de limpieza', 'name' => 'Sosa cáustica 1kg', 'price' => 3.20],
                ['category' => 'Insumos de limpieza', 'name' => 'Base para jabón líquido 1L', 'price' => 2.80],
                ['category' => 'Insumos de limpieza', 'name' => 'Fragancia para limpiador 250ml', 'price' => 1.50],

                // Bodega: higiene personal
                ['category' => 'Higiene personal', 'name' => 'Jabón de baño 125g', 'price' => 0.80],
                ['category' => 'Higiene personal', 'name' => 'Champú 400ml', 'price' => 2.80],
                ['category' => 'Higiene personal', 'name' => 'Pasta dental 90g', 'price' => 1.10],
                ['category' => 'Higiene personal', 'name' => 'Papel higiénico doble hoja (4 rollos)', 'price' => 2.20],
            ];

            foreach ($products as $product) {
                Product::firstOrCreate(
                    [
                        'category_id' => $categoryModels[$product['category']]->id,
                        'name' => $product['name'],
                    ],
                    [
                        'description' => null,
                        'price' => $product['price'],
                        'is_active' => true,
                    ]
                );
            }

        }
    }
}
