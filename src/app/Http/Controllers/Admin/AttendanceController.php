<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use App\Models\UserBreak;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Http\Request;
use App\Http\Requests\Admin\UpdateAttendanceRequest;
use Illuminate\Support\Facades\DB;

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

    /**
     *
     * @return view ビュー
     * 勤怠詳細画面
     *
     */
    public function show(Attendance $attendance)
    {
        // 関連するユーザー情報と休憩情報を一緒に読み込む
        $attendance->load('user', 'userBreaks');

        return view('admin.attendances.detail', compact('attendance'));
    }

    /**
     *
     * @return redirect リダイレクト
     * 管理者による勤怠直接修正
     *
     */
    public function update(UpdateAttendanceRequest $request, Attendance $attendance)
    {
        $validatedData = $request->validated();

        DB::transaction(function () use ($attendance, $validatedData) {
            $date = $attendance->date->format('Y-m-d');

            // 出勤・退勤時刻を直接更新
            $attendance->update([
                'clock_in' => $date . ' ' . $validatedData['clock_in'],
                'clock_out' => $date. ' '. $validatedData['clock_out']
            ]);

            // 既存の休憩時間を更新
            if (isset($validatedData['breaks'])) {
                foreach ($validatedData['breaks'] as $breakId => $times) {
                    // 休憩開始・終了の両方が入力されている場合のみ更新
                    if (!empty($times['break_start']) &&!empty($times['break_end'])) {
                        UserBreak::find($breakId)->update([
                            'break_start' => $date. ' '. $times['break_start'],
                            'break_end'   => $date. ' '. $times['break_end']
                        ]);
                    }
                }
            }

            // 新規休憩を追加
            if (!empty($validatedData['new_breaks']['break_start']) &&!empty($validatedData['new_breaks']['break_end'])) {
                $newBreakData = array(
                    'break_start' => $date. ' '. $validatedData['new_breaks']['break_start'],
                    'break_end'   => $date. ' '. $validatedData['new_breaks']['break_end']
                );
                $attendance->userBreaks()->create($newBreakData);
            }

        });

        // 修正後は、元のスタッフの月次勤怠一覧画面に戻る
        return redirect()->route('admin.users.attendances', ['user' => $attendance->user_id])
                         ->with('status_message', '勤怠情報を更新しました。');
    }
}
