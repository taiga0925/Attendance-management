<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceListController extends Controller
{
    /**
     * @return view ビュー
     * 一般ユーザーの勤怠一覧画面
     */
    public function index(Request $request)
    {
        Carbon::setLocale('ja');

        // inputに値がなければ現在の日時をデフォルト値とする
        $year = $request->input('year', Carbon::now()->year);
        $month = $request->input('month', Carbon::now()->month);

        $currentMonth = Carbon::create($year, $month, 1);
        $previousMonth = $currentMonth->copy()->subMonth();
        $nextMonth = $currentMonth->copy()->addMonth();

        $user = Auth::user();

        // Eager LoadingでN+1問題を回避、日付でアクセスしやすいようにkeyByで変換
        $attendances = Attendance::with('userBreaks')
            ->where('user_id', $user->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->orderBy('date', 'asc')
            ->get()
            ->keyBy(function ($item) {
                return Carbon::parse($item->date)->day;
            });

        $daysInMonth = $currentMonth->daysInMonth;

        $attendancesData = array();

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = $currentMonth->copy()->day($day);
            $attendance = $attendances->get($day);

            if ($attendance) {
                // 勤怠データがある日は、モデルオブジェクトをそのまま格納
                $attendancesData[$day] = $attendance;
            } else {
                // 勤怠データがない日は、nullを格納
                $attendancesData[$day] = null;
            }
        }

        return view('attendance.list', compact('attendancesData', 'currentMonth', 'previousMonth', 'nextMonth'));
    }
}
