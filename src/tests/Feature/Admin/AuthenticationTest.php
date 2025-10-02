<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * 管理者ログイン画面が正しく表示される
     */
    public function the_admin_login_screen_can_be_rendered()
    {
        $response = $this->get('/admin/login');
        $response->assertStatus(200);
    }

    /**
     * @test
     * メールアドレスが未入力の場合、バリデーションメッセージが表示される
     */
    public function email_is_required_for_admin_login()
    {
        $response = $this->post('/admin/login', [
            'email' => '',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
    }

    /**
     * @test
     * パスワードが未入力の場合、バリデーションメッセージが表示される
     */
    public function password_is_required_for_admin_login()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => '',
        ]);

        $response->assertSessionHasErrors('password');
    }

    /**
     * @test
     * 登録内容と一致しない場合（パスワードが違う）、ログインできない
     */
    public function admin_cannot_authenticate_with_invalid_password()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'wrong-password',
        ]);

        // 'admin'ガードで認証に失敗していることを確認
        $this->assertGuest('admin');
    }

    /**
     * @test
     * 一般ユーザーは管理者ログイン画面からログインできない
     */
    public function a_general_user_cannot_authenticate_as_admin()
    {
        // 'role'が'general'の一般ユーザーを作成
        $generalUser = User::factory()->create(['role' => 'general']);

        $this->post('/admin/login', [
            'email' => $generalUser->email,
            'password' => 'password',
        ]);

        // 'admin'ガードで認証に失敗していることを確認
        $this->assertGuest('admin');
    }

    /**
     * @test
     * 登録済みの管理者が正常にログインできる
     */
    public function an_existing_admin_can_authenticate()
    {
        // 'role'が'admin'の管理者ユーザーを作成
        $admin = User::factory()->create(['role' => 'admin']);

        // ログインリクエストを送信
        $response = $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'password', // UserFactoryのデフォルトパスワードは 'password'
        ]);

        // 'admin'ガードで認証に成功したことを確認
        $this->assertAuthenticatedAs($admin, 'admin');

        // 管理者用の勤怠一覧画面にリダイレクトされることを確認
        $response->assertRedirect('/admin/attendances');
    }
}
