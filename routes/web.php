<?php

use App\Http\Controllers;
use App\Http\Middleware\SetLocale;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check() ? to_route('dashboard') : view('welcome'))->name('home');

Route::middleware('guest')->group(function () {
    Route::get('register', [Controllers\Auth\RegisterController::class, 'create'])->name('register');
    Route::post('register', [Controllers\Auth\RegisterController::class, 'store'])->middleware('throttle:6,1')->name('register.store');

    Route::post('email/resend', [Controllers\Auth\EmailVerificationController::class, 'resend'])
        ->middleware('throttle:6,1')
        ->name('verification.resend');
});

// The activation link from the email. Signed, so it works without being logged in.
Route::get('email/verify/{user}/{hash}', [Controllers\Auth\EmailVerificationController::class, 'verify'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

// The EN / BM switch, for guests and signed-in users alike.
Route::post('locale/{locale}', Controllers\LocaleController::class)->whereIn('locale', array_keys(SetLocale::LOCALES))->name('locale.update');

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

            Route::get('notifications', [Controllers\NotificationController::class, 'index'])->name('notifications.index');
            Route::get('notifications/{notification}', [Controllers\NotificationController::class, 'open'])->name('notifications.open');
            Route::post('notifications/read-all', [Controllers\NotificationController::class, 'readAll'])->name('notifications.read_all');

            Route::get('tutorial', Controllers\HelpController::class)->name('help');

            Route::post('logout', [Controllers\Auth\LoginController::class, 'destroy'])->name('logout');

            if ($guard === 'web') {
                // Employee self-service.
                Route::get('leave', [Controllers\LeaveController::class, 'index'])->name('leave.index');
                Route::post('leave', [Controllers\LeaveController::class, 'store'])->name('leave.store');
                Route::put('leave/{leaveRequest}/cancel', [Controllers\LeaveController::class, 'cancel'])->name('leave.cancel');

                Route::get('attendance', [Controllers\AttendanceController::class, 'index'])->name('attendance.index');
                Route::post('attendance/clock-in', [Controllers\AttendanceController::class, 'clockIn'])->name('attendance.clock_in');
                Route::post('attendance/clock-out', [Controllers\AttendanceController::class, 'clockOut'])->name('attendance.clock_out');
                Route::get('overtime', [Controllers\OvertimeController::class, 'index'])->name('overtime.index');
                Route::post('overtime', [Controllers\OvertimeController::class, 'store'])->name('overtime.store');
                Route::put('overtime/{overtime}/cancel', [Controllers\OvertimeController::class, 'cancel'])->name('overtime.cancel');

                Route::get('movements', [Controllers\MovementController::class, 'index'])->name('movements.index');
                Route::post('movements', [Controllers\MovementController::class, 'store'])->name('movements.store');
                Route::put('movements/{movement}/back', [Controllers\MovementController::class, 'back'])->name('movements.back');
                Route::delete('movements/{movement}', [Controllers\MovementController::class, 'destroy'])->name('movements.destroy');

                Route::get('training', [Controllers\TrainingController::class, 'index'])->name('trainings.index');

                Route::get('approvals', [Controllers\ApprovalController::class, 'index'])->name('approvals.index');
                Route::put('approvals/{approval}', [Controllers\ApprovalController::class, 'decide'])->name('approvals.decide');
                Route::get('approvals/{approval}/receipt', [Controllers\ApprovalController::class, 'receipt'])->name('approvals.receipt');

                // Opened by scanning the office QR code (signed, expires after a few minutes).
                Route::get('attendance/scan', [Controllers\AttendanceController::class, 'scan'])->name('attendance.scan');
                Route::post('attendance/scan', [Controllers\AttendanceController::class, 'qr'])->name('attendance.qr');

                // A clock-in link HR shares with everyone (work from home). Guests sign in, then come back here.
                Route::get('clock/{token}', [Controllers\ClockLinkController::class, 'show'])->name('clock_link.show');
                Route::post('clock/{token}', [Controllers\ClockLinkController::class, 'store'])->name('clock_link.store');

                Route::get('payslips', [Controllers\PayslipController::class, 'index'])->name('payslips.index');
                Route::get('payslips/{payslip}', [Controllers\PayslipController::class, 'show'])->name('payslips.show');
                Route::get('ea-forms', [Controllers\EaFormController::class, 'index'])->name('ea_forms.index');
                Route::get('ea-forms/{year}', [Controllers\EaFormController::class, 'show'])->whereNumber('year')->name('ea_forms.show');

                Route::get('claims', [Controllers\ClaimController::class, 'index'])->name('claims.index');
                Route::post('claims', [Controllers\ClaimController::class, 'store'])->name('claims.store');
                Route::put('claims/{claim}/cancel', [Controllers\ClaimController::class, 'cancel'])->name('claims.cancel');
                Route::get('claims/{claim}/receipt', [Controllers\ClaimController::class, 'receipt'])->name('claims.receipt');
            }

            if ($guard === 'admin') {
                Route::middleware('admin')->group(function () {
                    // Each group needs one App\Enums\Permission. The super admin passes every check.
                    Route::middleware('can:users.manage')->group(function () {
                        Route::resource('users', Controllers\Admin\UserController::class)->only(['index', 'edit', 'update', 'destroy']);
                    });

                    Route::middleware('can:roles.manage')->group(function () {
                        Route::resource('roles', Controllers\Admin\RoleController::class)->except('show');
                    });

                    // HR. Manage routes come first so employees/create is not read as employees/{employee}.
                    Route::middleware('can:employees.manage')->group(function () {
                        Route::get('employees/{employee}/approvers', [Controllers\Admin\ApproverController::class, 'edit'])->name('employees.approvers.edit');
                        Route::put('employees/{employee}/approvers', [Controllers\Admin\ApproverController::class, 'update'])->name('employees.approvers.update');
                        Route::resource('employees', Controllers\Admin\EmployeeController::class)->except(['index', 'show']);
                        Route::resource('departments', Controllers\Admin\DepartmentController::class)->except('show');
                        Route::resource('leave-types', Controllers\Admin\LeaveTypeController::class)->except('show')->names('leave_types');
                    });

                    Route::middleware('can:employees.view')->group(function () {
                        Route::resource('employees', Controllers\Admin\EmployeeController::class)->only(['index', 'show']);
                    });

                    Route::resource('holidays', Controllers\Admin\HolidayController::class)->except(['create', 'show'])->middleware('can:holidays.manage');

                    Route::middleware('can:leave.review')->group(function () {
                        Route::get('leave-requests', [Controllers\Admin\LeaveRequestController::class, 'index'])->name('leave_requests.index');
                        Route::get('leave-requests/{leaveRequest}', [Controllers\Admin\LeaveRequestController::class, 'show'])->name('leave_requests.show');
                        Route::put('leave-requests/{leaveRequest}/review', [Controllers\Admin\LeaveRequestController::class, 'review'])->name('leave_requests.review');
                    });

                    Route::middleware('can:overtime.review')->group(function () {
                        Route::get('overtime', [Controllers\Admin\OvertimeRequestController::class, 'index'])->name('overtime.index');
                        Route::get('overtime/{overtime}', [Controllers\Admin\OvertimeRequestController::class, 'show'])->name('overtime.show');
                        Route::put('overtime/{overtime}/review', [Controllers\Admin\OvertimeRequestController::class, 'review'])->name('overtime.review');
                    });

                    Route::resource('training', Controllers\Admin\TrainingController::class)->except('show')
                        ->names('trainings')->parameters(['training' => 'training'])->middleware('can:training.manage');

                    Route::middleware('can:employees.manage')->prefix('attendance')->name('attendance.')->group(function () {
                        Route::resource('links', Controllers\Admin\ClockLinkController::class)->except(['create', 'edit'])->parameters(['links' => 'link']);
                        Route::put('links/{link}/renew', [Controllers\Admin\ClockLinkController::class, 'renew'])->name('links.renew');
                    });

                    Route::middleware('can:attendance.view')->group(function () {
                        Route::get('movements', [Controllers\Admin\MovementController::class, 'index'])->name('movements.index');
                        Route::get('attendance', [Controllers\Admin\AttendanceController::class, 'index'])->name('attendance.index');
                        Route::get('attendance/qr', [Controllers\Admin\AttendanceQrController::class, 'show'])->name('attendance.qr');
                        Route::get('attendance/qr/code', [Controllers\Admin\AttendanceQrController::class, 'code'])->name('attendance.qr_code');
                    });

                    // Payroll
                    Route::middleware('can:payroll.view')->group(function () {
                        Route::get('ea-forms', [Controllers\Admin\EaFormController::class, 'index'])->name('ea_forms.index');
                        Route::get('ea-forms/{year}/{employee}', [Controllers\Admin\EaFormController::class, 'show'])->whereNumber('year')->name('ea_forms.show');
                        Route::get('payroll', [Controllers\Admin\PayrollRunController::class, 'index'])->name('payroll.index');
                        Route::get('payroll/{payrollRun}', [Controllers\Admin\PayrollRunController::class, 'show'])->name('payroll.show');
                        Route::get('payroll/{payrollRun}/payslips/{payslip}', [Controllers\Admin\PayrollRunController::class, 'payslip'])->name('payroll.payslip')->scopeBindings();
                    });

                    Route::middleware('can:payroll.manage')->group(function () {
                        Route::post('payroll', [Controllers\Admin\PayrollRunController::class, 'store'])->name('payroll.store');
                        Route::delete('payroll/{payrollRun}', [Controllers\Admin\PayrollRunController::class, 'destroy'])->name('payroll.destroy');
                        Route::post('payroll/{payrollRun}/recalculate', [Controllers\Admin\PayrollRunController::class, 'recalculate'])->name('payroll.recalculate');

                        Route::post('employees/{employee}/pay-items', [Controllers\Admin\PayItemController::class, 'store'])->name('employees.pay_items.store');
                        Route::delete('employees/{employee}/pay-items/{payItem}', [Controllers\Admin\PayItemController::class, 'destroy'])->name('employees.pay_items.destroy')->scopeBindings();
                    });

                    Route::middleware('can:payroll.approve')->group(function () {
                        Route::post('payroll/{payrollRun}/approve', [Controllers\Admin\PayrollRunController::class, 'approve'])->name('payroll.approve');
                        Route::post('payroll/{payrollRun}/pay', [Controllers\Admin\PayrollRunController::class, 'pay'])->name('payroll.pay');
                    });

                    // Purchasing and stock. Create routes come before {id} routes.
                    Route::prefix('purchasing')->group(function () {
                        Route::middleware('can:procurement.manage')->group(function () {
                            Route::resource('purchase-requests', Controllers\Admin\PurchaseRequestController::class)->only(['create', 'store', 'edit', 'update'])->names('purchase_requests');
                            Route::put('purchase-requests/{purchase_request}/cancel', [Controllers\Admin\PurchaseRequestController::class, 'cancel'])->name('purchase_requests.cancel');

                            Route::resource('purchase-orders', Controllers\Admin\PurchaseOrderController::class)->names('purchase_orders');
                            Route::post('purchase-orders/{purchase_order}/place', [Controllers\Admin\PurchaseOrderController::class, 'place'])->name('purchase_orders.place');
                            Route::post('purchase-orders/{purchase_order}/receive', [Controllers\Admin\PurchaseOrderController::class, 'receive'])->name('purchase_orders.receive');
                            Route::post('purchase-orders/{purchase_order}/cancel', [Controllers\Admin\PurchaseOrderController::class, 'cancel'])->name('purchase_orders.cancel');
                            Route::post('purchase-orders/{purchase_order}/bill', [Controllers\Admin\PurchaseOrderController::class, 'bill'])->name('purchase_orders.bill')->middleware('can:invoices.manage');
                        });

                        Route::middleware('can:procurement.view')->group(function () {
                            Route::resource('purchase-requests', Controllers\Admin\PurchaseRequestController::class)->only(['index', 'show'])->names('purchase_requests');
                            Route::put('purchase-requests/{purchase_request}/review', [Controllers\Admin\PurchaseRequestController::class, 'review'])->name('purchase_requests.review');
                        });

                        Route::middleware('can:inventory.manage')->group(function () {
                            Route::resource('products', Controllers\Admin\ProductController::class);
                            Route::post('products/{product}/adjust', [Controllers\Admin\ProductController::class, 'adjust'])->name('products.adjust');
                        });
                    });

                    // Sales & CRM
                    Route::prefix('sales')->middleware('can:crm.manage')->group(function () {
                        Route::resource('leads', Controllers\Admin\LeadController::class);
                        Route::post('leads/{lead}/convert', [Controllers\Admin\LeadController::class, 'convert'])->name('leads.convert');
                        Route::post('leads/{lead}/activities', [Controllers\Admin\LeadController::class, 'activity'])->name('leads.activity');

                        Route::resource('quotations', Controllers\Admin\QuotationController::class);
                        Route::post('quotations/{quotation}/status', [Controllers\Admin\QuotationController::class, 'mark'])->name('quotations.mark');
                        Route::post('quotations/{quotation}/invoice', [Controllers\Admin\QuotationController::class, 'invoice'])->name('quotations.invoice')->middleware('can:invoices.manage');
                    });

                    // Finance
                    Route::prefix('finance')->group(function () {
                        Route::middleware('can:ledger.manage')->group(function () {
                            Route::resource('accounts', Controllers\Admin\AccountController::class)->except('show');
                            Route::resource('journal', Controllers\Admin\JournalEntryController::class)->only(['index', 'create', 'store', 'show'])
                                ->names('journal_entries')->parameters(['journal' => 'journalEntry']);
                            Route::post('journal/{journalEntry}/reverse', [Controllers\Admin\JournalEntryController::class, 'reverse'])->name('journal_entries.reverse');
                        });

                        Route::middleware('can:invoices.manage')->group(function () {
                            Route::resource('contacts', Controllers\Admin\ContactController::class)->except('show');

                            foreach (['invoices' => Controllers\Admin\InvoiceController::class, 'bills' => Controllers\Admin\BillController::class] as $path => $controller) {
                                Route::resource($path, $controller)->parameters([$path => 'invoice']);
                                Route::post("{$path}/{invoice}/issue", [$controller, 'issue'])->name("{$path}.issue");
                                Route::post("{$path}/{invoice}/payments", [$controller, 'pay'])->name("{$path}.pay");
                                Route::post("{$path}/{invoice}/void", [$controller, 'void'])->name("{$path}.void");
                            }
                        });

                        Route::middleware('can:claims.view')->group(function () {
                            Route::get('claims', [Controllers\Admin\ExpenseClaimController::class, 'index'])->name('claims.index');
                            Route::get('claims/{claim}', [Controllers\Admin\ExpenseClaimController::class, 'show'])->name('claims.show');
                            Route::get('claims/{claim}/receipt', [Controllers\Admin\ExpenseClaimController::class, 'receipt'])->name('claims.receipt');
                        });
                        Route::put('claims/{claim}/review', [Controllers\Admin\ExpenseClaimController::class, 'review'])->name('claims.review')->middleware('can:claims.review');
                        Route::post('claims/{claim}/reimburse', [Controllers\Admin\ExpenseClaimController::class, 'reimburse'])->name('claims.reimburse')->middleware('can:claims.reimburse');

                        Route::middleware('can:reports.view')->group(function () {
                            Route::get('reports/trial-balance', [Controllers\Admin\ReportController::class, 'trialBalance'])->name('reports.trial_balance');
                            Route::get('reports/profit-and-loss', [Controllers\Admin\ReportController::class, 'profitAndLoss'])->name('reports.profit_and_loss');
                            Route::get('reports/balance-sheet', [Controllers\Admin\ReportController::class, 'balanceSheet'])->name('reports.balance_sheet');
                            Route::get('reports/general-ledger', [Controllers\Admin\ReportController::class, 'generalLedger'])->name('reports.general_ledger');
                        });
                    });

                    // Audit
                    Route::middleware('can:audit.view')->group(function () {
                        Route::get('audit', [Controllers\Admin\AuditLogController::class, 'index'])->name('audit.index');
                        Route::get('audit/export', [Controllers\Admin\AuditLogController::class, 'export'])->name('audit.export');
                        Route::get('audit/{auditLog}', [Controllers\Admin\AuditLogController::class, 'show'])->name('audit.show');
                    });
                });
            }
        });
    });
}
