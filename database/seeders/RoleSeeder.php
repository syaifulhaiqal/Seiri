<?php

namespace Database\Seeders;

use App\Enums\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Starting admin roles. The super admin can change them or add more under Roles & permissions.
 */
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'HR manager' => [
                'description' => 'Runs employee records, leave, attendance, overtime, training and public holidays.',
                'permissions' => [
                    Permission::EmployeesManage, Permission::LeaveReview, Permission::AttendanceView,
                    Permission::HolidaysManage, Permission::OvertimeReview, Permission::TrainingManage,
                ],
            ],
            'Payroll officer' => [
                'description' => 'Prepares the monthly payroll and keeps pay settings up to date.',
                'permissions' => [Permission::EmployeesView, Permission::PayrollManage],
            ],
            'Finance manager' => [
                'description' => 'Approves payroll, claims and purchase requests, and runs invoices, bills and the books.',
                'permissions' => [
                    Permission::PayrollApprove, Permission::ClaimsReview, Permission::ClaimsReimburse,
                    Permission::InvoicesManage, Permission::LedgerManage, Permission::ReportsView,
                    Permission::ProcurementApprove,
                ],
            ],
            'Purchasing officer' => [
                'description' => 'Raises purchase requests and orders, receives goods and keeps stock.',
                'permissions' => [Permission::ProcurementManage, Permission::InventoryManage],
            ],
            'Sales executive' => [
                'description' => 'Works leads and sends quotations.',
                'permissions' => [Permission::CrmManage],
            ],
            'Auditor' => [
                'description' => 'Read-only access to records, reports and the audit trail.',
                'permissions' => [
                    Permission::EmployeesView, Permission::AttendanceView, Permission::PayrollView,
                    Permission::ClaimsView, Permission::ReportsView, Permission::AuditView,
                ],
            ],
        ];

        foreach ($roles as $name => $role) {
            Role::query()->firstOrCreate(['name' => $name], $role);
        }
    }
}
