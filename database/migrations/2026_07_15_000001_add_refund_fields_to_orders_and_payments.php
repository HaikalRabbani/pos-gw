<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('refund_status', 20)->nullable()->after('payment_method');
            $table->text('refund_note')->nullable()->after('refund_status');
            $table->timestamp('refunded_at')->nullable()->after('refund_note');
            $table->foreignId('refunded_by')->nullable()->constrained('users')->nullOnDelete()->after('refunded_at');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->bigInteger('refunded_amount')->default(0)->after('amount');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->integer('refunded_qty')->default(0)->after('qty');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['refunded_by']);
            $table->dropColumn(['refund_status', 'refund_note', 'refunded_at', 'refunded_by']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('refunded_amount');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('refunded_qty');
        });
    }
};
