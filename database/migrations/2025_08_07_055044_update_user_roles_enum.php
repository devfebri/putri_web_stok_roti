<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->change(); // Change to string first
        });
        
        // Then update to new enum
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('pimpinan', 'kepalabakery', 'kepalatokokios', 'admin', 'frontliner', 'kasir', 'koki') DEFAULT 'kepalatokokios'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('pimpinan', 'kepalabakery', 'kepalatokokios', 'admin', 'frontliner') DEFAULT 'kepalatokokios'");
        });
    }
};
