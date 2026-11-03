<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PatientSettingsController extends Controller
{
    public function edit(Request $request)
    {
        return view('patient.settings', ['user' => $request->user()]);
    }

    public function update(Request $request)
    {
        $data = $request->validate(['locale' => ['required', Rule::in(SetLocale::SUPPORTED)]]);

        $request->user()->forceFill([
            'notify_email' => $request->boolean('notify_email'),
            'notify_sms'   => $request->boolean('notify_sms'),
            'locale'       => $data['locale'],
        ])->save();

        session(['locale' => $data['locale']]);
        app()->setLocale($data['locale']);

        return back()->with('success', __('Settings saved.'));
    }
}