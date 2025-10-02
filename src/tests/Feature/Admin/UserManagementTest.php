<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private $admin;

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
    }

    /**
     * @test
     * 管理者ユーザーが全一般ユーザーの「氏名」「メールアドレス」を確認できる
     */
    public function it_displays_all_general_users_on_staff_list_page()
    {
        // Arrange: 2人の一般ユーザーを作成します
        $userA = new User();
        $userA->name = '一般ユーザーA';
        $userA->email = 'usera@test.com';
        $userA->password = bcrypt('password');
        $userA->role = 'general';
        $userA->save();

        $userB = new User();
        $userB->name = '一般ユーザーB';
        $userB->email = 'userb@test.com';
        $userB->password = bcrypt('password');
        $userB->role = 'general';
        $userB->save();

        // Act: スタッフ一覧画面にアクセスします
        $response = $this->get(route('admin.users.index'));

        // Assert:
        $response->assertStatus(200);
        $response->assertSee('一般ユーザーA');
        $response->assertSee('usera@test.com');
        $response->assertSee('一般ユーザーB');
        $response->assertSee('userb@test.com');
        $response->assertDontSee('管理者'); // 管理者自身の情報は表示されない
    }

    /**
     * @test
     * ユーザーの勤怠情報が正しく表示される
     */
    public function it_displays_correct_monthly_attendance_for_a_user()
    {
        // Arrange: 特定のユーザーの勤怠記録を作成します
        $user = User::factory()->create(['role' => 'general']);
        $attendance = new Attendance();
        $attendance->user_id = $user->id;
        $attendance->date = Carbon::today();
        $attendance->clock_in = Carbon::today()->setTime(9, 0, 0);
        $attendance->save();

        // Act: そのユーザーの月次勤怠一覧画面にアクセスします
        $response = $this->get(route('admin.users.attendances', ['user' => $user->id]));

        // Assert:
        $response->assertStatus(200);
        $response->assertSee($user->name); // ユーザー名が表示されている
        $response->assertSee('09:00'); // 出勤時刻が表示されている
    }

    /**
     * @test
     * 「前月」を押下した時に表示月の前月の情報が表示される
     */
    public function it_can_navigate_to_previous_month_on_staff_attendance_page()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $previousMonthDate = Carbon::today()->subMonth();

        // factory を使って前月の勤怠データを作成
        Attendance::factory()->create([
            'user_id'    => $user->id,
            'date'       => $previousMonthDate->toDateString(),
            'clock_in'   => $previousMonthDate->copy()->setTime(9, 30, 0),
        ]);

        // Act: 管理者としてログインし、URLに前月の年月を指定してアクセスします
        $response = $this->actingAs($user) // ログイン処理
            ->get(route('admin.users.attendances', [
                'user'  => $user, // ユーザーを指定
                'year'  => $previousMonthDate->year,  // 前月の年
                'month' => $previousMonthDate->month, // 前月の月
            ]));

        // Assert:
        $response->assertStatus(200);
        $response->assertSee('09:30'); // 前月の勤怠データが表示されている
    }

    /**
     * @test
     * 「翌月」を押下した時に表示月の翌月の情報が表示される
     */
    public function it_can_navigate_to_next_month_on_staff_attendance_page()
    {
        $user = User::factory()->create(['role' => 'admin']);

        $nextMonthDate = Carbon::today()->addMonth();

        Attendance::factory()->create([
            'user_id'    => $user->id,
            'date'       => $nextMonthDate->toDateString(),
            'clock_in'   => $nextMonthDate->copy()->setTime(10, 0, 0),
        ]);

        // Act:
        $response = $this->actingAs($user) // 管理者ユーザーとしてログイン
            ->get(route('admin.users.attendances', [
                'user' => $user,
                'year' => $nextMonthDate->year,
                'month' => $nextMonthDate->month,
            ]));

        // Assert:
        $response->assertStatus(200);
        $response->assertSee('10:00');
    }

    /**
     * @test
     * 「詳細」を押下すると、その日の勤怠詳細画面に遷移する
     */
    public function it_can_transition_to_daily_detail_page_from_staff_attendance_page()
    {
        // Arrange:
        $user = User::factory()->create(['role' => 'general']);
        $attendance = new Attendance();
        $attendance->user_id = $user->id;
        $attendance->date = Carbon::today();
        $attendance->clock_in = now();
        $attendance->save();

        // Act: そのユーザーの月次勤怠一覧画面にアクセスします
        $response = $this->get(route('admin.users.attendances', ['user' => $user->id]));

        // Assert:
        $response->assertStatus(200);
        // 管理者用の勤怠詳細画面への正しいリンクが含まれているかを確認します
        $response->assertSee(route('admin.attendances.detail', ['attendance' => $attendance->id]));
    }
}
