<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\User;
use Carbon\Carbon;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * Demo patients + bookings for testing the scheduling algorithm.
 *
 * Creates 40 patients (patient1@hqos.test ... patient40@hqos.test, password: password)
 * and appointments that cover these test scenarios:
 *   1. History (last 45 days)  -> completed / no-show / cancelled, feeds avg consult time + no-show prediction
 *   2. Busy doctors today       -> 3 doctors with a long queue, some already served, rest waiting
 *   3. Same-time collision      -> 2 patients booked into the SAME slot of a busy doctor (tie-break test)
 *   4. Mid-queue cancellation   -> a waiting patient cancels, queue behind should move up
 *   5. Normal load today        -> 1-3 bookings for every other bookable doctor, morning + afternoon
 *   6. Upcoming 5 days          -> spread bookings for all bookable doctors
 *   7. Frequent no-show patients (patient1-5) with upcoming bookings -> prediction should flag them
 *   8. Same patient, two doctors on the same day (patient31) -> must not clash
 *   9. Unbookable doctors (unavailable / flagged / expired license) get NO future bookings
 *
 * Safe to re-run: it deletes only the appointments of these 40 demo patients first.
 * Run: php artisan db:seed --class=PatientBookingSeeder
 */
class PatientBookingSeeder extends Seeder
{
    // Set to false if your scheduling algorithm creates queue_entries itself
    private bool $fillQueueEntries = true;

    private array $firstNames = [
        'Rahim', 'Karim', 'Fatema', 'Ayesha', 'Hasan', 'Sumaiya', 'Rakib', 'Nadia', 'Tamim', 'Mithila',
        'Sakib', 'Jannat', 'Riyad', 'Tania', 'Fahim', 'Lamia', 'Shuvo', 'Nabila', 'Imran', 'Sadia',
    ];
    private array $lastNames = [
        'Uddin', 'Ahmed', 'Begum', 'Khatun', 'Hossain', 'Islam', 'Rahman', 'Akter', 'Chowdhury', 'Sarker',
    ];
    private array $complaints = [
        'Fever and headache', 'Chest pain', 'Follow-up visit', 'Back pain', 'Cough for two weeks',
        'Toothache', 'Knee pain', 'Child vaccination check', 'High blood pressure', 'Skin rash',
        'Stomach pain', 'Routine check-up', 'Shortness of breath', 'Ear pain', 'Joint swelling',
    ];

    private array $apptCols = [];
    private string $patientCol = 'patient_id';
    private array $status = [];
    private array $busy = []; // "patientId|Y-m-d H:i" => true, prevents a patient being in two places at once
    private $faker;
    private array $counts = [];

