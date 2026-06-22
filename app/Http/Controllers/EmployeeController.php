<?php

namespace App\Http\Controllers;

use App\Models\Party;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index()
    {
        $employees = Employee::with('party')
            ->orderByDesc('id')
            ->paginate(25);

        return view('admin.employees.index', compact('employees'));
    }

    public function create()
    {
        $employee = new Employee();
        $party = new Party(['type' => 'employee', 'is_active' => true]);
        $users = User::orderBy('name')->get();

        return view('admin.employees.form', [
            'employee' => $employee,
            'party' => $party,
            'users' => $users,
            'mode' => 'create',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $party = Party::create([
            'type' => 'employee',
            'name' => $data['name'],
            'document_type' => $data['document_type'] ?? null,
            'document_number' => $data['document_number'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'notes' => $data['notes'] ?? null,
            'is_active' => $data['is_active'] ?? false,
        ]);

        Employee::create([
            'party_id' => $party->id,
            'user_id' => $data['user_id'] ?? null,
            'role' => $data['role'] ?? null,
            'hire_date' => $data['hire_date'] ?? null,
            'salary_type' => $data['salary_type'] ?? null,
            'monthly_salary' => $data['monthly_salary'] ?? null,
            'hourly_rate' => $data['hourly_rate'] ?? null,
            'is_current' => $data['is_current'] ?? false,
        ]);

        return redirect()->route('employees.index')
            ->with('status', 'Empleado creado correctamente.');
    }

    public function edit(Employee $employee)
    {
        $employee->load('party');
        $party = $employee->party;
        $users = User::orderBy('name')->get();

        return view('admin.employees.form', [
            'employee' => $employee,
            'party' => $party,
            'users' => $users,
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, Employee $employee)
    {
        $data = $this->validateData($request, $employee->party_id);

        $employee->party->update([
            'name' => $data['name'],
            'document_type' => $data['document_type'] ?? null,
            'document_number' => $data['document_number'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'notes' => $data['notes'] ?? null,
            'is_active' => $data['is_active'] ?? false,
        ]);

        $employee->update([
            'user_id' => $data['user_id'] ?? null,
            'role' => $data['role'] ?? null,
            'hire_date' => $data['hire_date'] ?? null,
            'salary_type' => $data['salary_type'] ?? null,
            'monthly_salary' => $data['monthly_salary'] ?? null,
            'hourly_rate' => $data['hourly_rate'] ?? null,
            'is_current' => $data['is_current'] ?? false,
        ]);

        return redirect()->route('employees.index')
            ->with('status', 'Empleado actualizado correctamente.');
    }

    protected function validateData(Request $request, ?int $ignoreEmployeeId = null): array
    {
        $documentRule = 'nullable|string|max:50';
        $userRule = 'nullable|exists:users,id';
        if ($ignoreEmployeeId) {
            $userRule .= '|unique:employees,user_id,' . $ignoreEmployeeId;
        } else {
            $userRule .= '|unique:employees,user_id';
        }

        return $request->validate([
            'name' => 'required|string|max:255',
            'document_type' => 'nullable|in:RIF,CI',
            'document_number' => $documentRule,
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'notes' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'role' => 'nullable|string|max:100',
            'hire_date' => 'nullable|date',
            'salary_type' => 'nullable|in:mensual,por_hora',
            'monthly_salary' => 'nullable|numeric|min:0',
            'hourly_rate' => 'nullable|numeric|min:0',
            'is_current' => 'sometimes|boolean',
            'user_id' => $userRule,
        ]);
    }
}
