<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    /**
     * 管理者の勤怠一覧画面を表示する (PG08)
     */
    public function index(Request $request)
    {
        // Carbonのロケールを日本語に設定 [1]
        Carbon::setLocale('ja');

        // URLパラメータから日付を取得、なければ今日の日付を使用
        $date = $request->input('date') ? Carbon::parse($request->input('date')) : Carbon::now();

        // 勤怠情報を取得
        // 出勤時刻がその日の全一般ユーザーの情報を取得
        $attendances = Attendance::with('user', 'userBreaks')
            ->whereHas('user', function ($query) {
                $query->where('role', User::ROLE_GENERAL);
            })
            ->whereDate('date', $date)
            ->get();

        // 各勤怠記録について、休憩時間と合計勤務時間を計算
        foreach ($attendances as $attendance) {
            $totalBreakTime = 0;
            foreach ($attendance->userBreaks as $userBreak) {
                if ($userBreak->break_start && $userBreak->break_end) {
                    $totalBreakTime += $userBreak->break_end->diffInSeconds($userBreak->break_start);
                }
            }
            $attendance->totalBreakTime = Carbon::createFromTimestamp($totalBreakTime)->format('H:i:s');

            $totalWorkTime = 0;
            if ($attendance->clock_in && $attendance->clock_out) {
                $totalWorkTime = $attendance->clock_out->diffInSeconds($attendance->clock_in) - $totalBreakTime;
            }
            $attendance->totalWorkTime = Carbon::createFromTimestamp($totalWorkTime)->format('H:i:s');
        }

        return view('admin.attendances.index', compact('attendances', 'date'));
    }

}
