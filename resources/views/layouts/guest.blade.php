<!DOCTYPE html>
<html lang="{{ $lang ?? config('app.locale', 'ar') }}"
      dir="{{ $dir ?? (config('app.locale', 'ar') === 'ar' ? 'rtl' : 'ltr') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }}</title>
    @if(!app()->environment('testing'))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    @livewireStyles
</head>
<body class="min-h-screen bg-gray-50 flex items-center justify-center">
    <main class="w-full max-w-md px-6 py-8">
        {{ $slot }}
    </main>
    @livewireScripts
</body>
</html>
