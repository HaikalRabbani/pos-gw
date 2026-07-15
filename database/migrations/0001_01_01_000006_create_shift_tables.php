<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Master shift types
        Schema::create('shift_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')->constrained()->cascadeOnDelete();
            $table->string('name', 50); // Pagi, Siang, Malam
            $table->time('start_time'); // 07:00
            $table->time('end_time');   // 15:00
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('outlet_id');
        });

        // Employee shift schedules
        Schema::create('shift_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shift_type_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('status', 20)->default('scheduled'); // scheduled, confirmed, absent
            $table->timestamps();

            $table->unique(['user_id', 'shift_type_id', 'date']);
            $table->index(['outlet_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_schedules');
        Schema::dropIfExists('shift_types');
    }
};
