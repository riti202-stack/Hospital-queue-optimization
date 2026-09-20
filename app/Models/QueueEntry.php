<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QueueEntry extends Model
{
    protected $fillable = [
        'patient_id', 'doctor_id', 'department_id', 'appointment_id',
        'urgency_level', 'priority_score', 'status', 'started_at', 'completed_at',
    ];

    public function patient() { return $this->belongsTo(User::class, 'patient_id'); }
    public function doctor() { return $this->belongsTo(Doctor::class); }
    public function department() { return $this->belongsTo(Department::class); }
    public function appointment() { return $this->belongsTo(Appointment::class); }
    public function queueLogs() { return $this->hasMany(QueueLog::class); }
}
