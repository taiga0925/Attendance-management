<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // 'admin'ガードで認証されているか、かつそのユーザーがisAdmin()メソッドでtrueを返すかを確認
        if (!Auth::guard('admin')->check() || !Auth::guard('admin')->user()->isAdmin()) {
            // 条件を満たさない場合は、管理者ログインページにリダイレクト
            return redirect()->route('admin.login')->with('error', '管理者権限がありません。');
        }

        return $next($request);
    }
}
