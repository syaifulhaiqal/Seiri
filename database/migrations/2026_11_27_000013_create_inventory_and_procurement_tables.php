<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Products with stock movements, and purchasing: requests → approval → orders → receiving → bill.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 50)->unique();
            $table->string('name', 150);
            $table->string('unit', 20)->default('pcs');
            $table->decimal('cost_price', 12, 2)->default(0);
            $table->decimal('sale_price', 12, 2)->default(0);
            $table->decimal('reorder_level', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('description', 1000)->nullable();
            $table->timestamps();
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            // Positive is stock in, negative is stock out.
            $table->decimal('quantity', 12, 2);
            $table->decimal('unit_cost', 12, 2)->nullable();
            $table->string('type', 20);
            $table->nullableMorphs('source');
            $table->string('note', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['product_id', 'date']);
        });

        Schema::create('purchase_requests', function (Blueprint $table) {
            $table->id();
            $table->string('number', 20)->unique();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('needed_by')->nullable();
            $table->string('purpose', 500);
            $table->string('status', 20)->index();
            $table->decimal('total', 14, 2)->default(0);
            $table->string('review_note', 500)->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('number', 20)->unique();
            $table->foreignId('contact_id')->constrained()->restrictOnDelete();
            $table->foreignId('purchase_request_id')->nullable()->constrained()->nullOnDelete();
            $table->date('order_date');
            $table->date('expected_date')->nullable();
            $table->string('status', 20)->index();
            $table->decimal('total', 14, 2)->default(0);
            $table->string('notes', 1000)->nullable();
            $table->foreignId('bill_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
        });

        foreach (['purchase_request', 'purchase_order'] as $document) {
            Schema::create("{$document}_lines", function (Blueprint $table) use ($document) {
                $table->id();
                $table->foreignId("{$document}_id")->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
                $table->string('description', 255);
                $table->decimal('quantity', 12, 2);
                $table->decimal('unit_price', 12, 2);
                $table->decimal('amount', 14, 2);
                $table->unsignedSmallInteger('sort')->default(0);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_lines');
        Schema::dropIfExists('purchase_request_lines');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('purchase_requests');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('products');
    }
};
