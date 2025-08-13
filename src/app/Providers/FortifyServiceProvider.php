<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Http\Responses\LoginSuccessfulResponse;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Fortify\Fortify;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Contracts\LogoutResponse;
use Illuminate\Support\Facades\URL;
use App\Http\Requests\LoginRequest;
use App\Http\Responses\LoginFailedResponse;
use Laravel\Fortify\Contracts\FailedLoginResponse as FailedLoginResponseContract;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Http\Requests\LoginRequest as FortifyLoginRequest;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Fortifyのログアウト後のリダイレクトをカスタマイズ
        $this->app->instance(LogoutResponse::class, new class implements LogoutResponse {
            public function toResponse($request)
            {
                $previousUrl = URL::previous();

                if (str_starts_with($previousUrl, url('/admin'))) {
                    return redirect(route('admin.login'));
                }

                return redirect(route('login'));
            }
        });

        // Fortifyの認証失敗レスポンスをカスタムクラスでオーバーライド
        $this->app->singleton(FailedLoginResponseContract::class, LoginFailedResponse::class);

        // FortifyにカスタムLoginRequestを使用するように指示
        $this->app->singleton(FortifyLoginRequest::class, LoginRequest::class);

        // 認証成功レスポンスをカスタムクラスでオーバーライド
        $this->app->singleton(LoginResponseContract::class, LoginSuccessfulResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->input('email') . $request->ip());
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        Fortify::loginView(function () {
            return view('auth.login');
        });

        Fortify::registerView(function () {
            return view('auth.register');
        });

        Fortify::requestPasswordResetLinkView(function () {
            return view('auth.forgot-password');
        });

        Fortify::resetPasswordView(function (Request $request) {
            return view('auth.reset-password', ['request' => $request]);
        });

        // 修正点: Fortify::authenticateUsing を使用して認証ロジックを厳密に制御
        Fortify::authenticateUsing(function (Request $request) {
            // ユーザーをメールアドレスで検索
            $user = User::where('email', $request->email)->first();

            // ユーザーが存在し、パスワードが正しいかを確認
            if (!$user || !Hash::check($request->password, $user->password)) {
                // 認証情報が間違っている場合は、認証失敗
                return null;
            }

            // ユーザーの役割とログイン画面のルートが一致するかを確認
            if ($request->routeIs('admin.login')) {
                // 管理者ログイン画面からのリクエストの場合、ユーザーが管理者であることを確認
                return $user->isAdmin() ? $user : null;
            }

            if ($request->routeIs('login')) {
                // 一般ユーザーログイン画面からのリクエストの場合、ユーザーが一般ユーザーであることを確認
                return $user->isGeneral() ? $user : null;
            }

            // 上記のいずれの条件にも一致しない場合は認証失敗
            return null;
        });
    }
}
