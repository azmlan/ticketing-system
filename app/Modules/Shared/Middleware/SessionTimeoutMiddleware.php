<?php

namespace App\Modules\Shared\Middleware;

use App\Modules\Admin\Models\AppSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;

class SessionTimeoutMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $hours = (int) (AppSetting::get('session_timeout_hours') ?? 8);
        } catch (\Throwable) {
            $hours = 8;
        }

        Config::set('session.lifetime', max(1, $hours) * 60);

        return $next($request);
    }
}
