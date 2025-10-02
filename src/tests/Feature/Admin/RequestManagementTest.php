<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\StampCorrectionRequest;
use Carbon\Carbon;

class RequestManagementTest extends TestCase
{
    use RefreshDatabase;

    private $admin;

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
    }

    /**
     * @test
     * 承認待ちの修正申請が全て表示されている
     */
    public function it_displays_all_pending_requests()
    {
        // Arrange: 承認待ちの申請データを作成します
        $user = User::factory()->create(['name' => '申請者A', 'role' => 'general']);
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);
        StampCorrectionRequest::factory()->create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'status' => 'pending',
        ]);

        // Act: 管理者用の申請一覧画面にアクセスします
        $response = $this->get(route('admin.requests.list'));

        // Assert:
        $response->assertStatus(200);
        $response->assertSee('申請者A'); // 申請したユーザーの名前が表示されている
        $response->assertSee('承認待ち'); // ステータスが表示されている
    }

    /**
     * @test
     * 承認済みの修正申請が全て表示されている
     */
    public function it_displays_all_processed_requests()
    {
        // Arrange: 承認済みの申請データを作成します
        $user = User::factory()->create(['name' => '申請者B']); // 名前を '申請者B' に指定
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);
        StampCorrectionRequest::factory()->create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'status' => 'approved',
        ]);

        // Act: 「承認済み」タブにアクセスします
        $response = $this->get(route('admin.requests.list', ['tab' => 'processed']));

        // Assert:
        $response->assertStatus(200);
        $response->assertSee('申請者B');
        $response->assertSee('承認済み');
    }

    /**
     * @test
     * 修正申請の詳細内容が正しく表示されている
     */
    public function it_displays_the_details_of_a_correction_request()
    {
        // Arrange: 詳細な申請データを作成します
        $user = User::factory()->create(['name' => '詳細確認ユーザー']);
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);
        $request = StampCorrectionRequest::factory()->create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'requested_clock_in' => '2025-10-01 09:05:00',
            'remarks' => '電車遅延のため',
            'status' => 'pending',
        ]);

        // Act: 作成した申請の詳細画面にアクセスします
        $response = $this->get(route('admin.requests.show', ['request' => $request->id]));

        // Assert:
        $response->assertStatus(200);
        $response->assertSee('詳細確認ユーザー');
        $response->assertSee('09:05'); // 申請された出勤時刻
        $response->assertSee('電車遅延のため'); // 備考
    }

    /**
     * @test
     * 修正申請の承認処理が正しく行われる
     */
    public function it_can_approve_a_correction_request()
    {
        // Arrange: 承認対象の申請データを作成します
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'clock_in' => '2025-10-01 09:00:00', // 修正前の時刻
        ]);
        $request = StampCorrectionRequest::factory()->create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'requested_clock_in' => '2025-10-01 09:05:00', // 修正後の時刻
            'status' => 'pending',
        ]);

        // Act: 承認処理を実行します
        $response = $this->patch(route('admin.requests.approve', ['request' => $request->id]));

        // Assert:
        // 1. 承認後、同じ詳細画面にリダイレクトされることを確認
        $response->assertRedirect(route('admin.requests.show', ['request' => $request->id]));

        // 2. 申請レコードのステータスが 'approved' に更新されたことを確認
        $this->assertDatabaseHas('stamp_correction_requests', [
            'id' => $request->id,
            'status' => 'approved',
        ]);

        // 3. 元の勤怠レコード(attendances)が、申請内容で更新されたことを確認
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'clock_in' => '2025-10-01 09:05:00', // 申請後の時刻に更新されている
        ]);
    }
}
