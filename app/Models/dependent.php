<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dependent extends Model
{
    public const RELATIONS    = ['Father', 'Mother', 'Spouse', 'Son', 'Daughter', 'Brother', 'Sister', 'Grandparent', 'Other'];
    public const GENDERS      = ['Male', 'Female', 'Other'];
    public const BLOOD_GROUPS = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

    protected $fillable = ['user_id', 'name', 'relation', 'gender', 'date_of_birth', 'blood_group', 'phone'];

    protected $casts = ['date_of_birth' => 'date'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function age(): ?int
    {
        return $this->date_of_birth?->age;
    }

    public function label(): string
    {
        return $this->name . ' (' . $this->relation . ')';
    }
}