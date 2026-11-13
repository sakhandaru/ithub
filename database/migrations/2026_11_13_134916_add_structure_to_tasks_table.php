<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignUlid('parent_task_id')->nullable()->after('project_id')->constrained('tasks')->nullOnDelete();
            $table->foreignUlid('milestone_id')->nullable()->after('parent_task_id')->constrained()->nullOnDelete();
            $table->index(['project_id', 'parent_task_id']);
            $table->index(['project_id', 'milestone_id']);
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['parent_task_id']);
            $table->dropForeign(['milestone_id']);
            $table->dropColumn(['parent_task_id', 'milestone_id']);
        });
    }
};
