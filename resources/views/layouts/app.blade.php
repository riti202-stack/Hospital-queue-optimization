<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Hospital Queue System') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            min-height: 100vh;
            font-family: 'figtree', system-ui, sans-serif;
            background: linear-gradient(rgba(4,52,44,0.75), rgba(4,52,44,0.85)),
                        url('https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?auto=format&fit=crop&w=1600&q=80') center/cover fixed no-repeat;
        }
        .page-card {
            background: rgba(255,255,255,0.92);
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.25);
            backdrop-filter: blur(6px);
        }
        .dash-card {
            border: none;
            border-radius: 14px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
            transition: transform .15s ease, box-shadow .15s ease;
            text-decoration: none;
            color: inherit;
        }
        .dash-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,0.15); color: inherit; }
        .dash-card i { font-size: 28px; color: #0f6e56; }
    </style>
</head>
<body>
    <div class="min-h-screen">
        @include('layouts.navigation')

        @isset($header)
            <header class="pt-4">
                <div class="max-w-7xl mx-auto px-4">
                    {{ $header }}
                </div>
            </header>
        @endisset

        <main class="py-4">
            {{ $slot }}
        </main>
    </div>
</body>
</html>