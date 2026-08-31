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
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('status_id')->nullable()->after('status')->constrained()->restrictOnDelete();
        });

        $statusIdsByName = DB::table('statuses')->pluck('id', 'name');

        $legacyStatusMap = [
            'todo' => 'To Do',
            'in_progress' => 'In Progress',
            'in_review' => 'In Review',
            'done' => 'Done',
            'cancelled' => 'Cancelled',
        ];

        foreach ($legacyStatusMap as $legacyValue => $statusName) {
            if ($statusIdsByName->has($statusName)) {
                DB::table('tasks')
                    ->where('status', $legacyValue)
                    ->update(['status_id' => $statusIdsByName[$statusName]]);
            }
        }

        DB::table('tasks')
            ->whereNull('status_id')
            ->update(['status_id' => $statusIdsByName['To Do']]);

        DB::statement('ALTER TABLE tasks MODIFY status_id BIGINT UNSIGNED NOT NULL');

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('status')->default('todo')->after('description');
        });

        DB::table('tasks')->update(['status' => 'todo']);

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('status_id');
        });
    }
};
