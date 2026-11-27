<?php

namespace App\Http\Controllers;

use App\Enums\PayrollStatus;
use App\Models\Payslip;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The signed-in employee's own payslips, once payroll is approved.
 */
class PayslipController extends Controller
{
    public function index(Request $request): View
    {
        $employee = $request->user()->employee;

        return view('payslips.index', [
            'employee' => $employee,
            'payslips' => $employee
                ? $employee->payslips()
                    ->with('payrollRun')
                    ->whereHas('payrollRun', fn (Builder $query) => $query->where('status', '!=', PayrollStatus::Draft))
                    ->join('payroll_runs', 'payroll_runs.id', '=', 'payslips.payroll_run_id')
                    ->orderByDesc('payroll_runs.period')
                    ->select('payslips.*')
                    ->paginate(12)
                : null,
        ]);
    }

    public function show(Request $request, Payslip $payslip): View
    {
        $payslip->load('payrollRun');

        abort_unless(
            $payslip->employee_id === $request->user()->employee?->id && ! $payslip->payrollRun->isDraft(),
            404,
        );

        return view('payslips.show', [
            'run' => $payslip->payrollRun,
            'payslip' => $payslip,
        ]);
    }
}
