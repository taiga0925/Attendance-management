<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class UpdateAttendanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // ルート側で'admin'ミドルウェアによる認可を行っているため、ここではtrueを返します
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return array(
            // 出勤・退勤時間のチェック
            'clock_in' => 'required|date_format:H:i',
            'clock_out' => 'required|date_format:H:i|after:clock_in',

            // 休憩時間のチェック
            'breaks.*.break_start' => array('nullable', 'date_format:H:i', $this->breakTimeRule()),
            'breaks.*.break_end' => array('nullable', 'date_format:H:i', 'after:breaks.*.break_start', $this->breakTimeRule()),
            'new_breaks.break_start' => array('nullable', 'date_format:H:i', $this->breakTimeRule()),
            'new_breaks.break_end' => array('nullable', 'date_format:H:i', 'after:new_breaks.break_start', $this->breakTimeRule()),

            // 備考欄のチェック
            'remarks' => 'required|string|max:500',
        );
    }

    /**
     * カスタムエラーメッセージを定義
     *
     * @return array
     */
    public function messages()
    {
        return array(
            'clock_out.after' => '出勤時間もしくは退勤時間が不適切な値です。',
            'remarks.required' => '備考を記入してください。',
        );
    }

    /**
     * 2. 休憩時間が勤務時間内であるかをチェックするカスタムルール
     */
    private function breakTimeRule()
    {
        return function ($attribute, $value, $fail) {
            // 入力された出勤・退勤時間を取得
            $clockIn = $this->input('clock_in');
            $clockOut = $this->input('clock_out');

            // 出勤・退勤時間が正しく入力されていない場合は、このチェックは行わない
            if (!$clockIn || !$clockOut) {
                return;
            }

            // Carbonを使って時間を比較できるようにする
            $breakTime = Carbon::parse($value);
            $workStartTime = Carbon::parse($clockIn);
            $workEndTime = Carbon::parse($clockOut);

            // 休憩時間が勤務時間の範囲外であればエラー
            if (!$breakTime->between($workStartTime, $workEndTime, true)) {
                $fail('休憩時間が勤務時間外です。');
            }
        };
    }
}
