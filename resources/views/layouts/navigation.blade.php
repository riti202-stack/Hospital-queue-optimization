<nav class="navbar navbar-expand-lg" style="background: rgba(8,80,65,0.85); backdrop-filter: blur(10px); border-bottom: 1px solid rgba(255,255,255,0.15);">
    <div class="container">
        <a class="navbar-brand text-white fw-semibold" href="{{ route('dashboard') }}">
            <i class="ti ti-building-hospital"></i> Hospital Queue System
        </a>
        <div class="d-flex flex-wrap align-items-center">
            @if(auth()->user()->role === 'admin')
                <a href="{{ route('departments.index') }}" class="nav-link d-inline-block text-white me-3">Departments</a>
                <a href="{{ route('users.index') }}" class="nav-link d-inline-block text-white me-3">Users</a>
                <a href="{{ route('doctors.index') }}" class="nav-link d-inline-block text-white me-3">Doctors</a>
                <a href="{{ route('appointments.index') }}" class="nav-link d-inline-block text-white me-3">Appointments</a>
                <a href="{{ route('queue-entries.index') }}" class="nav-link d-inline-block text-white me-3">Queue</a>
                <a href="{{ route('queue-logs.index') }}" class="nav-link d-inline-block text-white me-3">Logs</a>
            @endif
            @if(auth()->user()->role === 'doctor')
                <a href="{{ route('doctor.queue') }}" class="nav-link d-inline-block text-white me-3">My Queue</a>
                <a href="{{ route('doctor.availability') }}" class="nav-link d-inline-block text-white me-3">Availability</a>
                <a href="{{ route('doctor.appointments') }}" class="nav-link d-inline-block text-white me-3">My Appointments</a>
            @endif
            @if(auth()->user()->role === 'patient')
                <a href="{{ route('patient.book') }}" class="nav-link d-inline-block text-white me-3">Book Appointment</a>
                <a href="{{ route('patient.checkin') }}" class="nav-link d-inline-block text-white me-3">Check In</a>
                <a href="{{ route('patient.queue-status') }}" class="nav-link d-inline-block text-white me-3">Queue Status</a>
                <a href="{{ route('patient.appointments') }}" class="nav-link d-inline-block text-white me-3">My Appointments</a>
            @endif

            <div class="dropdown">
                <button class="btn btn-sm btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    {{ auth()->user()->name }}
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="{{ route('profile.edit') }}">Profile</a></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item">Log out</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>