    public function run(): void
    {
        $this->faker = Faker::create();
        $this->faker->seed(2026);

        $this->apptCols   = Schema::getColumnListing('appointments');
        $this->patientCol = in_array('patient_id', $this->apptCols) ? 'patient_id' : 'user_id';
        $this->status     = $this->mapEnum('appointments', 'status', [
            'booked'    => ['booked', 'pending', 'scheduled', 'confirmed', 'waiting'],
            'completed' => ['completed', 'done', 'served', 'finished'],
            'cancelled' => ['cancelled', 'canceled'],
            'no-show'   => ['no-show', 'no_show', 'noshow', 'missed'],
        ]);

        // ---------- Patients ----------
        $password = Hash::make('password');
        $patients = collect();
        for ($i = 1; $i <= 40; $i++) {
            // unique first+last combination for all 40 patients
            $name = $this->firstNames[($i - 1) % 20] . ' '
                . $this->lastNames[(($i - 1) + intdiv($i - 1, 20) * 3) % 10];
            $patients->push(User::updateOrCreate(
                ['email' => "patient{$i}@hqos.test"],
                ['name' => $name, 'password' => $password, 'role' => 'patient']
            ));
        }
        $p = fn (int $n) => $patients[$n - 1]; // patient number -> user

        // ---------- Clean previous demo bookings ----------
        $this->cleanOldBookings($patients->pluck('id'));

        // ---------- Doctors ----------
        $all = Doctor::with('department', 'user')->orderBy('id')->get();
        $bookable = $all->filter(fn ($d) =>
            $d->is_available
            && ($d->background_check_status ?? 'cleared') !== 'flagged'
            && (! $d->license_expiry || Carbon::parse($d->license_expiry)->isFuture())
        )->values();

        if ($bookable->isEmpty()) {
            $this->command->error('No bookable doctors found (available, not flagged, license valid).');
            return;
        }

        // 3 busy doctors from 3 different departments
        $busyDoctors = $bookable->groupBy('department_id')->map->first()->take(3)->values();
        $otherDoctors = $bookable->reject(fn ($d) => $busyDoctors->contains('id', $d->id))->values();

        $frequentNoShow = range(1, 5);   // patients with poor attendance history
        $regular        = range(6, 30);  // normal attendance
        $newPatients    = range(31, 40); // no history at all

        // ---------- 1. History: last 45 days ----------
        foreach (array_merge($frequentNoShow, $regular) as $n) {
            $regularDoctor = $bookable->random();
            $noShowRate = in_array($n, $frequentNoShow) ? 0.6 : 0.08;

            for ($k = 0, $visits = $this->faker->numberBetween(4, 8); $k < $visits; $k++) {
                $doctor = $this->faker->boolean(60) ? $regularDoctor : $bookable->random();
                $time = $this->slot(now()->subDays($this->faker->numberBetween(1, 45)));

                $roll = $this->faker->randomFloat(2, 0, 1);
                $status = $roll < 0.10 ? 'cancelled' : ($roll < 0.10 + $noShowRate ? 'no-show' : 'completed');

                $this->book($p($n), $doctor, $time, $status, $time->copy()->subDays($this->faker->numberBetween(1, 7)), 'history');
            }
        }

        // ---------- 2-4. Busy doctors today ----------
        // Queue starts 2 hours before "now" and the doctor runs ~1 hour behind schedule,
        // so waiting patients have genuinely different waiting times (realistic busy clinic)
        $start = now()->copy()->subHours(2)->minute(intdiv(now()->minute, 15) * 15)->second(0);
        $start = $start->max(today()->setTime(9, 0))->min(today()->setTime(20, 0));

        $patientPool = array_merge($regular, $newPatients);
        shuffle($patientPool);

        foreach ($busyDoctors as $d => $doctor) {
            $ids = [];
            for ($s = 0; $s < 14; $s++) {
                $time = $start->copy()->addMinutes(15 * $s);
                $status = $time->lt(now()->subMinutes(60)) ? ($s === 1 ? 'no-show' : 'completed') : 'booked';
                $n = $this->freePatient($patientPool, $p, $time);
                $ids[$s] = $this->book($p($n), $doctor, $time, $status,
                    now()->subDays(3)->addMinutes($s * 7), 'busy-today');
            }

            // 3. Collision: second patient in the exact same slot as slot #6, booked 20 minutes later
            $collisionTime = $start->copy()->addMinutes(15 * 6);
            $n = $this->freePatient($patientPool, $p, $collisionTime);
            $this->book($p($n), $doctor, $collisionTime,
                $collisionTime->lt(now()) ? 'completed' : 'booked',
                now()->subDays(3)->addMinutes(6 * 7 + 20), 'same-slot-collision');

            // 4. Mid-queue cancellation (slot #9)
            DB::table('appointments')->where('id', $ids[9])->update(['status' => $this->status['cancelled']]);
            $this->counts['busy-today']--;
            $this->counts['mid-queue-cancel'] = ($this->counts['mid-queue-cancel'] ?? 0) + 1;
        }

        // ---------- 5. Normal load today ----------
        foreach ($otherDoctors as $doctor) {
            for ($k = 0, $c = $this->faker->numberBetween(1, 3); $k < $c; $k++) {
                $time = $this->slot(today());
                $status = $time->lt(now()) ? ($this->faker->boolean(85) ? 'completed' : 'no-show') : 'booked';
                $n = $this->freePatient($patientPool, $p, $time);
                $this->book($p($n), $doctor, $time, $status, now()->subDays($this->faker->numberBetween(1, 5)), 'normal-today');
            }
        }

        // ---------- 6. Upcoming 5 days ----------
        for ($day = 1; $day <= 5; $day++) {
            foreach ($bookable as $doctor) {
                for ($k = 0, $c = $this->faker->numberBetween(1, 4); $k < $c; $k++) {
                    $time = $this->slot(today()->addDays($day));
                    $n = $this->freePatient($patientPool, $p, $time);
                    $this->book($p($n), $doctor, $time, 'booked', now()->subHours($this->faker->numberBetween(1, 120)), 'upcoming');
                }
            }
        }

        // ---------- 7. Frequent no-show patients with upcoming bookings ----------
        foreach ($frequentNoShow as $i => $n) {
            $time = today()->addDay()->setTime(10, 0)->addMinutes(15 * $i);
            if (! isset($this->busy[$p($n)->id . '|' . $time->format('Y-m-d H:i')])) {
                $this->book($p($n), $busyDoctors[$i % $busyDoctors->count()], $time, 'booked', now()->subDay(), 'risky-upcoming');
            }
        }

        // ---------- 8. One patient, two doctors, same day ----------
        $twoDocs = $bookable->shuffle()->take(2)->values();
        $this->book($p(31), $twoDocs[0], today()->addDays(2)->setTime(9, 30), 'booked', now(), 'two-doctors-same-day');
        if ($twoDocs->count() > 1) {
            $this->book($p(31), $twoDocs[1], today()->addDays(2)->setTime(15, 0), 'booked', now(), 'two-doctors-same-day');
        }

        // ---------- Queue entries for today ----------
        if ($this->fillQueueEntries) {
            $this->buildTodayQueue($busyDoctors, $patientPool, $p);
        }

        // ---------- Summary ----------
        $this->command->info('Demo patients: patient1@hqos.test ... patient40@hqos.test (password: password)');
        $this->command->table(['Scenario', 'Appointments'], collect($this->counts)->map(fn ($v, $k) => [$k, $v])->values());
        $this->command->table(['Busy doctor (today)', 'Department', 'Doctor ID'], $busyDoctors->map(fn ($d) => [
            $d->user->name ?? 'Doctor #' . $d->id, $d->department->name ?? '-', $d->id,
        ]));
        $unbookable = $all->count() - $bookable->count();
        $this->command->line("Unbookable doctors (unavailable / flagged / expired) left with no future bookings: {$unbookable}");
        $this->command->line('Frequent no-show patients: patient1 - patient5 | New patients (no history): patient31 - patient40');
    }

