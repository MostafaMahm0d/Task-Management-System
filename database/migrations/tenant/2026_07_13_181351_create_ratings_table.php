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
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('rater_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('work_quality');
            $table->unsignedTinyInteger('communication');
            $table->unsignedTinyInteger('teamwork');
            $table->unsignedTinyInteger('punctuality');
            $table->decimal('overall_score', 3, 2);
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->text('comments')->nullable();
            $table->timestamps();

            $table->index('employee_id');
            $table->index('rater_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};
