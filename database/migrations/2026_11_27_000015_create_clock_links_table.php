<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Shared clock-in links: one link HR sends to everyone (e.g. for work from home), used after signing in.
     */
    public function up(): void
    {
        Schema::create('clock_links', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('token', 64)->unique();
            $table->date('expires_on')->nullable();
            $table->boolean('require_location')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clock_links');
    }
};
