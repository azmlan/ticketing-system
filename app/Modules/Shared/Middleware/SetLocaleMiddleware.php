<?php

namespace App\Modules\Shared\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = Auth::check()
            ? Auth::user()->locale
            : config('app.locale', 'ar');

        App::setLocale($locale);

        $dir  = $locale === 'ar' ? 'rtl' : 'ltr';
        $lang = $locale;

        View::share('dir', $dir);
        View::share('lang', $lang);

        return $next($request);
    }
}
