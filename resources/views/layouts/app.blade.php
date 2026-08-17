<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-slate-900">
        <div x-data="{ sidebarOpen: false }" class="min-h-screen bg-slate-50">
            <div x-cloak x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 z-30 bg-slate-900/50 lg:hidden"></div>
            <div :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-40 transition-transform duration-200 lg:translate-x-0">
                @include('layouts.sidebar')
            </div>
            <div class="lg:pl-72">
                @include('layouts.topbar')
                <main class="p-4 sm:p-6 lg:p-8"><div class="mx-auto max-w-7xl space-y-6"><x-flash-message />{{ $slot }}</div></main>
            </div>
        </div>
    </body>
</html>
