<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabTest extends Model
{
    protected $fillable =['name','category','price','room_no','duration_minutes','preparation','description','is_active'];

    protected $casts = ['is_active'=>'boolean'];
}

