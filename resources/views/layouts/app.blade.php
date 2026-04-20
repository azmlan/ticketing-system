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
<body class="min-h-screen bg-gray-100">

<div x-data="{ open: true }" class="flex min-h-screen">

    {{-- ── Sidebar ──────────────────────────────────────────────────────── --}}
    <aside
        x-show="open"
        class="w-64 flex-shrink-0 bg-gray-900 text-white flex flex-col"
        style="display: flex;"
    >
        {{-- Brand --}}
        <div class="px-4 py-5 border-b border-gray-700">
            <span class="text-lg font-bold tracking-wide">{{ config('app.name') }}</span>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 py-4 space-y-1" aria-label="{{ __('layout.nav_profile') }}">

            <a href="{{ route('profile') }}"
               class="flex items-center gap-3 px-4 py-2 text-sm hover:bg-gray-700 rounded-md mx-2">
                {{-- Person icon --}}
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/>
                </svg>
                {{ __('layout.nav_profile') }}
            </a>

            @permission('user.promote')
            <a href="{{ route('promote') }}"
               class="flex items-center gap-3 px-4 py-2 text-sm hover:bg-gray-700 rounded-md mx-2">
                {{-- Arrow up icon --}}
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5 12 3m0 0 7.5 7.5M12 3v18"/>
                </svg>
                {{ __('layout.nav_promote') }}
            </a>
            @endpermission

            @permission('ticket.view-all')
            <a href="#"
               class="flex items-center gap-3 px-4 py-2 text-sm hover:bg-gray-700 rounded-md mx-2">
                {{-- Ticket icon --}}
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 0 1 0 5.198v3.031c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.03a3 3 0 0 1 0-5.2V6.375c0-.621-.504-1.125-1.125-1.125H3.375Z"/>
                </svg>
                {{ __('layout.nav_all_tickets') }}
            </a>
            @endpermission

        </nav>
    </aside>

    {{-- ── Main area ────────────────────────────────────────────────────── --}}
    <div class="flex-1 flex flex-col min-w-0">

        {{-- Top bar --}}
        <header class="bg-white shadow-sm">
            <div class="flex items-center justify-between px-4 py-3">

                {{-- Sidebar toggle --}}
                <button @click="open = !open"
                        class="p-1 rounded text-gray-500 hover:bg-gray-100"
                        aria-label="{{ __('layout.toggle_sidebar') }}">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                    </svg>
                </button>

                {{-- Right-side controls --}}
                <div class="flex items-center gap-4">

                    {{-- Language switcher --}}
                    @if(app()->getLocale() === 'ar')
                        <form method="POST" action="{{ route('locale.toggle', 'en') }}">
                            @csrf
                            <button type="submit" class="text-sm text-gray-600 hover:text-gray-900">
                                {{ __('layout.switch_to_english') }}
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('locale.toggle', 'ar') }}">
                            @csrf
                            <button type="submit" class="text-sm text-gray-600 hover:text-gray-900">
                                {{ __('layout.switch_to_arabic') }}
                            </button>
                        </form>
                    @endif

                    {{-- User name + profile link --}}
                    <a href="{{ route('profile') }}"
                       class="text-sm font-medium text-gray-700 hover:text-gray-900">
                        {{ auth()->user()->full_name }}
                    </a>

                    {{-- Logout --}}
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="text-sm text-gray-500 hover:text-red-600">
                            {{ __('layout.logout') }}
                        </button>
                    </form>

                </div>
            </div>
        </header>

        {{-- Page content --}}
        <main class="flex-1 p-6">
            {{ $slot }}
        </main>

    </div>
</div>

@livewireScripts
</body>
</html>
