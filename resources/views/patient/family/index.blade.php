@extends('layouts.main')

@section('content')
<div class="page-card">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
        <h3 class="mb-0"><i class="ti ti-users"></i> {{ __('Family Members') }}</h3>
        <a href="{{ route('patient.family.create') }}" class="btn btn-brand"><i class="ti ti-plus"></i> {{ __('Add member') }}</a>
    </div>
    <p class="text-muted">{{ __('Book appointments, check in and keep records for your family from one account.') }}</p>

    @include('partials.flash')

    <div class="row g-3">
        @forelse($members as $m)
            <div class="col-md-6 col-lg-4">
                <div class="card entry-card h-100">
                    <div class="card-body">
                        <h5 class="mb-1">{{ $m->name }}</h5>
                        <span class="badge bg-success-subtle text-success mb-2">{{ __($m->relation) }}</span>
                        <ul class="list-unstyled small mb-3">
                            @if($m->age() !== null)<li><span class="text-muted">{{ __('Age') }}:</span> {{ $m->age() }}</li>@endif
                            @if($m->gender)<li><span class="text-muted">{{ __('Gender') }}:</span> {{ __($m->gender) }}</li>@endif
                            @if($m->blood_group)<li><span class="text-muted">{{ __('Blood group') }}:</span> {{ $m->blood_group }}</li>@endif
                            @if($m->phone)<li><span class="text-muted">{{ __('Phone') }}:</span> {{ $m->phone }}</li>@endif
                        </ul>
                        <div class="d-flex gap-2">
                            <a href="{{ route('patient.family.edit', $m) }}" class="btn btn-sm btn-outline-primary"><i class="ti ti-edit"></i> {{ __('Edit') }}</a>
                            <form method="POST" action="{{ route('patient.family.destroy', $m) }}" onsubmit="return confirm('{{ __('Remove this family member?') }}')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="ti ti-trash"></i> {{ __('Delete') }}</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12"><div class="alert alert-info mb-0">{{ __('No family members added yet.') }}</div></div>
        @endforelse
    </div>
</div>
@endsection