<?php

use App\Http\Controllers;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check() ? to_route('dashboard') : view('welcome'))->name('home');

Route::middleware('guest')->group(function () {
    Route::get('register', [Controllers\Auth\RegisterController::class, 'create'])->name('register');
    Route::post('register', [Controllers\Auth\RegisterController::class, 'store'])->name('register.store');

    Route::post('email/resend', [Controllers\Auth\EmailVerificationController::class, 'resend'])
        ->middleware('throttle:6,1')
        ->name('verification.resend');
});

// The activation link from the email. Signed, so it works without being logged in.
Route::get('email/verify/{user}/{hash}', [Controllers\Auth\EmailVerificationController::class, 'verify'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

// Two sign-in areas sharing the same controllers: users at /login (`web` guard) and admins at
// /admin/login (`admin` guard). Each guard has its own session key, so both can be signed in at once.
foreach (['web' => '', 'admin' => 'admin'] as $guard => $area) {
    Route::prefix($area)->name($area ? "{$area}." : '')->group(function () use ($guard) {
        Route::middleware("guest:{$guard}")->group(function () {
            Route::get('login', [Controllers\Auth\LoginController::class, 'create'])->name('login');
            Route::post('login', [Controllers\Auth\LoginController::class, 'store'])->name('login.store');
        });

        Route::middleware(["auth:{$guard}", 'active'])->group(function () use ($guard) {
            Route::get('dashboard', Controllers\DashboardController::class)->name('dashboard');

            Route::get('profile', [Controllers\ProfileController::class, 'edit'])->name('profile.edit');
            Route::put('profile', [Controllers\ProfileController::class, 'update'])->name('profile.update');
            Route::put('profile/password', [Controllers\ProfileController::class, 'updatePassword'])->name('profile.update_password');

            Route::post('logout', [Controllers\Auth\LoginController::class, 'destroy'])->name('logout');

            if ($guard === 'web') {
                // Employee self-service.
                Route::get('leave', [Controllers\LeaveController::class, 'index'])->name('leave.index');
                Route::post('leave', [Controllers\LeaveController::class, 'store'])->name('leave.store');
                Route::put('leave/{leaveRequest}/cancel', [Controllers\LeaveController::class, 'cancel'])->name('leave.cancel');

                Route::get('attendance', [Controllers\AttendanceController::class, 'index'])->name('attendance.index');
                Route::post('attendance/clock-in', [Controllers\AttendanceController::class, 'clockIn'])->name('attendance.clock_in');
                Route::post('attendance/clock-out', [Controllers\AttendanceController::class, 'clockOut'])->name('attendance.clock_out');

                Route::get('payslips', [Controllers\PayslipController::class, 'index'])->name('payslips.index');
                Route::get('payslips/{payslip}', [Controllers\PayslipController::class, 'show'])->name('payslips.show');

                Route::get('claims', [Controllers\ClaimController::class, 'index'])->name('claims.index');
                Route::post('claims', [Controllers\ClaimController::class, 'store'])->name('claims.store');
                Route::put('claims/{claim}/cancel', [Controllers\ClaimController::class, 'cancel'])->name('claims.cancel');
                Route::get('claims/{claim}/receipt', [Controllers\ClaimController::class, 'receipt'])->name('claims.receipt');
            }

            if ($guard === 'admin') {
                Route::middleware('admin')->group(function () {
                    Route::resource('users', Controllers\Admin\UserController::class)->only(['index', 'edit', 'update', 'destroy']);

                    // HR
                    Route::resource('employees', Controllers\Admin\EmployeeController::class);
                    Route::resource('departments', Controllers\Admin\DepartmentController::class)->except('show');
                    Route::resource('leave-types', Controllers\Admin\LeaveTypeController::class)->except('show')->names('leave_types');

                    Route::get('leave-requests', [Controllers\Admin\LeaveRequestController::class, 'index'])->name('leave_requests.index');
                    Route::get('leave-requests/{leaveRequest}', [Controllers\Admin\LeaveRequestController::class, 'show'])->name('leave_requests.show');
                    Route::put('leave-requests/{leaveRequest}/review', [Controllers\Admin\LeaveRequestController::class, 'review'])->name('leave_requests.review');

                    Route::get('attendance', [Controllers\Admin\AttendanceController::class, 'index'])->name('attendance.index');

                    Route::post('employees/{employee}/pay-items', [Controllers\Admin\PayItemController::class, 'store'])->name('employees.pay_items.store');
                    Route::delete('employees/{employee}/pay-items/{payItem}', [Controllers\Admin\PayItemController::class, 'destroy'])->name('employees.pay_items.destroy')->scopeBindings();

                    // Payroll
                    Route::get('payroll', [Controllers\Admin\PayrollRunController::class, 'index'])->name('payroll.index');
                    Route::post('payroll', [Controllers\Admin\PayrollRunController::class, 'store'])->name('payroll.store');
                    Route::get('payroll/{payrollRun}', [Controllers\Admin\PayrollRunController::class, 'show'])->name('payroll.show');
                    Route::delete('payroll/{payrollRun}', [Controllers\Admin\PayrollRunController::class, 'destroy'])->name('payroll.destroy');
                    Route::post('payroll/{payrollRun}/recalculate', [Controllers\Admin\PayrollRunController::class, 'recalculate'])->name('payroll.recalculate');
                    Route::post('payroll/{payrollRun}/approve', [Controllers\Admin\PayrollRunController::class, 'approve'])->name('payroll.approve');
                    Route::post('payroll/{payrollRun}/pay', [Controllers\Admin\PayrollRunController::class, 'pay'])->name('payroll.pay');
                    Route::get('payroll/{payrollRun}/payslips/{payslip}', [Controllers\Admin\PayrollRunController::class, 'payslip'])->name('payroll.payslip')->scopeBindings();

                    // Finance
                    Route::prefix('finance')->group(function () {
                        Route::resource('accounts', Controllers\Admin\AccountController::class)->except('show');
                        Route::resource('journal', Controllers\Admin\JournalEntryController::class)->only(['index', 'create', 'store', 'show'])
                            ->names('journal_entries')->parameters(['journal' => 'journalEntry']);
                        Route::post('journal/{journalEntry}/reverse', [Controllers\Admin\JournalEntryController::class, 'reverse'])->name('journal_entries.reverse');

                        Route::resource('contacts', Controllers\Admin\ContactController::class)->except('show');

                        foreach (['invoices' => Controllers\Admin\InvoiceController::class, 'bills' => Controllers\Admin\BillController::class] as $path => $controller) {
                            Route::resource($path, $controller)->parameters([$path => 'invoice']);
                            Route::post("{$path}/{invoice}/issue", [$controller, 'issue'])->name("{$path}.issue");
                            Route::post("{$path}/{invoice}/payments", [$controller, 'pay'])->name("{$path}.pay");
                            Route::post("{$path}/{invoice}/void", [$controller, 'void'])->name("{$path}.void");
                        }

                        Route::get('claims', [Controllers\Admin\ExpenseClaimController::class, 'index'])->name('claims.index');
                        Route::get('claims/{claim}', [Controllers\Admin\ExpenseClaimController::class, 'show'])->name('claims.show');
                        Route::put('claims/{claim}/review', [Controllers\Admin\ExpenseClaimController::class, 'review'])->name('claims.review');
                        Route::post('claims/{claim}/reimburse', [Controllers\Admin\ExpenseClaimController::class, 'reimburse'])->name('claims.reimburse');
                        Route::get('claims/{claim}/receipt', [Controllers\Admin\ExpenseClaimController::class, 'receipt'])->name('claims.receipt');

                        Route::get('reports/trial-balance', [Controllers\Admin\ReportController::class, 'trialBalance'])->name('reports.trial_balance');
                        Route::get('reports/profit-and-loss', [Controllers\Admin\ReportController::class, 'profitAndLoss'])->name('reports.profit_and_loss');
                        Route::get('reports/balance-sheet', [Controllers\Admin\ReportController::class, 'balanceSheet'])->name('reports.balance_sheet');
                        Route::get('reports/general-ledger', [Controllers\Admin\ReportController::class, 'generalLedger'])->name('reports.general_ledger');
                    });

                    // Audit
                    Route::get('audit', [Controllers\Admin\AuditLogController::class, 'index'])->name('audit.index');
                    Route::get('audit/export', [Controllers\Admin\AuditLogController::class, 'export'])->name('audit.export');
                    Route::get('audit/{auditLog}', [Controllers\Admin\AuditLogController::class, 'show'])->name('audit.show');
                });
            }
        });
    });
}
