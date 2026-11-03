<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::create('dependents',function(Blueprint $t)
        {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('name',100);
            $t->string('relation',30);
            $t->string('gender',10)->nullable();
            $t->date('date_of_birth')->nullable();
            $t->string('blood_group',5)->nullable();
            $t->string('phone',20)->nullable();
            $t->timestamps();
        });

        foreach(['appointments','queue_entries','test_requests'] as $table){
            if(!Schema::hasColumn($table,'dependent_id'))
                {
                    Schema::table($table,function(Blueprint $t)
                    {
                        $t->foreignId('dependent_id')->nullable()->constrained('dependents')->nullOnDelete();

                    });
                }
        }

        Schema::create('prescriptions',function(Blueprint $t)
        {

        $t->id();
            $t->foreignId('queue_entry_id')->nullable()->unique()->constrained()->nullOnDelete();
            $t->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('patient_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('dependent_id')->nullable()->constrained('dependents')->nullOnDelete();
            $t->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $t->text('symptoms')->nullable();
            $t->text('diagnosis');
            $t->text('tests_advised')->nullable();
            $t->text('advice')->nullable();
            $t->date('follow_up_date')->nullable();
            $t->timestamps();

        });

        Schema::create('prescription_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('prescription_id')->constrained()->cascadeOnDelete();
            $t->string('medicine', 120);
            $t->string('dosage', 30);              // 1+0+1
            $t->string('duration', 30);            // 7 days
            $t->string('instruction', 120)->nullable(); // after meal
            $t->timestamps();
        });


        Schema::create('doctor_reviews', function (Blueprint $t) {
            $t->id();
            $t->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $t->foreignId('patient_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('queue_entry_id')->unique()->constrained()->cascadeOnDelete();
            $t->unsignedTinyInteger('rating');
            $t->text('comment')->nullable();
            $t->boolean('is_visible')->default(true);
            $t->timestamps();
        });

        Schema::table('test_requests', function (Blueprint $t) {
            if (!Schema::hasColumn('test_requests', 'report_path'))        $t->string('report_path')->nullable();
            if (!Schema::hasColumn('test_requests', 'report_uploaded_at')) $t->timestamp('report_uploaded_at')->nullable();
            if (!Schema::hasColumn('test_requests', 'result_note'))        $t->text('result_note')->nullable();
        });


        Schema::table('appointments', function (Blueprint $t) {
            if (!Schema::hasColumn('appointments', 'reminder_sent_at')) $t->timestamp('reminder_sent_at')->nullable();
            if (!Schema::hasColumn('appointments', 'reschedule_count')) $t->unsignedTinyInteger('reschedule_count')->default(0);
            if (!Schema::hasColumn('appointments', 'cancelled_at'))     $t->timestamp('cancelled_at')->nullable();
            if (!Schema::hasColumn('appointments', 'cancel_reason'))    $t->string('cancel_reason')->nullable();
        });


        Schema::table('queue_entries', function (Blueprint $t) {
            if (!Schema::hasColumn('queue_entries', 'near_alert_sent_at')) $t->timestamp('near_alert_sent_at')->nullable();
        });

        Schema::table('users', function (Blueprint $t) {
            if (!Schema::hasColumn('users', 'notify_email')) $t->boolean('notify_email')->default(true);
            if (!Schema::hasColumn('users', 'notify_sms'))   $t->boolean('notify_sms')->default(true);
            if (!Schema::hasColumn('users', 'locale'))       $t->string('locale', 5)->default('en');
        });



    }

    
    public function down(): void
    {
        Schema::dropIfExists('doctor_reviews');
        Schema::dropIfExists('prescription_items');
        Schema::dropIfExists('prescriptions');

        foreach (['appointments', 'queue_entries', 'test_requests'] as $table) {
            if (Schema::hasColumn($table, 'dependent_id')) {
                Schema::table($table, fn (Blueprint $t) => $t->dropConstrainedForeignId('dependent_id'));
            }
        }

        Schema::table('test_requests', fn (Blueprint $t) => $t->dropColumn(['report_path', 'report_uploaded_at', 'result_note']));
        Schema::table('appointments', fn (Blueprint $t) => $t->dropColumn(['reminder_sent_at', 'reschedule_count', 'cancelled_at', 'cancel_reason']));
        Schema::table('queue_entries', fn (Blueprint $t) => $t->dropColumn('near_alert_sent_at'));
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['notify_email', 'notify_sms', 'locale']));

        Schema::dropIfExists('dependents');
    }
};
