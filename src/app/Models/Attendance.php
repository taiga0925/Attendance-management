<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'clock_in',
        'clock_out',
    ];

    protected $casts = [
        'date' => 'date',
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function userBreaks()
    {
        return $this->hasMany(UserBreak::class);
    }

    public function stampCorrectionRequest()
    {
        return $this->hasOne(StampCorrectionRequest::class);
    }

    public function hasPendingCorrectionRequest(): bool
    {
        return $this->stampCorrectionRequest()->where('status', 'pending')->exists();
    }

    /**
     * 合計休憩時間を計算してフォーマット
     *
     * @return string
     */
    public function getTotalBreakTimeAttribute(): string
    {
        $totalBreakTimeInSeconds = $this->userBreaks->sum(function ($userBreak) {
            if ($userBreak->break_start && $userBreak->break_end) {
                return $userBreak->break_end->diffInSeconds($userBreak->break_start);
            }
            return 0;
        });

        $totalMinutes = floor($totalBreakTimeInSeconds / 60);
        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;

        return sprintf('%d:%02d', $hours, $minutes);
    }

    /**
     * 合計勤務時間を計算してフォーマット
     *
     * @return string
     */
    public function getTotalWorkTimeAttribute(): string
    {
        if (!$this->clock_in ||!$this->clock_out) {
            return '0:00';
        }

        $totalBreakTimeInSeconds = $this->userBreaks->sum(function ($userBreak) {
            if ($userBreak->break_start && $userBreak->break_end) {
                return $userBreak->break_end->diffInSeconds($userBreak->break_start);
            }
            return 0;
        });

        $totalWorkTimeInSeconds = $this->clock_out->diffInSeconds($this->clock_in) - $totalBreakTimeInSeconds;
        $totalWorkTimeInSeconds = max(0, $totalWorkTimeInSeconds); // マイナスにならないように

        $totalMinutes = floor($totalWorkTimeInSeconds / 60);
        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;

        return sprintf('%d:%02d', $hours, $minutes);
    }
}
