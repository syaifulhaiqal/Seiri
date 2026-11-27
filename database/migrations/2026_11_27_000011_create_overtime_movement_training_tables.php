<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Overtime claims (paid through payroll), movement records (out of the office during work) and training.
     */
    public function up(): void
    {
        Schema::create('overtime_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->decimal('hours', 5, 2);
            $table->string('day_type', 20);
            $table->string('reason', 500);
            $table->string('status', 20)->index();
            $table->string('review_note', 500)->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            // The payslip that paid it. Cleared when a draft payroll is recalculated or deleted.
            $table->foreignId('payslip_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['employee_id', 'date']);
        });

        Schema::table('payslips', function (Blueprint $table) {
            $table->decimal('overtime_hours', 6, 2)->default(0)->after('allowances');
            $table->decimal('overtime_pay', 12, 2)->default(0)->after('overtime_hours');
        });

        Schema::create('movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->time('time_out');
            $table->time('time_back')->nullable();
            $table->string('destination', 150);
            $table->string('purpose', 500);
            $table->timestamps();

            $table->index(['employee_id', 'date']);
        });

        Schema::create('trainings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('title', 150);
            $table->string('provider', 150)->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('hours', 6, 1)->nullable();
            $table->decimal('cost', 12, 2)->default(0);
            $table->string('status', 20);
            $table->string('notes', 1000)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trainings');
        Schema::dropIfExists('movements');

        Schema::table('payslips', function (Blueprint $table) {
            $table->dropColumn(['overtime_hours', 'overtime_pay']);
        });

        Schema::dropIfExists('overtime_requests');
    }
};
