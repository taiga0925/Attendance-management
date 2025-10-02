<?php

namespace Tests\Feature\Attendance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\UserBreak;
use Carbon\Carbon;

class AttendanceDetailTest extends TestCase
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
            'name' => 'テストユーザー', // 検証しやすいように名前を固定
            'email_verified_at' => now(),
            'role' => 'general',
        ]);
        $this->actingAs($this->user);
    }

    /**
     * @test
     * 勤怠詳細画面に必要な情報がすべて正しく表示される
     */
    public function it_displays_all_attendance_details_correctly()
    {
        // Arrange: 検証の基準となる、具体的な勤怠データを作成します
        $targetDate = Carbon::create(2025, 9, 26); // 2025年9月26日(金)

        // 1. 勤怠記録を作成
        $attendance = new Attendance();
        $attendance->user_id = $this->user->id;
        $attendance->date = $targetDate;
        $attendance->clock_in = $targetDate->copy()->setTime(9, 0, 0);  // 09:00
        $attendance->clock_out = $targetDate->copy()->setTime(18, 0, 0); // 18:00
        $attendance->save();

        // 2. 新しいUserBreakモデルのインスタンス（空のオブジェクト）を作成します
        $break = new UserBreak();
        $break->attendance_id = $attendance->id; // どの勤怠記録に紐づくか
        $break->break_start = $targetDate->copy()->setTime(12, 0, 0); // 12:00
        $break->break_end = $targetDate->copy()->setTime(13, 0, 0);   // 13:00
        $break->save();

        // Act: 作成した勤怠記録の詳細ページにアクセスします
        $response = $this->get(route('attendance.detail', ['id' => $attendance->id]));

        // Assert: 画面に期待通りの情報が表示されているか、一つずつ確認します
        $response->assertStatus(200);
        $response->assertSee('勤怠詳細'); // ページのタイトル

        // 1. 名前の確認
        $response->assertSee('テストユーザー');

        // 2. 日付の確認 (ビューの表示形式に合わせて検証)
        $response->assertSee('2025年');
        $response->assertSee('9月26日');

        // 3. 出勤・退勤時間の確認 (ビューの表示形式に合わせて検証)
        $response->assertSee('09:00');
        $response->assertSee('18:00');

        // 4. 休憩時間の確認 (ビューの表示形式に合わせて検証)
        $response->assertSee('12:00');
        $response->assertSee('13:00');
    }
}
