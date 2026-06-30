<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CashShift;
use App\Models\SalesLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CashShiftController extends Controller
{
    public function open(Request $request)
    {
        $data = $request->validate([
            'opening_amount' => 'required|numeric|min:0',
            'sales_location_id' => 'required|exists:sales_locations,id',
        ]);

        CashShift::create([
            'user_id' => Auth::id(),
            'sales_location_id' => $data['sales_location_id'],
            'opening_amount' => $data['opening_amount'],
            'is_active' => true,
        ]);

        return redirect()->route('pos.index')->with('success', 'Turno de caja abierto.');
    }

    public function close(Request $request)
    {
        $data = $request->validate([
            'closing_amount' => 'required|numeric|min:0',
        ]);

        $shift = CashShift::where('is_active', true)->latest()->first();
        if ($shift) {
            $shift->update([
                'closed_at' => now(),
                'closing_amount' => $data['closing_amount'],
                'is_active' => false,
            ]);
        }

        return redirect()->route('pos.index')->with('success', 'Turno de caja cerrado.');
    }
}
