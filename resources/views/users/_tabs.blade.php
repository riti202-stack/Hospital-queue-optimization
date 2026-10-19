<style>
    .user-tabs .nav-link { color:#0f6e56; font-weight:600; border-radius:12px; }
    .user-tabs .nav-link.active { background:#0f6e56; color:#fff; }
</style>
<ul class="nav nav-pills user-tabs mb-4 gap-2">
    <li class="nav-item">
        <a class="nav-link {{ $tab === 'overview' ? 'active' : '' }}" href="{{ route('users.index') }}">
            <i class="ti ti-layout-grid"></i> Overview
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $tab === 'patients' ? 'active' : '' }}" href="{{ route('users.patients') }}">
            <i class="ti ti-user-heart"></i> Patients <span class="badge bg-light text-dark ms-1">{{ $counts['patient'] ?? 0 }}</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $tab === 'doctors' ? 'active' : '' }}" href="{{ route('users.doctors') }}">
            <i class="ti ti-stethoscope"></i> Doctors <span class="badge bg-light text-dark ms-1">{{ $counts['doctor'] ?? 0 }}</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $tab === 'admins' ? 'active' : '' }}" href="{{ route('users.admins') }}">
            <i class="ti ti-shield-lock"></i> Admins <span class="badge bg-light text-dark ms-1">{{ $counts['admin'] ?? 0 }}</span>
        </a>
    </li>
</ul>