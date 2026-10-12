<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('queue_entries', 'token_no')) {
            Schema::table('queue_entries', function (Blueprint $table) {
                $table->unsignedInteger('token_no')->nullable()->after('appointment_id');
            });
        }

        // Stop MySQL/MariaDB from resetting checked_in_at on every update
        DB::statement("ALTER TABLE queue_entries MODIFY checked_in_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");

        // Give existing rows a queue number: per department, per day, in arrival order
        $rows = DB::table('queue_entries')->orderBy('checked_in_at')->orderBy('id')
            ->get(['id', 'department_id', 'checked_in_at']);

        $counters = [];
        foreach ($rows as $r) {
            $key = $r->department_id . '|' . substr((string) $r->checked_in_at, 0, 10);
            $counters[$key] = ($counters[$key] ?? 0) + 1;
            DB::table('queue_entries')->where('id', $r->id)->update([
                'token_no'      => $counters[$key],
                'checked_in_at' => $r->checked_in_at,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('queue_entries', function (Blueprint $table) {
            $table->dropColumn('token_no');
        });
    }
};