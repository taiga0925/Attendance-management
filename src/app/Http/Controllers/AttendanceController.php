<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * @return view ビュー
     * 勤怠打刻画面
     */
    public function index()
    {
        $user = Auth::user();
        $today = Carbon::today();
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        $status = '勤務外';

        if ($attendance) {
            $latestBreak = $attendance->userBreaks()->latest('break_start')->first();
            if ($attendance->clock_out) {
                $status = '退勤済';
            } elseif ($latestBreak && !$latestBreak->break_end) {
                $status = '休憩中';
            } elseif ($attendance->clock_in) {
                $status = '出勤中';
            }
        }

        $message = session('status_message');
        $error = session('error');
        $current_date_time = Carbon::now();

        return view('attendance.index', compact('status', 'message', 'error', 'current_date_time'));
    }

    /**
     * @return redirect リダイレクト
     * 打刻処理
     */
    public function clockIn()
    {
        if (Auth::user()->clockIn()) {
            return redirect()->back()->with('status_message', '出勤しました');
        }
        return redirect()->back()->with('error', '本日は既に出勤済みです。');
    }

    /**
     * @return redirect リダイレクト
     * 退勤処理
     */
    public function clockOut()
    {
        [$success, $message] = Auth::user()->clockOut();
        if ($success) {
            return redirect()->back()->with('status_message', $message);
        }
        return redirect()->back()->with('error', $message);
    }

    /**
     * @return redirect リダイレクト
     * 休憩開始処理
     */
    public function startBreak()
    {
        [$success, $message] = Auth::user()->startBreak();
        if ($success) {
            return redirect()->back()->with('status_message', $message);
        }
        return redirect()->back()->with('error', $message);
    }

    /**
     * @return redirect リダイレクト
     * 休憩終了処理
     */
    public function endBreak()
    {
        [$success, $message] = Auth::user()->endBreak();
        if ($success) {
            return redirect()->back()->with('status_message', $message);
        }
        return redirect()->back()->with('error', $message);
    }
}
