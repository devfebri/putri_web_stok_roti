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
        Schema::table('wastes', function (Blueprint $table) {
            // Drop old rotipo_id column
            $table->dropColumn('rotipo_id');
            
            // Add stok_history_id foreign key
            $table->foreignId('stok_history_id')->after('kode_waste')->constrained('stok_history')->onDelete('cascade');
            
            // Add tanggal_expired for tracking expiration
            $table->date('tanggal_expired')->after('stok_history_id');
            
            // Remove jumlah_terjual as it's already tracked in stok_history
            $table->dropColumn('jumlah_terjual');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wastes', function (Blueprint $table) {
            // Restore rotipo_id column
            $table->integer('rotipo_id')->after('kode_waste');
            
            // Drop stok_history_id foreign key
            $table->dropForeign(['stok_history_id']);
            $table->dropColumn('stok_history_id');
            
            // Drop tanggal_expired
            $table->dropColumn('tanggal_expired');
            
            // Restore jumlah_terjual
            $table->integer('jumlah_terjual')->after('jumlah_waste');
        });
    }
};
