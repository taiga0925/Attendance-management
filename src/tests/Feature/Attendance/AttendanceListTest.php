<?php

namespace Tests\Feature\Attendance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    private $user;

    /**
     * 各テストの前に実行されるセットアップメソッド
     */
    protected function setUp(): void
    {
        parent::setUp();

        // メール認証済みの一般ユーザーを作成し、ログインさせます
        $this->user = User::factory()->create([
            'email_verified_at' => now(),
            'role' => 'general',
        ]);
        $this->actingAs($this->user);
    }

    /**
     * @test
     * 自分が行った勤怠情報が全て表示されている
     */
    public function it_displays_only_the_authenticated_users_attendance_records()
    {
        // 1. テストの基準となる日付を「2025年9月15日」に固定します
        Carbon::setTestNow(Carbon::create(2025, 9, 15));

        // 2. ログインしているユーザーの、今日（9/15）の勤怠記録を「09:00」出勤で作成します
        $attendanceForSelf = new Attendance();
        $attendanceForSelf->user_id = $this->user->id;
        $attendanceForSelf->date = Carbon::today();
        $attendanceForSelf->clock_in = Carbon::today()->setTime(9, 0, 0);
        $attendanceForSelf->save();

        // 3. 別のユーザーを作成し、そのユーザーの今日（9/15）の勤怠記録を「10:00」出勤で作成します
        $otherUser = User::factory()->create();
        $attendanceForOther = new Attendance();
        $attendanceForOther->user_id = $otherUser->id;
        $attendanceForOther->date = Carbon::today();
        $attendanceForOther->clock_in = Carbon::today()->setTime(10, 0, 0);
        $attendanceForOther->save();

        // Act: 勤怠一覧画面にアクセスします（デフォルトで2025年9月が表示されます）
        $response = $this->get(route('attendance.list'));

        // Assert:
        $response->assertStatus(200);
        // 画面に自分の出勤時刻「09:00」が表示されていることを確認します
        $response->assertSee('09:00');
        // 画面に他のユーザーの出勤時刻「10:00」が表示されていないことを確認します
        $response->assertDontSee('10:00');
    }

    /**
     * @test
     * 勤怠一覧画面に遷移した際に現在の月が表示される
     */
    public function it_displays_the_current_month_by_default()
    {
        // Arrange: 現在時刻を「2025年9月」に固定
        Carbon::setTestNow(Carbon::create(2025, 9, 15));

        // Act: 勤怠一覧画面にアクセス
        $response = $this->get(route('attendance.list'));

        // Assert:
        $response->assertStatus(200);
        $response->assertSee('2025/09'); // 現在の月が表示されることを確認
    }

    /**
     * @test
     * 「前月」を押下した時に表示月の前月の情報が表示される
     */
    public function it_can_navigate_to_the_previous_month()
    {
        // Arrange: 現在を「2025年9月」とし、前月（8月）の勤怠記録を作成
        Carbon::setTestNow(Carbon::create(2025, 9, 15));
        $previousMonthAttendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::create(2025, 8, 10),
            'clock_in' => Carbon::create(2025, 8, 10, 9, 0, 0),
        ]);

        // Act: 前月の勤怠一覧ページにアクセス
        $response = $this->get(route('attendance.list', ['year' => 2025, 'month' => 8]));

        // Assert:
        $response->assertStatus(200);
        $response->assertSee('2025/08'); // 前月の表示になっていることを確認
        $response->assertSee('09:00'); // 前月の勤怠データが表示されていることを確認
    }

    /**
     * @test
     * 「翌月」を押下した時に表示月の翌月の情報が表示される
     */
    public function it_can_navigate_to_the_next_month()
    {
        // Arrange: 現在を「2025年9月」とし、翌月（10月）の勤怠記録を作成
        Carbon::setTestNow(Carbon::create(2025, 9, 15));
        $nextMonthAttendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::create(2025, 10, 5),
            'clock_in' => Carbon::create(2025, 10, 5, 9, 0, 0),
        ]);

        // Act: 翌月の勤怠一覧ページにアクセス
        $response = $this->get(route('attendance.list', ['year' => 2025, 'month' => 10]));

        // Assert:
        $response->assertStatus(200);
        $response->assertSee('2025/10'); // 翌月の表示になっていることを確認
        $response->assertSee('09:00'); // 翌月の勤怠データが表示されていることを確認
    }

    /**
     * @test
     * 「詳細」を押下すると、その日の勤怠詳細画面に遷移する
     */
    public function it_can_transition_to_the_daily_detail_page()
    {
        // Arrange: テスト用の勤怠記録を作成
        $attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
        ]);

        // Act: 作成された勤怠記録の詳細ページに直接アクセス
        $response = $this->get(route('attendance.detail', ['id' => $attendance->id]));

        // Assert:
        $response->assertStatus(200);
        $response->assertSee('勤怠詳細'); // 詳細ページのタイトルが表示されることを確認
        $response->assertSee($this->user->name); // 自分の名前が表示されていることを確認
    }
}
