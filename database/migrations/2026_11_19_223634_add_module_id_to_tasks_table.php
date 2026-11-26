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
            $table->foreignUlid('module_id')->nullable()->after('milestone_id')->constrained()->nullOnDelete();
            $table->index(['project_id', 'module_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropForeign(['module_id']);
            $table->dropIndex(['project_id', 'module_id']);
            $table->dropColumn('module_id');
        });
    }
};
