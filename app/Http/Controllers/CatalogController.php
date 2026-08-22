<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function index(Request $request)
    {
        $categoryId = $request->query('category_id');
        $search = $request->query('search');

        $query = Product::where('is_active', true)
            ->where('is_raw_material', false)
            ->whereNull('parent_product_id')
            ->with(['mainImage', 'images', 'variants'])
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->orderBy('name');

        $products = $query->get();
        $categories = Category::orderBy('name')->get();

        return view('catalog.index', compact('products', 'categories', 'categoryId', 'search'));
    }

    public function show(Product $product)
    {
        $product->load(['mainImage', 'images', 'variants', 'variantAttributes']);
        return view('catalog.show', compact('product'));
    }

    public function showByBarcode(string $barcode)
    {
        $product = Product::where('is_active', true)
            ->whereHas('barcodes', fn ($q) => $q->where('barcode', $barcode))
            ->with(['mainImage', 'images'])
            ->first();

        if (! $product) {
            abort(404, 'Producto no encontrado');
        }

        return view('catalog.show', compact('product'));
    }
}
