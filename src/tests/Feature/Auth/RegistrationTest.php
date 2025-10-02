<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RegistrationTest extends TestCase
{
    use RefreshDatabase; // 各テスト実行後にデータベースをリセットする

    /**
     * @test
     * 名前が未入力の場合、バリデーションメッセージが表示される
     */
    public function a_name_is_required()
    {
        $response = $this->post('/register', [
            'name' => '', // 名前を空にする
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('name');
    }

    /**
     * @test
     * メールアドレスが未入力の場合、バリデーションメッセージが表示される
     */
    public function an_email_is_required()
    {
        $response = $this->post('/register',);

        $response->assertSessionHasErrors('email');
    }

    /**
     * @test
     * パスワードが未入力の場合、バリデーションメッセージが表示される
     */
    public function a_password_is_required()
    {
        $response = $this->post('/register',);

        $response->assertSessionHasErrors('password');
    }

    /**
     * @test
     * パスワードが8文字未満の場合、バリデーションメッセージが表示される
     */
    public function password_must_be_at_least_8_characters()
    {
        $response = $this->post('/register',);

        $response->assertSessionHasErrors('password');
    }

    /**
     * @test
     * パスワードが一致しない場合、バリデーションメッセージが表示される
     */
    public function passwords_must_match()
    {
        $response = $this->post('/register',);

        $response->assertSessionHasErrors('password');
    }

    /**
     * @test
     * フォームに内容が入力されていた場合、データが正常に保存される
     */
    public function a_user_can_be_registered_successfully()
    {
        // テスト用のユーザーデータ
        $userData = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        // 会員登録リクエストを送信
        $response = $this->post('/register', $userData);

        // データベースにユーザーが作成されたかを確認
        $this->assertDatabaseHas('users', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
        ]);

        // 登録後はまずホーム画面（/attendance）にリダイレクトされることを確認
        $response->assertRedirect('/attendance');

        // 認証されていることを確認
        $this->assertAuthenticated();
    }
}