    // ===================================================================

    private function book(User $patient, Doctor $doctor, Carbon $time, string $status, Carbon $bookedAt, string $scenario): int
    {
        $this->busy[$patient->id . '|' . $time->format('Y-m-d H:i')] = true;
        $bookedAt = $bookedAt->min(now())->min($time->copy()->subMinutes(30));

        $row = [
            $this->patientCol  => $patient->id,
            'doctor_id'        => $doctor->id,
            'department_id'    => $doctor->department_id,
            'scheduled_time'   => $time->format('Y-m-d H:i:s'),
            'appointment_date' => $time->format('Y-m-d'),
            'appointment_time' => $time->format('H:i:s'),
            'status'           => $this->status[$status],
            'reason'           => $this->faker->randomElement($this->complaints),
            'notes'            => null,
            'created_at'       => $bookedAt->format('Y-m-d H:i:s'),
            'updated_at'       => now()->format('Y-m-d H:i:s'),
        ];
        if (in_array('symptoms', $this->apptCols)) $row['symptoms'] = $row['reason'];

        $row = array_intersect_key($row, array_flip($this->apptCols)); // only columns your table has
        $this->counts[$scenario] = ($this->counts[$scenario] ?? 0) + 1;

        return DB::table('appointments')->insertGetId($row);
    }

