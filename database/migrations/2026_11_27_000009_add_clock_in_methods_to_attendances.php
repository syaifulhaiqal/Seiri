<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * How and where each clock in / out happened (office QR or remote link), so HR can check remote work.
     */
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            foreach (['in', 'out'] as $event) {
                $table->string("clock_{$event}_method", 10)->nullable();
                $table->string("clock_{$event}_ip", 45)->nullable();
                $table->decimal("clock_{$event}_latitude", 10, 7)->nullable();
                $table->decimal("clock_{$event}_longitude", 10, 7)->nullable();
            }

            $table->string('work_location', 100)->nullable();
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->boolean('can_work_remotely')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('can_work_remotely');
        });

        Schema::table('attendances', function (Blueprint $table) {
            foreach (['in', 'out'] as $event) {
                $table->dropColumn(["clock_{$event}_method", "clock_{$event}_ip", "clock_{$event}_latitude", "clock_{$event}_longitude"]);
            }

            $table->dropColumn('work_location');
        });
    }
};
