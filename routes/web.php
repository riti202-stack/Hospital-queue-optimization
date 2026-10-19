<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\QueueEntryController;
use App\Http\Controllers\QueueLogController;
use App\Http\Controllers\DoctorPortalController;
use App\Http\Controllers\PatientPortalController;
use App\Http\Controllers\AppointmentPdfController;
use App\Http\Controllers\DoctorReportPdfController;

use App\Http\Controllers\DoctorProfilePdfController;
use App\Http\Controllers\DoctorQueueController;
use App\Http\Controllers\PatientQueueController;
use App\Http\Controllers\PatientBookingController;
use App\Http\Controllers\PatientHomeController;


Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth','role:admin'])->group(function(){
    Route::resource('departments',DepartmentController::class);

    Route::resource('doctors',DoctorController::class);

    Route::get('/users/patients', [UserController::class, 'patients'])->name('users.patients');
Route::get('/users/doctors', [UserController::class, 'doctors'])->name('users.doctors');
Route::get('/users/admins', [UserController::class, 'admins'])->name('users.admins');

    Route::resource('users',UserController::class);

    Route::resource('queue-logs',QueueLogController::class)->only(['index','destroy']);

    Route::get('/doctor-report-pdf', [DoctorReportPdfController::class, 'form'])->name('admin.doctor-report-pdf.form');

    Route::post('/doctor-report-pdf', [DoctorReportPdfController::class, 'generate'])->name('admin.doctor-report-pdf.generate');

    Route::get('/doctor-profile-pdf', [DoctorProfilePdfController::class, 'form'])->name('admin.doctor-profile-pdf.form');
Route::post('/doctor-profile-pdf', [DoctorProfilePdfController::class, 'generate'])->name('admin.doctor-profile-pdf.generate');
Route::get('/doctors/{doctor}/pdf', [DoctorProfilePdfController::class, 'quick'])->name('admin.doctor-profile-pdf.quick');
});

Route::middleware(['auth','role:admin,doctor'])->group(function(){

Route::resource('appointments',AppointmentController::class);


Route::get('/queue-entries/department/{department}', [QueueEntryController::class, 'department'])
    ->name('queue-entries.department');
Route::get('/queue-entries/department/{department}/doctor/{doctor}', [QueueEntryController::class, 'doctor'])
    ->name('queue-entries.doctor');
Route::resource('queue-entries',QueueEntryController::class);

Route::get('/appointments-pdf', [AppointmentPdfController::class, 'form'])->name('admin.appointment-pdf.form');
Route::post('/appointments-pdf', [AppointmentPdfController::class, 'generate'])->name('admin.appointment-pdf.generate');
});

Route::get('/dashboard', function () {
    return auth()->user()->role === 'patient'
        ? redirect()->route('patient.home')
        : view('dashboard');
})->middleware(['auth'])->name('dashboard');

Route::get('/', function () {
    return view('home');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth','role:doctor'])->prefix('doctor')->name('doctor.')->group(function () {

    // New queue page (replaces DoctorPortalController@queue)
    Route::get('/queue', [DoctorQueueController::class, 'index'])->name('queue');
    Route::post('/queue/next', [DoctorQueueController::class, 'callNext'])->name('queue.next');
    Route::post('/queue/complete', [DoctorQueueController::class, 'complete'])->name('queue.complete');
    Route::post('/queue/{entry}/call', [DoctorQueueController::class, 'callSpecific'])->name('queue.call');

    // Existing features, unchanged
    Route::get('/queue/data', [DoctorPortalController::class, 'queueData'])->name('queue.data');
    Route::get('/queue/referred', [DoctorPortalController::class, 'referredList'])->name('queue.referred');
    Route::post('/queue/{entry}/refer', [DoctorPortalController::class, 'referPatient'])->name('queue.refer');
    Route::post('/queue/{entry}/return', [DoctorPortalController::class, 'returnPatient'])->name('queue.return');

    Route::get('/availability', [DoctorPortalController::class, 'availability'])->name('availability');
    Route::post('/availability', [DoctorPortalController::class, 'toggleAvailability'])->name('availability.update');
     Route::get('/appointments', [DoctorQueueController::class, 'appointments'])->name('appointments');
});

Route::middleware(['auth', 'role:patient'])->prefix('patient')->name('patient.')->group(function () {

    // Home
    Route::get('/home', [PatientHomeController::class, 'index'])->name('home');

    // Book appointment: department → doctors → slot
    Route::redirect('/book', '/patient/departments')->name('book');
    Route::post('/book', [PatientPortalController::class, 'bookStore'])->name('book.store');
    Route::get('/departments', [PatientBookingController::class, 'departments'])->name('departments');
    Route::get('/departments/{department}/doctors', [PatientBookingController::class, 'doctors'])->name('department.doctors');
    Route::get('/doctors/{doctor}/book', [PatientBookingController::class, 'bookForm'])->name('doctor.book');
    Route::post('/doctors/{doctor}/book', [PatientBookingController::class, 'bookStore'])->name('doctor.book.store');

    // Walk-in check-in: department → doctors → confirm
    Route::get('/checkin', [PatientBookingController::class, 'checkinDepartments'])->name('checkin');
    Route::get('/checkin/departments/{department}', [PatientBookingController::class, 'checkinDoctors'])->name('checkin.doctors');
    Route::get('/checkin/confirm/{department}/{doctor?}', [PatientBookingController::class, 'checkinConfirm'])->name('checkin.confirm');
    Route::post('/checkin', [PatientBookingController::class, 'checkinStore'])->name('checkin.store');

    // My Appointments + appointment check-in
    Route::get('/appointments', [PatientQueueController::class, 'appointments'])->name('appointments');
    Route::post('/appointments/{appointment}/checkin', [PatientQueueController::class, 'checkinAppointment'])->name('appointment.checkin');

    // My Queue + live data + call alerts
    Route::get('/queue-status', [PatientQueueController::class, 'status'])->name('queue-status');
    Route::get('/queue-status/data', [PatientQueueController::class, 'statusData'])->name('queue-status.data');
    Route::get('/alerts', [PatientQueueController::class, 'alerts'])->name('alerts');
    Route::post('/alerts/{id}/read', [PatientQueueController::class, 'readAlert'])->name('alerts.read');
});


require __DIR__.'/auth.php';
