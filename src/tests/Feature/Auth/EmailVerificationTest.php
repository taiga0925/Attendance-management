<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\URL;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * 会員登録後、認証メールが送信される
     */
    public function a_verification_email_is_sent_upon_registration()
    {
        // Laravelの通知機能を偽装（フェイク）
        Notification::fake();

        // 会員登録リクエストを送信
        $this->post(route('register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        // 指定したユーザーを取得
        $user = User::where('email', 'test@example.com')->first();

        $this->assertNotNull($user, 'ユーザーが作成されていません。');

        // 指定したユーザーに、VerifyEmail通知が送信されたことを表明
        Notification::assertSentTo(
            $user,
            VerifyEmail::class
        );
    }

    /**
     * @test
     * メール認証が済んでいないユーザーは、勤怠画面にアクセスできず、認証案内画面にリダイレクトされる
     */
    public function an_unverified_user_is_redirected_to_the_verify_email_screen()
    {
        // Arrange: メール認証が済んでいないユーザーを作成します
        $user = new User();
        $user->name = 'Unverified User';
        $user->email = 'unverified@example.com';
        $user->password = bcrypt('password');
        $user->email_verified_at = null; // 未認証
        $user->save();

        // Act: 作成したユーザーでログインし、勤怠画面にアクセスします
        $response = $this->actingAs($user)->get(route('attendance.index'));

        // Assert: 勤怠画面ではなく、メール認証案内画面にリダイレクトされることを確認します
        $response->assertRedirect('/email/verify');
    }

    /**
     * @test
     * メール認証リンクをクリックすると、正しく認証され、勤怠画面に遷移する
     */
    public function an_email_can_be_verified()
    {
        // Arrange: メール認証が済んでいないユーザーを作成します
        $user = new User();
        $user->name = 'Verify User';
        $user->email = 'verify@example.com';
        $user->password = bcrypt('password');
        $user->email_verified_at = null;
        $user->save();

        // Laravelのイベント機能を偽装します
        Event::fake();

        // 認証用の署名付きURLを生成します
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
        );

        // Act: 作成したユーザーでログインし、生成した認証URLにアクセスします
        $response = $this->actingAs($user)->get($verificationUrl);

        // Assert:
        // 1. ユーザーのメール認証が完了したことを示すイベントが発行されたことを確認
        Event::assertDispatched(Verified::class);
        // 2. ユーザーのemail_verified_atカラムに、現在時刻が記録されたことを表明
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        // 3. 勤怠画面にリダイレクトされることを確認
        $response->assertRedirect(route('attendance.index') . '?verified=1');
    }
}
