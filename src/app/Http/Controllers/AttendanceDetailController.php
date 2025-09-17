<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Attendance;
use App\Models\StampCorrectionRequest;

class AttendanceDetailController extends Controller
{
    /**
     * @return view ビュー
     * 勤怠詳細画面
     */
    public function show($id)
    {
        $attendance = Attendance::with('user', 'userBreaks', 'stampCorrectionRequest')->findOrFail($id);

        if ($attendance->user_id!== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        // 勤怠記録に「承認待ち(pending)」の修正申請が既にあるかを確認
        $isPending = $attendance->stampCorrectionRequest()->where('status', 'pending')->exists();

        return view('attendance.detail', compact('attendance', 'isPending'));
    }


    /**
     * 勤怠修正申請
     *
     * @param Request $request
     * @param int $id 勤怠記録のID
     * @return redirect リダイレクト
     */
    public function update(Request $request, $id)
    {
        $attendance = Attendance::findOrFail($id);

        // 他人の勤怠情報を修正しようとした場合はアクセス拒否
        if ($attendance->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        // 既に承認待ちの申請がある場合は、エラーを返して処理を中断
        if ($attendance->stampCorrectionRequest()->where('status', 'pending')->exists()) {
            return redirect()->back()->with('error', '既に承認待ちの申請があるため、新たな申請はできません。');
        }

        // --- ここからバリデーションとデータ保存処理 ---
        $validatedData = $request->validate([
            'clock_in' => 'required|date_format:H:i',
            'clock_out' => 'required|date_format:H:i|after:clock_in',
            'breaks.*.break_start' => 'nullable|date_format:H:i',
            'breaks.*.break_end' => 'nullable|date_format:H:i|after:breaks.*.break_start',
            'new_breaks.break_start' => 'nullable|date_format:H:i',
            'new_breaks.break_end' => 'nullable|date_format:H:i|after:new_breaks.break_start',
            'remarks' => 'required|string|max:500',
        ]);

        // データベースへの書き込み
        DB::transaction(function () use ($attendance, $validatedData) {

            $breaksData = []; // 空の配列として初期化

            // 既存の休憩時間を配列に追加
            // issetとis_arrayで変数の存在と型をチェック
            if (isset($validatedData['breaks']) && is_array($validatedData['breaks'])) {
                foreach ($validatedData['breaks'] as $times) {
                    // 休憩開始と終了の両方が空でないことを確認
                    if (!empty($times['break_start']) && !empty($times['break_end'])) {
                        // $breaksData 配列に要素を追加
                        $breaksData[] = [
                            'break_start' => $times['break_start'],
                            'break_end'   => $times['break_end']
                        ];
                    }
                }
            }

            // 新規休憩を追加
            if (!empty($validatedData['new_breaks']['break_start']) && !empty($validatedData['new_breaks']['break_end'])) {
                // 上書きではなく配列に要素を追加
                $breaksData[] = [
                    'break_start' => $validatedData['new_breaks']['break_start'],
                    'break_end'   => $validatedData['new_breaks']['break_end']
                ];
            }

            // 勤怠記録の日付を取得
            $date = $attendance->date->format('Y-m-d');

            // 修正申請テーブルに新しいレコードを作成
            StampCorrectionRequest::create([
                'user_id'               => auth()->id(),
                'attendance_id'         => $attendance->id,
                'requested_clock_in'    => $date . ' ' . $validatedData['clock_in'], // 日付と時刻を結合
                'requested_clock_out'   => $date. ' '. $validatedData['clock_out'], // 日付と時刻を結合
                'requested_breaks_data' => json_encode($breaksData),
                'remarks'               => $validatedData['remarks'],
                'status'                => 'pending',
            ]);
        });

        return redirect()->route('attendance.list')->with('status_message', '勤怠修正を申請しました。');
    }
}
