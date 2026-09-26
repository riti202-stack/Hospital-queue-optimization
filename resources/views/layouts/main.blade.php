<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Hospital Queue Optimization</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
    <style>
        :root {
            --brand: #0f6e56;
            --brand-dark: #085041;
            --glass-bg: rgba(255,255,255,0.85);
        }
        body {
            min-height: 100vh;
            background: linear-gradient(rgba(4,52,44,0.75), rgba(4,52,44,0.85)),
                        url('https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?auto=format&fit=crop&w=1600&q=80') center/cover fixed no-repeat;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        .navbar-glass {
            background: rgba(8, 80, 65, 0.85);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255,255,255,0.15);
        }
        .navbar-glass .nav-link, .navbar-glass .navbar-brand {
            color: #fff !important;
            font-weight: 500;
        }
        .navbar-glass .nav-link:hover { color: #9fe1cb !important; }
        .page-card {
            background: var(--glass-bg);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.25);
            backdrop-filter: blur(6px);
        }
        .entry-card {
            border: none;
            border-radius: 14px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
            transition: transform .15s ease, box-shadow .15s ease;
        }
        .entry-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,0.15); }
        .badge-emergency { background: #E24B4A; color: #fff; }
        .badge-urgent { background: #EF9F27; color: #412402; }
        .badge-routine { background: #9FE1CB; color: #04342C; }
        .btn-brand { background: var(--brand); color: #fff; border: none; }
        .btn-brand:hover { background: var(--brand-dark); color: #fff; }
        .live-dot {
            width: 8px; height: 8px; border-radius: 50%;
            background: #5DCAA5; display: inline-block; margin-right: 6px;
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(93,202,165,0.6); }
            70% { box-shadow: 0 0 0 8px rgba(93,202,165,0); }
            100% { box-shadow: 0 0 0 0 rgba(93,202,165,0); }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-glass mb-4">
        <div class="container">
            <a class="navbar-brand" href="{{ route('dashboard') }}"><i class="ti ti-building-hospital"></i> Hospital Queue System</a>
            <div class="d-flex flex-wrap">
                @auth
                    @if(auth()->user()->role === 'admin')
                        <a href="{{ route('departments.index') }}" class="nav-link d-inline-block me-3">Departments</a>
                        <a href="{{ route('users.index') }}" class="nav-link d-inline-block me-3">Users</a>
                        <a href="{{ route('doctors.index') }}" class="nav-link d-inline-block me-3">Doctors</a>
                        <a href="{{ route('appointments.index') }}" class="nav-link d-inline-block me-3">Appointments</a>
                        <a href="{{ route('queue-entries.index') }}" class="nav-link d-inline-block me-3">Queue</a>
                        <a href="{{ route('queue-logs.index') }}" class="nav-link d-inline-block me-3">Logs</a>
                    @endif
                    @if(auth()->user()->role === 'doctor')
                        <a href="{{ route('doctor.queue') }}" class="nav-link d-inline-block me-3">My Queue</a>
                        <a href="{{ route('doctor.availability') }}" class="nav-link d-inline-block me-3">Availability</a>
                        <a href="{{ route('doctor.appointments') }}" class="nav-link d-inline-block me-3">My Appointments</a>
                    @endif
                    @if(auth()->user()->role === 'patient')
                        <a href="#" class="nav-link d-inline-block me-3">Book Appointment</a>
                        <a href="#" class="nav-link d-inline-block me-3">Check In</a>
                        <a href="#" class="nav-link d-inline-block me-3">My Appointments</a>
                    @endif

                    
                    <a href="{{ route('profile.edit') }}" class="nav-link d-inline-block me-3">Profile</a>
                    <form action="{{ route('logout') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-light">Log out</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="nav-link d-inline-block me-3">Login</a>
                    <a href="{{ route('register') }}" class="nav-link d-inline-block">Register</a>
                @endauth
            </div>
        </div>
    </nav>

    <div class="container pb-5">
        @yield('content')
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>