<x-app-layout>
    <x-slot name="header">
        <h2 class="fw-semibold fs-4 text-white">Dashboard</h2>
    </x-slot>

    <div class="container">
        <div class="page-card p-4">
            <h3 class="mb-4">Welcome, {{ auth()->user()->name }}</h3>

            @if(auth()->user()->role === 'admin')
                <div class="row g-3">
                    <div class="col-md-4"><a href="{{ route('departments.index') }}" class="card dash-card p-4 h-100"><i class="ti ti-building-hospital mb-2"></i><div class="fw-semibold">Departments</div><p class="text-muted small mb-0">Manage hospital departments</p></a></div>
                    <div class="col-md-4"><a href="{{ route('doctors.index') }}" class="card dash-card p-4 h-100"><i class="ti ti-stethoscope mb-2"></i><div class="fw-semibold">Doctors</div><p class="text-muted small mb-0">Manage doctor profiles</p></a></div>
                    <div class="col-md-4"><a href="{{ route('users.index') }}" class="card dash-card p-4 h-100"><i class="ti ti-users mb-2"></i><div class="fw-semibold">Users</div><p class="text-muted small mb-0">Manage all accounts and roles</p></a></div>
                    <div class="col-md-4"><a href="{{ route('appointments.index') }}" class="card dash-card p-4 h-100"><i class="ti ti-calendar mb-2"></i><div class="fw-semibold">Appointments</div><p class="text-muted small mb-0">View and manage bookings</p></a></div>
                    <div class="col-md-4"><a href="{{ route('queue-entries.index') }}" class="card dash-card p-4 h-100"><i class="ti ti-list-details mb-2"></i><div class="fw-semibold">Live Queue</div><p class="text-muted small mb-0">See the full queue across departments</p></a></div>
                    <div class="col-md-4"><a href="{{ route('queue-logs.index') }}" class="card dash-card p-4 h-100"><i class="ti ti-history mb-2"></i><div class="fw-semibold">Queue Logs</div><p class="text-muted small mb-0">Crash-recovery audit trail</p></a></div>
                </div>
            @endif

            @if(auth()->user()->role === 'doctor')
                <div class="row g-3">
                    <div class="col-md-4"><a href="{{ route('doctor.queue') }}" class="card dash-card p-4 h-100"><i class="ti ti-list-details mb-2"></i><div class="fw-semibold">My Queue</div><p class="text-muted small mb-0">See who's waiting for you right now</p></a></div>
                    <div class="col-md-4"><a href="{{ route('doctor.availability') }}" class="card dash-card p-4 h-100"><i class="ti ti-toggle-right mb-2"></i><div class="fw-semibold">Availability</div><p class="text-muted small mb-0">Mark yourself available or unavailable</p></a></div>
                    <div class="col-md-4"><a href="{{ route('doctor.appointments') }}" class="card dash-card p-4 h-100"><i class="ti ti-calendar mb-2"></i><div class="fw-semibold">My Appointments</div><p class="text-muted small mb-0">View your upcoming schedule</p></a></div>
                </div>
            @endif

            @if(auth()->user()->role === 'patient')
                <div class="row g-3">
                    <div class="col-md-6"><a href="{{ route('patient.book') }}" class="card dash-card p-4 h-100"><i class="ti ti-calendar-plus mb-2"></i><div class="fw-semibold">Book an appointment</div><p class="text-muted small mb-0">Schedule a visit with a doctor</p></a></div>
                    <div class="col-md-6"><a href="{{ route('patient.checkin') }}" class="card dash-card p-4 h-100"><i class="ti ti-clipboard-check mb-2"></i><div class="fw-semibold">Walk-in check-in</div><p class="text-muted small mb-0">No appointment? Join the queue directly</p></a></div>
                    <div class="col-md-6"><a href="{{ route('patient.queue-status') }}" class="card dash-card p-4 h-100"><i class="ti ti-activity mb-2"></i><div class="fw-semibold">Live queue status</div><p class="text-muted small mb-0">Check your current position and wait time</p></a></div>
                    <div class="col-md-6"><a href="{{ route('patient.appointments') }}" class="card dash-card p-4 h-100"><i class="ti ti-calendar mb-2"></i><div class="fw-semibold">My appointments</div><p class="text-muted small mb-0">View past and upcoming visits</p></a></div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>