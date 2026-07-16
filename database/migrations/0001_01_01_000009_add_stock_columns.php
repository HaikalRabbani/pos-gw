<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Outlets: stock mode selection
        Schema::table('outlets', function (Blueprint $table) {
            $table->string('stock_mode', 20)->default('product')->after('is_active');
            // 'product' = stock di-set per produk
            // 'ingredient' = stock di-set per ingredient (BOM)
        });

        // Products: stock for product mode
        Schema::table('products', function (Blueprint $table) {
            $table->integer('stock')->default(0)->after('cost');
            $table->integer('min_stock')->nullable()->after('stock');
        });

        // Ingredients: stock for ingredient mode
        Schema::table('ingredients', function (Blueprint $table) {
            $table->integer('stock')->default(0)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('outlets', function (Blueprint $table) {
            $table->dropColumn('stock_mode');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['stock', 'min_stock']);
        });

        Schema::table('ingredients', function (Blueprint $table) {
            $table->dropColumn('stock');
        });
    }
};
