<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('color')->default('#6b7280');
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_completed')->default(false);
            $table->timestamps();

            $table->unique('name');
        });

        $now = now();

        DB::table('statuses')->insert([
            ['name' => 'To Do', 'color' => '#6b7280', 'position' => 1, 'is_default' => true, 'is_completed' => false, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'In Progress', 'color' => '#3b82f6', 'position' => 2, 'is_default' => false, 'is_completed' => false, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'In Review', 'color' => '#f59e0b', 'position' => 3, 'is_default' => false, 'is_completed' => false, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Done', 'color' => '#10b981', 'position' => 4, 'is_default' => false, 'is_completed' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Cancelled', 'color' => '#ef4444', 'position' => 5, 'is_default' => false, 'is_completed' => false, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('statuses');
    }
};
