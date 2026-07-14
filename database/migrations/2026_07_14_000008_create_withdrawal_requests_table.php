<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withdrawal_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // siapa yg minta
            $table->bigInteger('amount'); // jumlah withdraw (dalam cents)
            $table->string('bank_name', 100); // BCA, Mandiri, dll
            $table->string('bank_account', 50); // no rekening
            $table->string('account_holder', 200); // atas nama
            $table->string('status', 30)->default('pending'); // pending, processing, completed, failed
            $table->string('xendit_ref')->nullable(); // referensi dari Xendit payout
            $table->text('note')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['outlet_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawal_requests');
    }
};
