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
use App\Actions\Fortify\CheckUserRoleForLogin;
use Laravel\Fortify\Actions\AttemptToAuthenticate;
use Laravel\Fortify\Actions\PrepareAuthenticatedSession;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Actions\EnsureLoginIsNotThrottled;
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
            return Limit::perMinute(5)->by($request->input('email').$request->ip());
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

        // カスタム認証パイプラインを定義
        Fortify::authenticateThrough(function (Request $request) {
            return[
                AttemptToAuthenticate::class, // 認証情報の検証
                CheckUserRoleForLogin::class, // カスタムの役割チェック
                PrepareAuthenticatedSession::class, // 認証後のセッション準備 
            ];
        });
    }
}
