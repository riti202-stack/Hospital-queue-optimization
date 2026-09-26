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

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth','role:admin'])->group(function(){
    Route::resource('departments',DepartmentController::class);

    Route::resource('doctors',DoctorController::class);

    Route::resource('users',UserController::class);

    Route::resource('queue-logs',QueueLogController::class)->only(['index','destroy']);
});

Route::middleware(['auth','role:admin,doctor'])->group(function(){

Route::resource('appointments','AppointmentController::class');

Route::resource('queue-entries',QueueEntryController::class);
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth','role:doctor'])->prefix('doctor')->name('doctor.')->group(function(){

   Route::get('/queue',[DoctorPortalController::class,'queue'])->name('queue');

   Route::get('/availability',[DoctorPortalController::class,'availability'])->name('availability');

   Route::post('/availability',[DoctorPortalController::class,'toggleAvailability'])->name('availability.update');

   Route::get('/appointments',[DoctorPortalController::class,'appointments'])->name('appointments');



});


require __DIR__.'/auth.php';
