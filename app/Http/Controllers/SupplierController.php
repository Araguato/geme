<?php

namespace App\Http\Controllers;

use App\Models\Party;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index()
    {
        $suppliers = Supplier::with('party')
            ->orderByDesc('id')
            ->paginate(25);

        return view('admin.suppliers.index', compact('suppliers'));
    }

    public function create()
    {
        $supplier = new Supplier();
        $party = new Party(['type' => 'supplier', 'is_active' => true]);

        return view('admin.suppliers.form', [
            'supplier' => $supplier,
            'party' => $party,
            'mode' => 'create',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $party = Party::create([
            'type' => 'supplier',
            'name' => $data['name'],
            'document_type' => $data['document_type'] ?? null,
            'document_number' => $data['document_number'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'notes' => $data['notes'] ?? null,
            'is_active' => $data['is_active'] ?? false,
        ]);

        Supplier::create([
            'party_id' => $party->id,
            'contact_name' => $data['contact_name'] ?? null,
            'payment_terms' => $data['payment_terms'] ?? null,
            'default_currency' => $data['default_currency'] ?? null,
        ]);

        return redirect()->route('suppliers.index')
            ->with('status', 'Proveedor creado correctamente.');
    }

    public function edit(Supplier $supplier)
    {
        $supplier->load('party');
        $party = $supplier->party;

        return view('admin.suppliers.form', [
            'supplier' => $supplier,
            'party' => $party,
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, Supplier $supplier)
    {
        $data = $this->validateData($request, $supplier->party_id);

        $supplier->party->update([
            'name' => $data['name'],
            'document_type' => $data['document_type'] ?? null,
            'document_number' => $data['document_number'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'notes' => $data['notes'] ?? null,
            'is_active' => $data['is_active'] ?? false,
        ]);

        $supplier->update([
            'contact_name' => $data['contact_name'] ?? null,
            'payment_terms' => $data['payment_terms'] ?? null,
            'default_currency' => $data['default_currency'] ?? null,
        ]);

        return redirect()->route('suppliers.index')
            ->with('status', 'Proveedor actualizado correctamente.');
    }

    protected function validateData(Request $request, ?int $ignorePartyId = null): array
    {
        $documentRule = 'nullable|string|max:50';
        // Más adelante se puede agregar validación única combinando tipo+numero.

        return $request->validate([
            'name' => 'required|string|max:255',
            'document_type' => 'nullable|in:RIF,CI',
            'document_number' => $documentRule,
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'notes' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'contact_name' => 'nullable|string|max:255',
            'payment_terms' => 'nullable|string|max:50',
            'default_currency' => 'nullable|string|max:10',
        ]);
    }
}
