<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;

// ★★★ 以下の3つのuse宣言を追加してください ★★★
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);


        // 一般ユーザー用のログイン認証ロジックをカスタマイズ
        Fortify::authenticateUsing(function (Request $request) {
            // メールアドレスでユーザーを検索
            $user = User::where('email', $request->email)->first();

            // ユーザーが存在し、パスワードが正しく、かつ「一般ユーザー」であるかを確認
            if (
                $user &&
                Hash::check($request->password, $user->password) &&
                $user->isGeneral()
            ) { // Userモデルに以前作成したisGeneral()メソッドを利用
                return $user;
            }

            // 条件に合わない場合は認証を失敗させる
            return null;
        });


        // 以下はFortifyのデフォルト設定
        Fortify::registerView(function () {
            return view('auth.register');
        });

        Fortify::loginView(function () {
            return view('auth.login');
        });

        // メール認証案内ページのビューを指定
        Fortify::verifyEmailView(function () {
            return view('auth.verify-email');
        });

        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->email;

            return Limit::perMinute(10)->by($email . $request->ip());
        });
    }
}
