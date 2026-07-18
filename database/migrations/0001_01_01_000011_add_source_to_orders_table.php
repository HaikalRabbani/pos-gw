<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menandai asal order: dibuat staff (Flutter cashier app / admin) atau
     * dibuat sendiri oleh customer lewat self-order (scan QR meja).
     * Berguna buat laporan, filtering, dan validasi keamanan endpoint publik
     * (customer cuma boleh akses order dengan source = self_order miliknya).
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('source', 20)->default('staff')->after('table_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
