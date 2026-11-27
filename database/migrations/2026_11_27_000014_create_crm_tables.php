<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sales: leads with their calls and meetings, and quotations that become invoices.
     */
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150);
            $table->string('contact_name', 100);
            $table->string('company', 150)->nullable();
            $table->string('email')->nullable();
            $table->string('phone_number', 20)->nullable();
            $table->string('source', 50)->nullable();
            $table->string('status', 20)->index();
            $table->decimal('value', 14, 2)->default(0);
            $table->date('expected_close')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            // The customer record, once the lead is converted.
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->string('notes', 2000)->nullable();
            $table->timestamps();
        });

        Schema::create('lead_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->dateTime('happened_at');
            $table->string('summary', 1000);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('number', 20)->unique();
            $table->foreignId('contact_id')->constrained()->restrictOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->date('issue_date');
            $table->date('valid_until');
            $table->string('status', 20)->index();
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->string('notes', 1000)->nullable();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('quotation_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description', 255);
            $table->decimal('quantity', 12, 2);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('amount', 14, 2);
            $table->unsignedSmallInteger('sort')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_lines');
        Schema::dropIfExists('quotations');
        Schema::dropIfExists('lead_activities');
        Schema::dropIfExists('leads');
    }
};