    /** Random 15-minute slot on a given day: 9:00-12:45 or 14:00-17:45 */
    private function slot(Carbon $day): Carbon
    {
        $hour = $this->faker->boolean(55)
            ? $this->faker->numberBetween(9, 12)
            : $this->faker->numberBetween(14, 17);
        return $day->copy()->setTime($hour, $this->faker->randomElement([0, 15, 30, 45]));
    }

    /** A patient number that has no other booking at this exact time */
    private function freePatient(array $pool, callable $p, Carbon $time): int
    {
        foreach ($this->faker->shuffleArray($pool) as $n) {
            if (! isset($this->busy[$p($n)->id . '|' . $time->format('Y-m-d H:i')])) {
                return $n;
            }
        }
        return $pool[0];
    }

    private function cleanOldBookings($patientIds): void
    {
        if (Schema::hasTable('queue_entries')) {
            $removed = DB::table('queue_entries')->whereIn('patient_id', $patientIds)->delete();
            if ($removed) $this->command->line("Removed {$removed} old demo queue entries.");
        }

        $old = DB::table('appointments')->whereIn($this->patientCol, $patientIds)->pluck('id');
        if ($old->isEmpty()) return;

        try {
            if (Schema::hasTable('queue_entries') && Schema::hasColumn('queue_entries', 'appointment_id')) {
                $qe = DB::table('queue_entries')->whereIn('appointment_id', $old)->pluck('id');
                if (Schema::hasTable('queue_logs') && Schema::hasColumn('queue_logs', 'queue_entry_id')) {
                    DB::table('queue_logs')->whereIn('queue_entry_id', $qe)->delete();
                }
                DB::table('queue_entries')->whereIn('appointment_id', $old)->delete();
            }
            DB::table('appointments')->whereIn('id', $old)->delete();
            $this->command->line("Removed {$old->count()} old demo appointments.");
        } catch (\Throwable $e) {
            $this->command->warn('Could not delete old demo appointments (other tables reference them). '
                . 'New ones will be added on top. Use migrate:fresh --seed for a clean start.');
        }
    }

