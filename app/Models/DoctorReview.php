<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoctorReview extends Model
{
    protected $fillable = ['doctor_id', 'patient_id', 'queue_entry_id', 'rating', 'comment', 'is_visible'];

    protected $casts = ['is_visible' => 'boolean'];

    public function doctor()     { return $this->belongsTo(Doctor::class); }
    public function patient()    { return $this->belongsTo(User::class, 'patient_id'); }
    public function queueEntry() { return $this->belongsTo(QueueEntry::class); }
}