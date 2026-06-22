<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Recipe;
use App\Models\RecipeItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecipeController extends Controller
{
    public function edit(Product $product)
    {
        // Solo tiene sentido para productos preparados
        abort_unless($product->is_prepared, 404);

        $recipe = Recipe::firstOrCreate(
            ['product_id' => $product->id],
            [
                'name' => $product->name,
                'yield_quantity' => 1,
                'yield_unit' => $product->default_unit ?: 'unidad',
            ]
        );

        // Insumos disponibles: productos marcados como materia prima o que controlan inventario
        $components = Product::where(function ($q) {
                $q->where('is_raw_material', true)
                  ->orWhere('is_stock_tracked', true);
            })
            ->orderBy('name')
            ->get();

        $recipe->load('items.component');

        return view('admin.recipes.edit', [
            'product' => $product,
            'recipe' => $recipe,
            'components' => $components,
        ]);
    }

    public function update(Request $request, Product $product)
    {
        abort_unless($product->is_prepared, 404);

        $data = $request->validate([
            'yield_quantity' => 'required|numeric|min:0.001',
            'yield_unit' => 'nullable|string|max:20',
            'items' => 'array',
            'items.*.component_product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.unit' => 'nullable|string|max:20',
            'items.*.wastage_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        DB::transaction(function () use ($product, $data) {
            $recipe = Recipe::firstOrCreate(
                ['product_id' => $product->id],
                [
                    'name' => $product->name,
                    'yield_quantity' => 1,
                    'yield_unit' => $product->default_unit ?: 'unidad',
                ]
            );

            $recipe->update([
                'yield_quantity' => $data['yield_quantity'],
                'yield_unit' => $data['yield_unit'] ?? $recipe->yield_unit,
            ]);

            // Limpiar e insertar líneas
            $recipe->items()->delete();

            if (!empty($data['items'])) {
                foreach ($data['items'] as $item) {
                    $recipe->items()->create([
                        'component_product_id' => $item['component_product_id'],
                        'quantity' => $item['quantity'],
                        'unit' => $item['unit'] ?? null,
                        'wastage_percent' => $item['wastage_percent'] ?? 0,
                    ]);
                }
            }
        });

        return redirect()
            ->route('products.edit', $product)
            ->with('status', 'Receta actualizada correctamente.');
    }
}
