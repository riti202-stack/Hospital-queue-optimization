<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::table('doctors', function (Blueprint $table) {

        $table->date('date_of_birth')->nullable()->after('user_id');
        $table->enum('gender',['male','female','other'])->nullable()->after('date_of_birth');
        $table->string('photo')->nullable()->after('gender');
        $table->string('personal_phone')->nullable()->after('photo');
        $table->string('residential_address')->nullable()->after('personal_phone');
        $table->string('emergency_contact_name')->nullable()->after('residential_address');
        $table->string('emergency_contact_phone')->nullable()->after('emergency_contact_name');



        //Licensure & specialization

        $table->string('license_number')->nullable()->after('room_no');
        $table->string('license_issuing_body')->nullable()->after('license_number');
        $table->date('license_expiry')->nullable()->after('license_issuing_body');
        $table->string('specialization')->nullable()->after('license_expiry');
        $table->string('sub_speciality')->nullable()->after('specialization');

        //employment & operational

        $table->enum('employment_type',['full_time','part_time','visiting'])->default('full_time')->after('sub_speciality');

        $table->date('date_of_joining')->nullable()->after('employment_type');

        $table->string('working_hours')->nullable()->after('date_of_joining');

        $table->decimal('fee_share_percent',5,2)->nullable()->after('working_hours');

        $table->string('malpractice_insurance_policy_no')->nullable()->after('fee_share_percent');

        $table->enum('background_check_status',['pending','cleared','flagged'])->default('pending')->after('malpractice_insurance_policy_no');
            
        });
    }

    
    public function down(): void
    {
        Schema::table('doctors', function (Blueprint $table) {

        $table->dropColumn([
            'date_of_birth', 'gender', 'photo', 'personal_phone', 'residential_address',
            'emergency_contact_name', 'emergency_contact_phone', 'license_number',
            'license_issuing_body', 'license_expiry', 'specialization', 'sub_specialty',
            'employment_type', 'date_of_joining', 'working_hours', 'fee_share_percent',
            'malpractice_insurance_policy_no', 'background_check_status',
        ]);
            //
        });
    }
};
