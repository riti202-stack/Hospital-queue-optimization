<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetLocale
{
    public const SUPPORTED = ['en', 'bn'];

    public function handle(Request $request, Closure $next)
    {
        $locale = session('locale') ?? $request->user()?->locale ?? config('app.locale');
        if (!in_array($locale, self::SUPPORTED, true)) {
            $locale = 'en';
        }

        App::setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }
}