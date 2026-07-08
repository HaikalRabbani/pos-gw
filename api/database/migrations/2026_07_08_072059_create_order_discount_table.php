<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_discount', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('discount_id')->nullable()->constrained()->nullOnDelete();
            $table->string('discount_name');
            $table->string('discount_type', 20); // percent, nominal
            $table->bigInteger('discount_value');
            $table->bigInteger('discount_amount'); // calculated amount applied
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_discount');
    }
};
