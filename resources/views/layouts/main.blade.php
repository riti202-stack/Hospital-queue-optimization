<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
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

        /* ---------- Navbar ---------- */
        .navbar-glass {
            background: rgba(8, 80, 65, 0.92);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255,255,255,0.12);
            padding: .55rem 0;
        }
        .navbar-glass .navbar-brand {
            color: #fff; font-weight: 700; font-size: 1.15rem;
            display: flex; align-items: center; gap: .5rem; white-space: nowrap;
        }
        .brand-icon {
            width: 34px; height: 34px; border-radius: 10px; background: rgba(255,255,255,.14);
            display: inline-flex; align-items: center; justify-content: center; font-size: 1.15rem;
        }
        .navbar-glass .nav-link {
            color: rgba(255,255,255,.85) !important; font-weight: 500; font-size: .95rem;
            padding: .45rem .75rem !important; border-radius: 8px; white-space: nowrap;
        }
        .navbar-glass .nav-link:hover { color: #fff !important; background: rgba(255,255,255,.08); }
        .navbar-glass .nav-link.active { color: #fff !important; background: rgba(255,255,255,.16); }
        .navbar-glass .dropdown-menu { border: none; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,.18); padding: .4rem; }
        .navbar-glass .dropdown-item { border-radius: 8px; padding: .45rem .75rem; }
        .navbar-glass .dropdown-item.active, .navbar-glass .dropdown-item:active { background: var(--brand); }
        .lang-btn {
            color: #fff; border: 1px solid rgba(255,255,255,.45); border-radius: 999px;
            padding: .2rem .7rem; font-size: .85rem; text-decoration: none; white-space: nowrap;
        }
        .lang-btn:hover { background: rgba(255,255,255,.12); color: #fff; }
        .avatar {
            width: 34px; height: 34px; border-radius: 50%; background: #9fe1cb; color: #04342c;
            display: inline-flex; align-items: center; justify-content: center; font-weight: 700;
        }
        .user-toggle { color: #fff; text-decoration: none; display: flex; align-items: center; gap: .5rem; }
        .user-toggle:hover { color: #fff; }
        .navbar-glass .navbar-toggler { border-color: rgba(255,255,255,.4); }
        .navbar-glass .navbar-toggler-icon { filter: invert(1); }

        /* ---------- Shared ---------- */
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
        /* Filled stars without the separate "filled" icon font */
        .ti-star-filled::before, .ti-star-half-filled::before { content: "\2605"; font-family: system-ui, sans-serif; }
    </style>
</head>

<body>
@php
    $link = fn ($route, $label, $icon) =>
        '<li class="nav-item"><a href="' . route($route) . '" class="nav-link' . (request()->routeIs($route, $route . '.*') ? ' active' : '') . '">'
        . '<i class="ti ' . $icon . ' me-1"></i>' . e($label) . '</a></li>';
    $item = fn ($route, $label, $icon) =>
        '<li><a class="dropdown-item' . (request()->routeIs($route, $route . '.*') ? ' active' : '') . '" href="' . route($route) . '">'
        . '<i class="ti ' . $icon . ' me-2"></i>' . e($label) . '</a></li>';
    $user = auth()->user();
@endphp

<nav class="navbar navbar-expand-xl navbar-glass mb-4">
    <div class="container">
        <a class="navbar-brand" href="{{ route('dashboard') }}">
            <span class="brand-icon"><i class="ti ti-building-hospital"></i></span> Hospital Queue
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-label="Menu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav mx-auto gap-xl-1">
                @auth
                    {{-- ================= ADMIN ================= --}}
                    @if($user->role === 'admin')
                        {!! $link('departments.index', 'Departments', 'ti-building') !!}
                        {!! $link('doctors.index', 'Doctors', 'ti-stethoscope') !!}
                        {!! $link('users.index', 'Users', 'ti-users') !!}
                        {!! $link('appointments.index', 'Appointments', 'ti-calendar-event') !!}
                        {!! $link('queue-entries.index', 'Live Queue', 'ti-list-numbers') !!}
                        @php $pendingTests = \App\Models\TestRequest::whereIn('status', ['pending', 'accepted'])->count(); @endphp
                        <li class="nav-item">
                            <a href="{{ route('admin.test-requests.index') }}" class="nav-link {{ request()->routeIs('admin.test-requests.*') ? 'active' : '' }}">
                                <i class="ti ti-flask me-1"></i>Lab Requests
                                @if($pendingTests)<span class="badge rounded-pill bg-danger ms-1">{{ $pendingTests }}</span>@endif
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown"><i class="ti ti-dots me-1"></i>More</a>
                            <ul class="dropdown-menu">
                                {!! $item('admin.reviews.index', 'Reviews', 'ti-star') !!}
                                {!! $item('queue-logs.index', 'Queue Logs', 'ti-history') !!}
                                <li><hr class="dropdown-divider"></li>
                                {!! $item('admin.appointment-pdf.form', 'Export appointments PDF', 'ti-file-type-pdf') !!}
                                {!! $item('admin.doctor-report-pdf.form', 'Doctor report', 'ti-report') !!}
                                {!! $item('admin.doctor-profile-pdf.form', 'Doctor profile PDF', 'ti-id') !!}
                            </ul>
                        </li>
                    @endif

                    {{-- ================= DOCTOR ================= --}}
                    @if($user->role === 'doctor')
                        {!! $link('doctor.queue', 'My Queue', 'ti-list-numbers') !!}
                        {!! $link('doctor.appointments', 'Appointments', 'ti-calendar-event') !!}
                        {!! $link('doctor.lab-reports', 'Lab Reports', 'ti-file-certificate') !!}
                        {!! $link('doctor.reviews', 'My Ratings', 'ti-star') !!}
                        {!! $link('doctor.availability', 'Availability', 'ti-toggle-right') !!}
                    @endif

                    {{-- ================= PATIENT ================= --}}
                    @if($user->role === 'patient')
                        {!! $link('patient.home', __('Home'), 'ti-home') !!}
                        {!! $link('patient.book', __('Book'), 'ti-calendar-plus') !!}
                        {!! $link('patient.checkin', __('Check In'), 'ti-user-check') !!}
                        {!! $link('patient.appointments', __('Appointments'), 'ti-calendar-event') !!}
                        {!! $link('patient.queue-status', __('My Queue'), 'ti-list-numbers') !!}
                        {!! $link('patient.tests', __('Lab Tests'), 'ti-flask') !!}
                        {!! $link('patient.records', __('Records'), 'ti-notes') !!}
                    @endif
                @endauth
            </ul>

            <div class="d-flex align-items-center gap-3 mt-3 mt-xl-0">
                <a href="{{ route('lang.switch', app()->getLocale() === 'bn' ? 'en' : 'bn') }}" class="lang-btn">
                    <i class="ti ti-language me-1"></i>{{ app()->getLocale() === 'bn' ? 'English' : 'বাংলা' }}
                </a>

                @auth
                    <div class="dropdown">
                        <a href="#" class="user-toggle dropdown-toggle" data-bs-toggle="dropdown">
                            <span class="avatar">{{ strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                            <span class="d-none d-xxl-inline">{{ \Illuminate\Support\Str::of($user->name)->before(' ') }}</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li class="px-3 py-2">
                                <div class="fw-semibold">{{ $user->name }}</div>
                                <small class="text-muted text-capitalize">{{ $user->role }}</small>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            @if($user->role === 'patient')
                                {!! $item('patient.family.index', __('Family'), 'ti-users-group') !!}
                                {!! $item('patient.settings', __('Settings'), 'ti-settings') !!}
                            @endif
                            {!! $item('profile.edit', __('Profile'), 'ti-user') !!}
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger"><i class="ti ti-logout me-2"></i>{{ __('Log out') }}</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="nav-link text-white">Login</a>
                    <a href="{{ route('register') }}" class="btn btn-sm btn-light">Register</a>
                @endauth
            </div>
        </div>
    </div>
</nav>

<div class="container pb-5">
    @yield('content')
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

@include('partials.footer')

@auth
    @if(auth()->user()->role === 'patient')
        @include('partials.call-alert')
    @endif
@endauth
</body>
</html>