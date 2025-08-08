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
        Schema::create('stok_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('roti_id')->constrained('rotis')->onDelete('cascade');
            $table->integer('stok');
            $table->integer('stok_awal');
            $table->date('tanggal');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stok_history');
    }
};
