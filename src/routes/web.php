<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceListController;
use App\Http\Controllers\AttendanceDetailController;
use App\Http\Controllers\UserRequestController;

// Adminコントローラ
use App\Http\Controllers\Admin\AdminLoginController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\StaffAttendanceController as AdminStaffAttendanceController;
use App\Http\Controllers\Admin\RequestController as AdminRequestController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// =========================================================================
// 一般ユーザー用ルート
// =========================================================================

// --- 未認証の一般ユーザー ---
// ログインしていないユーザーのみがアクセス可能
Route::middleware(['guest'])->group(function () {
    // ログイン画面表示 (GET /login)
    // 実際のログイン処理(POST /login)はFortifyが担当
    Route::get('/login', function () {
        return view('auth.login');
    })->name('login');

    // 会員登録画面表示 (GET /register)
    // 実際の会員登録処理(POST /register)はFortifyが担当
    Route::get('/register', function () {
        return view('auth.register');
    })->name('register');
});

// --- 認証済みの一般ユーザー ---
// 一般ユーザーとしてログインしているユーザーのみがアクセス可能
Route::middleware(['auth', 'verified'])->group(function () {

    // ログアウト処理
    Route::post('/logout', function (Request $request) {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    })->name('logout');

    // 勤怠入力画面
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])->name('attendance.clockIn');
    Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])->name('attendance.clockOut');
    Route::post('/attendance/start-break', [AttendanceController::class, 'startBreak'])->name('attendance.startBreak');
    Route::post('/attendance/end-break', [AttendanceController::class, 'endBreak'])->name('attendance.endBreak');

    // 勤怠一覧画面
    Route::get('/attendance/list', [AttendanceListController::class, 'index'])->name('attendance.list');

    // 勤怠詳細画面
    Route::get('/attendance/{id}', [AttendanceDetailController::class, 'show'])->name('attendance.detail');
    Route::patch('/attendance/{id}', [AttendanceDetailController::class, 'update'])->name('attendance.update');

    // 申請一覧画面
    Route::get('/stamp_correction_request/list', [UserRequestController::class, 'index'])->name('user_requests.list');
});


// =========================================================================
// 管理者用ルート
// =========================================================================

Route::prefix('admin')->name('admin.')->group(function () {

    // --- 未認証の管理者 ---
    // 管理者としてログインしていないユーザーのみがアクセス可能
    Route::middleware('guest:admin')->group(function () {
        // 管理者ログイン画面表示
        Route::get('/login', function () {
            return view('admin.auth.login');
        })->name('login');
        // 管理者ログイン処理
        Route::post('/login', [AdminLoginController::class, 'store'])->name('login.post');
    });

    // --- 認証済みの管理者 ---
    // 管理者としてログインしているユーザーのみがアクセス可能
    Route::middleware('admin')->group(function () {

        // 管理者ログアウト処理
        Route::post('/logout', [AdminLoginController::class, 'destroy'])->name('logout');

        // 勤怠一覧画面（管理者）
        Route::get('/attendances', [AdminAttendanceController::class, 'index'])->name('attendances.index');

        // 勤怠詳細　（管理者）
        Route::get('/attendances/{attendance}', [AdminAttendanceController::class, 'show'])->name('attendances.detail');

        // 勤怠修正
        Route::patch('/attendances/{attendance}', [AdminAttendanceController::class, 'update'])->name('attendances.update');

        // スタッフ一覧画面
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');

        // スタッフ別勤怠一覧画面
        Route::get('/users/{user}/attendances', [AdminStaffAttendanceController::class, 'index'])->name('users.attendances');

        // CSV出力
        Route::get('/users/{user}/attendances/export', [AdminStaffAttendanceController::class, 'exportCsv'])->name('users.attendances.export');

        // 申請一覧画面
        Route::get('/requests', [AdminRequestController::class, 'index'])->name('requests.list');

        // 申請詳細・承認画面
        Route::get('/requests/{request}', [AdminRequestController::class, 'show'])->name('requests.show');

        // 申請承認処理
        Route::patch('/requests/{request}', [AdminRequestController::class, 'approve'])->name('requests.approve');
    });
});
