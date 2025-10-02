<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * ログイン画面が正しく表示される
     */
    public function the_login_screen_can_be_rendered()
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    /**
     * @test
     * メールアドレスが未入力の場合、バリデーションメッセージが表示される
     */
    public function email_is_required_for_login()
    {
        $response = $this->post('/login', [
            'email' => '',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
    }

    /**
     * @test
     * パスワードが未入力の場合、バリデーションメッセージが表示される
     */
    public function password_is_required_for_login()
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => '',
        ]);

        $response->assertSessionHasErrors('password');
    }

    /**
     * @test
     * 登録内容と一致しない場合（パスワードが違う）、バリデーションメッセージが表示される
     */
    public function user_cannot_authenticate_with_invalid_password()
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest(); // ログインに失敗していることを確認
    }

    /**
     * @test
     * 登録内容と一致しない場合（メールアドレスが存在しない）、バリデーションメッセージが表示される
     */
    public function user_cannot_authenticate_with_non_existent_email()
    {
        $this->post('/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password',
        ]);

        $this->assertGuest(); // ログインに失敗していることを確認
    }

    /**
     * @test
     * 登録済みのユーザーが正常にログインできる
     */
    public function an_existing_user_can_authenticate()
    {
        // メール認証済みのユーザーを作成
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // ログインリクエストを送信
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password', // UserFactoryのデフォルトパスワードは 'password'
        ]);

        $this->assertAuthenticated(); // ログインに成功したことを確認
        $response->assertRedirect('/attendance'); // 勤怠打刻画面にリダイレクトされることを確認
    }
}
