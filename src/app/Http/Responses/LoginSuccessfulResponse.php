<?php

namespace App\Http\Responses;

use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use App\Models\User;
use App\Providers\RouteServiceProvider;

class LoginSuccessfulResponse implements LoginResponseContract
{
    /**
     * Create an HTTP response that represents the object's result.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        // ログイン後のリダイレクトをカスタマイズ
        $user = Auth::user();

        if ($user && $user->isAdmin()) {
            return redirect()->intended(RouteServiceProvider::ADMIN_HOME);
        }

        return redirect()->intended(RouteServiceProvider::HOME);
    }
}
