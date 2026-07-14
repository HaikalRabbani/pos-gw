<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('discounts', function (Blueprint $table) {
            $table->string('target_type', 30)->default('transaction')->after('value'); // product, category, transaction
            $table->foreignId('target_id')->nullable()->after('target_type');
            $table->bigInteger('min_purchase')->nullable()->after('target_id'); // in cents
            $table->bigInteger('max_discount')->nullable()->after('min_purchase'); // in cents
            $table->unsignedSmallInteger('buy_x')->nullable()->after('max_discount'); // buy X
            $table->unsignedSmallInteger('buy_y')->nullable()->after('buy_x'); // get Y free
            $table->dateTime('start_date')->nullable()->after('buy_y');
            $table->dateTime('end_date')->nullable()->after('start_date');
        });
    }

    public function down(): void
    {
        Schema::table('discounts', function (Blueprint $table) {
            $table->dropColumn([
                'target_type', 'target_id', 'min_purchase',
                'max_discount', 'buy_x', 'buy_y',
                'start_date', 'end_date',
            ]);
        });
    }
};
