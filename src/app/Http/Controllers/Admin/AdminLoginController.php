<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Providers\RouteServiceProvider;

class AdminLoginController extends Controller
{
    /**
     * 管理者ログインを処理
     */
    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required']
        ]);

        if (Auth::guard('admin')->attempt($credentials)) {

            // 認証したユーザーが本当に管理者かを確認
            $user = Auth::guard('admin')->user();
            if ($user->isAdmin()) {
                $request->session()->regenerate();
                // ログイン後のリダイレクト先（例: /admin/dashboard）
                return redirect()->intended(RouteServiceProvider::ADMIN_HOME);
            }

            // 管理者でなければログアウトさせる
            Auth::guard('admin')->logout();
        }

        // 認証失敗
        throw ValidationException::withMessages([
            'email' => __('ログイン情報が登録されていないか、管理者権限がありません。'),
        ]);
    }

    /**
     * 管理者ログアウトを処理
     */
    public function destroy(Request $request)
    {

        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
