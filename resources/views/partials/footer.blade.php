@php
    // ===== Hospital details — edit these =====
    $hospital = [
        'name'    => 'Hospital Queue System',
        'address' => 'KDA Avenue, Khulna 9100, Bangladesh',
        'phone'   => '+880 1700-000000',
        'email'   => 'info@hqos.test',
    ];

    $role = auth()->user()->role ?? null;

    // Quick links per role: [label, route name, icon]
    $links = match ($role) {
        'admin' => [
            ['Departments', 'departments.index', 'building-hospital'],
            ['Doctors', 'doctors.index', 'stethoscope'],
            ['Users', 'users.index', 'users'],
            ['Live Queue', 'queue-entries.index', 'list-numbers'],
            ['Appointments', 'appointments.index', 'calendar'],
            ['Doctor Report', 'admin.doctor-report-pdf.form', 'file-download'],
        ],
        'doctor' => [
            ['My Queue', 'doctor.queue', 'list-numbers'],
            ['My Appointments', 'doctor.appointments', 'calendar'],
            ['Availability', 'doctor.availability', 'toggle-right'],
            ['Profile', 'profile.edit', 'user'],
        ],
        'patient' => [
            ['Book Appointment', 'patient.departments', 'calendar-plus'],
            ['Check In', 'patient.checkin', 'login'],
            ['My Queue', 'patient.queue-status', 'list-numbers'],
            ['My Appointments', 'patient.appointments', 'calendar'],
        ],
        default => [
            ['Log in', 'login', 'login'],
            ['Register', 'register', 'user-plus'],
        ],
    };

    $tagline = match ($role) {
        'admin'   => 'Manage departments, doctors and live queues from one place.',
        'doctor'  => 'Your patients, in the right order, one click at a time.',
        'patient' => 'Book online, check in, and wait from anywhere — we will call you.',
        default   => 'Less waiting for patients, less guesswork for doctors.',
    };
@endphp

<style>
    body { min-height: 100vh; display: flex; flex-direction: column; }
    .site-footer { margin-top: auto; background: #0b3d31; color: #cfe7df; font-size: 14px; }
    .site-footer a { color: #cfe7df; text-decoration: none; }
    .site-footer a:hover { color: #ffffff; text-decoration: underline; }
    .site-footer h6 { color: #ffffff; font-weight: 700; letter-spacing: .5px; text-transform: uppercase; font-size: 13px; margin-bottom: 14px; }
    .site-footer .f-brand { font-size: 20px; font-weight: 700; color: #ffffff; }
    .site-footer .f-links li { margin-bottom: 8px; }
    .site-footer .f-row { display: flex; gap: 10px; margin-bottom: 10px; }
    .site-footer .f-row i { font-size: 18px; color: #7fd1b5; flex: none; }
    .site-footer .f-emergency { background: #c0392b; color: #fff; border-radius: 12px; padding: 12px 16px; display: inline-flex; gap: 10px; align-items: center; }
    .site-footer .f-emergency b { font-size: 22px; }
    .site-footer .f-bottom { border-top: 1px solid rgba(255,255,255,.12); color: #9cc4b6; font-size: 13px; }
</style>

<footer class="site-footer">
    <div class="container py-5">
        <div class="row g-4">
            {{-- Brand --}}
            <div class="col-lg-4">
                <div class="f-brand mb-2"><i class="ti ti-building-hospital"></i> {{ $hospital['name'] }}</div>
                <p class="mb-3">{{ $tagline }}</p>
                <div class="f-emergency">
                    <i class="ti ti-ambulance fs-3"></i>
                    <div>
                        <small class="d-block">Medical emergency? Call</small>
                        <b>999</b>
                    </div>
                </div>
            </div>

            {{-- Quick links (role-based) --}}
            <div class="col-6 col-lg-2">
                <h6>Quick links</h6>
                <ul class="list-unstyled f-links mb-0">
                    @foreach($links as [$label, $route, $icon])
                        @if(Route::has($route))
                            <li><a href="{{ route($route) }}"><i class="ti ti-{{ $icon }}"></i> {{ $label }}</a></li>
                        @endif
                    @endforeach
                </ul>
            </div>

            {{-- Opening hours --}}
            <div class="col-6 col-lg-3">
                <h6>Opening hours</h6>
                <div class="f-row"><i class="ti ti-clock"></i><span>Outpatient: Sat – Thu<br>9:00 AM – 5:00 PM</span></div>
                <div class="f-row"><i class="ti ti-heartbeat"></i><span>Emergency: open 24/7</span></div>
                <div class="f-row"><i class="ti ti-calendar-time"></i><span>Hospital time: <strong id="f-clock">{{ now()->format('h:i A') }}</strong></span></div>
            </div>

            {{-- Contact --}}
            <div class="col-lg-3">
                <h6>Contact</h6>
                <div class="f-row"><i class="ti ti-map-pin"></i><span>{{ $hospital['address'] }}</span></div>
                <div class="f-row"><i class="ti ti-phone"></i><a href="tel:{{ preg_replace('/[^0-9+]/', '', $hospital['phone']) }}">{{ $hospital['phone'] }}</a></div>
                <div class="f-row"><i class="ti ti-mail"></i><a href="mailto:{{ $hospital['email'] }}">{{ $hospital['email'] }}</a></div>
            </div>
        </div>
    </div>

    <div class="f-bottom">
        <div class="container py-3 d-flex flex-wrap justify-content-between gap-2">
            <span>© {{ date('Y') }} {{ $hospital['name'] }}. All rights reserved.</span>
            <span>
                @auth
                    Signed in as <strong>{{ auth()->user()->name }}</strong> · {{ ucfirst($role) }}
                @else
                    Hospital Queue Optimization System
                @endauth
            </span>
        </div>
    </div>
</footer>

<script>
    (function () {
        const el = document.getElementById('f-clock');
        if (!el) return;
        const tick = () => el.textContent = new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', timeZone: 'Asia/Dhaka' });
        tick();
        setInterval(tick, 30000);
    })();
</script>