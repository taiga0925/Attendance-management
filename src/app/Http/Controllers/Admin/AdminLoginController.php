<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use App\Providers\RouteServiceProvider;

class AdminLoginController extends Controller
{
    /**
     * 管理者ログインを処理する
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // ログインフォームのバリデーション
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required']
        ]);

        // ユーザーをメールアドレスで検索
        $user = User::where('email', $credentials['email'])->first();

        // ユーザーが存在し、パスワードが一致し、かつ管理者である場合のみ認証
        if (Auth::attempt($credentials) && $user && $user->isAdmin()) {
            $request->session()->regenerate();
            return redirect()->intended(RouteServiceProvider::ADMIN_HOME);
        }

        // 認証失敗
        throw ValidationException::withMessages([
            'email' => __('ログイン情報が登録されていません。'),
        ]);
    }

    /**
     * 管理者ログアウトを処理する
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login');
    }
}
