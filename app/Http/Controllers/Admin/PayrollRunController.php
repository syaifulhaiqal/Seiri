<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PayrollStatus;
use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PayFromAccountRequest;
use App\Http\Requests\Admin\PayrollRunRequest;
use App\Models\Account;
use App\Models\Employee;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Notifications\PayrollAwaitingApproval;
use App\Notifications\PayslipReady;
use App\Services\PayrollService;
use App\Support\Notify;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class PayrollRunController extends Controller
{
    public function __construct(protected PayrollService $payroll) {}

    public function index(): View
    {
        return view('admin.payroll.index', [
            'runs' => PayrollRun::query()->orderByDesc('period')->paginate(12),
            'nextPeriod' => $this->nextPeriod(),
        ]);
    }

    public function store(PayrollRunRequest $request): RedirectResponse
    {
        $run = new PayrollRun(['period' => $request->period()]);
        $run->created_by = $request->user()->id;
        $run->save();

        $this->payroll->generate($run);

        if ($run->employee_count > 0) {
            Notify::admins(Permission::PayrollApprove, new PayrollAwaitingApproval($run));
        }

        return to_route('admin.payroll.show', $run)->with('status', trans_choice(
            'Payroll for :month prepared: :count payslip. Check it, then approve.|Payroll for :month prepared: :count payslips. Check them, then approve.',
            $run->employee_count,
            ['month' => $run->periodLabel(), 'count' => $run->employee_count],
        ));
    }

    public function show(PayrollRun $payrollRun): View
    {
        $payrollRun->load(['creator', 'approver', 'journalEntry', 'paymentEntry']);

        return view('admin.payroll.show', [
            'run' => $payrollRun,
            'payslips' => $payrollRun->payslips()->get(),
            'bankAccounts' => Account::query()->cashAndBank()->active()->orderBy('code')->get()->mapWithKeys(fn (Account $account) => [$account->id => $account->displayName()]),
            'missingSalary' => $payrollRun->isDraft()
                ? Employee::query()->active()->where('basic_salary', '<=', 0)->orderBy('name')->get(['id', 'name'])
                : collect(),
        ]);
    }

    public function recalculate(PayrollRun $payrollRun): RedirectResponse
    {
        if (! $payrollRun->isDraft()) {
            return back()->withErrors(['delete' => __('Approved payroll cannot be recalculated.')]);
        }

        $this->payroll->generate($payrollRun);

        return back()->with('status', __('Payroll recalculated from the current pay settings.'));
    }

    public function approve(Request $request, PayrollRun $payrollRun): RedirectResponse
    {
        if (! $payrollRun->isDraft() || $payrollRun->employee_count === 0) {
            return back()->withErrors(['delete' => __('Only a draft with payslips can be approved.')]);
        }

        $this->payroll->approve($payrollRun, $request->user());

        $payrollRun->payslips()->with('employee.user')->get()
            ->each(fn (Payslip $payslip) => Notify::employee($payslip->employee, new PayslipReady($payslip->setRelation('payrollRun', $payrollRun))));

        return back()->with('status', __('Payroll for :month approved and posted to the ledger.', ['month' => $payrollRun->periodLabel()]));
    }

    public function pay(PayFromAccountRequest $request, PayrollRun $payrollRun): RedirectResponse
    {
        if ($payrollRun->status !== PayrollStatus::Approved) {
            return back()->withErrors(['delete' => __('Approve the payroll before marking it paid.')]);
        }

        $this->payroll->markPaid($payrollRun, Account::query()->findOrFail($request->validated('account_id')), $request->validated('date'));

        return back()->with('status', __('Salaries for :month marked as paid.', ['month' => $payrollRun->periodLabel()]));
    }

    public function destroy(PayrollRun $payrollRun): RedirectResponse
    {
        if (! $payrollRun->isDraft()) {
            return back()->withErrors(['delete' => __('Approved payroll cannot be deleted.')]);
        }

        $month = $payrollRun->periodLabel();
        $payrollRun->delete();

        return to_route('admin.payroll.index')->with('status', __('Draft payroll for :month deleted.', ['month' => $month]));
    }

    public function payslip(PayrollRun $payrollRun, Payslip $payslip): View
    {
        return view('admin.payroll.payslip', [
            'run' => $payrollRun,
            'payslip' => $payslip,
        ]);
    }

    /**
     * The month after the latest run, or this month when there are none.
     */
    protected function nextPeriod(): string
    {
        $latest = PayrollRun::query()->max('period');

        return ($latest ? Carbon::parse($latest)->addMonthNoOverflow() : today())->format('Y-m');
    }
}
