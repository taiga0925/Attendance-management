<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class StaffAttendanceController extends Controller
{
    /**
     * @return view ビュー
     * 特定スタッフの月次勤怠一覧を表示
     */
    public function index(Request $request, User $user)
    {
        Carbon::setLocale('ja');

        $year = $request->input('year', Carbon::now()->year);
        $month = $request->input('month', Carbon::now()->month);

        $currentMonth = Carbon::create($year, $month, 1);
        $previousMonth = $currentMonth->copy()->subMonth();
        $nextMonth = $currentMonth->copy()->addMonth();

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
            $attendancesData[$day] = $attendances->get($day);
        }

        return view('admin.staff_attendances.list', compact('user', 'attendancesData', 'currentMonth', 'previousMonth', 'nextMonth'));
    }

    /**
     * 特定スタッフの月次勤怠をCSV形式で出力
     */
    public function exportCsv(Request $request, User $user)
    {
        $year = $request->input('year', Carbon::now()->year);
        $month = $request->input('month', Carbon::now()->month);

        $fileName = sprintf('%s_%d%02d_attendance.csv', $user->name, $year, $month);
        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $callback = function() use ($user, $year, $month) {
            $file = fopen('php://output', 'w');

            // ヘッダー行を書き込み
            $header = ['日付', '出勤', '退勤', '休憩時間', '合計勤務時間'];
            // 日本語の文字化けを防ぐためにBOMを先頭に追加
            fwrite($file, "\xEF\xBB\xBF");
            fputcsv($file, $header);

            $attendances = Attendance::with('userBreaks')
                ->where('user_id', $user->id)
                ->whereYear('date', $year)
                ->whereMonth('date', $month)
                ->orderBy('date', 'asc')
                ->get();

            foreach ($attendances as $attendance) {
                $row = array(
                    $attendance->date->format('Y-m-d'),
                    $attendance->clock_in?->format('H:i:s'),
                    $attendance->clock_out?->format('H:i:s'),
                    $attendance->total_break_time,
                    $attendance->total_work_time
                );

                // データ行を書き込み
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
