<?php
// nama file: ..._update_status_enum_in_orders_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Ubah tipe kolom enum untuk menambahkan 'in_progress'
            $table->enum('status', [
                'pending',
                'processed',
                'assigned',
                'in_progress', // <-- Status baru kita
                'completed',
                'cancelled'
            ])->default('pending')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Logika untuk mengembalikan ke kondisi semula jika di-rollback
            $table->enum('status', [
                'pending',
                'processed',
                'assigned',
                'completed',
                'cancelled'
            ])->default('pending')->change();
        });
    }
};