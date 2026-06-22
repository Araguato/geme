<?php

namespace App\Http\Controllers;

use App\Models\PayrollConcept;
use App\Models\PayrollEntry;
use App\Models\PayrollEntryItem;
use App\Models\PayrollRun;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PayrollEntryController extends Controller
{
    private const STATUS_OPTIONS = [
        'draft' => 'Borrador',
        'approved' => 'Aprobado',
    ];

    public function index(Request $request)
    {
        $runId = $request->input('run_id');

        $entriesQuery = PayrollEntry::with([
            'employee.party',
            'run.period',
        ])->orderByDesc('created_at');

        if ($runId) {
            $entriesQuery->where('payroll_run_id', $runId);
        }

        $entries = $entriesQuery->paginate(25)->withQueryString();

        $runOptions = PayrollRun::with('period')
            ->orderByDesc('created_at')
            ->get()
            ->mapWithKeys(function (PayrollRun $run) {
                $period = $run->period;
                $label = $run->code ?: 'Corrida #' . $run->id;
                if ($period) {
                    $label .= sprintf(
                        ' · %s (%s – %s)',
                        ucfirst($period->period_type ?? ''),
                        optional($period->start_date)->format('d/m/Y'),
                        optional($period->end_date)->format('d/m/Y')
                    );
                }

                return [$run->id => trim($label)];
            });

        return view('admin.payroll.entries.index', [
            'entries' => $entries,
            'runOptions' => $runOptions,
            'currentRunId' => $runId,
        ]);
    }

    public function show(PayrollEntry $payrollEntry)
    {
        $payrollEntry->loadMissing([
            'employee.party',
            'run.period',
            'items.concept',
        ]);

        return view('admin.payroll.entries.show', [
            'entry' => $payrollEntry,
        ]);
    }

    public function edit(PayrollEntry $payrollEntry)
    {
        $payrollEntry->loadMissing([
            'employee.party',
            'run.period',
            'items.concept',
        ]);

        if ($payrollEntry->run && $payrollEntry->run->status === 'approved') {
            abort(403, 'No se puede editar una entrada perteneciente a una corrida aprobada.');
        }

        $conceptOptions = PayrollConcept::query()
            ->where('is_active', true)
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        $manualItems = $payrollEntry->items->filter(function (PayrollEntryItem $item) {
            return ($item->metadata['origin'] ?? null) === 'manual';
        });

        $autoItems = $payrollEntry->items->reject(function (PayrollEntryItem $item) {
            return ($item->metadata['origin'] ?? null) === 'manual';
        });

        return view('admin.payroll.entries.edit', [
            'entry' => $payrollEntry,
            'autoItems' => $autoItems,
            'manualItems' => $manualItems,
            'conceptOptions' => $conceptOptions,
            'statusOptions' => self::STATUS_OPTIONS,
        ]);
    }

    public function update(Request $request, PayrollEntry $payrollEntry)
    {
        $payrollEntry->loadMissing(['run', 'items.concept']);

        if ($payrollEntry->run && $payrollEntry->run->status === 'approved') {
            throw ValidationException::withMessages([
                'status' => 'La corrida ya fue aprobada; no se permiten ajustes.',
            ]);
        }

        $validated = $request->validate([
            'hours_worked' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['nullable', Rule::in(array_keys(self::STATUS_OPTIONS))],
            'existing_manual_items' => ['sometimes', 'array'],
            'existing_manual_items.*.quantity' => ['nullable', 'numeric'],
            'existing_manual_items.*.rate' => ['nullable', 'numeric'],
            'existing_manual_items.*.amount' => ['required_with:existing_manual_items.*.quantity,existing_manual_items.*.rate', 'numeric'],
            'delete_manual_items' => ['sometimes', 'array'],
            'delete_manual_items.*' => ['integer'],
            'new_manual_items' => ['sometimes', 'array'],
            'new_manual_items.*.concept_id' => ['nullable', 'integer', 'exists:payroll_concepts,id'],
            'new_manual_items.*.quantity' => ['nullable', 'numeric'],
            'new_manual_items.*.rate' => ['nullable', 'numeric'],
            'new_manual_items.*.amount' => ['nullable', 'numeric'],
        ], [
            'existing_manual_items.*.amount.required_with' => 'Debes indicar el monto de cada ajuste manual existente.',
        ]);

        $existingManualPayload = $validated['existing_manual_items'] ?? [];
        $deleteIdsInput = collect($validated['delete_manual_items'] ?? [])->filter()->map(fn ($id) => (int) $id);
        $newManualPayload = $validated['new_manual_items'] ?? [];

        $newConceptIds = collect($newManualPayload)
            ->pluck('concept_id')
            ->filter()
            ->unique()
            ->values();

        $conceptsById = $newConceptIds->isNotEmpty()
            ? PayrollConcept::whereIn('id', $newConceptIds)->get()->keyBy('id')
            : collect();

        DB::transaction(function () use ($payrollEntry, $validated, $existingManualPayload, $deleteIdsInput, $newManualPayload, $conceptsById) {
            $payrollEntry->load('items.concept');

            $manualItems = $payrollEntry->items->filter(function (PayrollEntryItem $item) {
                return ($item->metadata['origin'] ?? null) === 'manual';
            });

            $idsToDelete = $manualItems
                ->pluck('id')
                ->intersect($deleteIdsInput);

            if ($idsToDelete->isNotEmpty()) {
                PayrollEntryItem::whereIn('id', $idsToDelete)->delete();
                // Refresh manual collection after deletion
                $manualItems = $manualItems->reject(fn (PayrollEntryItem $item) => $idsToDelete->contains($item->id));
            }

            foreach ($existingManualPayload as $itemId => $payload) {
                $itemId = (int) $itemId;
                if ($idsToDelete->contains($itemId)) {
                    continue;
                }

                /** @var PayrollEntryItem|null $item */
                $item = $manualItems->firstWhere('id', $itemId);
                if (!$item) {
                    continue;
                }

                $item->quantity = array_key_exists('quantity', $payload) && $payload['quantity'] !== null && $payload['quantity'] !== ''
                    ? (float) $payload['quantity']
                    : null;
                $item->rate = array_key_exists('rate', $payload) && $payload['rate'] !== null && $payload['rate'] !== ''
                    ? (float) $payload['rate']
                    : null;
                $item->amount = isset($payload['amount']) ? round((float) $payload['amount'], 2) : 0.0;
                $item->metadata = array_merge($item->metadata ?? [], ['origin' => 'manual']);
                $item->save();
            }

            foreach ($newManualPayload as $payload) {
                $isBlank = blank($payload['concept_id'] ?? null)
                    && blank($payload['quantity'] ?? null)
                    && blank($payload['rate'] ?? null)
                    && blank($payload['amount'] ?? null);

                if ($isBlank) {
                    continue;
                }

                $conceptId = $payload['concept_id'] ?? null;
                if (!$conceptId) {
                    throw ValidationException::withMessages([
                        'new_manual_items' => 'Cada ajuste nuevo debe indicar un concepto válido.',
                    ]);
                }

                $concept = $conceptsById[$conceptId] ?? PayrollConcept::find($conceptId);
                if (!$concept) {
                    throw ValidationException::withMessages([
                        'new_manual_items' => 'El concepto seleccionado ya no está disponible.',
                    ]);
                }

                $amount = $payload['amount'] ?? null;
                $quantity = $payload['quantity'] ?? null;
                $rate = $payload['rate'] ?? null;

                if ($amount === null && $quantity !== null && $rate !== null) {
                    $amount = (float) $quantity * (float) $rate;
                }

                if ($amount === null) {
                    throw ValidationException::withMessages([
                        'new_manual_items' => 'Debes indicar el monto del ajuste manual.',
                    ]);
                }

                $payrollEntry->items()->create([
                    'payroll_concept_id' => $concept->id,
                    'type' => $concept->type,
                    'quantity' => $quantity !== null && $quantity !== '' ? (float) $quantity : null,
                    'rate' => $rate !== null && $rate !== '' ? (float) $rate : null,
                    'amount' => round((float) $amount, 2),
                    'is_taxable' => (bool) $concept->is_taxable,
                    'is_social_security_applicable' => (bool) $concept->is_social_security_applicable,
                    'metadata' => [
                        'origin' => 'manual',
                    ],
                ]);
            }

            if (array_key_exists('hours_worked', $validated)) {
                $payrollEntry->hours_worked = $validated['hours_worked'] !== null
                    ? round((float) $validated['hours_worked'], 2)
                    : null;
            }

            if (array_key_exists('notes', $validated)) {
                $payrollEntry->notes = $validated['notes'] ?? null;
            }

            if (!empty($validated['status'])) {
                $payrollEntry->status = $validated['status'];
            }

            $payrollEntry->load('items');

            $earningsTotal = round((float) $payrollEntry->items->where('type', 'earning')->sum('amount'), 2);
            $deductionsTotal = round((float) $payrollEntry->items->where('type', 'deduction')->sum('amount'), 2);
            $contributionsTotal = round((float) $payrollEntry->items->where('type', 'contribution')->sum('amount'), 2);

            $payrollEntry->earnings_total = $earningsTotal;
            $payrollEntry->deductions_total = $deductionsTotal;
            $payrollEntry->contributions_total = $contributionsTotal;
            $payrollEntry->net_pay = round($earningsTotal - $deductionsTotal, 2);

            $payrollEntry->save();
        });

        $payrollEntry->refresh();

        return redirect()
            ->route('payroll-entries.show', $payrollEntry)
            ->with('status', 'Entrada de nómina actualizada correctamente.');
    }
}
