<?php

namespace App\Http\Responses;

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        $user = Auth::user();

        if ($user && $user->role_id == User::ROLE_CLIENT) {
            return redirect('/');
        }

        return redirect()->intended(RouteServiceProvider::HOME);
    }
}
