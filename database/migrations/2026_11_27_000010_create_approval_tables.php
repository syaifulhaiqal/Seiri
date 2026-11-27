<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Approval chains: each employee can have up to three approvers (e.g. team lead, then manager) per kind of
     * request. Every request then gets one approval step per approver, decided in order.
     */
    public function up(): void
    {
        Schema::create('approvers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->unsignedTinyInteger('level');
            $table->foreignId('approver_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['employee_id', 'type', 'level']);
        });

        Schema::create('approvals', function (Blueprint $table) {
            $table->id();
            $table->morphs('approvable');
            $table->unsignedTinyInteger('level');
            $table->foreignId('approver_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20)->index();
            $table->string('note', 500)->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approvals');
        Schema::dropIfExists('approvers');
    }
};
