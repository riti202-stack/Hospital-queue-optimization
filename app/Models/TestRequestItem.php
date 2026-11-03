<?php

namespace App\Models;

use Illumiate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Model as EloquentModel;

class TestRequestItem extends EloquentModel
{
    protected $fillable = ['test_request_id','lab_test_id','test_name','price'];

    public function labTest()
    {
        return $this->belongsTo(LabTest::class);
    }
}