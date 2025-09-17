<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StampCorrectionRequest;
use App\Models\Attendance;
use App\Models\UserBreak;
use Illuminate\Support\Facades\DB;

class RequestController extends Controller
{
    /**
     * @return view ビュー
     * 勤怠修正申請一覧画面
     */
    public function index(Request $request)
    {
        // URLのクエリパラメータから、どちらのタブが選択されているかを取得
        // 指定がなければ、デフォルトで 'pending' (承認待ち) を表示
        $activeTab = $request->input('tab', 'pending');

        // 「承認待ち」の申請を全て取得
        // with()で、関連するユーザー情報と勤怠情報も一緒に取得
        $pendingRequests = StampCorrectionRequest::with('user', 'attendance')
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        // 「承認済み」または「却下」された申請を全て取得
        $processedRequests = StampCorrectionRequest::with('user', 'attendance')
            ->whereIn('status', ['approved', 'rejected', 'approved_by_admin']) // 管理者による直接修正も含む
            ->orderBy('created_at', 'desc')
            ->get();

        // 取得したデータと、どちらのタブがアクティブかの情報をビューに渡す
        return view('admin.requests.list', compact('pendingRequests', 'processedRequests', 'activeTab'));
    }


    /**
     * 修正申請の詳細を表示
     *
     * @param StampCorrectionRequest $request
     * @return \Illuminate\View\View
     */
    public function show(StampCorrectionRequest $request)
    {
        // 関連するユーザー情報と勤怠情報を一緒に読み込む
        $request->load('user', 'attendance.userBreaks');

        // JSON形式で保存されている申請された休憩時間をPHPの配列に変換
        $requestedBreaks = json_decode($request->requested_breaks_data, true);

        return view('admin.requests.show', compact('request', 'requestedBreaks'));
    }

    /**
     * 修正申請を承認
     *
     * @param StampCorrectionRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function approve(StampCorrectionRequest $request)
    {
        // データベースへの複数の書き込みを安全に行う (トランザクション)
        DB::transaction(function () use ($request) {
            $attendance = $request->attendance;
            $date = $attendance->date->format('Y-m-d');

            // 1. 勤怠テーブル(attendances)を申請内容で更新
            $attendance->update([
                'clock_in'  => $request->requested_clock_in,
                'clock_out' => $request->requested_clock_out,
            ]);

            // 2. 既存の休憩記録を一旦すべて削除
            $attendance->userBreaks()->delete();

            // 3. 申請された休憩記録を新しく作成
            $requestedBreaks = json_decode($request->requested_breaks_data, true);
            if (is_array($requestedBreaks)) {
                foreach ($requestedBreaks as $break) {
                    if (!empty($break['break_start']) &&!empty($break['break_end'])) {
                        $attendance->userBreaks()->create([
                            'break_start' => $date. ' '. $break['break_start'],
                            'break_end'   => $date. ' '. $break['break_end']
                        ]);
                    }
                }
            }

            // 4. 申請レコードのステータスを 'approved' に更新
            $request->update(['status' => 'approved']);
        });

        // 承認後は、同じ詳細画面に戻り、「承認済み」の表示に切り替える
        return redirect()->route('admin.requests.show', ['request' => $request->id])
                         ->with('status_message', '勤怠修正申請を承認しました。');
    }
}
