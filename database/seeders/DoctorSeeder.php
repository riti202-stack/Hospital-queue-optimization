<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Doctor;
use Illuminate\Database\Seeder;

class DoctorSeeder extends Seeder
{
    public function run(): void
    {
        $departments = ['General', 'emergency', 'Dental', 'Cardiology', 'Orthopedics', 'Pediatrics'];

        foreach ($departments as $name) {
            Department::firstOrCreate(['name' => $name]);
        }

        // Only top up to 50 doctors, so running this again doesn't add 50 more
        $missing = 50 - Doctor::count();
        if ($missing > 0) {
            Doctor::factory()->count($missing)->create();
        }

        // Fill license, specialization, contact, employment, compliance + qualifications
        $this->call(FillDoctorCredentialsSeeder::class);
    }
}