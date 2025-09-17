<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\StampCorrectionRequest;

class UserRequestController extends Controller
{
    /**
     *@return view ビュー
     * ログインユーザーの勤怠修正申請一覧を表示
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $activeTab = $request->input('tab', 'pending');

        $pendingRequests = StampCorrectionRequest::with('attendance', 'user')
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        $processedRequests = StampCorrectionRequest::with('attendance', 'user')
            ->where('user_id', $user->id)
            ->whereIn('status', ['approved', 'rejected'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('user_requests.list', compact('pendingRequests', 'processedRequests', 'activeTab'));
    }
}
