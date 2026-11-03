<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Doctor;

trait ResolvesDoctor
{
    protected function currentDoctor(): Doctor
    {
        return Doctor::where('user_id', auth()->id())->firstOrFail();
    }
}