<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $param = [
            'name' => 'admin',
            'email' => 'admin@test.com',
            'email_verified_at' => now(),
            'password' => Hash::make('admintest1'),
            'role' => 'admin',
        ];
        DB::table('users')->insert($param);

        $param = [
            'name' => 'master',
            'email' => 'master@test.com',
            'email_verified_at' => now(),
            'password' => Hash::make('admintest2'), 
            'role' => 'admin',
        ];
        DB::table('users')->insert($param);
    }
}
