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
        Schema::create('transaksi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('roti_id')->constrained('rotis')->onDelete('cascade');
            $table->foreignId('stok_history_id');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('kode_transaksi')->nullable();
            $table->string('nama_customer')->nullable();
            $table->integer('jumlah');
            $table->decimal('harga_satuan', 10, 2);
            $table->string('total_harga', 20);
            $table->string('metode_pembayaran', 50);
            $table->timestamp('tanggal_transaksi');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksi');
    }
};
