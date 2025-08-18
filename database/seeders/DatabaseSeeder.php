<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User seeding - using role enum that matches database schema
        User::create([
            'name' => 'Admin',
            'email' => 'admin@gmail.com',
            'username' => 'admin',
            'role' => 'admin',
            'password' => bcrypt('password'),
        ]);

        User::create([
            'name' => 'Kepala Bakery',
            'email' => 'kepalabakery@gmail.com',
            'username' => 'kepalabakery',
            'role' => 'kepalabakery',
            'password' => bcrypt('password'),
        ]);

        User::create([
            'name' => 'Pimpinan',
            'email' => 'pimpinan@gmail.com',
            'username' => 'pimpinan',
            'role' => 'pimpinan',
            'password' => bcrypt('password'),
        ]);

        User::create([
            'name' => 'RS Abdul Manap',
            'email' => 'rsabdulmanap@gmail.com',
            'username' => 'rsabdulmanap',
            'role' => 'kepalatokokios',
            'password' => bcrypt('password'),
        ]);

        User::create([
            'name' => 'Klinik Basmallah',
            'email' => 'klinikbasmallah@gmail.com',
            'username' => 'klinikbasmallah',
            'role' => 'kepalatokokios',
            'password' => bcrypt('password'),
        ]);

        User::create([
            'name' => 'Graha Utama',
            'email' => 'grahautama@gmail.com',
            'username' => 'grahautama',
            'role' => 'kepalatokokios',
            'password' => bcrypt('password'),
        ]);

        User::create([
            'name' => 'RS Sungai Kambang',
            'email' => 'rssungaikambang@gmail.com',
            'username' => 'rssungaikambang',
            'role' => 'kepalatokokios',
            'password' => bcrypt('password'),
        ]);

        // Add frontliner user
        User::create([
            'name' => 'front1',
            'email' => 'front1@gmail.com',
            'username' => 'front1',
            'role' => 'frontliner',
            'kepalatokokios_id' => 4, // RS Abdul Manap
            'password' => bcrypt('password'),
        ]);

        // Run seeders
        $this->call([
            RotiSeeder::class,
            // StokHistorySeeder::class,
        ]);
    }
}
