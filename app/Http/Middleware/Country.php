<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Cache;

class Country
{
    /**
     * Handle an incoming request.
     *
     */
    public function handle(Request $request, Closure $next)
    {
        if (!$request->session()->has('country')) {
            $ip = $request->ip(); // Simplified IP detection
            
            $country = Cache::remember("ip_country_{$ip}", 86400, function() use ($ip) {
                try {
                    $response = \Illuminate\Support\Facades\Http::timeout(3)->get("http://www.geoplugin.net/json.gp?ip=" . $ip);
                    if ($response->successful()) {
                        $data = $response->json();
                        return $data['geoplugin_countryCode'] ?? null;
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning("GeoPlugin lookup failed for IP {$ip}: " . $e->getMessage());
                }
                return null;
            }) ?: 'AE';

            $request->session()->put('country', $country);
        }
        return $next($request);
    }
}
