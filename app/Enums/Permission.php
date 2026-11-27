<?php

namespace App\Enums;

/**
 * What an admin role can do. The super admin has every permission; other admins get the ones their role lists.
 */
enum Permission: string
{
    case UsersManage = 'users.manage';

    case EmployeesView = 'employees.view';
    case EmployeesManage = 'employees.manage';
    case LeaveReview = 'leave.review';
    case AttendanceView = 'attendance.view';
    case HolidaysManage = 'holidays.manage';
    case OvertimeReview = 'overtime.review';
    case TrainingManage = 'training.manage';

    case PayrollView = 'payroll.view';
    case PayrollManage = 'payroll.manage';
    case PayrollApprove = 'payroll.approve';

    case ClaimsView = 'claims.view';
    case ClaimsReview = 'claims.review';
    case ClaimsReimburse = 'claims.reimburse';

    case InvoicesManage = 'invoices.manage';
    case LedgerManage = 'ledger.manage';
    case ReportsView = 'reports.view';

    case ProcurementManage = 'procurement.manage';
    case ProcurementApprove = 'procurement.approve';
    case InventoryManage = 'inventory.manage';

    case CrmManage = 'crm.manage';

    case AuditView = 'audit.view';

    public function label(): string
    {
        return match ($this) {
            self::UsersManage => __('Manage user accounts'),
            self::EmployeesView => __('View employees'),
            self::EmployeesManage => __('Manage employees, departments and leave types'),
            self::LeaveReview => __('Approve or reject leave'),
            self::AttendanceView => __('View attendance, movements and the clock-in QR'),
            self::HolidaysManage => __('Manage public holidays'),
            self::OvertimeReview => __('Approve or reject overtime'),
            self::TrainingManage => __('Manage training records'),
            self::PayrollView => __('View payroll and payslips'),
            self::PayrollManage => __('Prepare payroll and edit pay settings'),
            self::PayrollApprove => __('Approve payroll and mark it paid'),
            self::ClaimsView => __('View expense claims'),
            self::ClaimsReview => __('Approve or reject expense claims'),
            self::ClaimsReimburse => __('Pay back approved claims'),
            self::InvoicesManage => __('Invoices, bills, customers and suppliers'),
            self::LedgerManage => __('Journal entries and chart of accounts'),
            self::ReportsView => __('Financial reports'),
            self::ProcurementManage => __('Raise purchase requests and orders'),
            self::ProcurementApprove => __('Approve purchase requests'),
            self::InventoryManage => __('Manage products and stock'),
            self::CrmManage => __('Leads, activities and quotations'),
            self::AuditView => __('View and export the audit trail'),
        };
    }

    /**
     * The module heading the permission is listed under.
     */
    public function module(): string
    {
        return match ($this) {
            self::UsersManage => __('Users'),
            self::EmployeesView, self::EmployeesManage, self::LeaveReview, self::AttendanceView,
            self::HolidaysManage, self::OvertimeReview, self::TrainingManage => __('Human resources'),
            self::PayrollView, self::PayrollManage, self::PayrollApprove => __('Payroll'),
            self::ClaimsView, self::ClaimsReview, self::ClaimsReimburse => __('Expense claims'),
            self::InvoicesManage, self::LedgerManage, self::ReportsView => __('Finance'),
            self::ProcurementManage, self::ProcurementApprove, self::InventoryManage => __('Purchasing & stock'),
            self::CrmManage => __('Sales & CRM'),
            self::AuditView => __('Audit'),
        };
    }

    /**
     * Permissions this one includes: whoever can manage or approve something can also see it.
     *
     * @return array<int, self>
     */
    public function includes(): array
    {
        return match ($this) {
            self::EmployeesManage => [self::EmployeesView],
            self::PayrollManage, self::PayrollApprove => [self::PayrollView],
            self::ClaimsReview, self::ClaimsReimburse => [self::ClaimsView],
            default => [],
        };
    }

    /**
     * @return array<string, array<string, string>> module => [value => label]
     */
    public static function grouped(): array
    {
        return collect(self::cases())
            ->groupBy(fn (self $permission) => $permission->module())
            ->map(fn ($permissions) => $permissions->mapWithKeys(fn (self $permission) => [$permission->value => $permission->label()])->all())
            ->all();
    }
}
