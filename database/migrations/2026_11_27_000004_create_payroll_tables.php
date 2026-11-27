<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->decimal('basic_salary', 12, 2)->default(0)->after('end_date');
            // Monthly tax deduction (PCB / MTD) worked out with the LHDN calculator.
            $table->decimal('monthly_pcb', 10, 2)->default(0)->after('basic_salary');
            $table->string('bank_name', 100)->nullable()->after('monthly_pcb');
            $table->string('bank_account_no', 30)->nullable()->after('bank_name');
        });

        Schema::create('pay_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('type', 20);
            $table->decimal('amount', 12, 2);
            $table->timestamps();
        });

        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            // First day of the month being paid.
            $table->date('period')->unique();
            $table->string('status', 20)->default('draft')->index();
            $table->unsignedInteger('employee_count')->default(0);
            $table->decimal('total_gross', 14, 2)->default(0);
            $table->decimal('total_deductions', 14, 2)->default(0);
            $table->decimal('total_net', 14, 2)->default(0);
            $table->decimal('total_employer_contributions', 14, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('journal_entry_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            // Copies of the employee's details at the time, so old payslips never change.
            $table->string('employee_no', 20);
            $table->string('employee_name');
            $table->string('job_title', 100)->nullable();
            $table->string('department_name', 100)->nullable();
            $table->string('bank_name', 100)->nullable();
            $table->string('bank_account_no', 30)->nullable();
            $table->unsignedSmallInteger('working_days');
            $table->unsignedSmallInteger('days_worked');
            $table->unsignedSmallInteger('unpaid_leave_days')->default(0);
            $table->decimal('basic_salary', 12, 2);
            $table->decimal('basic_pay', 12, 2);
            $table->decimal('allowances', 12, 2)->default(0);
            $table->decimal('unpaid_leave_deduction', 12, 2)->default(0);
            $table->decimal('gross_pay', 12, 2);
            $table->decimal('epf_employee', 10, 2)->default(0);
            $table->decimal('socso_employee', 10, 2)->default(0);
            $table->decimal('eis_employee', 10, 2)->default(0);
            $table->decimal('pcb', 10, 2)->default(0);
            $table->decimal('other_deductions', 12, 2)->default(0);
            $table->decimal('total_deductions', 12, 2);
            $table->decimal('net_pay', 12, 2);
            $table->decimal('epf_employer', 10, 2)->default(0);
            $table->decimal('socso_employer', 10, 2)->default(0);
            $table->decimal('eis_employer', 10, 2)->default(0);
            $table->json('items')->nullable();
            $table->timestamps();

            $table->unique(['payroll_run_id', 'employee_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payslips');
        Schema::dropIfExists('payroll_runs');
        Schema::dropIfExists('pay_items');

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['basic_salary', 'monthly_pcb', 'bank_name', 'bank_account_no']);
        });
    }
};
