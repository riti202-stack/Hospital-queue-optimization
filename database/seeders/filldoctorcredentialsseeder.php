<?php

namespace Database\Seeders;

use App\Models\Doctor;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;


class FillDoctorCredentialsSeeder extends Seeder
{
    // Keyword in department name => [specialization, sub-specialties, postgraduate degree]
    private array $map = [
        'cardio'   => ['Cardiology', ['Interventional Cardiology', 'Cardiac Electrophysiology', 'Heart Failure'], 'MD (Cardiology)'],
        'medicine' => ['Internal Medicine', ['Diabetes & Endocrinology', 'Infectious Diseases', 'Gastroenterology'], 'FCPS (Medicine)'],
        'pediatr'  => ['Pediatrics', ['Neonatology', 'Pediatric Cardiology', 'Pediatric Neurology'], 'FCPS (Pediatrics)'],
        'paediatr' => ['Pediatrics', ['Neonatology', 'Pediatric Cardiology', 'Pediatric Neurology'], 'FCPS (Pediatrics)'],
        'child'    => ['Pediatrics', ['Neonatology', 'Pediatric Neurology'], 'FCPS (Pediatrics)'],
        'ortho'    => ['Orthopedic Surgery', ['Spine Surgery', 'Joint Replacement', 'Sports Medicine'], 'MS (Orthopedics)'],
        'gyn'      => ['Obstetrics & Gynecology', ['Infertility', 'Fetal Medicine', 'Gynecologic Oncology'], 'FCPS (Obstetrics & Gynecology)'],
        'obs'      => ['Obstetrics & Gynecology', ['Infertility', 'Fetal Medicine'], 'FCPS (Obstetrics & Gynecology)'],
        'ent'      => ['Otolaryngology (ENT)', ['Head & Neck Surgery', 'Otology', 'Rhinology'], 'MS (ENT)'],
        'neuro'    => ['Neurology', ['Stroke', 'Epilepsy', 'Movement Disorders'], 'MD (Neurology)'],
        'derma'    => ['Dermatology', ['Cosmetic Dermatology', 'Pediatric Dermatology'], 'MD (Dermatology)'],
        'skin'     => ['Dermatology', ['Cosmetic Dermatology'], 'MD (Dermatology)'],
        'eye'      => ['Ophthalmology', ['Retina', 'Cornea', 'Glaucoma'], 'MS (Ophthalmology)'],
        'ophthal'  => ['Ophthalmology', ['Retina', 'Cornea', 'Glaucoma'], 'MS (Ophthalmology)'],
        'surg'     => ['General Surgery', ['Laparoscopic Surgery', 'Hepatobiliary Surgery'], 'FCPS (Surgery)'],
        'psych'    => ['Psychiatry', ['Child Psychiatry', 'Addiction Psychiatry'], 'MD (Psychiatry)'],
        'dent'     => ['Dentistry', ['Oral Surgery', 'Orthodontics'], 'MS (Oral & Maxillofacial Surgery)'],
        'radio'    => ['Radiology', ['Interventional Radiology', 'Neuroradiology'], 'MD (Radiology)'],
        'emerg'    => ['Emergency Medicine', ['Trauma Care', 'Critical Care'], 'FCPS (Emergency Medicine)'],
        'general'  => ['General Medicine', ['Family Medicine', 'Primary Care'], 'FCPS (Medicine)'],
    ];

    private array $medicalColleges = [
        'Dhaka Medical College', 'Khulna Medical College', 'Rajshahi Medical College',
        'Chittagong Medical College', 'Sir Salimullah Medical College', 'Mymensingh Medical College',
        'Sylhet MAG Osmani Medical College',
    ];

    private array $areas = [
        'Dhanmondi, Dhaka', 'Mirpur 10, Dhaka', 'Banani, Dhaka', 'Uttara, Dhaka',
        'Sonadanga, Khulna', 'Boyra, Khulna', 'Nirala R/A, Khulna', 'Gollamari, Khulna',
    ];

    private array $schedules = [
        'Sun-Thu 9am-3pm, OPD Sun & Tue',
        'Sun-Thu 8am-2pm, OPD daily',
        'Sat-Wed 10am-5pm, OPD Sat & Mon',
        'Sat, Mon, Wed 2pm-8pm, OPD Mon',
        'Sun, Tue, Thu 3pm-8pm, OPD Tue',
        'Thu 4pm-9pm, OPD Thu',
    ];

