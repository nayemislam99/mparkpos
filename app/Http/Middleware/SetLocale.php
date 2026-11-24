<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->query('lang')
            ?? session('locale')
            ?? config('app.locale', 'en');

        if (in_array($locale, ['en', 'bn'], true)) {
            App::setLocale($locale);
            if (! session()->has('locale') || session('locale') !== $locale) {
                session(['locale' => $locale]);
            }
        }

        return $next($request);
    }
}
