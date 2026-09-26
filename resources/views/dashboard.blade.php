<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

            @if(auth()->user()->role === 'admin')
                <h3 class="text-lg font-semibold mb-4">Welcome back, {{ auth()->user()->name }}</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                    <a href="{{ route('departments.index') }}" class="block bg-white p-5 rounded-xl shadow hover:shadow-lg transition">
                        <div class="font-semibold">Departments</div>
                        <p class="text-sm text-gray-500 mt-1">Manage hospital departments</p>
                    </a>
                    <a href="{{ route('doctors.index') }}" class="block bg-white p-5 rounded-xl shadow hover:shadow-lg transition">
                        <div class="font-semibold">Doctors</div>
                        <p class="text-sm text-gray-500 mt-1">Manage doctor profiles</p>
                    </a>
                    <a href="{{ route('users.index') }}" class="block bg-white p-5 rounded-xl shadow hover:shadow-lg transition">
                        <div class="font-semibold">Users</div>
                        <p class="text-sm text-gray-500 mt-1">Manage all accounts and roles</p>
                    </a>
                    <a href="{{ route('appointments.index') }}" class="block bg-white p-5 rounded-xl shadow hover:shadow-lg transition">
                        <div class="font-semibold">Appointments</div>
                        <p class="text-sm text-gray-500 mt-1">View and manage bookings</p>
                    </a>
                    <a href="{{ route('queue-entries.index') }}" class="block bg-white p-5 rounded-xl shadow hover:shadow-lg transition">
                        <div class="font-semibold">Live Queue</div>
                        <p class="text-sm text-gray-500 mt-1">See the full queue across departments</p>
                    </a>
                    <a href="{{ route('queue-logs.index') }}" class="block bg-white p-5 rounded-xl shadow hover:shadow-lg transition">
                        <div class="font-semibold">Queue Logs</div>
                        <p class="text-sm text-gray-500 mt-1">Crash-recovery audit trail</p>
                    </a>
                </div>
            @endif

            @if(auth()->user()->role === 'doctor')
                <h3 class="text-lg font-semibold mb-4">Welcome, Dr. {{ auth()->user()->name }}</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <a href="{{ route('doctor.queue') }}" class="block bg-white p-5 rounded-xl shadow hover:shadow-lg transition">
                        <div class="font-semibold">My Queue</div>
                        <p class="text-sm text-gray-500 mt-1">See who's waiting for you right now</p>
                    </a>
                    <a href="{{ route('doctor.availability') }}" class="block bg-white p-5 rounded-xl shadow hover:shadow-lg transition">
                        <div class="font-semibold">Availability</div>
                        <p class="text-sm text-gray-500 mt-1">Mark yourself available or unavailable</p>
                    </a>
                    <a href="{{ route('doctor.appointments') }}" class="block bg-white p-5 rounded-xl shadow hover:shadow-lg transition">
                        <div class="font-semibold">My Appointments</div>
                        <p class="text-sm text-gray-500 mt-1">View your upcoming schedule</p>
                    </a>
                </div>
            @endif

            @if(auth()->user()->role === 'patient')
                <h3 class="text-lg font-semibold mb-4">Welcome, {{ auth()->user()->name }}</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <a href="{{ route('patient.book') }}" class="block bg-white p-5 rounded-xl shadow hover:shadow-lg transition">
                        <div class="font-semibold">Book an appointment</div>
                        <p class="text-sm text-gray-500 mt-1">Schedule a visit with a doctor</p>
                    </a>
                    <a href="{{ route('patient.checkin') }}" class="block bg-white p-5 rounded-xl shadow hover:shadow-lg transition">
                        <div class="font-semibold">Walk-in check-in</div>
                        <p class="text-sm text-gray-500 mt-1">No appointment? Join the queue directly</p>
                    </a>
                    <a href="{{ route('patient.queue-status') }}" class="block bg-white p-5 rounded-xl shadow hover:shadow-lg transition">
                        <div class="font-semibold">Live queue status</div>
                        <p class="text-sm text-gray-500 mt-1">Check your current position and wait time</p>
                    </a>
                    <a href="{{ route('patient.appointments') }}" class="block bg-white p-5 rounded-xl shadow hover:shadow-lg transition">
                        <div class="font-semibold">My appointments</div>
                        <p class="text-sm text-gray-500 mt-1">View past and upcoming visits</p>
                    </a>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>