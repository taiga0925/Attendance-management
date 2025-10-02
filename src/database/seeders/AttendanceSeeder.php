<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use App\Models\UserBreak; 
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 'general'ロールを持つユーザーのみを取得
        $users = User::where('role', 'general')->get();

        // 9月の日付を取得
        $month = 9;
        $year = Carbon::now()->year; // 今年
        $startDate = Carbon::create($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();

        foreach ($users as $user) {
            for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                // 土日（週末）はスキップ
                if ($date->isWeekend()) {
                    continue;
                }

                // --- 勤怠時間の生成 ---
                $clockIn = $date->copy()->setTime(rand(8, 9), rand(0, 59), rand(0, 59));
                $totalBreakMinutes = rand(10, 90);
                $clockOut = $clockIn->copy()->addHours(8)->addMinutes($totalBreakMinutes);

                $attendance = Attendance::create([
                    'user_id' => $user->id,
                    'date' => $date,
                    'clock_in' => $clockIn,
                    'clock_out' => $clockOut,
                ]);

                // --- 休憩時間の生成 ---
                $remainingBreak = $totalBreakMinutes;
                $breakCount = rand(1, 3);

                for ($i = 0; $i < $breakCount; $i++) {
                    if ($remainingBreak < 10) continue;

                    $breakDuration = ($i == $breakCount - 1) ? $remainingBreak : rand(10, $remainingBreak);
                    $remainingBreak -= $breakDuration;

                    $breakStartRangeBegin = $clockIn->copy()->addHour()->timestamp;
                    $breakStartRangeEnd = $clockOut->copy()->subHour()->subMinutes($breakDuration)->timestamp;

                    if ($breakStartRangeBegin < $breakStartRangeEnd) {
                        $breakStart = Carbon::createFromTimestamp(rand($breakStartRangeBegin, $breakStartRangeEnd));
                        $breakEnd = $breakStart->copy()->addMinutes($breakDuration);

                        // 1. 新しいUserBreakモデルのインスタンスを作成
                        $newBreak = new UserBreak();
                        // 2. 必要な値を一つずつ設定
                        $newBreak->attendance_id = $attendance->id;
                        $newBreak->break_start = $breakStart;
                        $newBreak->break_end = $breakEnd;
                        // 3. データベースに保存
                        $newBreak->save();
                    }
                    if ($remainingBreak < 10) break;
                }
            }
        }
    }
}
