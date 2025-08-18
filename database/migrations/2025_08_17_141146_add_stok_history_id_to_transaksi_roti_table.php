<?php

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
        Schema::table('transaksi_roti', function (Blueprint $table) {
            $table->unsignedBigInteger('stok_history_id')->nullable()->after('roti_id');
            $table->foreign('stok_history_id')->references('id')->on('stok_history')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaksi_roti', function (Blueprint $table) {
            $table->dropForeign(['stok_history_id']);
            $table->dropColumn('stok_history_id');
        });
    }
};
