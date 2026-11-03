<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class appointment extends Model
{
    protected $fillable = ['patient_id', 'doctor_id', 'department_id', 'scheduled_time', 'status','dependent_id'];

    public function patient() { return $this->belongsTo(User::class, 'patient_id'); }
    public function doctor() { return $this->belongsTo(Doctor::class); }
    public function department() { return $this->belongsTo(Department::class); }

    public function dependent()
{
    return $this->belongsTo(\App\Models\Dependent::class);
}

public function patientLabel(): string
{
    return $this->dependent ? $this->dependent->label() : ($this->patient->name ?? 'Patient');
}
}
