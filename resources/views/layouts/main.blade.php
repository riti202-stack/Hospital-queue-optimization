<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Hospital Queue Optimization</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="{{ route('dashboard') }}">Hospital Queue System</a>
            <div>
                @auth
                    @if(auth()->user()->role === 'admin')
                        <a href="{{ route('departments.index') }}" class="text-white me-3">Departments</a>
                        <a href="{{ route('users.index') }}" class="text-white me-3">Users</a>
                        <a href="{{ route('doctors.index') }}" class="text-white me-3">Doctors</a>
                        <a href="{{ route('appointments.index') }}" class="text-white me-3">Appointments</a>
                        <a href="{{ route('queue-entries.index') }}" class="text-white me-3">Queue</a>
                        <a href="{{ route('queue-logs.index') }}" class="text-white me-3">Logs</a>
                    @endif

                    @if(auth()->user()->role === 'doctor')
                        <a href="{{ route('doctor.queue') }}" class="text-white me-3">My Queue</a>
                        <a href="{{ route('doctor.availability') }}" class="text-white me-3">Availability</a>
                        <a href="{{ route('doctor.appointments') }}" class="text-white me-3">My Appointments</a>
                    @endif

                    @if(auth()->user()->role === 'patient')
                        <a href="#" class="text-white me-3">Book Appointment</a>
                        <a href="#" class="text-white me-3">Check In</a>
                        <a href="#" class="text-white me-3">My Appointments</a>
                    @endif

                    <a href="{{ route('profile.edit') }}" class="text-white me-3">Profile</a>
                    <form action="{{ route('logout') }}" method="POST" style="display:inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-light">Log out</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="text-white me-3">Login</a>
                    <a href="{{ route('register') }}" class="text-white">Register</a>
                @endauth
            </div>
        </div>
    </nav>

    @yield('content')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>