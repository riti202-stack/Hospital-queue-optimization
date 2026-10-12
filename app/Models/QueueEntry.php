<?php

namespace App\Models;

use App\Notifications\ConsultationCompleted;
use App\Notifications\PatientCalled;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class QueueEntry extends Model
{
    protected $fillable = [
        'patient_id', 'doctor_id', 'department_id', 'appointment_id', 'token_no',
        'urgency_level', 'priority_score', 'checked_in_at', 'status', 'started_at', 'completed_at',
    ];

    protected $casts = [
        'checked_in_at' => 'datetime',
        'started_at'    => 'datetime',
        'completed_at'  => 'datetime',
    ];

    public function patient()    { return $this->belongsTo(User::class, 'patient_id'); }
    public function doctor()     { return $this->belongsTo(Doctor::class); }
    public function department() { return $this->belongsTo(Department::class); }
    public function appointment(){ return $this->belongsTo(Appointment::class); }
    public function logs()       { return $this->hasMany(QueueLog::class); }

    protected static function booted(): void
    {
        // Every new check-in, from ANY page: block duplicates and give a queue number
        static::creating(function (QueueEntry $e) {
            $e->status ??= 'waiting';
            $e->checked_in_at ??= now();

            $already = static::where('patient_id', $e->patient_id)
                ->whereIn('status', ['waiting', 'in_progress'])
                ->where('checked_in_at', '>=', today())
                ->exists();

            if ($already) {
                throw ValidationException::withMessages([
                    'patient_id' => 'This patient is already in a queue today.',
                ]);
            }

            if (! $e->token_no) {
                $e->token_no = (int) static::where('department_id', $e->department_id)
                    ->where('checked_in_at', '>=', today())
                    ->max('token_no') + 1;
            }
        });

        // Every status change, from ANY page: keep appointment + patient in sync
        static::updated(function (QueueEntry $e) {
            if (! $e->wasChanged('status')) {
                return;
            }

            if ($e->status === 'in_progress') {
                if (! $e->started_at) {
                    $e->started_at = now();
                    $e->saveQuietly();
                }
                static::notifyPatient($e, new PatientCalled($e));
            }

            if ($e->status === 'completed') {
                if (! $e->completed_at) {
                    $e->completed_at = now();
                    $e->saveQuietly();
                }
                $e->appointment?->update(['status' => 'completed']);
                static::notifyPatient($e, new ConsultationCompleted($e));
            }
        });
    }

    protected static function notifyPatient(QueueEntry $e, $notification): void
    {
        // No alerts for old visits closed by the end-of-day cleanup
        if ($e->checked_in_at && $e->checked_in_at->lt(today())) {
            return;
        }

        try {
            $e->load('patient', 'doctor.user', 'department');
            $e->patient?->notify($notification);
        } catch (\Throwable $ex) {
            report($ex);
        }
    }
}