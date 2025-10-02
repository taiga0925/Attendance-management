<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use Carbon\Carbon;

class AttendanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        // テスト用の勤怠データの、基本的なデフォルト値を定義します
        return array(
            'user_id' => User::factory(), // 関連するユーザーを自動で作成
            'date' => $this->faker->dateTimeThisMonth(),
            'clock_in' => function (array $attributes) {
                // 'date'で設定された日付に、ランダムな時刻を設定
                return Carbon::parse($attributes['date'])->setTime(rand(8, 9), rand(0, 59));
            },
            'clock_out' => function (array $attributes) {
                // 'clock_in'の時刻から8時間〜9時間後を退勤時間とする
                return Carbon::parse($attributes['clock_in'])->addHours(8)->addMinutes(rand(0, 60));
            },
        );
    }
}
