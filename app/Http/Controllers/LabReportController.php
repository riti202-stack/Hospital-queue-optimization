<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\TestRequest;
use App\Models\User;
use App\Notifications\LabReportReady;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LabReportController extends Controller
{
    private const DISK = 'local';   // private: storage/app/private/lab-reports

    /** Admin uploads the report */
    public function upload(Request $request, TestRequest $testRequest)
    {
        $request->validate([
            'report'      => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'result_note' => 'nullable|string|max:500',
        ]);

        if ($testRequest->report_path) {
            Storage::disk(self::DISK)->delete($testRequest->report_path);
        }

        $path = $request->file('report')->store('lab-reports', self::DISK);

        $testRequest->forceFill([
            'report_path'        => $path,
            'report_uploaded_at' => now(),
            'result_note'        => $request->input('result_note'),
        ])->save();

        User::find($testRequest->patient_id)?->notify(new LabReportReady($testRequest));

        return back()->with('success', 'Report uploaded. The patient has been notified.');
    }

    /** Admin removes a wrong upload */
    public function destroy(TestRequest $testRequest)
    {
        if ($testRequest->report_path) {
            Storage::disk(self::DISK)->delete($testRequest->report_path);
        }
        $testRequest->forceFill(['report_path' => null, 'report_uploaded_at' => null, 'result_note' => null])->save();

        return back()->with('success', 'Report removed.');
    }

    /** Admin, the patient, or the referring doctor downloads */
    public function download(TestRequest $testRequest)
    {
        $user = auth()->user();

        $allowed = $user->role === 'admin'
            || (int) $testRequest->patient_id === (int) $user->id
            || ($user->role === 'doctor' && $testRequest->doctor_id
                && (int) Doctor::where('user_id', $user->id)->value('id') === (int) $testRequest->doctor_id);

        abort_unless($allowed, 403);
        abort_unless($testRequest->report_path && Storage::disk(self::DISK)->exists($testRequest->report_path), 404, 'Report not found.');

        $ext  = pathinfo($testRequest->report_path, PATHINFO_EXTENSION);
        $name = 'lab-report-LT-' . str_pad($testRequest->id, 5, '0', STR_PAD_LEFT) . '.' . $ext;

        return Storage::disk(self::DISK)->download($testRequest->report_path, $name);
    }
}