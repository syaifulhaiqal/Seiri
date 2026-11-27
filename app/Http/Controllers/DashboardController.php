<?php

namespace App\Http\Controllers;

use App\Enums\ClaimStatus;
use App\Enums\InvoiceType;
use App\Enums\LeaveStatus;
use App\Enums\PayrollStatus;
use App\Enums\UserRole;
use App\Models\Account;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\ExpenseClaim;
use App\Models\Invoice;
use App\Models\LeaveRequest;
use App\Models\PayrollRun;
use App\Models\User;
use App\Services\FinancialReports;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $isAdmin = $user->isAdmin();

        return view('dashboard', [
            'user' => $user,
            'stats' => $user->can('users.manage') ? $this->userStats() : null,
            'hrStats' => $user->can('employees.view') ? $this->hrStats() : null,
            'financeStats' => $user->can('reports.view') ? $this->financeStats() : null,
            'waiting' => $isAdmin ? $this->waiting($user) : [],
            'pendingLeave' => $user->can('leave.review')
                ? LeaveRequest::query()->with(['employee', 'leaveType'])->where('status', LeaveStatus::Pending)->oldest()->take(5)->get()
                : null,
            'employee' => $isAdmin ? null : $user->employee,
            'todayAttendance' => $isAdmin ? null : $user->employee?->attendances()->whereDate('date', today())->first(),
        ]);
    }

    /**
     * Work waiting for this admin, limited to what their role lets them act on.
     *
     * @return array<int, array{label: string, url: string}>
     */
    protected function waiting(User $user): array
    {
        $items = [];

        if ($user->can('claims.review') && $count = ExpenseClaim::query()->where('status', ClaimStatus::Pending)->count()) {
            $items[] = ['label' => trans_choice(':count expense claim to review|:count expense claims to review', $count), 'url' => route('admin.claims.index', ['status' => 'pending'])];
        }

        if ($user->can('claims.reimburse') && $count = ExpenseClaim::query()->where('status', ClaimStatus::Approved)->count()) {
            $items[] = ['label' => trans_choice(':count approved claim to pay back|:count approved claims to pay back', $count), 'url' => route('admin.claims.index', ['status' => 'approved'])];
        }

        if ($user->can('payroll.approve') && $count = PayrollRun::query()->where('status', PayrollStatus::Draft)->count()) {
            $items[] = ['label' => trans_choice(':count payroll draft to approve|:count payroll drafts to approve', $count), 'url' => route('admin.payroll.index')];
        }

        return $items;
    }

    /**
     * @return array<string, int>
     */
    protected function userStats(): array
    {
        return [
            'total' => User::query()->count(),
            'active' => User::query()->whereNotNull('email_verified_at')->count(),
            'inactive' => User::query()->whereNull('email_verified_at')->count(),
            'admins' => User::query()->where('role', UserRole::Admin)->count(),
        ];
    }

    /**
     * @return array<string, int>
     */
    protected function hrStats(): array
    {
        return [
            'employees' => Employee::query()->active()->count(),
            'present' => Attendance::query()
                ->whereDate('date', today())
                ->whereNotNull('clock_in_at')
                ->whereHas('employee', fn (Builder $query) => $query->active())
                ->count(),
            'on_leave' => LeaveRequest::query()
                ->where('status', LeaveStatus::Approved)
                ->overlapping(today(), today())
                ->whereHas('employee', fn (Builder $query) => $query->active())
                ->distinct('employee_id')
                ->count('employee_id'),
            'pending_leave' => LeaveRequest::query()->where('status', LeaveStatus::Pending)->count(),
        ];
    }

    /**
     * Money figures for the admin dashboard, in ringgit.
     *
     * @return array{cash: float, receivable: float, payable: float, profit: float}
     */
    protected function financeStats(): array
    {
        $reports = app(FinancialReports::class);
        $balances = $reports->balances();
        $outstanding = fn (InvoiceType $type): float => (float) Invoice::query()->ofType($type)->outstanding()->sum('total')
            - (float) Invoice::query()->ofType($type)->outstanding()->sum('amount_paid');

        return [
            'cash' => (float) Money::fromCents(Account::query()->cashAndBank()->pluck('id')->sum(fn (int $id) => $balances[$id] ?? 0)),
            'receivable' => $outstanding(InvoiceType::Sales),
            'payable' => $outstanding(InvoiceType::Purchase),
            'profit' => (float) Money::fromCents($reports->profitAndLoss(today()->startOfMonth(), today())['net']),
        ];
    }
}
