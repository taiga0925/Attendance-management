<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 管理者ユーザーの作成
        User::create([
            'name' => 'admin',
            'email' => 'admin@test.com',
            'email_verified_at' => now(),
            'password' => Hash::make('admintest1'),
            'role' => 'admin',
        ]);
        User::create([
            'name' => 'master',
            'email' => 'master@test.com',
            'email_verified_at' => now(),
            'password' => Hash::make('admintest2'),
            'role' => 'admin',
        ]);

        // 5名の一般ユーザーを、固定のパスワードで作成します
        User::factory()->count(5)->create([
            'role' => 'general',
            'password' => Hash::make('password'), // 全員のパスワードを 'password' に設定
        ]);
    }
}
