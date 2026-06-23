<?php

namespace App\Http\Controllers;

use App\Models\Party;
use Illuminate\Http\Request;

class PartyController extends Controller
{
    public function create(Request $request)
    {
        $type = $request->query('type', 'customer');
        $redirect = $request->query('redirect', '');
        $party = new Party(['type' => $type, 'is_active' => true]);

        return view('admin.parties.form', compact('party', 'type', 'redirect'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|in:customer,supplier',
            'name' => 'required|string|max:255',
            'document_type' => 'nullable|in:RIF,CI,PASAPORTE',
            'document_number' => 'required|string|max:50',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'notes' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $party = Party::create([
            'type' => $data['type'],
            'name' => $data['name'],
            'document_type' => $data['document_type'] ?? 'RIF',
            'document_number' => $data['document_number'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'notes' => $data['notes'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        $redirect = $request->query('redirect');
        if ($redirect === 'pos') {
            return redirect()->route('pos.index')
                ->with('success', 'Cliente creado. Puedes seleccionarlo en el TPV.');
        }

        return redirect()->route('suppliers.index')
            ->with('status', 'Tercero creado correctamente.');
    }
}
