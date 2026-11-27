<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EmployeeStatus;
use App\Enums\EmploymentType;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EmployeeRequest;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::enum(EmployeeStatus::class)],
        ]);

        $employees = Employee::query()
            ->with('department')
            ->search($filters['search'] ?? null)
            ->when($filters['department'] ?? null, fn ($query, int $id) => $query->where('department_id', $id))
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.employees.index', [
            'employees' => $employees,
            'filters' => $filters,
            'departments' => Department::query()->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function create(): View
    {
        return view('admin.employees.form', $this->formData(new Employee([
            'employment_type' => EmploymentType::Permanent,
            'status' => EmployeeStatus::Active,
            'join_date' => today(),
        ])));
    }

    public function store(EmployeeRequest $request): RedirectResponse
    {
        $employee = new Employee(Arr::except($request->validated(), 'user_id'));
        $employee->user_id = $request->validated('user_id');
        $employee->save();

        return to_route('admin.employees.show', $employee)->with('status', __(':name has been added.', ['name' => $employee->name]));
    }

    public function show(Employee $employee): View
    {
        $employee->load(['department', 'user', 'payItems']);

        return view('admin.employees.show', [
            'employee' => $employee,
            'balances' => $employee->leaveBalances(now()->year),
            'leaveRequests' => $employee->leaveRequests()->with('leaveType')->latest('start_date')->take(10)->get(),
            'attendances' => $employee->attendances()->latest('date')->take(10)->get(),
            'payslips' => $employee->payslips()->with('payrollRun')->latest('id')->take(6)->get(),
        ]);
    }

    public function edit(Employee $employee): View
    {
        return view('admin.employees.form', $this->formData($employee));
    }

    public function update(EmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $employee->fill(Arr::except($request->validated(), 'user_id'));
        $employee->user_id = $request->validated('user_id');
        $employee->save();

        return to_route('admin.employees.show', $employee)->with('status', __('Employee updated.'));
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        // Payroll and claims are financial records; they must survive, so the employee stays too.
        if ($employee->payslips()->exists() || $employee->expenseClaims()->exists()) {
            return back()->withErrors(['delete' => __(':name has payslips or expense claims, so the record must be kept. Set the status to Resigned instead.', ['name' => $employee->name])]);
        }

        $name = $employee->name;
        $employee->delete();

        return to_route('admin.employees.index')->with('status', __(':name has been deleted.', ['name' => $name]));
    }

    /**
     * @return array<string, mixed>
     */
    protected function formData(Employee $employee): array
    {
        return [
            'employee' => $employee,
            'departments' => Department::query()->orderBy('name')->pluck('name', 'id'),
            // Regular logins that are free, plus the one this employee already has.
            'users' => User::query()
                ->where('role', UserRole::User)
                ->where(fn ($query) => $query
                    ->whereDoesntHave('employee')
                    ->when($employee->user_id, fn ($query, int $id) => $query->orWhere('id', $id)))
                ->orderBy('name')
                ->get(['id', 'name', 'email'])
                ->mapWithKeys(fn (User $user) => [$user->id => "{$user->name} ({$user->email})"]),
        ];
    }
}
