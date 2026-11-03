<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesDoctor;
use App\Models\Dependent;
use App\Models\DoctorReview;
use App\Models\Prescription;
use App\Models\QueueEntry;
use App\Models\TestRequest;
use App\Models\User;
use Illuminate\Http\Request;

class DoctorRecordsController extends Controller
{
    use ResolvesDoctor;

    /** Full history of one patient (or one family member) */
    public function history(Request $request, User $patient)
    {
        $doctor = $this->currentDoctor();

        abort_unless(
            QueueEntry::where('doctor_id', $doctor->id)->where('patient_id', $patient->id)->exists(),
            403,
            'You can only view the history of your own patients.'
        );

        $dependentId = $request->integer('dependent') ?: null;
        $dependent   = $dependentId ? Dependent::where('user_id', $patient->id)->findOrFail($dependentId) : null;

        $prescriptions = Prescription::with('items', 'doctor')
            ->where('patient_id', $patient->id)
            ->where('dependent_id', $dependentId)
            ->latest()->paginate(10)->withQueryString();

        $labReports = TestRequest::where('patient_id', $patient->id)
            ->where('dependent_id', $dependentId)
            ->whereNotNull('report_path')
            ->latest('report_uploaded_at')->get();

        $visits = QueueEntry::where('patient_id', $patient->id)
            ->where('dependent_id', $dependentId)
            ->where('status', 'completed')->count();

        return view('doctor.patients.history', compact('patient', 'dependent', 'prescriptions', 'labReports', 'visits'));
    }

    /** Lab reports of tests this doctor referred */
    public function labReports()
    {
        $doctor = $this->currentDoctor();

        $reports = TestRequest::with('items')
            ->where('doctor_id', $doctor->id)
            ->whereNotNull('report_path')
            ->latest('report_uploaded_at')->paginate(15);

        return view('doctor.lab-reports', compact('reports'));
    }

    /** The doctor's own ratings */
    public function reviews()
    {
        $doctor = $this->currentDoctor();
        $base   = DoctorReview::where('doctor_id', $doctor->id);

        $avg     = (clone $base)->where('is_visible', true)->avg('rating');
        $count   = (clone $base)->where('is_visible', true)->count();
        $dist    = (clone $base)->where('is_visible', true)->selectRaw('rating, COUNT(*) as c')->groupBy('rating')->pluck('c', 'rating');
        $reviews = (clone $base)->with('patient')->latest()->paginate(10);

        return view('doctor.reviews', compact('avg', 'count', 'dist', 'reviews'));
    }
}