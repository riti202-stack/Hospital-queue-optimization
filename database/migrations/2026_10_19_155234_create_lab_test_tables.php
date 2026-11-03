<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::create('lab_tests', function (Blueprint $t) {

            $t->id();
            $t->string('name');
            $t->string('category');
            $t->decimal('price',10,2);
            $t->string('room_no')->nullable();
            $t->unsignedSmallInteger('duration_minutes')->default(15);
            $t->string('preparation')->nullable();
            $t->text('description')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('test_requests',function(Blueprint $t)
        {
            $t->id();
            $t->foreignId('patient_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();//prescribed by

            $t->enum('status',['pending','suggested','accepted','scheduled','completed','cancelled'])->default('pending');
            $t->dateTime('preferred_at');
            $t->dateTime('suggested_at')->nullable();
            $t->dateTime('scheduled_at')->nullable();
            $t->string('room_no')->nullable();
            $t->string('conducted_by')->nullable();
            $t->decimal('total_amount',10,2)->default(0);
            $t->text('patient_note')->nullable();
            $t->text('admin_message')->nullable();
            $t->timestamps();


        });


        Schema::create('test_request_items',function(Blueprint $t)
        {
            $t->id();
            $t->foreignId('test_request_id')->constrained()->cascadeOnDelete();
            $t->foreignId('lab_test_id')->constrained()->restrictOnDelete();
            $t->string('test_name');
            $t->decimal('price',10,2);
            $t->timestamps();

        });
    }

    
    public function down(): void
    {
        Schema::dropIfExists('test_request_items');
        Schema::dropIfExists('test_requests');
        Schema::dropIfExists('lab_tests');
    }
};
