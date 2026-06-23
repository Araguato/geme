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
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->orderBy('name');

        $products = $query->get();
        $categories = Category::orderBy('name')->get();

        return view('catalog.index', compact('products', 'categories', 'categoryId', 'search'));
    }

    public function show(Product $product)
    {
        return view('catalog.show', compact('product'));
    }
}