    /**
     * Builds today's queue_entries to match your table:
     * urgency_level (emergency/urgent/routine), checked_in_at, status (waiting/in_progress/completed),
     * started_at, completed_at. priority_score is left at 0 for YOUR algorithm to calculate.
     */
    private function buildTodayQueue($busyDoctors, array $patientPool, callable $p): void
    {
        if (! Schema::hasTable('queue_entries')) {
            $this->command->line('No queue_entries table found, queue step skipped.');
            return;
        }

        $deptNames = \App\Models\Department::pluck('name', 'id');
        $urgencyFor = function ($departmentId) use ($deptNames) {
            $isEmergencyDept = str_contains(strtolower($deptNames[$departmentId] ?? ''), 'emerg');
            $roll = $this->faker->numberBetween(1, 100);
            if ($isEmergencyDept) {
                return $roll <= 30 ? 'emergency' : ($roll <= 70 ? 'urgent' : 'routine');
            }
            return $roll <= 7 ? 'emergency' : ($roll <= 25 ? 'urgent' : 'routine');
        };

        // Only patients who were seen or are still expected; cancelled + no-show never enter the queue
        $today = DB::table('appointments')
            ->whereDate('scheduled_time', today())
            ->whereIn('status', [$this->status['booked'], $this->status['completed']])
            ->orderBy('doctor_id')->orderBy('scheduled_time')->orderBy('created_at')
            ->get();

        $rows = [];
        $inProgressGiven = [];

        foreach ($today as $a) {
            $slot = Carbon::parse($a->scheduled_time);
            $done = $a->status === $this->status['completed'];

            // Patient arrives 5-40 min before the slot; anyone not arrived yet is not in the queue
            $arrived = $slot->copy()->subMinutes($this->faker->numberBetween(5, 40));
            if ($arrived->gt(now())) {
                continue;
            }

            $status = 'waiting';
            $started = $completed = null;

            if ($done) {
                $status = 'completed';
                $started = $slot->copy()->addMinutes($this->faker->numberBetween(0, 10));
                $completed = $started->copy()->addMinutes($this->faker->numberBetween(6, 20));
                if ($completed->gt(now())) {
                    $completed = now()->subMinute();
                }
            } elseif ($busyDoctors->contains('id', $a->doctor_id) && ! isset($inProgressGiven[$a->doctor_id]) && $slot->lte(now())) {
                // Exactly one patient currently with each busy doctor
                $status = 'in_progress';
                $started = now()->subMinutes($this->faker->numberBetween(2, 8));
                $inProgressGiven[$a->doctor_id] = true;
            }

            $rows[] = [
                'patient_id'     => $a->{$this->patientCol},
                'doctor_id'      => $a->doctor_id,
                'department_id'  => $a->department_id,
                'appointment_id' => $a->id,
                'urgency_level'  => $urgencyFor($a->department_id),
                'priority_score' => 0,
                'checked_in_at'  => $arrived->format('Y-m-d H:i:s'),
                'status'         => $status,
                'started_at'     => $started?->format('Y-m-d H:i:s'),
                'completed_at'   => $completed?->format('Y-m-d H:i:s'),
                'created_at'     => $arrived->format('Y-m-d H:i:s'),
                'updated_at'     => now()->format('Y-m-d H:i:s'),
            ];
        }

        // Walk-ins with NO appointment for each busy doctor: they should jump the queue
        $queued = collect($rows)->pluck('patient_id')->flip(); // patients already in a queue today
        foreach ($busyDoctors as $doctor) {
            foreach ([['emergency', 4], ['urgent', 15], ['routine', 25]] as [$level, $minsAgo]) {
                $candidates = array_values(array_filter($patientPool, fn ($n) => ! isset($queued[$p($n)->id])));
                $n = $candidates ? $candidates[array_rand($candidates)] : $patientPool[0];
                $queued[$p($n)->id] = true;
                $at = now()->subMinutes($minsAgo);
                $rows[] = [
                    'patient_id'     => $p($n)->id,
                    'doctor_id'      => $doctor->id,
                    'department_id'  => $doctor->department_id,
                    'appointment_id' => null,
                    'urgency_level'  => $level,
                    'priority_score' => 0,
                    'checked_in_at'  => $at->format('Y-m-d H:i:s'),
                    'status'         => 'waiting',
                    'started_at'     => null,
                    'completed_at'   => null,
                    'created_at'     => $at->format('Y-m-d H:i:s'),
                    'updated_at'     => now()->format('Y-m-d H:i:s'),
                ];
                $this->counts["walk-in-{$level}"] = ($this->counts["walk-in-{$level}"] ?? 0) + 1;
            }
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('queue_entries')->insert($chunk);
        }

        $byStatus = collect($rows)->countBy('status');
        $byUrgency = collect($rows)->where('status', 'waiting')->countBy('urgency_level');
        $this->command->line(sprintf(
            'Queue today: %d waiting (%d emergency, %d urgent, %d routine), %d in progress, %d completed.',
            $byStatus['waiting'] ?? 0, $byUrgency['emergency'] ?? 0, $byUrgency['urgent'] ?? 0,
            $byUrgency['routine'] ?? 0, $byStatus['in_progress'] ?? 0, $byStatus['completed'] ?? 0
        ));
    }

    /** Maps wanted status names to the values your enum column actually allows */
    private function mapEnum(string $table, string $column, array $wanted): array
    {
        $allowed = null;
        try {
            $info = DB::select("SHOW COLUMNS FROM `{$table}` WHERE Field = ?", [$column]);
            if ($info && str_starts_with(strtolower($info[0]->Type), 'enum(')) {
                preg_match_all("/'([^']*)'/", $info[0]->Type, $m);
                $allowed = $m[1];
            }
        } catch (\Throwable $e) {
        }

        $map = [];
        foreach ($wanted as $key => $candidates) {
            $map[$key] = $allowed
                ? (collect($candidates)->first(fn ($c) => in_array($c, $allowed)) ?? $allowed[0])
                : $key;
        }
        return $map;
    }
}