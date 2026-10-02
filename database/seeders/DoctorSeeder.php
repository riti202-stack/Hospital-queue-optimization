<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Doctor;
use Illuminate\Database\Seeder;

class DoctorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = ['General','emergency','Dental','Cardiology','Orthopedics','Pediatrics'];

        foreach($departments as $name)
            {
                Department::firstOrCreate(['name'=>$name]);
            }

            Doctor::factory()->count(50)->create();
        //
    }
}
