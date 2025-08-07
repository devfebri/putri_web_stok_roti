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
        $data = new User();
        $data->name = 'Admin';
        $data->email = 'admin@gmail.com';
        $data->username = 'admin';
        $data->role = 'admin';
        $data->password = bcrypt('password');
        $data->save();

        $data = new User();
        $data->name = 'Frontliner';
        $data->email = 'frontliner@gmail.com';
        $data->username = 'frontliner';
        $data->role = 'frontliner';
        $data->password = bcrypt('password');
        $data->save();

        $data = new User();
        $data->name = 'Kepala Toko Kios';
        $data->email = 'kepalatokokios@gmail.com';
        $data->username = 'kepalatokokios';
        $data->role = 'kepalatokokios';
        $data->password = bcrypt('password');
        $data->save();

        $data = new User();
        $data->name = 'Kepala Bakery';
        $data->email = 'kepalabakery@gmail.com';
        $data->username = 'kepalabakery';
        $data->role = 'kepalabakery';
        $data->password = bcrypt('password');
        $data->save();

        $data = new User();
        $data->name = 'Pimpinan';
        $data->email = 'pimpinan@gmail.com';
        $data->username = 'pimpinan';
        $data->role = 'pimpinan';
        $data->password = bcrypt('password');
        $data->save();
    }
}
