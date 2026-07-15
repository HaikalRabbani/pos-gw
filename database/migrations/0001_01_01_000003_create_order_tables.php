<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ========================
        // PRODUCT VARIANTS
        // ========================
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->bigInteger('price_extra')->default(0);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
        });

        // ========================
        // ORDERS — with bill_group_id + refund fields
        // ========================
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('table_id')->nullable()->constrained('tables')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_name')->nullable();
            $table->string('status', 20)->default('draft');
            $table->bigInteger('subtotal')->default(0);
            $table->bigInteger('discount_total')->default(0);
            $table->bigInteger('tax_total')->default(0);
            $table->bigInteger('grand_total')->default(0);
            $table->string('payment_status', 20)->default('pending');
            $table->string('payment_method', 20)->nullable();
            $table->text('notes')->nullable();
            $table->string('bill_group_id')->nullable()->index();
            // Refund fields
            $table->string('refund_status', 20)->nullable();
            $table->text('refund_note')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->foreignId('refunded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('outlet_id');
            $table->index('status');
            $table->index('payment_status');
        });

        // ========================
        // ORDER DISCOUNT (pivot)
        // ========================
        Schema::create('order_discount', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('discount_id')->nullable()->constrained()->nullOnDelete();
            $table->string('discount_name');
            $table->string('discount_type', 20); // percent, nominal
            $table->bigInteger('discount_value');
            $table->bigInteger('discount_amount');
            $table->timestamps();
        });

        // ========================
        // ORDER ITEMS — with unit_cost + refunded_qty
        // ========================
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->string('product_name', 200);
            $table->string('variant_name', 100)->nullable();
            $table->integer('qty');
            $table->integer('refunded_qty')->default(0);
            $table->bigInteger('unit_price');
            $table->bigInteger('unit_cost')->default(0);
            $table->bigInteger('total_price');
            $table->text('notes')->nullable();
        });

        // ========================
        // PAYMENTS — with refunded_amount + bill_group_id
        // ========================
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('method', 20); // cash, qris, midtrans
            $table->bigInteger('amount');
            $table->bigInteger('refunded_amount')->default(0);
            $table->string('midtrans_ref')->nullable();
            $table->string('midtrans_status', 50)->nullable();
            $table->string('bill_group_id')->nullable()->index();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        // ========================
        // ORDER LOGS (audit trail)
        // ========================
        Schema::create('order_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('order_id');
        });

        // ========================
        // SHIFTS (cash reconciliation)
        // ========================
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('outlet_id')->constrained()->cascadeOnDelete();
            $table->timestamp('start_at');
            $table->timestamp('end_at')->nullable();
            $table->bigInteger('cash_begin')->default(0);
            $table->bigInteger('cash_expected')->nullable();
            $table->bigInteger('cash_actual')->nullable();
            $table->bigInteger('cash_diff')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('outlet_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
        Schema::dropIfExists('order_logs');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('order_discount');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('product_variants');
    }
};
