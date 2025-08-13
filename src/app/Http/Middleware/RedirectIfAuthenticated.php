<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string|null  ...$guards
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, ...$guards)
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                // ログインしているユーザーの役割をチェック
                $user = Auth::guard($guard)->user();
                if ($user->isAdmin()) {
                    return redirect(RouteServiceProvider::ADMIN_HOME); // 管理者ホームへリダイレクト
                } else {
                    return redirect(RouteServiceProvider::HOME); // 一般ユーザーホームへリダイレクト
                }
            }
        }

        return $next($request);
    }
}
