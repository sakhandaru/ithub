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
        Schema::table('tasks', function (Blueprint $table): void {
            $table->index(['project_id', 'due_date', 'status']);
            $table->index(['project_id', 'requester_id', 'status']);
            $table->index(['project_id', 'milestone_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropIndex(['project_id', 'due_date', 'status']);
            $table->dropIndex(['project_id', 'requester_id', 'status']);
            $table->dropIndex(['project_id', 'milestone_id', 'status']);
        });
    }
};