    public function run(): void
    {
        $faker = Faker::create();
        $faker->seed(2026); // same data every run

        $doctors = Doctor::with(['user', 'department', 'qualifications'])->orderBy('id')->get();
        $this->command->info("Filling credentials for {$doctors->count()} existing doctors...");

        foreach ($doctors as $i => $doctor) {
            [$spec, $subs, $postgrad] = $this->specFor($doctor->department?->name);
            $employment = $faker->randomElement(['full_time', 'full_time', 'part_time', 'visiting']);
            $dob = $faker->dateTimeBetween('-62 years', '-30 years');

            $values = [
                // Personal & contact
                'date_of_birth' => $dob->format('Y-m-d'),
                'gender' => $faker->randomElement(['male', 'female']),
                'personal_phone' => $this->bdPhone($faker),
                'residential_address' => 'House ' . $faker->numberBetween(1, 60) . ', Road '
                    . $faker->numberBetween(1, 20) . ', ' . $faker->randomElement($this->areas),
                'emergency_contact_name' => $faker->name() . ' ('
                    . $faker->randomElement(['spouse', 'father', 'mother', 'brother', 'sister']) . ')',
                'emergency_contact_phone' => $this->bdPhone($faker),

                // Licensure & specialization
                'license_number' => 'BMDC A-' . (30000 + $doctor->id * 137 % 39999),
                'license_issuing_body' => 'BMDC',
                'license_expiry' => $faker->dateTimeBetween('+6 months', '+4 years')->format('Y-m-d'),
                'specialization' => $spec,
                'sub_specialty' => $faker->randomElement($subs),

                // Employment & operational
                'employment_type' => $employment,
                'date_of_joining' => $faker->dateTimeBetween('-12 years', '-3 months')->format('Y-m-d'),
                'working_hours' => $faker->randomElement($this->schedules),
                'fee_share_percent' => match ($employment) {
                    'visiting'  => $faker->randomElement([55, 60, 65]),
                    'part_time' => $faker->randomElement([40, 45, 50]),
                    default     => $faker->randomElement([30, 35, 40]),
                },

                // Legal & compliance
                'malpractice_insurance_policy_no' => 'MIP-2025-' . str_pad($doctor->id, 4, '0', STR_PAD_LEFT),
                'background_check_status' => 'cleared',
            ];

            // Demo test cases: every 7th doctor has an expired license, every 9th is flagged, every 5th pending
            if ($i % 7 === 6) {
                $values['license_expiry'] = $faker->dateTimeBetween('-1 year', '-1 day')->format('Y-m-d');
            }
            if ($i % 9 === 8) {
                $values['background_check_status'] = 'flagged';
            } elseif ($i % 5 === 4) {
                $values['background_check_status'] = 'pending';
            }

            // Only fill what is empty (keeps hand-entered data). employment_type and
            // background_check_status have DB defaults, so they are always set here.
            $toSave = [];
            foreach ($values as $field => $value) {
                $alwaysSet = in_array($field, ['employment_type', 'background_check_status']);
                if ($alwaysSet || blank($doctor->{$field})) {
                    $toSave[$field] = $value;
                }
            }
            $doctor->update($toSave);

            // Qualifications only if the doctor has none
            if ($doctor->qualifications->isEmpty()) {
                $mbbsYear = (int) $dob->format('Y') + 24;
                $doctor->qualifications()->create([
                    'degree' => 'MBBS',
                    'institution' => $faker->randomElement($this->medicalColleges),
                    'passing_year' => $mbbsYear,
                ]);
                $doctor->qualifications()->create([
                    'degree' => $postgrad,
                    'institution' => str_starts_with($postgrad, 'FCPS')
                        ? 'Bangladesh College of Physicians and Surgeons'
                        : 'Bangabandhu Sheikh Mujib Medical University',
                    'passing_year' => min($mbbsYear + 6, (int) date('Y')),
                ]);
            }

            $this->command->line("  #{$doctor->id} {$doctor->user?->name} — {$spec}");
        }

        $this->command->info('Done.');
    }

    private function specFor(?string $departmentName): array
    {
        $name = strtolower($departmentName ?? '');
        foreach ($this->map as $keyword => $data) {
            if (str_contains($name, $keyword)) {
                return $data;
            }
        }
        // Unknown department: use its own name as the specialization
        $label = $departmentName ?: 'General Practice';
        return [$label, [$label . ' (General)'], 'FCPS (' . $label . ')'];
    }

    private function bdPhone($faker): string
    {
        return '01' . $faker->randomElement(['3', '5', '7', '8', '9']) . $faker->numerify('########');
    }
}