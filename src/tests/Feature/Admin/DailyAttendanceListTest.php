<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class DailyAttendanceListTest extends TestCase
{
    use RefreshDatabase;

    private $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($this->admin, 'admin');
    }

    /** @test */
    public function it_displays_all_users_attendance_for_the_day()
    {
        // Arrange
        $userA = new User();
        $userA->name = 'ユーザーA';
        $userA->email = 'usera@test.com';
        $userA->password = bcrypt('password');
        $userA->role = 'general';
        $userA->save();

        $userB = new User();
        $userB->name = 'ユーザーB';
        $userB->email = 'userb@test.com';
        $userB->password = bcrypt('password');
        $userB->role = 'general';
        $userB->save();

        $attendanceA = new Attendance();
        $attendanceA->user_id = $userA->id;
        $attendanceA->date = Carbon::today();
        $attendanceA->clock_in = Carbon::today()->setTime(9, 0, 0);
        $attendanceA->save();

        $attendanceB = new Attendance();
        $attendanceB->user_id = $userB->id;
        $attendanceB->date = Carbon::today();
        $attendanceB->clock_in = Carbon::today()->setTime(9, 5, 0);
        $attendanceB->save();

        // Act
        $response = $this->get(route('admin.attendances.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertSee('ユーザーA');
        $response->assertSee('ユーザーB');
    }

    /** @test */
    public function it_displays_the_current_date_by_default()
    {
        // Arrange
        Carbon::setTestNow(Carbon::create(2025, 10, 1));

        // Act
        $response = $this->get(route('admin.attendances.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertSee('2025/10/01');
    }

    /** @test */
    public function it_can_navigate_to_the_previous_day()
    {
        // Arrange
        $targetDate = Carbon::create(2025, 10, 1);
        Carbon::setTestNow($targetDate->copy()->subDay());

        $user = new User();
        $user->name = '昨日出勤ユーザー';
        $user->email = 'yesterday@test.com';
        $user->password = bcrypt('password');
        $user->role = 'general';
        $user->save();

        $attendance = new Attendance();
        $attendance->user_id = $user->id;
        $attendance->date = Carbon::today(); // Carbon::today() は '2025-09-30' を返します
        $attendance->clock_in = Carbon::today()->setTime(9, 0, 0);
        $attendance->save();

        // Act: URLパラメータなしでアクセスします
        $response = $this->get(route('admin.attendances.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertSee('昨日出勤ユーザー');
    }

    /** @test */
    public function it_can_navigate_to_the_next_day()
    {
        // Arrange
        $targetDate = Carbon::create(2025, 10, 1);
        Carbon::setTestNow($targetDate->copy()->addDay());

        $user = new User();
        $user->name = '明日出勤ユーザー';
        $user->email = 'tomorrow@test.com';
        $user->password = bcrypt('password');
        $user->role = 'general';
        $user->save();

        $attendance = new Attendance();
        $attendance->user_id = $user->id;
        $attendance->date = Carbon::today(); // Carbon::today() は '2025-10-02' を返します
        $attendance->clock_in = Carbon::today()->setTime(9, 0, 0);
        $attendance->save();

        // Act: URLパラメータなしでアクセスします
        $response = $this->get(route('admin.attendances.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertSee('明日出勤ユーザー');
    }
}
