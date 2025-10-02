<?php

namespace Tests\Feature\Attendance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceUpdateRequestTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $attendance;

    protected function setUp(): void
    {
        parent::setUp();

        // ログインユーザーを作成
        $this->user = new User();
        $this->user->name = 'テストユーザー';
        $this->user->email = 'test@example.com';
        $this->user->password = bcrypt('password');
        $this->user->email_verified_at = now();
        $this->user->role = 'general';
        $this->user->save();
        $this->actingAs($this->user);

        // テスト対象の勤怠記録を作成
        $this->attendance = new Attendance();
        $this->attendance->user_id = $this->user->id;
        $this->attendance->date = Carbon::today();
        $this->attendance->clock_in = Carbon::today()->setTime(9, 0, 0);
        $this->attendance->clock_out = Carbon::today()->setTime(18, 0, 0);
        $this->attendance->save();
    }

    /** @test */
    public function clock_in_time_cannot_be_after_clock_out_time()
    {
        $response = $this->patch(route('attendance.update', ['id' => $this->attendance->id]), [
            'clock_in' => '19:00', // 不正な値
            'clock_out' => '18:00',
            'remarks' => 'テスト備考',
        ]);
        $response->assertSessionHasErrors('clock_out');
    }

    /** @test */
    public function break_start_time_must_be_within_work_hours()
    {
        $response = $this->patch(route('attendance.update', ['id' => $this->attendance->id]), [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'new_breaks' => ['break_start' => '19:00', 'break_end' => '19:10'], // 不正な値
            'remarks' => 'テスト備考',
        ]);
        $response->assertSessionHasErrors('new_breaks.break_start');
    }

    /** @test */
    public function break_end_time_must_be_within_work_hours()
    {
        $response = $this->patch(route('attendance.update', ['id' => $this->attendance->id]), [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'new_breaks' => ['break_start' => '17:50', 'break_end' => '18:10'], // 不正な値
            'remarks' => 'テスト備考',
        ]);
        $response->assertSessionHasErrors('new_breaks.break_end');
    }

    /** @test */
    public function remarks_field_is_required()
    {
        $response = $this->patch(route('attendance.update', ['id' => $this->attendance->id]), [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'remarks' => '', // 未入力
        ]);
        $response->assertSessionHasErrors('remarks');
    }

    /** @test */
    public function it_can_submit_an_update_request_successfully()
    {
        $updateData = [
            'clock_in' => '09:05',
            'clock_out' => '18:05',
            'remarks' => '電車遅延のため修正',
        ];

        $response = $this->patch(route('attendance.update', ['id' => $this->attendance->id]), $updateData);

        $response->assertRedirect(route('attendance.list'));
        $this->assertDatabaseHas('stamp_correction_requests', [
            'attendance_id' => $this->attendance->id,
            'remarks' => '電車遅延のため修正',
            'status' => 'pending',
        ]);
    }
}
