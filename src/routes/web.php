<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceListController;
use App\Http\Controllers\AttendanceDetailController;
use App\Http\Controllers\UserRequestController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\StaffAttendanceController;
use App\Http\Controllers\Admin\RequestController as AdminRequestController;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;

/*

|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// 未認証ユーザー向けルート
Route::middleware(['guest'])->group(function () {
    // 一般ユーザーのログイン・新規登録はFortifyのデフォルトルートを使用

    // 管理者ログイン画面
    Route::get('/admin/login', function () {
        return view('admin.auth.login');
    })->name('admin.login');
});

// 認証済み一般ユーザー向けルート (A)
Route::middleware(['auth'])->group(function () {

    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])->name('attendance.clockIn');
    Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])->name('attendance.clockOut');
    Route::post('/attendance/start-break',)->name('attendance.startBreak');
    Route::post('/attendance/end-break',)->name('attendance.endBreak');

    Route::get('/attendance/list', [AttendanceListController::class, 'index'])->name('attendance.list');

    Route::get('/attendance/{id}',)->name('attendance.detail');
    Route::put('/attendance/{id}',)->name('attendance.update');

    Route::get('/stamp_correction_request/list',)->name('user_requests.list');
});

// 認証済み管理者ユーザー向けルート (B)
Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware(['auth', 'admin'])->group(function () {
        Route::post('/logout',)->name('logout');

        // 勤怠一覧画面（管理者）（PG08）
        Route::get('/attendances', [AdminAttendanceController::class, 'index'])->name('attendances.index');

        Route::get('/attendances/{id}', [AdminAttendanceController::class, 'show'])->name('attendances.detail');
        Route::put('/attendances/{id}', [AdminAttendanceController::class, 'update'])->name('attendances.update');

        // 修正点: スタッフ一覧画面（PG10）へのルートを正しく定義
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');

        Route::get('/users/{user}/attendances',)->name('users.attendances');
        Route::get('/users/{user}/attendances/export-csv',)->name('users.attendances.exportCsv');

        Route::get('/requests',)->name('requests.list');

        Route::get('/requests/{id}',)->name('requests.detail');
        Route::put('/requests/{id}/approve',)->name('requests.approve');
    });
});
