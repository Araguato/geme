<?php

namespace App\Http\Controllers;

use App\Models\PayrollEntry;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Services\Payroll\PayrollGenerator;

class PayrollRunController extends Controller
{
    private const STATUS_OPTIONS = [
        'draft' => 'Borrador',
        'processing' => 'En proceso',
        'approved' => 'Aprobado',
    ];

    public function __construct(private readonly PayrollGenerator $payrollGenerator)
    {
    }

    public function index(Request $request)
    {
        $periodId = $request->input('period_id');

        $runsQuery = PayrollRun::with(['period', 'approvedBy'])
            ->withCount('entries')
            ->orderByDesc('created_at');

        if ($periodId) {
            $runsQuery->where('payroll_period_id', $periodId);
        }

        $runs = $runsQuery->paginate(20)->withQueryString();

        $periodOptions = PayrollPeriod::orderByDesc('start_date')
            ->orderByDesc('id')
            ->get()
            ->mapWithKeys(function (PayrollPeriod $period) {
                $label = $period->name ?: sprintf(
                    '%s (%s – %s)',
                    ucfirst($period->period_type),
                    optional($period->start_date)->format('d/m/Y'),
                    optional($period->end_date)->format('d/m/Y'),
                );

                return [$period->id => $label];
            });

        return view('admin.payroll.runs.index', [
            'runs' => $runs,
            'statusLabels' => self::STATUS_OPTIONS,
            'periodOptions' => $periodOptions,
            'currentPeriodId' => $periodId,
        ]);
    }

    public function create(Request $request)
    {
        $period = null;
        if ($request->filled('period_id')) {
            $period = PayrollPeriod::find($request->input('period_id'));
        }

        $run = new PayrollRun([
            'status' => 'draft',
            'payroll_period_id' => $period?->id,
        ]);

        return view('admin.payroll.runs.form', [
            'run' => $run,
            'mode' => 'create',
            'statusOptions' => self::STATUS_OPTIONS,
            'periodOptions' => $this->periodSelectOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $period = PayrollPeriod::findOrFail($data['payroll_period_id']);
        $this->ensurePeriodAllowsStatus($period, $data['status']);

        if (blank($data['code'])) {
            $data['code'] = $this->generateCode($data['payroll_period_id']);
        }

        $this->assertStatusTransition(null, $data['status']);
        $this->applyStatusSideEffects($data);

        $run = PayrollRun::create($data);

        return redirect()
            ->route('payroll-runs.index', ['period_id' => $run->payroll_period_id])
            ->with('status', 'Corrida de nómina creada correctamente.');
    }

    public function edit(PayrollRun $payrollRun)
    {
        return view('admin.payroll.runs.form', [
            'run' => $payrollRun,
            'mode' => 'edit',
            'statusOptions' => self::STATUS_OPTIONS,
            'periodOptions' => $this->periodSelectOptions(),
        ]);
    }

    public function update(Request $request, PayrollRun $payrollRun)
    {
        $data = $this->validateData($request, $payrollRun->id);

        if (blank($data['code'])) {
            $data['code'] = $payrollRun->code;
        }

        $this->ensurePeriodAllowsStatus($payrollRun->period, $data['status']);
        $this->assertStatusTransition($payrollRun, $data['status']);

        $this->applyStatusSideEffects($data, $payrollRun);

        $payrollRun->fill($data);
        $payrollRun->save();

        return redirect()
            ->route('payroll-runs.index', ['period_id' => $payrollRun->payroll_period_id])
            ->with('status', 'Corrida de nómina actualizada correctamente.');
    }

    public function generate(PayrollRun $payrollRun)
    {
        $this->payrollGenerator->generateForRun($payrollRun, false);

        return redirect()
            ->route('payroll-runs.index', ['period_id' => $payrollRun->payroll_period_id])
            ->with('status', 'Entradas de nómina generadas correctamente.');
    }

    public function recalculate(PayrollRun $payrollRun)
    {
        $this->ensurePeriodAllowsGeneration($payrollRun);

        $this->payrollGenerator->generateForRun($payrollRun, true);

        return redirect()
            ->route('payroll-runs.index', ['period_id' => $payrollRun->payroll_period_id])
            ->with('status', 'Entradas de nómina recalculadas correctamente.');
    }

    private function validateData(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'payroll_period_id' => ['required', 'exists:payroll_periods,id'],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('payroll_runs', 'code')->ignore($ignoreId)],
            'status' => ['required', Rule::in(array_keys(self::STATUS_OPTIONS))],
        ]);

