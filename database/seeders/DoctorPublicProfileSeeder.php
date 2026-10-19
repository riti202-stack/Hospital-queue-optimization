<?php

namespace Database\Seeders;

use App\Models\Doctor;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

/** Fills "About" and appointment phone for doctors that don't have them. Fictional data. */
class DoctorPublicProfileSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        $faker->seed(77);

        $lines = [
            'Provides diagnosis, treatment and long-term follow-up for adults and children.',
            "Known for clear explanations and treatment plans that fit each patient's daily life.",
            'Focuses on early diagnosis and preventive care to avoid hospital admissions.',
            'Manages both routine and complex cases, working closely with other departments.',
            'Offers second opinions and coordinates tests so patients need fewer visits.',
        ];

        foreach (Doctor::with('department')->get() as $d) {
            $updates = [];

            if (blank($d->about)) {
                $spec = $d->specialization ?: ucfirst($d->department->name ?? 'General Medicine');
                $updates['about'] = "Consultant in {$spec}"
                    . ($d->sub_specialty ? " with a special interest in {$d->sub_specialty}" : '')
                    . '. ' . $faker->randomElement($lines);
            }

            if (blank($d->chamber_phone)) {
                $updates['chamber_phone'] = '01' . $faker->randomElement(['3', '5', '7', '8', '9']) . $faker->numerify('########');
            }

            if ($updates) {
                $d->update($updates);
            }
        }

        $this->command->info('Doctor public profiles filled.');
    }
}