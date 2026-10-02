<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        DB::statement("ALTER TABLE queue_entries MODIFY status ENUM('waiting','in_progress','referred','completed') DEFAULT 'waiting'");
    }

    
    public function down(): void
    {
        DB::statement("ALTER TABLE queue_entries MODIFY status ENUM('waiting','in_progress','completed')DEFAULT 'waiting'");
    }
};
