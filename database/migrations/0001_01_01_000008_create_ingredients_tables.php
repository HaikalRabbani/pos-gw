<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ========================
        // INGREDIENTS — master data bahan & add-on (outlet-scoped)
        // ========================
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('outlet_id');
        });

        // ========================
        // PRODUCT_INGREDIENTS — pivot: produk → bahan/add-on
        // is_removable : true → customer bisa menonaktifkan bahan ini
        // extra_price   : > 0 → add-on berbayar (topping)
        // is_default    : true → aktif secara default (bahan bawaan)
        // ========================
        Schema::create('product_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_removable')->default(true);
            $table->bigInteger('extra_price')->default(0); // in cents
            $table->boolean('is_default')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'ingredient_id']);
            $table->index('product_id');
            $table->index('ingredient_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_ingredients');
        Schema::dropIfExists('ingredients');
    }
};
