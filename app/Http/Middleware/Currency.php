<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class Currency
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (Session::has('appcurrency')) {
            $currency = Session::get('appcurrency');
        } else if (request('appcurrency')) {
            $currency = request('appcurrency');
        } else {
            $currency = 'LBP';
        }

        if ($request->hasHeader('Accept-Currency')) {
            if(in_array($request->header('Accept-Currency'), $this->getCurrenciesCodes())){
                $currency = $request->header('Accept-Currency');
            }
        }

        // set app currency
        Session::put('appcurrency', $currency);

        return $next($request);
    }

    public function getCurrenciesCodes(): array
    {
        return ['LBP', 'AED'];
    }
}
