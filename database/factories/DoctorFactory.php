<?php

namespace Database\Factories;

use App\Models\Doctor;
use App\Models\Department;
use App\Models\User;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Doctor>
 */
class DoctorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id'=>User::factory()->state(['role'=>'doctor']),
            'department_id'=>Department::inRandomOrder()->first()?->id ?? Department::factory(),
            
            'room_no'=>$this->faker->numberBetween(1,5).$this->faker->randomLetter().'-'.$this->faker->numberBetween(100,499),
            'is_available'=>$this->faker->boolean(80),
            //
        ];
    }
}
