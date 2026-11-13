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
        Schema::create('task_relations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('project_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('task_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('related_task_id')->constrained('tasks')->cascadeOnDelete();
            $table->string('type', 30);
            $table->timestamps();

            $table->unique(['task_id', 'related_task_id', 'type']);
            $table->index(['project_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_relations');
    }
};
