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
        Schema::table('transaksi', function (Blueprint $table) {
            // Check and drop foreign key constraints first
            $foreignKeys = \Illuminate\Support\Facades\DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'transaksi' 
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ");
            
            foreach ($foreignKeys as $fk) {
                if (str_contains($fk->CONSTRAINT_NAME, 'roti_id')) {
                    \Illuminate\Support\Facades\DB::statement("ALTER TABLE transaksi DROP FOREIGN KEY {$fk->CONSTRAINT_NAME}");
                }
                if (str_contains($fk->CONSTRAINT_NAME, 'stok_history_id')) {
                    \Illuminate\Support\Facades\DB::statement("ALTER TABLE transaksi DROP FOREIGN KEY {$fk->CONSTRAINT_NAME}");
                }
            }
            
            // Now drop the columns
            if (Schema::hasColumn('transaksi', 'roti_id')) {
                $table->dropColumn('roti_id');
            }
            if (Schema::hasColumn('transaksi', 'jumlah')) {
                $table->dropColumn('jumlah');
            }
            if (Schema::hasColumn('transaksi', 'harga_satuan')) {
                $table->dropColumn('harga_satuan');
            }
            if (Schema::hasColumn('transaksi', 'stok_history_id')) {
                $table->dropColumn('stok_history_id');
            }
            
            // Make nama_customer nullable
            $table->string('nama_customer')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaksi', function (Blueprint $table) {
            // Restore the dropped columns
            $table->unsignedBigInteger('roti_id')->nullable();
            $table->integer('jumlah')->nullable();
            $table->decimal('harga_satuan', 10, 2)->nullable();
            $table->unsignedBigInteger('stok_history_id')->nullable();
            
            // Make nama_customer not nullable again
            $table->string('nama_customer')->nullable(false)->change();
        });
    }
};
