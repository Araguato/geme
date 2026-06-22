<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ExpenseController extends Controller
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

    public function index(Request $request)
    {
        $this->authorizeFinances();

        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        $type = $request->query('type'); // business / personal / null
        $categoryId = $request->query('category_id');

        $query = Expense::with('category')->orderByDesc('date')->orderByDesc('id');

        if ($dateFrom) {
            $query->whereDate('date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('date', '<=', $dateTo);
        }
        if ($type && in_array($type, ['business', 'personal'], true)) {
            $query->where('type', $type);
        }
        if ($categoryId) {
            $query->where('expense_category_id', $categoryId);
        }

        $expenses = $query->paginate(50)->withQueryString();

        $sumBusiness = (clone $query)->where('type', 'business')->sum('amount');
        $sumPersonal = (clone $query)->where('type', 'personal')->sum('amount');

        $categories = ExpenseCategory::where('is_active', true)->orderBy('name')->get();

        return view('finances.index', compact(
            'expenses',
            'categories',
            'dateFrom',
            'dateTo',
            'type',
            'categoryId',
            'sumBusiness',
            'sumPersonal',
        ));
    }

    public function create()
    {
        $this->authorizeFinances();

        $categories = ExpenseCategory::where('is_active', true)->orderBy('name')->get();

        return view('finances.form', [
            'expense' => new Expense([
                'date' => now()->toDateString(),
                'type' => 'business',
            ]),
            'categories' => $categories,
            'mode' => 'create',
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeFinances();

        $data = $this->validateData($request);
        $data['user_id'] = $request->user()?->id;

        Expense::create($data);

        return redirect()->route('finances.index');
    }

    public function edit(Expense $expense)
    {
        $this->authorizeFinances();

        $categories = ExpenseCategory::where('is_active', true)->orderBy('name')->get();

        return view('finances.form', [
            'expense' => $expense,
            'categories' => $categories,
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, Expense $expense)
    {
        $this->authorizeFinances();

        $data = $this->validateData($request);

        $expense->update($data);

        return redirect()->route('finances.index');
    }

    public function destroy(Expense $expense)
    {
        $this->authorizeFinances();

        $expense->delete();

        return redirect()->route('finances.index');
    }

    public function monthlyReport(Request $request)
    {
        $this->authorizeFinances();

        $year = (int)($request->query('year') ?: now()->year);
        $month = (int)($request->query('month') ?: now()->month);
        $monthsBack = 3;

        $current = Carbon::createFromDate($year, $month, 1)->startOfMonth();

        $periods = [];

        for ($i = $monthsBack; $i >= 0; $i--) {
            $start = (clone $current)->subMonthsNoOverflow($i)->startOfMonth();
            $end = (clone $start)->endOfMonth();

            $totals = Expense::whereBetween('date', [$start->toDateString(), $end->toDateString()])
                ->selectRaw("type, SUM(amount) as total")
                ->groupBy('type')
                ->pluck('total', 'type');

            $periods[] = [
                'label' => $start->format('m/Y'),
                'start' => $start,
                'end' => $end,
                'business' => (float)($totals['business'] ?? 0),
                'personal' => (float)($totals['personal'] ?? 0),
            ];
        }

        return view('finances.reports.monthly', [
            'selectedYear' => $year,
            'selectedMonth' => $month,
            'periods' => $periods,
        ]);
    }

    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'date' => 'required|date',
            'due_date' => 'nullable|date',
            'paid_at' => 'nullable|date',
            'amount' => 'required|numeric|min:0',
            'type' => 'required|in:business,personal',
            'expense_category_id' => 'required|exists:expense_categories,id',
            'payment_method' => 'nullable|string|max:50',
            'note' => 'nullable|string',
        ]);

        return $data;
    }
}
