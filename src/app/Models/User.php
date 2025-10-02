<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Carbon\Carbon;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    // ユーザーの役割定義
    public const ROLE_GENERAL = 'general';
    public const ROLE_ADMIN = 'admin';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * ユーザーが管理者であるかを確認
     */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * ユーザーが一般ユーザーであるかを確認
     */
    public function isGeneral(): bool
    {
        return $this->role === self::ROLE_GENERAL;
    }

    /**
     * ユーザーの勤怠記録を取得
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * ユーザーの勤怠修正申請を取得
     */
    public function stampCorrectionRequests()
    {
        return $this->hasMany(StampCorrectionRequest::class);
    }

    /**
     * 今日の勤怠記録を取得
     * @return Attendance|null
     */
    private function getTodayAttendance():?Attendance
    {
        return $this->attendances()->whereDate('date', Carbon::today())->first();
    }

    /**
     * 出勤処理
     * @return bool 成功したか
     */
    public function clockIn(): bool
    {
        if ($this->getTodayAttendance()) {
            return false; // 既に出勤済み
        }

        $this->attendances()->create([
            'date' => Carbon::today(),
            'clock_in' => Carbon::now(),
        ]);

        return true;
    }

    /**
     * 退勤処理
     * @return array [成功フラグ(bool), メッセージ(string)]
     */
    public function clockOut(): array
    {
        $attendance = $this->getTodayAttendance();

        if (!$attendance || $attendance->clock_out) {
            return [false, '既に出勤していないか、退勤済みです。'];
        }

        $latestBreak = $attendance->userBreaks()->latest('break_start')->first();
        if ($latestBreak &&!$latestBreak->break_end) {
            return [false, '休憩中は退勤できません。休憩を終了してください。'];
        }

        $attendance->update(['clock_out' => Carbon::now()]);
        return [true, 'お疲れ様でした'];
    }

    /**
     * 休憩開始処理
     * @return array [成功フラグ(bool), メッセージ(string)]
     */
    public function startBreak(): array
    {
        $attendance = $this->getTodayAttendance();

        if (!$attendance || $attendance->clock_out) {
            return [false, '出勤していないか、既に退勤済みです。'];
        }

        $latestBreak = $attendance->userBreaks()->latest('break_start')->first();
        if ($latestBreak &&!$latestBreak->break_end) {
            return [false, '既に休憩中です。'];
        }

        $attendance->userBreaks()->create(['break_start' => Carbon::now()]);
        return [true, '休憩を開始しました。'];
    }

    /**
     * 休憩終了処理
     * @return array [成功フラグ(bool), メッセージ(string)]
     */
    public function endBreak(): array
    {
        $attendance = $this->getTodayAttendance();

        if (!$attendance || $attendance->clock_out) {
            return [false, '出勤していないか、既に退勤済みです。'];
        }

        $latestBreak = $attendance->userBreaks()->latest('break_start')->first();
        if (!$latestBreak || $latestBreak->break_end) {
            return [false, '休憩中ではありません。'];
        }

        $latestBreak->update(['break_end' => Carbon::now()]);
        return [true, '休憩を終了しました。'];
    }
}
