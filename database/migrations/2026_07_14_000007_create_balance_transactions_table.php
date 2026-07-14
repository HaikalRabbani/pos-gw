<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
    }

    public function down(): void
    {
        Schema::dropIfExists('balance_transactions');
    }
};
