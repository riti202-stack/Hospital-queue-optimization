<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Doctor extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'department_id',
    'room_no', 'is_available','date_of_birth', 'gender', 'photo', 'personal_phone', 'residential_address',
        'emergency_contact_name', 'emergency_contact_phone',
        'license_number', 'license_issuing_body', 'license_expiry',
        'specialization', 'sub_specialty',
        'employment_type', 'date_of_joining', 'working_hours', 'fee_share_percent',
        'malpractice_insurance_policy_no', 'background_check_status',];

    public function user() { return $this->belongsTo(User::class); }
    public function department() { return $this->belongsTo(Department::class); }

    public function qualifications()
    {
        return $this->hasMany(DoctorQualification::class);
    }
       
    
}
