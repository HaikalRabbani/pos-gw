<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ========================
        // OUTLET BALANCES
        // ========================
        Schema::create('outlet_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')->constrained()->cascadeOnDelete();
            $table->bigInteger('balance')->default(0); // saldo saat ini (dalam cents)
            $table->bigInteger('total_withdrawn')->default(0);
            $table->timestamps();

            $table->unique('outlet_id');
        });

        // ========================
        // BALANCE TRANSACTIONS (ledger)
        // ========================
        Schema::create('balance_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30); // qris_payment, withdrawal, adjustment
            $table->bigInteger('amount'); // positif = masuk, negatif = keluar
            $table->string('description')->nullable();
            $table->string('reference_type')->nullable(); // order, withdrawal_request
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->timestamps();

            $table->index(['outlet_id', 'created_at']);
        });

        // ========================
        // WITHDRAWAL REQUESTS
        // ========================
        Schema::create('withdrawal_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->bigInteger('amount');
            $table->string('bank_name', 100);
            $table->string('bank_account', 50);
            $table->string('account_holder', 200);
            $table->string('status', 30)->default('pending'); // pending, processing, completed, failed
            $table->string('xendit_ref')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['outlet_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawal_requests');
        Schema::dropIfExists('balance_transactions');
        Schema::dropIfExists('outlet_balances');
    }
};
