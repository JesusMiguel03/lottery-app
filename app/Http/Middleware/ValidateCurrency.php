<?php

namespace App\Http\Middleware;

use App\Models\Currency;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateCurrency
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $currency_route = !$request->routeIs('filament.admin.resources.currencies.index');
        if ($request->routeIs('filament.admin.pages.home-dashboard') || $request->routeIs('filament.admin.resources.clients.*')) {
            return $next($request);
        }

        if ($currency_route && !Currency::whereDate('created_at', Carbon::today())->exists()) {
            return redirect(route('filament.admin.resources.currencies.index', ['required' => 'true']));
        }

        return $next($request);
    }
}
