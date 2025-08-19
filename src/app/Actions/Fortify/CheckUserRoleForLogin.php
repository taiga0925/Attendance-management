<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CheckUserRoleForLogin
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // 認証が成功した場合のみ、このアクションが実行される
        $user = Auth::user();
        if (!$user) {
            return $next($request);
        }

        // ログイン画面のルートとユーザーの役割が一致するかを厳密にチェック
        if ($request->routeIs('admin.login') && !$user->isAdmin()) {
            Auth::logout(); // 役割が一致しない場合はログアウトさせる
            throw ValidationException::withMessages([
                'email' => __('ログイン情報が登録されていません。'),
            ]);
        }

        if ($request->routeIs('login') && !$user->isGeneral()) {
            Auth::logout(); // 役割が一致しない場合はログアウトさせる
            throw ValidationException::withMessages([
                'email' => __('ログイン情報が登録されていません。'),
            ]);
        }

        return $next($request);
    }
}
