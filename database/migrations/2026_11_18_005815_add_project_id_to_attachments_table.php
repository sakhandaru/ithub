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
        Schema::table('attachments', function (Blueprint $table) {
            $table->foreignUlid('project_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('task_id')->nullable()->change();
            $table->index(['project_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attachments', function (Blueprint $table) {
            $table->dropIndex(['project_id', 'created_at']);
            $table->dropConstrainedForeignId('project_id');
            $table->foreignUlid('task_id')->nullable(false)->change();
        });
    }
};
