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
        Schema::create('projects', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('unit_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('identifier', 12);
            $table->text('description')->nullable();
            $table->string('visibility', 32)->default('unit');
            $table->string('status', 32)->default('draft');
            $table->foreignId('project_lead_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('default_assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('start_date')->nullable();
            $table->date('target_date')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->foreignId('archived_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['workspace_id', 'identifier']);
            $table->index(['workspace_id', 'status', 'archived_at']);
            $table->index(['unit_id', 'visibility']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
