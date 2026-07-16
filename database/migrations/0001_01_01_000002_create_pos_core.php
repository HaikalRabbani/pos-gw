<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ========================
        // OUTLETS — FK tenant_id → tenants (File #1)
        // ========================
        Schema::create('outlets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 200);
            $table->text('address')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('token_public', 64)->nullable()->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ========================
        // USER_OUTLETS (pivot)
        // ========================
        Schema::create('user_outlets', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('outlet_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20); // developer, owner, manager, cashier
            $table->primary(['user_id', 'outlet_id']);
        });

        // ========================
        // CATEGORIES
        // ========================
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('outlet_id');
        });

        // ========================
        // STATIONS (dapur/bar/grill)
        // ========================
        Schema::create('stations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('outlet_id');
        });

        // ========================
        // PRODUCTS — all columns (cost, description, station_id)
        // ========================
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('station_id')->nullable()->constrained('stations')->nullOnDelete();
            $table->string('name', 200);
            $table->bigInteger('price'); // in cents
            $table->bigInteger('cost')->default(0); // base cost in cents
            $table->string('image')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('outlet_id');
            $table->index('category_id');
            $table->index('station_id');
        });

        // ========================
        // TAXES — with sort_order
        // ========================
        Schema::create('taxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->decimal('rate', 5, 2); // percentage
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ========================
        // DISCOUNTS — all complex fields (target, min_purchase, BOGO, etc.)
        // ========================
        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('type', 20); // percent, nominal
            $table->bigInteger('value');
            $table->string('target_type', 30)->default('transaction'); // product, category, transaction
            $table->foreignId('target_id')->nullable();
            $table->bigInteger('min_purchase')->nullable(); // in cents
            $table->bigInteger('max_discount')->nullable(); // in cents
            $table->unsignedSmallInteger('buy_x')->nullable(); // buy X get Y
            $table->unsignedSmallInteger('buy_y')->nullable();
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ========================
        // TABLES (resto tables with QR)
        // ========================
        Schema::create('tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')->constrained()->cascadeOnDelete();
            $table->string('name', 50);
            $table->string('qr_token', 64)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tables');
        Schema::dropIfExists('discounts');
        Schema::dropIfExists('taxes');
        Schema::dropIfExists('products');
        Schema::dropIfExists('stations');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('user_outlets');
        Schema::dropIfExists('outlets');
    }
};
