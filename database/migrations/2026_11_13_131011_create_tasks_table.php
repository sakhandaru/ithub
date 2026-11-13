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
        Schema::create('tasks', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('project_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('number');
            $table->string('identifier', 32);
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('status', 30)->default('backlog');
            $table->string('priority', 20)->nullable();
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('requester_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('created_by_id')->constrained('users')->restrictOnDelete();
            $table->date('due_date')->nullable();
            $table->text('blocked_reason')->nullable();
            $table->text('canceled_reason')->nullable();
            $table->text('reopen_reason')->nullable();
            $table->text('completion_note')->nullable();
            $table->text('evidence_url')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'number']);
            $table->unique(['project_id', 'identifier']);
            $table->index(['project_id', 'status']);
            $table->index(['assignee_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
