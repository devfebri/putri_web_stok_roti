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
        Schema::table('roti_pos', function (Blueprint $table) {
            $table->unsignedBigInteger('frontliner_id')->nullable();
            $table->foreign('frontliner_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roti_pos', function (Blueprint $table) {
            $table->dropForeign(['frontliner_id']);
            $table->dropColumn('frontliner_id');
        });
    }
};
