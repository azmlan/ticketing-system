<!DOCTYPE html>
<html lang="{{ $lang ?? config('app.locale', 'ar') }}"
      dir="{{ $dir ?? (config('app.locale', 'ar') === 'ar' ? 'rtl' : 'ltr') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Arabic:wght@400;500;600;700&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    @if(!app()->environment('testing'))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    @livewireStyles
</head>
<body class="min-h-screen bg-surface-base flex items-center justify-center p-6">

    <div class="w-full max-w-md">

        {{-- Brand header --}}
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-10 h-10 rounded bg-primary-500 mb-4">
                <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 0 1 0 5.198v3.031c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.03a3 3 0 0 1 0-5.2V6.375c0-.621-.504-1.125-1.125-1.125H3.375Z"/>
                </svg>
            </div>
            <h2 class="text-lg font-semibold text-text-base">{{ config('app.name') }}</h2>
        </div>

        {{-- Card --}}
        <main class="bg-surface border border-border rounded shadow-sm px-8 py-8">
            {{ $slot }}
        </main>

    </div>

    @livewireScripts
</body>
</html>
