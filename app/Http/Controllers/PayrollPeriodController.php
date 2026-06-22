<?php

namespace App\Http\Controllers;

use App\Models\PayrollPeriod;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PayrollPeriodController extends Controller
{
    private const PERIOD_TYPES = [
        'semanal' => 'Semanal',
        'quincenal' => 'Quincenal',
        'mensual' => 'Mensual',
        'especial' => 'Especial',
    ];

    private const STATUS_OPTIONS = [
        'draft' => 'Borrador',
        'open' => 'Abierto',
        'closed' => 'Cerrado',
    ];

    public function index()
    {
        $periods = PayrollPeriod::orderByDesc('start_date')
            ->orderByDesc('id')
            ->paginate(20);

        return view('admin.payroll.periods.index', [
            'periods' => $periods,
            'typeLabels' => self::PERIOD_TYPES,
            'statusLabels' => self::STATUS_OPTIONS,
        ]);
    }

    public function create()
    {
        $period = new PayrollPeriod([
            'period_type' => 'mensual',
            'status' => 'draft',
        ]);

        return view('admin.payroll.periods.form', [
            'period' => $period,
            'mode' => 'create',
            'typeOptions' => self::PERIOD_TYPES,
            'statusOptions' => self::STATUS_OPTIONS,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $period = new PayrollPeriod($data);
        $this->applyStatusTransitions($period);
        $period->save();

        return redirect()
            ->route('payroll-periods.index')
            ->with('status', 'Periodo de nómina creado correctamente.');
    }

    public function edit(PayrollPeriod $payrollPeriod)
    {
        return view('admin.payroll.periods.form', [
            'period' => $payrollPeriod,
            'mode' => 'edit',
            'typeOptions' => self::PERIOD_TYPES,
            'statusOptions' => self::STATUS_OPTIONS,
        ]);
    }

    public function update(Request $request, PayrollPeriod $payrollPeriod)
    {
        $data = $this->validateData($request, $payrollPeriod->id);

        $originalStatus = $payrollPeriod->status;

        $payrollPeriod->fill($data);

        $this->applyStatusTransitions($payrollPeriod, $originalStatus);

        $payrollPeriod->save();

        return redirect()
            ->route('payroll-periods.index')
            ->with('status', 'Periodo de nómina actualizado correctamente.');
    }

    private function validateData(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'period_type' => ['required', Rule::in(array_keys(self::PERIOD_TYPES))],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'pay_date' => ['nullable', 'date'],
            'status' => ['required', Rule::in(array_keys(self::STATUS_OPTIONS))],
        ], [
            'end_date.after_or_equal' => 'La fecha de fin debe ser mayor o igual a la fecha de inicio.',
        ]);

        $data['start_date'] = Carbon::parse($data['start_date'])->toDateString();
        $data['end_date'] = Carbon::parse($data['end_date'])->toDateString();
        $data['pay_date'] = $data['pay_date'] ? Carbon::parse($data['pay_date'])->toDateString() : null;

        $duplicateQuery = PayrollPeriod::query()
            ->where('period_type', $data['period_type'])
            ->where('start_date', $data['start_date'])
            ->where('end_date', $data['end_date']);

        if ($ignoreId) {
            $duplicateQuery->whereKeyNot($ignoreId);
        }

        if ($duplicateQuery->exists()) {
            throw ValidationException::withMessages([
                'start_date' => 'Ya existe un periodo con el mismo tipo y rango de fechas.',
            ]);
        }

        return $data;
    }

    private function applyStatusTransitions(PayrollPeriod $period, ?string $originalStatus = null): void
    {
        $now = Carbon::now();

        if ($period->status === 'draft') {
            $period->locked_at = null;
            $period->closed_at = null;
            $period->closed_by = null;
            return;
        }

        if ($period->status === 'open') {
            if ($period->locked_at === null) {
                $period->locked_at = $now;
            }

            if ($originalStatus === 'closed') {
                $period->closed_at = null;
                $period->closed_by = null;
            }

            return;
        }

        if ($period->status === 'closed') {
            if ($period->runs()->where('status', '!=', 'approved')->exists()) {
                throw ValidationException::withMessages([
                    'status' => 'No se puede cerrar el periodo porque existen corridas sin aprobar.',
                ]);
            }

            if ($period->locked_at === null) {
                $period->locked_at = $now;
            }

            $period->closed_at = $period->closed_at ?: $now;
            $period->closed_by = $period->closed_by ?: Auth::id();

            return;
        }

        throw ValidationException::withMessages([
            'status' => 'Estado de periodo no soportado.',
        ]);
    }
}
