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
        Schema::table('project_updates', function (Blueprint $table): void {
            $table->date('period_start')->nullable()->after('author_id');
            $table->date('period_end')->nullable()->after('period_start');
            $table->text('accomplishments')->nullable()->after('summary');
            $table->text('decisions_needed')->nullable()->after('risks');
            $table->date('next_update_at')->nullable()->after('next_steps');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_updates', function (Blueprint $table): void {
            $table->dropColumn(['period_start', 'period_end', 'accomplishments', 'decisions_needed', 'next_update_at']);
        });
    }
};
