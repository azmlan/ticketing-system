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
<body class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-indigo-100 flex items-center justify-center p-4">
    <div class="w-full max-w-md">

        {{-- Brand header --}}
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-blue-600 shadow-lg mb-4">
                <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 0 1 0 5.198v3.031c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.03a3 3 0 0 1 0-5.2V6.375c0-.621-.504-1.125-1.125-1.125H3.375Z"/>
                </svg>
            </div>
            <h2 class="text-xl font-bold text-gray-900">{{ config('app.name') }}</h2>
        </div>

        {{-- Card --}}
        <main class="bg-white rounded-2xl shadow-xl px-8 py-10">
            {{ $slot }}
        </main>

    </div>
    @livewireScripts
</body>
</html>
