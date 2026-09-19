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

        <style>
            /* Glass Design Tokens */
            :root {
                --blur-px: 14px;
                --radius-lg: 20px;
                --radius-md: 14px;
                --glass-bg: rgba(255, 255, 255, 0.72);
                --glass-border: rgba(255, 255, 255, 0.55);
                --glass-shadow: 0 4px 16px rgba(15, 23, 42, 0.06);
            }
            .glass {
                background: var(--glass-bg);
                backdrop-filter: blur(var(--blur-px)) saturate(140%);
                -webkit-backdrop-filter: blur(var(--blur-px)) saturate(140%);
                border: 1px solid var(--glass-border);
                box-shadow: var(--glass-shadow);
            }
            .r-lg { border-radius: var(--radius-lg); }
        </style>
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>
        <!-- Fallback: load Alpine.js via CDN jika Vite build belum di-upload ke hosting -->
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.9/dist/cdn.min.js" crossorigin="anonymous"></script>
    </body>
</html>
