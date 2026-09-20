<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('queue_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('users')->onDelete('cascade');
        $table->foreignId('doctor_id')->nullable()->constrained('doctors')->onDelete('set null');
        $table->foreignId('department_id')->constrained()->onDelete('cascade');
        $table->foreignId('appointment_id')->nullable()->constrained('appointments')->onDelete('set null');
        $table->enum('urgency_level', ['emergency', 'urgent', 'routine']);
        $table->float('priority_score')->default(0);
        $table->timestamp('checked_in_at')->useCurrent();
        $table->enum('status', ['waiting', 'in_progress', 'completed'])->default('waiting');
        $table->timestamp('started_at')->nullable();
        $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('queue_entries');
    }
};
