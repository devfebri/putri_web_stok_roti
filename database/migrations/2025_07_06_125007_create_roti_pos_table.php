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
        Schema::create('roti_pos', function (Blueprint $table) {
            $table->id();
            $table->integer('roti_id');
            $table->integer('user_id');
            $table->string('kode_po',50);
            $table->integer('jumlah_po');
            $table->text('deskripsi');
            $table->date('tanggal_order');
            $table->tinyInteger('status')->default(0)->comment('0.proses, 1.selesai, 2.batal');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roti_pos');
    }
};
