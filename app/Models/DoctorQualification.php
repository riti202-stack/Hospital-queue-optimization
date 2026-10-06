<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoctorQualification extends Model{

   protected $fillable = ['doctor_id','degree','institution','passing_year'];

   public function doctor()
   {
     return $this->belongsTo(Doctor::class);
   }
}