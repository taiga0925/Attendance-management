<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use App\Models\UserBreak;
use Carbon\Carbon;
use Carbon\CarbonInterval;

class AttendanceListController extends Controller
{
    /**
     * 一般ユーザーの勤怠一覧画面を表示する (PG04)
     */
    public function index(Request $request)
    {
        // Carbonのロケールを日本語に設定
        Carbon::setLocale('ja');

        // URLパラメータから年月を取得、なければ現在年月を使用
        $year = $request->input('year')? $request->input('year') : Carbon::now()->year;
        $month = $request->input('month')? $request->input('month') : Carbon::now()->month;

        $currentMonth = Carbon::create($year, $month, 1);
        $previousMonth = $currentMonth->copy()->subMonth();
        $nextMonth = $currentMonth->copy()->addMonth();

        $user = Auth::user();

        // ログインユーザーの指定された月の勤怠データを取得
        // with('userBreaks') で休憩データをEager Loading
        $attendances = Attendance::with('userBreaks')
                                ->where('user_id', $user->id)
                                ->whereMonth('date', $month)
                                ->whereYear('date', $year)
                                ->orderBy('date', 'asc')
                                ->get()
                                ->keyBy(function ($item) {
                                    return Carbon::parse($item->date)->day;
                                });

        $daysInMonth = $currentMonth->daysInMonth; // 当月の日数を取得
        $attendancesData =; // 修正点: 空の配列として初期化

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = $currentMonth->copy()->day($day);
            $attendance = $attendances->get($day);

            // 勤怠データがない日のためのデフォルト値
            $formattedAttendance =;

            if ($attendance) {
                // 休憩時間の合計を秒単位で計算
                $totalBreakTimeInSeconds = $attendance->userBreaks->sum(function ($userBreak) {
                    if ($userBreak->break_start && $userBreak->break_end) {
                        return $userBreak->break_end->diffInSeconds($userBreak->break_start);
                    }
                    return 0;
                });

                // 合計勤務時間を計算
                $totalWorkTimeInSeconds = 0;
                if ($attendance->clock_in && $attendance->clock_out) {
                    $totalWorkTimeInSeconds = $attendance->clock_out->diffInSeconds($attendance->clock_in) - $totalBreakTimeInSeconds;
                }

                // 勤怠データをH:i形式でフォーマット
                $breakInterval = CarbonInterval::seconds($totalBreakTimeInSeconds)->cascade();
                $workInterval = CarbonInterval::seconds($totalWorkTimeInSeconds)->cascade();

                $formattedAttendance['id'] = $attendance->id;
                $formattedAttendance['clock_in'] = $attendance->clock_in?->format('H:i:s');
                $formattedAttendance['clock_out'] = $attendance->clock_out?->format('H:i:s');

                // 修正点: totalBreakTimeとtotalWorkTimeを配列のキーとして格納
                $formattedAttendance = sprintf('%02d:%02d', $breakInterval->h, $breakInterval->i);
                $formattedAttendance = sprintf('%02d:%02d', $workInterval->h, $workInterval->i);
            }

            $attendancesData[$day] = $formattedAttendance;
        }

        return view('attendance.list', compact('attendancesData', 'currentMonth', 'previousMonth', 'nextMonth'));
    }
}
