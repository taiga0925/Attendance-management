<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    /**
     * @return view ビュー
     * 勤怠一覧画面
     */
    public function index(Request $request)
    {

        Carbon::setLocale('ja');

        // URLから日付を取得、なければ今日の日付を使用
        $date = $request->input('date') ? Carbon::parse($request->input('date')) : Carbon::today();


        // 勤怠情報を取得
        $attendances = Attendance::with('user', 'userBreaks')
            ->whereHas('user', function ($query) {
                $query->where('role', User::ROLE_GENERAL); // 一般ユーザー
            })
            ->whereDate('date', $date)
            ->get();

        // 休憩時間と合計勤務時間を計算
        foreach ($attendances as $attendance) {

            $totalBreakTimeInSeconds = 0;
            // 休憩時間の合計を秒単位で計算
            foreach ($attendance->userBreaks as $userBreak) {
                if ($userBreak->break_start && $userBreak->break_end) {
                    $totalBreakTimeInSeconds += $userBreak->break_end->diffInSeconds($userBreak->break_start);
                }
            }
            // 秒を時間と分に変換し、合計休憩時間をH:i形式で表示
            $breakInterval = CarbonInterval::seconds($totalBreakTimeInSeconds)->cascade();
            $attendance->totalBreakTime = sprintf('%02d:%02d', $breakInterval->h, $breakInterval->i);

            $totalWorkTimeInSeconds = 0;
            // 合計勤務時間を計算
            if ($attendance->clock_in && $attendance->clock_out) {
                $totalWorkTimeInSeconds = $attendance->clock_out->diffInSeconds($attendance->clock_in) - $totalBreakTimeInSeconds;
            }
            // 秒を時間と分に変換し、合計勤務時間をH:i形式で表示
            $workInterval = CarbonInterval::seconds($totalWorkTimeInSeconds)->cascade();
            $attendance->totalWorkTime = sprintf('%02d:%02d', $workInterval->h, $workInterval->i);
        }

        return view('admin.attendances.index', compact('attendances', 'date'));
    }
}
