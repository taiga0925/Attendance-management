<?php

namespace Tests\Feature\Attendance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance; 
use Carbon\Carbon;

class AttendanceStatusTest extends TestCase
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
     * 勤怠打刻画面に現在の日付が正しく表示される
     */
    public function it_displays_the_current_date_on_the_attendance_page()
    {
        Carbon::setTestNow(Carbon::create(2025, 9, 26));
        $response = $this->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('2025年9月26日(金)');
    }

    /**
     * @test
     * 勤務外の場合、勤怠ステータスが正しく表示される
     */
    public function it_displays_off_duty_status_when_no_attendance_record_exists()
    {
        // Arrange: 今日の勤怠記録がない状態

        // Act: 勤怠打刻画面にアクセス
        $response = $this->get('/attendance');

        // Assert: 「勤務外」と表示されることを確認
        $response->assertStatus(200);
        $response->assertSee('勤務外');
    }

    /**
     * @test
     * 出勤中の場合、勤怠ステータスが正しく表示される
     */
    public function it_displays_working_status_when_clocked_in()
    {
        // Arrange: 今日の出勤記録を作成 (退勤はしていない)
        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::today(),
            'clock_in' => Carbon::now()->subHour(),
            'clock_out' => null,
        ]);

        // Act: 勤怠打刻画面にアクセス
        $response = $this->get('/attendance');

        // Assert: 「出勤中」と表示されることを確認
        $response->assertStatus(200);
        $response->assertSee('出勤中');
    }

    /**
     * @test
     * 休憩中の場合、勤怠ステータスが正しく表示される
     */
    public function it_displays_on_break_status_when_on_a_break()
    {
        // Arrange: 今日の出勤記録と、開始された休憩記録を作成
        $attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::today(),
            'clock_in' => Carbon::now()->subHours(2),
            'clock_out' => null,
        ]);
        $attendance->userBreaks()->create([
            'break_start' => Carbon::now()->subHour(),
            'break_end' => null, // 休憩は終了していない
        ]);

        // Act: 勤怠打刻画面にアクセス
        $response = $this->get('/attendance');

        // Assert: 「休憩中」と表示されることを確認
        $response->assertStatus(200);
        $response->assertSee('休憩中');
    }

    /**
     * @test
     * 退勤済の場合、勤怠ステータスが正しく表示される
     */
    public function it_displays_clocked_out_status_when_clocked_out()
    {
        // Arrange: 今日の出勤・退勤記録を作成
        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::today(),
            'clock_in' => Carbon::now()->subHours(9),
            'clock_out' => Carbon::now()->subHour(), // 退勤済み
        ]);

        // Act: 勤怠打刻画面にアクセス
        $response = $this->get('/attendance');

        // Assert: 「退勤済」と表示されることを確認
        $response->assertStatus(200);
        $response->assertSee('退勤済');
    }

    /**
     * @test
     * 出勤ボタンが正しく機能する
     */
    public function it_can_clock_in_successfully()
    {
        // Arrange: 現在時刻を「午前9時ちょうど」に固定します
        Carbon::setTestNow(Carbon::today()->setTime(9, 0, 0));

        // Act: 出勤ボタンにPOSTリクエストを送信します
        $response = $this->post(route('attendance.clockIn'));

        // Assert:
        // 1. 勤怠打刻画面に正しくリダイレクトされることを確認します
        $response->assertRedirect(route('attendance.index'));

        // 2. データベースから、作成されたはずの勤怠記録を直接取得します
        $attendance = Attendance::where('user_id', $this->user->id)
            ->whereDate('date', Carbon::today())
            ->first();

        // 3. 取得した勤怠記録が存在すること（nullでないこと）を確認します
        $this->assertNotNull($attendance, '勤怠記録がデータベースに作成されていません。');

        // 4. 保存された出勤時刻が、固定した時刻と一致することを確認します
        $this->assertEquals('09:00:00', $attendance->clock_in->format('H:i:s'));

        // 5. 退勤時間はまだ記録されていない（nullである）ことを確認します
        $this->assertNull($attendance->clock_out);
    }

    /**
     * @test
     * 出勤は一日一回のみできる
     */
    public function it_cannot_clock_in_more_than_once_a_day()
    {
        // Arrange: 既に出勤済みの状態をデータベースに作成
        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::today(),
            'clock_in' => Carbon::now()->subHour(),
        ]);
        // データベースに1件の勤怠記録があることを確認
        $this->assertDatabaseCount('attendances', 1);

        // Act: 再度、出勤ボタンにPOSTリクエストを送信
        $response = $this->post(route('attendance.clockIn'));

        // Assert:
        // 1. データベースの勤怠記録が増えていない（2回目は失敗した）ことを確認
        $this->assertDatabaseCount('attendances', 1);
        // 2. エラーメッセージと共にリダイレクトされたことを確認
        $response->assertSessionHas('error', '本日は既に出勤済みです。');
    }

    /**
     * @test
     * 出勤時刻が勤怠一覧画面で確認できる
     */
    public function it_displays_the_clock_in_time_on_the_attendance_list_page()
    {
        // Arrange: 午前9時に出勤する
        Carbon::setTestNow(Carbon::today()->setTime(9, 0, 0));
        $this->post(route('attendance.clockIn'));

        // Act: 勤怠一覧画面にアクセス
        $response = $this->get(route('attendance.list'));

        // Assert:
        // 1. 画面が正常に表示されることを確認
        $response->assertStatus(200);
        // 2. 画面に出勤時刻「09:00」が表示されていることを確認
        $response->assertSee('09:00');
    }

    /**
     * @test
     * 休憩開始ボタンが正しく機能する
     */
    public function it_can_start_a_break()
    {
        // Arrange: 出勤済みの状態を作成
        $attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::today(),
            'clock_in' => Carbon::now()->subHour(),
            'clock_out' => null,
        ]);
        Carbon::setTestNow(Carbon::now()); // 現在時刻を固定

        // Act: 休憩開始ボタンにPOSTリクエストを送信
        $response = $this->post(route('attendance.startBreak'));

        // Assert:
        $response->assertRedirect(route('attendance.index'));
        $this->assertDatabaseHas('user_breaks', [
            'attendance_id' => $attendance->id,
            'break_start' => Carbon::now(),
            'break_end' => null,
        ]);
    }

    /**
     * @test
     * 休憩終了ボタンが正しく機能する
     */
    public function it_can_end_a_break()
    {
        // Arrange: 休憩中の状態を作成
        $attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::today(),
            'clock_in' => Carbon::now()->subHours(2),
            'clock_out' => null,
        ]);
        $break = $attendance->userBreaks()->create([
            'break_start' => Carbon::now()->subHour(),
            'break_end' => null,
        ]);
        Carbon::setTestNow(Carbon::now()); // 現在時刻を固定

        // Act: 休憩終了ボタンにPOSTリクエストを送信
        $response = $this->post(route('attendance.endBreak'));

        // Assert:
        $response->assertRedirect(route('attendance.index'));
        $this->assertDatabaseHas('user_breaks', [
            'id' => $break->id,
            'break_end' => Carbon::now(),
        ]);
    }

    /**
     * @test
     * 休憩は一日に何回でもできる
     */
    public function it_can_take_multiple_breaks()
    {
        // Arrange: 出勤済みの状態を作成
        $attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::today(),
            'clock_in' => Carbon::now()->subHours(3),
            'clock_out' => null,
        ]);

        // Act & Assert 1回目
        $this->post(route('attendance.startBreak'));
        $this->assertDatabaseCount('user_breaks', 1);
        $this->post(route('attendance.endBreak'));

        // Act & Assert 2回目
        $this->post(route('attendance.startBreak'));
        $this->assertDatabaseCount('user_breaks', 2);
    }

    /**
     * @test
     * 休憩時刻が勤怠一覧画面で確認できる
     */
    public function it_displays_the_total_break_time_on_the_attendance_list_page()
    {
        // Arrange: 30分間の休憩を取った状態を作成
        $attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::today(),
            'clock_in' => Carbon::now()->subHours(3),
            'clock_out' => null,
        ]);
        $attendance->userBreaks()->create([
            'break_start' => Carbon::now()->subHours(2),
            'break_end' => Carbon::now()->subHours(2)->addMinutes(30), // 30分間の休憩
        ]);

        // Act: 勤怠一覧画面にアクセス
        $response = $this->get(route('attendance.list'));

        // Assert:
        $response->assertStatus(200);
        // 画面に合計休憩時間「0:30」が表示されていることを確認
        // (以前作成したAttendanceモデルのtotal_break_timeアクセサの計算結果)
        $response->assertSee('0:30');
    }

    /**
     * @test
     * 退勤ボタンが正しく機能する
     */
    public function it_can_clock_out_successfully()
    {
        // Arrange: 出勤済みの状態を作成
        $attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'date' => Carbon::today(),
            'clock_in' => Carbon::now()->subHours(8),
            'clock_out' => null,
        ]);
        Carbon::setTestNow(Carbon::now()); // 現在時刻を固定

        // Act: 退勤ボタンにPOSTリクエストを送信
        $response = $this->post(route('attendance.clockOut'));

        // Assert:
        $response->assertRedirect(route('attendance.index'));
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'clock_out' => Carbon::now(),
        ]);
    }

    /**
     * @test
     * 退勤時刻が勤怠一覧画面で確認できる
     */
    public function it_displays_the_clock_out_time_on_the_attendance_list_page()
    {
        // 新しいAttendanceモデルのインスタンス（空のオブジェクト）を作成します
        $attendance = new Attendance();

        // 必要な値をプロパティとして設定します
        $attendance->user_id = $this->user->id; // ログインしているテストユーザーのID
        $attendance->date = Carbon::today(); // 今日の日付
        $attendance->clock_in = Carbon::today()->setTime(9, 0, 0); // 午前9時に出勤
        $attendance->clock_out = Carbon::today()->setTime(18, 0, 0); // 午後18時に退勤

        // 最後に、完成した勤怠記録をデータベースに保存します
        $attendance->save();

        // Act: 勤怠一覧画面にアクセス
        $response = $this->get(route('attendance.list'));

        // Assert:
        $response->assertStatus(200);
        // 画面に退勤時刻「18:00」が表示されていることを確認
        $response->assertSee('18:00');
    }
}
