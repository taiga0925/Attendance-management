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

    // 承認待ちの修正申請があるかチェックする
    public function hasPendingCorrectionRequest(): bool
    {
        return $this->stampCorrectionRequest()->where('status', 'pending')->exists();
    }
}
