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
            <a class="navbar-brand" href="/">Hospital Queue System</a>
            <div>
                <a href="{{ route('departments.index') }}" class="text-white me-3">Departments</a>
                <a href="{{ route('users.index') }}" class="text-white me-3">Users</a>
                <a href="{{ route('doctors.index') }}" class="text-white me-3">Doctors</a>
                <a href="{{ route('appointments.index') }}" class="text-white me-3">Appointments</a>
                <a href="{{ route('queue-entries.index') }}" class="text-white me-3">Queue</a>
                <a href="{{ route('queue-logs.index') }}" class="text-white">Logs</a>
            </div>
        </div>
    </nav>

    @yield('content')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>