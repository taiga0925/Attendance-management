<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceListController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\AdminLoginController;

/*

|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// 未認証ユーザー
Route::middleware(['guest'])->group(function () {
    // ログイン画面
    Route::get('/login', function () {
        return view('auth.login');
    })->name('login');

    // 会員登録画面
    Route::get('/register', function () {
        return view('auth.register');
    })->name('register');

    // 管理者ログイン画面
    Route::get('/admin/login', function () {
        return view('admin.auth.login');
    })->name('admin.login');

    // 管理者ログイン
    Route::post('/admin/login', [AdminLoginController::class, 'store'])->name('admin.login.post');
});

// 認証済み一般ユーザー
Route::middleware(['auth'])->group(function () {

    // 勤怠入力画面
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');

    //勤務開始
    Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])->name('attendance.clockIn');
    //勤務終了
    Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])->name('attendance.clockOut');
    //休憩開始
    Route::post('/attendance/start-break', [AttendanceController::class, 'startBreak'])->name('attendance.startBreak');
    //休憩終了
    Route::post('/attendance/end-break', [AttendanceController::class, 'endBreak'])->name('attendance.endBreak');

    // 勤怠一覧画面
    Route::get('/attendance/list', [AttendanceListController::class, 'index'])->name('attendance.list');

    Route::get('/attendance/{id}',)->name('attendance.detail');

    Route::get('/stamp_correction_request/list',)->name('user_requests.list');
});

// 認証済み管理者ユーザー
Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware(['auth', 'admin'])->group(function () {

        // 勤怠一覧画面（管理者）
        Route::get('/attendances', [AdminAttendanceController::class, 'index'])->name('attendances.index');

        Route::get('/attendances/{id}', [AdminAttendanceController::class, 'show'])->name('attendances.detail');

        Route::get('/users',)->name('users.index');

        Route::get('/users/{user}/attendances',)->name('users.attendances');

        Route::get('/requests',)->name('requests.list');
    });
});
