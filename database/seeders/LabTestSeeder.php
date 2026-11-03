<?php

namespace Database\Seeders;

use App\Models\LabTest;
use Illuminate\Database\Seeder;


class LabTestSeeder extends Seeder
{
    public function run(): void
    {
        $tests = [
            // [name, category, price, room, preparation]
            ['Complete Blood Count (CBC)', 'Blood tests', 400, 'L-101', null],
            ['ESR', 'Blood tests', 200, 'L-101', null],
            ['Blood Grouping & Rh Factor', 'Blood tests', 150, 'L-101', null],
            ['C-Reactive Protein (CRP)', 'Blood tests', 600, 'L-101', null],
            ['Fasting Blood Sugar (FBS)', 'Diabetes', 150, 'L-101', 'Fast for 8–10 hours (water is fine)'],
            ['Random Blood Sugar (RBS)', 'Diabetes', 150, 'L-101', null],
            ['HbA1c', 'Diabetes', 1200, 'L-101', null],
            ['Lipid Profile', 'Heart & cholesterol', 1200, 'L-101', 'Fast for 10–12 hours (water is fine)'],
            ['Serum Creatinine', 'Kidney & liver', 400, 'L-102', null],
            ['Serum Uric Acid', 'Kidney & liver', 400, 'L-102', null],
            ['SGPT (ALT)', 'Kidney & liver', 400, 'L-102', null],
            ['Thyroid (TSH)', 'Hormones', 800, 'L-102', null],
            ['Vitamin D (25-OH)', 'Hormones', 2500, 'L-102', null],
            ['Urine Routine Examination', 'Urine & stool', 300, 'L-103', 'Bring an early-morning urine sample'],
            ['Stool Routine Examination', 'Urine & stool', 300, 'L-103', null],
            ['Dengue NS1 Antigen', 'Infection', 500, 'L-101', null],
            ['ECG', 'Heart & cholesterol', 400, 'C-105', null],
            ['Echocardiogram', 'Heart & cholesterol', 2500, 'C-106', null],
            ['Chest X-ray (PA view)', 'Imaging', 600, 'R-201', 'Remove metal jewellery'],
            ['Ultrasound — Whole Abdomen', 'Imaging', 2000, 'R-202', 'Fast for 6 hours and come with a full bladder'],
        ];

        foreach ($tests as [$name, $category, $price, $room, $prep]) {
            LabTest::updateOrCreate(['name' => $name], [
                'category' => $category, 'price' => $price, 'room_no' => $room,
                'preparation' => $prep, 'duration_minutes' => 15, 'is_active' => true,
            ]);
        }

        $this->command->info(count($tests) . ' lab tests ready.');
    }
}