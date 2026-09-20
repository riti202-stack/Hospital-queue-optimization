<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\QueueEntryController;
use App\Http\Controllers\QueueLogController;

Route::get('/', function () {
    return view('welcome');
});

Route::resource('departments', DepartmentController::class);
Route::resource('doctors', DoctorController::class);
Route::resource('users', UserController::class);
Route::resource('appointments', AppointmentController::class);
Route::resource('queue-entries', QueueEntryController::class);

Route::get('queue-logs', [QueueLogController::class, 'index'])->name('queue-logs.index');
Route::delete('queue-logs/{queueLog}', [QueueLogController::class, 'destroy'])->name('queue-logs.destroy');