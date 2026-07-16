<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('discounts', function (Blueprint $table) {
            $table->text('target_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Cannot reliably cast text back to bigInteger without data loss.
        // This is a one-way migration for safety.
    }
};
