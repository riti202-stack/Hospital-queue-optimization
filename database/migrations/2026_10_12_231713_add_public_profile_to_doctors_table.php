<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            if (! Schema::hasColumn('doctors', 'about')) {
                $table->text('about')->nullable();
            }
            if (! Schema::hasColumn('doctors', 'chamber_phone')) {
                $table->string('chamber_phone')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            $table->dropColumn(['about', 'chamber_phone']);
        });
    }
};