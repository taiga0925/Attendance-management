<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use App\Models\UserBreak;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * 勤怠打刻画面を表示
     * @return view ビュー
     */
    public function index()
    {

        $user = Auth::user();
        $today = Carbon::today();

        // 今日の勤怠記録を取得
        $attendance = Attendance::where('user_id', $user->id)
                                ->whereDate('date', $today)
                                ->first();

        $status = '勤務外'; // デフォルトステータス
        $message = session('status_message');
        $error = session('error');

        if ($attendance) {
            if ($attendance->clock_out) {
                $status = '退勤済';
            } elseif ($attendance->clock_in) {
                // 最後の休憩記録を取得、休憩中かどうか判定
                $latestBreak = $attendance->userBreaks()->latest('break_start')->first();
                if ($latestBreak &&!$latestBreak->break_end) {
                    $status = '休憩中';
                } else {
                    $status = '出勤中';
                }
            }
        }

        $current_date_time = Carbon::now(); // 現在の日付と時刻を取得

        return view('attendance.index', compact('status', 'message', 'error', 'current_date_time')); 
    }

    /**
     * @return redirect リダイレクト
     * 出勤打刻処理
     */
    public function clockIn(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today();

        // 出勤記録が既に存在しないかチェック
        if (Attendance::where('user_id', $user->id)->whereDate('date', $today)->exists()) {
            return redirect()->back()->with('error', '本日は既に出勤済みです。');
        }

        Attendance::create([
            'user_id' => $user->id,
            'date' => $today,
            'clock_in' => Carbon::now(),
        ]);

        return redirect()->back()->with('status_message', '出勤しました');
    }

    /**
     * 休憩開始打刻処理
     * @return redirect リダイレクト
     */
    public function startBreak(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today();

        $attendance = Attendance::where('user_id', $user->id)
                                ->whereDate('date', $today)
                                ->first();

        // 出勤していないか、既に退勤済みかチェック
        if (!$attendance || $attendance->clock_out) {
            return redirect()->back()->with('error', '出勤していないか、既に退勤済みです。');
        }

        // 既に休憩中ではないかチェック
        $latestBreak = $attendance->userBreaks()->latest('break_start')->first();
        if ($latestBreak &&!$latestBreak->break_end) {
            return redirect()->back()->with('error', '既に休憩中です。');
        }

        UserBreak::create([
            'attendance_id' => $attendance->id,
            'break_start' => Carbon::now(),
        ]);

        return redirect()->back()->with('status_message', '休憩を開始しました。');
    }

    /**
     * @return redirect リダイレクト
     * 休憩終了打刻を処理する
     */
    public function endBreak(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today();

        $attendance = Attendance::where('user_id', $user->id)
                                ->whereDate('date', $today)
                                ->first();

        // 出勤していないか、既に退勤済みかチェック
        if (!$attendance || $attendance->clock_out) {
            return redirect()->back()->with('error', '出勤していないか、既に退勤済みです。');
        }

        $latestBreak = $attendance->userBreaks()->latest('break_start')->first();

        // 休憩中ではないかチェック
        if (!$latestBreak || $latestBreak->break_end) {
            return redirect()->back()->with('error', '休憩中ではありません。');
        }

        $latestBreak->update([
            'break_end' => Carbon::now(),
        ]);

        return redirect()->back()->with('status_message', '休憩を終了しました。');
    }

    /**
     * @return redirect リダイレクト
     * 退勤打刻を処理する
     */
    public function clockOut(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today();

        $attendance = Attendance::where('user_id', $user->id)
                                ->whereDate('date', $today)
                                ->first();

        // 出勤していないか、既に退勤済みかチェック
        if (!$attendance || $attendance->clock_out) {
            return redirect()->back()->with('error', '既に退勤済みです。');
        }

        // 休憩中の場合は退勤できないようにする
        $latestBreak = $attendance->userBreaks()->latest('break_start')->first();
        if ($latestBreak &&!$latestBreak->break_end) {
            return redirect()->back()->with('error', '休憩中は退勤できません。休憩を終了してください。');
        }

        $attendance->update([
            'clock_out' => Carbon::now(),
        ]);

        return redirect()->back()->with('status_message', 'お疲れ様でした');
    }
}
