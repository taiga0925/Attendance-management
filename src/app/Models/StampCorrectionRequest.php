<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StampCorrectionRequest extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = array(
        'user_id',
        'attendance_id',
        'requested_clock_in',
        'requested_clock_out',
        'requested_breaks_data',
        'remarks',
        'status',
    );

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = array();

    /**
     * この申請を所有するユーザーを取得
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * この申請に関連する勤怠記録を取得
     */
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }
}