        $data['code'] = $data['code'] ?? null;

        return $data;
    }

    private function periodSelectOptions()
    {
        return PayrollPeriod::orderByDesc('start_date')
            ->orderByDesc('id')
            ->get()
            ->mapWithKeys(function (PayrollPeriod $period) {
                $label = trim(sprintf(
                    '%s (%s – %s)%s',
                    ucfirst($period->period_type),
                    optional($period->start_date)->format('d/m/Y'),
                    optional($period->end_date)->format('d/m/Y'),
                    $period->name ? ' · ' . $period->name : ''
                ));

                return [$period->id => $label];
            });
    }

    private function generateCode(int $periodId): string
    {
        $period = PayrollPeriod::find($periodId);
        $base = $period && $period->start_date
            ? 'RUN-' . $period->start_date->format('Ym')
            : 'RUN-' . now()->format('Ym');

        $sequence = PayrollRun::where('code', 'like', $base . '-%')->count() + 1;

        return sprintf('%s-%03d', $base, $sequence);
    }

    private function applyStatusSideEffects(array &$data, ?PayrollRun $existing = null): void
    {
        $status = $data['status'];
        $now = Carbon::now();

        if ($status === 'draft') {
            $data['processed_at'] = null;
            $data['approved_at'] = null;
            $data['approved_by'] = null;
            return;
        }

        if ($status === 'processing') {
            $processedAt = $existing?->processed_at;
            $data['processed_at'] = $processedAt ?: $now;
            $data['approved_at'] = null;
            $data['approved_by'] = null;
            return;
        }

        if ($status === 'approved') {
            $data['processed_at'] = $existing?->processed_at ?: $now;
            $data['approved_at'] = $existing && $existing->status === 'approved'
                ? ($existing->approved_at ?: $now)
                : $now;
            $data['approved_by'] = Auth::id();
            return;
        }

        throw ValidationException::withMessages([
            'status' => 'Estado de corrida no soportado.',
        ]);
    }

    private function ensurePeriodAllowsStatus(PayrollPeriod $period, string $status): void
    {
        if ($period->status === 'closed') {
            throw ValidationException::withMessages([
                'status' => 'El periodo está cerrado. No se pueden modificar corridas.',
            ]);
        }

        if ($period->status === 'draft' && $status !== 'draft') {
            throw ValidationException::withMessages([
                'status' => 'Debe abrir el periodo antes de avanzar la corrida a estados superiores.',
            ]);
        }
    }

    private function ensurePeriodAllowsGeneration(PayrollRun $run): void
    {
        $period = $run->period;
        if (!$period) {
            throw ValidationException::withMessages([
                'payroll_period_id' => 'La corrida no tiene un periodo asociado.',
            ]);
        }

        if ($period->status === 'draft') {
            throw ValidationException::withMessages([
                'status' => 'Debe abrir el periodo antes de generar entradas.',
            ]);
        }

        if ($period->status === 'closed' && $run->status !== 'approved') {
            throw ValidationException::withMessages([
                'status' => 'El periodo está cerrado y no se pueden recalcular corridas pendientes.',
            ]);
        }

        if ($run->status === 'approved') {
            throw ValidationException::withMessages([
                'status' => 'La corrida ya fue aprobada y no se puede recalcular.',
            ]);
        }
    }

    private function assertStatusTransition(?PayrollRun $run, string $to): void
    {
        $from = $run?->status;

        if ($from === null) {
            return;
        }

        if ($from === $to) {
            return;
        }

        $allowed = [
            'draft' => ['processing', 'approved'],
            'processing' => ['approved'],
            'approved' => [],
        ];

        $next = $allowed[$from] ?? [];

        if (!in_array($to, $next, true)) {
            throw ValidationException::withMessages([
                'status' => sprintf('No se puede cambiar el estado de %s a %s.', $from, $to),
            ]);
        }

        if ($to === 'approved') {
            $entriesCount = $run?->entries()->count() ?? 0;
            if ($entriesCount === 0) {
                throw ValidationException::withMessages([
                    'status' => 'Debe generar las entradas antes de aprobar la corrida.',
                ]);
            }
        }
    }
}
