<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prescription extends Model
{
    protected $fillable = [
        'queue_entry_id', 'appointment_id', 'patient_id', 'dependent_id', 'doctor_id',
        'symptoms', 'diagnosis', 'tests_advised', 'advice', 'follow_up_date',
    ];

    protected $casts = ['follow_up_date' => 'date'];

    public function items()      { return $this->hasMany(PrescriptionItem::class); }
    public function patient()    { return $this->belongsTo(User::class, 'patient_id'); }
    public function dependent()  { return $this->belongsTo(Dependent::class); }
    public function doctor()     { return $this->belongsTo(Doctor::class); }
    public function queueEntry() { return $this->belongsTo(QueueEntry::class); }

    public function code(): string
    {
        return 'RX-' . str_pad($this->id, 5, '0', STR_PAD_LEFT);
    }

    public function patientLabel(): string
    {
        return $this->dependent ? $this->dependent->label() : ($this->patient->name ?? 'Patient');
    }
}