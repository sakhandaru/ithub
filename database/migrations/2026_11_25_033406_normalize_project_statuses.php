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
        DB::table('projects')->whereIn('status', ['planned', 'on_hold', 'completed', 'canceled'])->update(['status' => 'active']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
