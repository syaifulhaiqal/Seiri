<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Identity and income tax numbers, printed on the yearly EA statement.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('ic_no', 20)->nullable()->after('date_of_birth');
            $table->string('tax_no', 20)->nullable()->after('monthly_pcb');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['ic_no', 'tax_no']);
        });
    }
};
