<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outlet_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')->constrained()->cascadeOnDelete();
            $table->bigInteger('balance')->default(0); // saldo saat ini (dalam cents)
            $table->bigInteger('total_withdrawn')->default(0); // total yg udah di-withdraw
            $table->timestamps();

            $table->unique('outlet_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outlet_balances');
    }
};
