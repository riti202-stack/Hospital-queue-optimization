@extends('layouts.main')

@section('content')
<div class="page-card" style="max-width:640px;margin:auto">
    <h3><i class="ti ti-settings"></i> {{ __('Settings') }}</h3>

    @include('partials.flash')

    <form method="POST" action="{{ route('patient.settings.update') }}">
        @csrf
        @method('PUT')

        <h5 class="mt-3">{{ __('Notifications') }}</h5>
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" role="switch" id="notify_email" name="notify_email" value="1" @checked($user->notify_email ?? true)>
            <label class="form-check-label" for="notify_email">
                <strong>{{ __('Email reminders') }}</strong><br>
                <small class="text-muted">{{ __('Get an email the day before your appointment.') }}</small>
            </label>
        </div>
        <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" role="switch" id="notify_sms" name="notify_sms" value="1" @checked($user->notify_sms ?? true)>
            <label class="form-check-label" for="notify_sms">
                <strong>{{ __('SMS reminders') }}</strong><br>
                <small class="text-muted">{{ __('Get an SMS the day before and when your turn is near.') }}</small>
            </label>
        </div>

        <h5 class="mt-4">{{ __('Language') }}</h5>
        <select name="locale" class="form-select mb-3" style="max-width:240px">
            <option value="en" @selected(($user->locale ?? app()->getLocale()) === 'en')>English</option>
            <option value="bn" @selected(($user->locale ?? app()->getLocale()) === 'bn')>বাংলা</option>
        </select>

        <button class="btn btn-brand"><i class="ti ti-device-floppy"></i> {{ __('Save settings') }}</button>
    </form>
</div>
@endsection