<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}"
      dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('errors.403_title') }} — {{ config('app.name') }}</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { font-family: system-ui, -apple-system, sans-serif; min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #f9fafb; margin: 0; }
        .card { text-align: center; max-width: 420px; padding: 2.5rem 2rem; }
        .code { font-size: 4.5rem; font-weight: 700; color: #1f2937; line-height: 1; margin: 0; }
        .title { font-size: 1.25rem; font-weight: 600; color: #374151; margin: 0.5rem 0 0; }
        .message { color: #6b7280; margin: 1rem 0 1.75rem; }
        .back { display: inline-block; padding: 0.5rem 1.5rem; background: #2563eb; color: #fff; text-decoration: none; border-radius: 6px; font-size: 0.875rem; font-weight: 500; }
        .back:hover { background: #1d4ed8; }
    </style>
</head>
<body>
    <div class="card">
        <p class="code">403</p>
        <p class="title">{{ __('errors.403_title') }}</p>
        <p class="message">{{ __('errors.403_message') }}</p>
        <a href="{{ url()->previous(url('/')) }}" class="back">{{ __('errors.403_back') }}</a>
    </div>
</body>
</html>
