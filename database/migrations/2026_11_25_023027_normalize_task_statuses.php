<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('tasks')->where('status', 'request')->update(['status' => 'backlog']);
        DB::table('tasks')->where('status', 'in_review')->update(['status' => 'review']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('tasks')->where('status', 'review')->update(['status' => 'in_review']);
    }
};
