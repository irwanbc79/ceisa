<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#0A1128">
        <meta name="description" content="Sistem Host-to-Host (H2H) CEISA 4.0 Bea Cukai — pengelolaan dokumen kepabeanan impor & ekspor PT Mora Multi Berkah.">

        <title>{{ isset($title) ? $title.' · '.config('app.name') : config('app.name', 'CEISA H2H') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800|fraunces:300,400,600,700|jetbrains-mono:400,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased relative min-h-screen overflow-x-hidden">
        <div class="min-h-screen text-slate-700 antialiased relative z-10">
            <!-- Cahaya dermaga: dua glow senja yang tenang (bukan mesh warna-warni) -->
            <div class="fixed -top-48 left-1/2 -translate-x-1/2 w-[900px] h-[420px] rounded-full bg-gold-500/[0.06] blur-[120px] pointer-events-none z-0" aria-hidden="true"></div>
            <div class="fixed -bottom-56 -right-40 w-[700px] h-[500px] rounded-full bg-sea-500/[0.07] blur-[140px] pointer-events-none z-0" aria-hidden="true"></div>
            <div class="fixed top-1/3 -left-56 w-[600px] h-[600px] rounded-full bg-indigo-500/[0.08] blur-[150px] pointer-events-none z-0" aria-hidden="true"></div>
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-panel/60 backdrop-blur-lg border-b border-slate-200/50 shadow-sm relative z-20">
                    <div class="max-w-7xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>

        @stack('scripts')
    </body>
</html>
