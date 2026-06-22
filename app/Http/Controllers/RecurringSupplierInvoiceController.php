<?php

namespace App\Http\Controllers;

use App\Models\RecurringSupplierInvoice;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RecurringSupplierInvoiceController extends Controller
{
    public function index()
    {
        $recurrings = RecurringSupplierInvoice::with('supplier.party')
            ->orderBy('is_active', 'desc')
            ->orderBy('next_due_date')
            ->paginate(25);

        return view('admin.suppliers.recurrings.index', compact('recurrings'));
    }

    public function create()
    {
        $recurring = new RecurringSupplierInvoice([
            'currency' => 'USD',
            'interval' => 'monthly',
            'next_due_date' => now()->toDateString(),
            'is_active' => true,
        ]);
        $suppliers = Supplier::with('party')->orderByDesc('id')->get();

        return view('admin.suppliers.recurrings.form', [
            'recurring' => $recurring,
            'suppliers' => $suppliers,
            'mode' => 'create',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        RecurringSupplierInvoice::create($data);

        return redirect()->route('recurring-supplier-invoices.index');
    }

    public function edit(RecurringSupplierInvoice $recurringSupplierInvoice)
    {
        $suppliers = Supplier::with('party')->orderByDesc('id')->get();

        return view('admin.suppliers.recurrings.form', [
            'recurring' => $recurringSupplierInvoice,
            'suppliers' => $suppliers,
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, RecurringSupplierInvoice $recurringSupplierInvoice)
    {
        $data = $this->validateData($request);

        $recurringSupplierInvoice->update($data);

        return redirect()->route('recurring-supplier-invoices.index');
    }

    public function destroy(RecurringSupplierInvoice $recurringSupplierInvoice)
    {
        $recurringSupplierInvoice->delete();

        return redirect()->route('recurring-supplier-invoices.index');
    }

    public function generate(Request $request)
    {
        $today = Carbon::today();
        $bcvRate = (float) Setting::get('bcv_rate', 0);

        $query = RecurringSupplierInvoice::where('is_active', true)
            ->whereDate('next_due_date', '<=', $today);

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->integer('supplier_id'));
        }

        $recurrings = $query->get();

        foreach ($recurrings as $recurring) {
            $amountUsd = (float) ($recurring->base_amount_usd ?? 0);
            $amountBs = (float) ($recurring->base_amount_bs ?? 0);

            if ($recurring->currency === 'USD' && $amountUsd <= 0 && $amountBs > 0 && $bcvRate > 0) {
                $amountUsd = $amountBs / $bcvRate;
            }

            if ($recurring->currency !== 'USD' && $amountBs <= 0 && $amountUsd > 0 && $bcvRate > 0) {
                $amountBs = $amountUsd * $bcvRate;
            }

            SupplierInvoice::create([
                'supplier_id' => $recurring->supplier_id,
                'invoice_number' => null,
                'date' => $recurring->next_due_date,
                'due_date' => $recurring->next_due_date,
                'bcv_rate_at_issue' => $bcvRate,
                'amount_usd' => $amountUsd,
                'amount_bs' => $amountBs ?: null,
                'currency' => $recurring->currency,
                'status' => 'pendiente',
                'notes' => $recurring->description,
            ]);

            $recurring->next_due_date = $this->calculateNextDueDate($recurring->next_due_date, $recurring->interval, $recurring->day_of_month, $recurring->day_of_week);
            $recurring->save();
        }

        return redirect()->route('recurring-supplier-invoices.index')
            ->with('status', __('Se generaron :count facturas recurrentes.', ['count' => $recurrings->count()]));
    }

    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'description' => 'required|string|max:255',
            'base_amount_usd' => 'nullable|numeric|min:0',
            'base_amount_bs' => 'nullable|numeric|min:0',
            'currency' => 'required|string|max:10',
            'interval' => 'required|in:monthly,yearly,weekly',
            'day_of_month' => 'nullable|integer|min:1|max:31',
            'day_of_week' => 'nullable|integer|min:1|max:7',
            'next_due_date' => 'required|date',
            'is_active' => 'sometimes|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    private function calculateNextDueDate(string $currentDate, string $interval, ?int $dayOfMonth, ?int $dayOfWeek): string
    {
        $date = Carbon::parse($currentDate);

        return match ($interval) {
            'weekly' => $date->addWeek()->toDateString(),
            'yearly' => $date->addYear()->toDateString(),
            default => $date->addMonthNoOverflow()->toDateString(),
        };
    }
}
