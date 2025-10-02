<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\UserBreak;
use Carbon\Carbon;

class AttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    private $admin;
    private $generalUser;
    private $attendance;

    /**
     * 各テストの前に実行されるセットアップメソッド
     */
    protected function setUp(): void
    {
        parent::setUp();

        // 管理者ユーザーを作成し、'admin'ガードでログインさせます
        $this->admin = new User();
        $this->admin->name = '管理者';
        $this->admin->email = 'admin@example.com';
        $this->admin->password = bcrypt('password');
        $this->admin->role = 'admin';
        $this->admin->save();
        $this->actingAs($this->admin, 'admin');

        // テスト対象となる一般ユーザーを作成します
        $this->generalUser = new User();
        $this->generalUser->name = 'テストユーザー';
        $this->generalUser->email = 'test@example.com';
        $this->generalUser->password = bcrypt('password');
        $this->generalUser->role = 'general';
        $this->generalUser->save();

        // テスト対象となる勤怠記録を作成します
        $this->attendance = new Attendance();
        $this->attendance->user_id = $this->generalUser->id;
        $this->attendance->date = Carbon::today();
        $this->attendance->clock_in = Carbon::today()->setTime(9, 0, 0);
        $this->attendance->clock_out = Carbon::today()->setTime(18, 0, 0);
        $this->attendance->save();
    }

    /**
     * @test
     * 勤怠詳細画面に表示されるデータが選択したものになっている
     */
    public function it_displays_correct_attendance_details_for_admin()
    {
        // Arrange: 休憩記録を追加します
        $break = new UserBreak();
        $break->attendance_id = $this->attendance->id;
        $break->break_start = Carbon::today()->setTime(12, 0, 0);
        $break->break_end = Carbon::today()->setTime(13, 0, 0);
        $break->save();

        // Act: 作成した勤怠記録の詳細ページにアクセスします
        $response = $this->get(route('admin.attendances.detail', ['attendance' => $this->attendance->id]));

        // Assert:
        $response->assertStatus(200);
        $response->assertSee('テストユーザー'); // 名前
        $response->assertSee(Carbon::today()->format('Y年')); // 日付（年）
        $response->assertSee(Carbon::today()->isoFormat('M月D日')); // 日付（月日）
        $response->assertSee('09:00'); // 出勤
        $response->assertSee('18:00'); // 退勤
        $response->assertSee('12:00'); // 休憩開始
        $response->assertSee('13:00'); // 休憩終了
    }

    /**
     * @test
     * 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function update_fails_when_clock_in_is_after_clock_out()
    {
        $invalidData = [
            'clock_in' => '19:00', // 不正な値
            'clock_out' => '18:00',
            'remarks' => 'テスト備考',
        ];

        $response = $this->patch(route('admin.attendances.update', ['attendance' => $this->attendance->id]), $invalidData);

        $response->assertSessionHasErrors('clock_out');
    }

    /**
     * @test
     * 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function update_fails_when_break_start_is_after_clock_out()
    {
        $invalidData = [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'new_breaks' => ['break_start' => '19:00', 'break_end' => '19:10'], // 不正な値
            'remarks' => 'テスト備考',
        ];

        $response = $this->patch(route('admin.attendances.update', ['attendance' => $this->attendance->id]), $invalidData);

        $response->assertSessionHasErrors('new_breaks.break_start');
    }

    /**
     * @test
     * 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function update_fails_when_break_end_is_after_clock_out()
    {
        $invalidData = [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'new_breaks' => ['break_start' => '17:50', 'break_end' => '18:10'], // 不正な値
            'remarks' => 'テスト備考',
        ];

        $response = $this->patch(route('admin.attendances.update', ['attendance' => $this->attendance->id]), $invalidData);

        $response->assertSessionHasErrors('new_breaks.break_end');
    }

    /**
     * @test
     * 備考欄が未入力の場合のエラーメッセージが表示される
     */
    public function update_fails_when_remarks_is_empty()
    {
        $invalidData = [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'remarks' => '', // 未入力
        ];

        $response = $this->patch(route('admin.attendances.update', ['attendance' => $this->attendance->id]), $invalidData);

        $response->assertSessionHasErrors('remarks');
    }
}
