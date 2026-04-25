<!DOCTYPE html>
<html lang="{{ $lang ?? app()->getLocale() }}"
      dir="{{ $dir ?? (app()->getLocale() === 'ar' ? 'rtl' : 'ltr') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('admin.admin_panel') }} — {{ config('app.name') }}</title>
    @if(!app()->environment('testing'))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    @livewireStyles
</head>
<body class="min-h-screen bg-gray-100">

<div x-data="{ open: true }" class="flex min-h-screen">

    {{-- ── Sidebar ──────────────────────────────────────────────────────────── --}}
    <aside
        x-show="open"
        class="w-64 flex-shrink-0 bg-gray-900 text-white flex flex-col"
        style="display: flex;"
    >
        {{-- Brand --}}
        <div class="px-4 py-5 border-b border-gray-700">
            <a href="{{ route('admin.categories.index') }}" class="text-lg font-bold tracking-wide hover:text-gray-200">
                {{ __('admin.admin_panel') }}
            </a>
            <p class="text-xs text-gray-400 mt-1">{{ config('app.name') }}</p>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 py-4 space-y-1 overflow-y-auto" aria-label="{{ __('admin.admin_panel') }}">

            @permission('category.manage')
            <a href="{{ route('admin.categories.index') }}"
               class="flex items-center gap-3 px-4 py-2 text-sm hover:bg-gray-700 rounded-md mx-2
                      {{ request()->routeIs('admin.categories*') ? 'bg-gray-700' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z"/>
                </svg>
                {{ __('admin.nav_categories') }}
            </a>
            @endpermission

            @permission('group.manage')
            <a href="{{ route('admin.groups.index') }}"
               class="flex items-center gap-3 px-4 py-2 text-sm hover:bg-gray-700 rounded-md mx-2
                      {{ request()->routeIs('admin.groups*') ? 'bg-gray-700' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z"/>
                </svg>
                {{ __('admin.nav_groups') }}
            </a>
            @endpermission

            @permission('system.manage-custom-fields')
            <a href="{{ route('admin.custom-fields.index') }}"
               class="flex items-center gap-3 px-4 py-2 text-sm hover:bg-gray-700 rounded-md mx-2
                      {{ request()->routeIs('admin.custom-fields*') ? 'bg-gray-700' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 0 0-1.883 2.542l.857 6a2.25 2.25 0 0 0 2.227 1.932H19.05a2.25 2.25 0 0 0 2.227-1.932l.857-6a2.25 2.25 0 0 0-1.883-2.542m-16.5 0V6A2.25 2.25 0 0 1 6 3.75h3.879a1.5 1.5 0 0 1 1.06.44l2.122 2.12a1.5 1.5 0 0 0 1.06.44H18A2.25 2.25 0 0 1 20.25 9v.776"/>
                </svg>
                {{ __('admin.nav_custom_fields') }}
            </a>
            @endpermission

            @permission('system.manage-sla')
            <a href="{{ route('admin.sla.settings') }}"
               class="flex items-center gap-3 px-4 py-2 text-sm hover:bg-gray-700 rounded-md mx-2
                      {{ request()->routeIs('admin.sla*') ? 'bg-gray-700' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
                {{ __('admin.nav_sla') }}
            </a>
            @endpermission

            @permission('system.manage-tags')
            <a href="{{ route('admin.tags.index') }}"
               class="flex items-center gap-3 px-4 py-2 text-sm hover:bg-gray-700 rounded-md mx-2
                      {{ request()->routeIs('admin.tags*') ? 'bg-gray-700' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z"/>
                </svg>
                {{ __('admin.nav_tags') }}
            </a>
            @endpermission

            @permission('system.manage-response-templates')
            <a href="{{ route('admin.response-templates.index') }}"
               class="flex items-center gap-3 px-4 py-2 text-sm hover:bg-gray-700 rounded-md mx-2
                      {{ request()->routeIs('admin.response-templates*') ? 'bg-gray-700' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                </svg>
                {{ __('admin.nav_response_templates') }}
            </a>
            @endpermission

            @permission('system.manage-departments')
            <a href="{{ route('admin.departments.index') }}"
               class="flex items-center gap-3 px-4 py-2 text-sm hover:bg-gray-700 rounded-md mx-2
                      {{ request()->routeIs('admin.departments*') ? 'bg-gray-700' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/>
                </svg>
                {{ __('admin.nav_departments') }}
            </a>
            @endpermission

            @permission('system.manage-locations')
            <a href="{{ route('admin.locations.index') }}"
               class="flex items-center gap-3 px-4 py-2 text-sm hover:bg-gray-700 rounded-md mx-2
                      {{ request()->routeIs('admin.locations*') ? 'bg-gray-700' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/>
                </svg>
                {{ __('admin.nav_locations') }}
            </a>
            @endpermission

            @permission('user.promote')
            <a href="#"
               class="flex items-center gap-3 px-4 py-2 text-sm hover:bg-gray-700 rounded-md mx-2">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/>
                </svg>
                {{ __('admin.nav_users') }}
            </a>
            @endpermission

            @permission('system.manage-notifications')
            <a href="#"
               class="flex items-center gap-3 px-4 py-2 text-sm hover:bg-gray-700 rounded-md mx-2">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/>
                </svg>
                {{ __('admin.nav_notifications') }}
            </a>
            @endpermission

            @if(auth()->user()?->is_super_user)
            <a href="#"
               class="flex items-center gap-3 px-4 py-2 text-sm hover:bg-gray-700 rounded-md mx-2">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 0 0-5.78 1.128 2.25 2.25 0 0 1-2.4 2.245 4.5 4.5 0 0 0 8.4-2.245c0-.399-.078-.78-.22-1.128Zm0 0a15.998 15.998 0 0 0 3.388-1.62m-5.043-.025a15.994 15.994 0 0 1 1.622-3.395m3.42 3.42a15.995 15.995 0 0 0 4.764-4.648l3.876-5.814a1.151 1.151 0 0 0-1.597-1.597L14.146 6.32a15.996 15.996 0 0 0-4.649 4.763m3.42 3.42a6.776 6.776 0 0 0-3.42-3.42"/>
                </svg>
                {{ __('admin.nav_branding') }}
            </a>
            @endif

        </nav>

        {{-- Back to app --}}
        <div class="p-4 border-t border-gray-700">
            <a href="{{ route('profile') }}"
               class="flex items-center gap-2 text-xs text-gray-400 hover:text-gray-200">
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                </svg>
                {{ __('layout.nav_profile') }}
            </a>
        </div>
    </aside>

    {{-- ── Main area ─────────────────────────────────────────────────────────── --}}
    <div class="flex-1 flex flex-col min-w-0">

        {{-- Top bar --}}
        <header class="bg-white shadow-sm">
            <div class="flex items-center justify-between px-4 py-3">

                <button @click="open = !open"
                        class="p-1 rounded text-gray-500 hover:bg-gray-100"
                        aria-label="{{ __('layout.toggle_sidebar') }}">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                    </svg>
                </button>

                <div class="flex items-center gap-4">

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

                    <span class="text-sm font-medium text-gray-700">
                        {{ auth()->user()->full_name }}
                    </span>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-sm text-gray-500 hover:text-red-600">
                            {{ __('layout.logout') }}
                        </button>
                    </form>

                </div>
            </div>
        </header>

        {{-- Flash messages --}}
        @if(session('success'))
            <div class="mx-6 mt-4 p-3 bg-green-50 border border-green-200 text-green-800 rounded-md text-sm">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mx-6 mt-4 p-3 bg-red-50 border border-red-200 text-red-800 rounded-md text-sm">
                {{ session('error') }}
            </div>
        @endif

        {{-- Page content --}}
        <main class="flex-1 p-6">
            {{ $slot }}
        </main>

    </div>
</div>

@livewireScripts
</body>
</html>
