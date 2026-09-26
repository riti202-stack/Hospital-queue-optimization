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
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'figtree', system-ui, sans-serif;
            background: linear-gradient(rgba(4,52,44,0.75), rgba(4,52,44,0.85)),
                        url('https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?auto=format&fit=crop&w=1600&q=80') center/cover fixed no-repeat;
        }
        .auth-card {
            background: rgba(255,255,255,0.92);
            border-radius: 18px;
            padding: 2.5rem;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 15px 45px rgba(0,0,0,0.3);
            backdrop-filter: blur(8px);
        }
        .auth-brand {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .auth-brand i {
            font-size: 42px;
            color: #0f6e56;
        }
        .auth-brand h1 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #04342C;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="auth-card">
        <a href="/" class="auth-brand d-block text-decoration-none">
            <i class="ti ti-building-hospital"></i>
            <h1>Hospital Queue System</h1>
        </a>

        {{ $slot }}
    </div>
</body>
</html>