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
        Schema::table('modules', function (Blueprint $table): void {
            $table->foreignUlid('release_id')->nullable()->after('project_id')->constrained()->nullOnDelete();
            $table->index(['project_id', 'release_id']);
        });

        Schema::table('milestones', function (Blueprint $table): void {
            $table->foreignUlid('release_id')->nullable()->after('project_id')->constrained()->nullOnDelete();
            $table->index(['project_id', 'release_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('milestones', function (Blueprint $table): void {
            $table->dropIndex(['project_id', 'release_id']);
            $table->dropConstrainedForeignId('release_id');
        });

        Schema::table('modules', function (Blueprint $table): void {
            $table->dropIndex(['project_id', 'release_id']);
            $table->dropConstrainedForeignId('release_id');
        });
    }
};
