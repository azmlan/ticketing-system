<?php

namespace App\Modules\Admin\Controllers;

use App\Modules\Admin\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;

class LogoController extends Controller
{
    public function show(Request $request): mixed
    {
        abort_unless($request->user(), 403);

        $path = AppSetting::get('logo_path');

        if (! $path || ! Storage::disk('local')->exists($path)) {
            abort(404);
        }

        $content = Storage::disk('local')->get($path);

        return response($content, 200, [
            'Content-Type'  => 'image/jpeg',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
