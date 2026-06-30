<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#4f46e5">
        <meta name="description" content="Sistem Host-to-Host (H2H) CEISA 4.0 Bea Cukai — pengelolaan dokumen kepabeanan impor & ekspor PT Mora Multi Berkah.">

        <title>{{ isset($title) ? $title.' · '.config('app.name') : config('app.name', 'CEISA H2H') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased relative min-h-screen overflow-x-hidden bg-slate-50">
        <!-- Premium Floating Mesh Gradient Blobs -->
        <div class="fixed -top-40 -left-40 w-[600px] h-[600px] rounded-full bg-indigo-500/22 blur-[130px] pointer-events-none animate-[pulse_12s_infinite_alternate]"></div>
        <div class="fixed top-1/4 -right-40 w-[550px] h-[550px] rounded-full bg-pink-500/18 blur-[120px] pointer-events-none animate-[pulse_10s_infinite_alternate] delay-[3s]"></div>
        <div class="fixed -bottom-40 left-1/4 w-[600px] h-[600px] rounded-full bg-teal-500/15 blur-[140px] pointer-events-none animate-[pulse_15s_infinite_alternate] delay-[1s]"></div>
        <div class="fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[550px] h-[550px] rounded-full bg-amber-500/12 blur-[130px] pointer-events-none animate-[pulse_18s_infinite_alternate] delay-[2s]"></div>

        <div class="min-h-screen bg-gradient-to-br from-indigo-50/50 via-slate-50/70 to-rose-50/50 text-slate-800 antialiased relative z-10">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white/50 backdrop-blur-lg border-b border-slate-200/50 shadow-sm relative z-20">
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
