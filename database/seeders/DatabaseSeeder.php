<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Admin;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();

         User::factory()->create([
             'name' => '高木　太郎',
             'email' => 'zuotao.peng@aispel.com',
             'password' => Hash::make('12345678'),
         ]);

//        $admin = new Admin();
//        $admin->name = '管理　太郎';
//        $admin->email = 'info@aispel.com';
//        $admin->password = Hash::make('12345678');
//        $admin->save();

    }
}
