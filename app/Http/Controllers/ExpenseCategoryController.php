<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use App\Models\Setting;
use Illuminate\Http\Request;

class ExpenseCategoryController extends Controller
{
    private function authorizeFinances(): void
    {
        $user = auth()->user();
        if (!$user || !method_exists($user, 'hasRole') || !$user->hasRole('admin')) {
            abort(403);
        }

        if (!Setting::get('finances_enabled', 0)) {
            abort(404);
        }
    }

    public function index()
    {
        $this->authorizeFinances();

        $categories = ExpenseCategory::orderBy('name')->get();

        return view('finances.categories.index', compact('categories'));
    }

    public function create()
    {
        $this->authorizeFinances();

        return view('finances.categories.form', [
            'category' => new ExpenseCategory(['is_active' => true]),
            'mode' => 'create',
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeFinances();

        $data = $this->validateData($request);

        ExpenseCategory::create($data);

        return redirect()->route('finances.categories.index');
    }

    public function edit(ExpenseCategory $category)
    {
        $this->authorizeFinances();

        return view('finances.categories.form', [
            'category' => $category,
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, ExpenseCategory $category)
    {
        $this->authorizeFinances();

        $data = $this->validateData($request);

        $category->update($data);

        return redirect()->route('finances.categories.index');
    }

    public function destroy(ExpenseCategory $category)
    {
        $this->authorizeFinances();

        $category->delete();

        return redirect()->route('finances.categories.index');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'is_active' => 'nullable|boolean',
        ], [
            'name.required' => 'El nombre de la categoría es obligatorio.',
        ]) + [
            'is_active' => $request->boolean('is_active', true),
        ];
    }
}
