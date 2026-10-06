<?php
namespace Database\Factories;

use App\Models\User;
use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

class DoctorFactory extends Factory
{
    protected array $specializations = [
        'Cardiology', 'Pediatrics', 'General Medicine', 'Orthopedics',
        'Dermatology', 'Neurology', 'Gynecology', 'ENT', 'Dentistry', 'Psychiatry',
    ];

    protected array $degrees = ['MBBS', 'MD', 'MS', 'FCPS', 'MRCP'];
    protected array $universities = [
        'Dhaka Medical College', 'Chittagong Medical College', 'Sir Salimullah Medical College',
        'Khulna Medical College', 'Rajshahi Medical College', 'Sylhet MAG Osmani Medical College',
    ];

    public function definition(): array
    {
        $joining = $this->faker->dateTimeBetween('-8 years', '-3 months');

        return [
            'user_id' => User::factory()->state(['role' => 'doctor']),
            'department_id' => Department::inRandomOrder()->first()?->id ?? Department::factory(),
            'room_no' => $this->faker->numberBetween(1, 5) . $this->faker->randomLetter() . '-' . $this->faker->numberBetween(100, 499),
            'is_available' => $this->faker->boolean(80),

            'date_of_birth' => $this->faker->dateTimeBetween('-60 years', '-28 years')->format('Y-m-d'),
            'gender' => $this->faker->randomElement(['male', 'female']),
            'personal_phone' => '01' . $this->faker->numberBetween(3, 9) . $this->faker->numerify('########'),
            'residential_address' => $this->faker->streetAddress() . ', ' . $this->faker->city(),
            'emergency_contact_name' => $this->faker->name(),
            'emergency_contact_phone' => '01' . $this->faker->numberBetween(3, 9) . $this->faker->numerify('########'),

            'license_number' => 'BMDC-' . $this->faker->unique()->numerify('######'),
            'license_issuing_body' => 'Bangladesh Medical and Dental Council (BMDC)',
            'license_expiry' => $this->faker->dateTimeBetween('+6 months', '+5 years')->format('Y-m-d'),
            'specialization' => $this->faker->randomElement($this->specializations),
            'sub_specialty' => $this->faker->optional(0.5)->randomElement(['Interventional', 'Pediatric', 'Surgical', 'Clinical']),

            'employment_type' => $this->faker->randomElement(['full_time', 'full_time', 'part_time', 'visiting']),
            'date_of_joining' => $joining->format('Y-m-d'),
            'working_hours' => $this->faker->randomElement([
                'Sun–Thu, 9:00 AM – 5:00 PM',
                'Sat–Wed, 10:00 AM – 6:00 PM',
                'OPD: Sun, Tue, Thu — 2:00 PM – 6:00 PM',
            ]),
            'fee_share_percent' => $this->faker->randomElement([60, 65, 70, 75]),

            'malpractice_insurance_policy_no' => 'MPI-' . $this->faker->unique()->numerify('#####-BD'),
            'background_check_status' => $this->faker->randomElement(['cleared', 'cleared', 'cleared', 'pending']),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (\App\Models\Doctor $doctor) {
            $degree = $this->faker->randomElement($this->degrees);
            $doctor->qualifications()->create([
                'degree' => $degree,
                'institution' => $this->faker->randomElement($this->universities),
                'passing_year' => $this->faker->numberBetween(1995, 2018),
            ]);

            if ($this->faker->boolean(40)) {
                $doctor->qualifications()->create([
                    'degree' => 'FCPS',
                    'institution' => 'Bangladesh College of Physicians and Surgeons',
                    'passing_year' => $this->faker->numberBetween(2005, 2022),
                ]);
            }
        });
    }
}