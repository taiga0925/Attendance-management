<?php

namespace Tests\Feature\Request;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\StampCorrectionRequest;
use Carbon\Carbon;

class UserRequestListTest extends TestCase
{
    use RefreshDatabase;

    private $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'general']);
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_displays_pending_requests_on_the_list_page()
    {
        // Arrange: 承認待ちの申請を作成
        $attendance = new Attendance();
        $attendance->user_id = $this->user->id;
        $attendance->date = Carbon::today();
        $attendance->clock_in = now();
        $attendance->save();

        $request = new StampCorrectionRequest();
        $request->user_id = $this->user->id;
        $request->attendance_id = $attendance->id;
        $request->requested_clock_in = now();
        $request->remarks = '承認待ちテスト';
        $request->status = 'pending';
        $request->save();

        // Act: 申請一覧ページにアクセス
        $response = $this->get(route('user_requests.list'));

        // Assert:
        $response->assertStatus(200);
        $response->assertSee('承認待ちテスト');
    }

    /** @test */
    public function it_displays_approved_requests_on_the_list_page()
    {
        // Arrange: 承認済みの申請を作成
        $attendance = new Attendance();
        $attendance->user_id = $this->user->id;
        $attendance->date = Carbon::today();
        $attendance->clock_in = now();
        $attendance->save();

        $request = new StampCorrectionRequest();
        $request->user_id = $this->user->id;
        $request->attendance_id = $attendance->id;
        $request->requested_clock_in = now();
        $request->remarks = '承認済みテスト';
        $request->status = 'approved';
        $request->save();

        // Act: 「承認済み」タブにアクセス
        $response = $this->get(route('user_requests.list', ['tab' => 'processed']));

        // Assert:
        $response->assertStatus(200);
        $response->assertSee('承認済みテスト');
    }

    /** @test */
    public function the_detail_link_on_the_request_list_page_works()
    {
        // Arrange: 申請データを作成
        $attendance = new Attendance();
        $attendance->user_id = $this->user->id;
        $attendance->date = Carbon::today();
        $attendance->clock_in = now();
        $attendance->save();

        $request = new StampCorrectionRequest();
        $request->user_id = $this->user->id;
        $request->attendance_id = $attendance->id;
        $request->requested_clock_in = now();
        $request->remarks = '詳細リンクテスト';
        $request->status = 'pending';
        $request->save();

        // Act: 申請一覧ページにアクセス
        $response = $this->get(route('user_requests.list'));

        // Assert:
        $response->assertStatus(200);
        // 勤怠詳細ページへの正しいリンクが含まれているかを確認
        $response->assertSee(route('attendance.detail', ['id' => $attendance->id]));
    }
}
