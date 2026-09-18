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
        Schema::create('exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('residence_id')->constrained('residences')->cascadeOnDelete();
            $table->foreignId('mandate_id')->constrained('mandates')->restrictOnDelete();
            $table->string('label'); // "2025", "2025-A" — JAMAIS un entier
            $table->date('started_on');
            $table->date('ended_on');
            $table->string('state')->default('open');
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['residence_id', 'started_on', 'ended_on']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared(<<<'SQL'
                CREATE EXTENSION IF NOT EXISTS btree_gist;

                -- Invariant T1 : Les exercices d'une résidence ne se chevauchent jamais
                ALTER TABLE exercises ADD CONSTRAINT exercises_no_overlap
                EXCLUDE USING gist (
                    residence_id WITH =,
                    daterange(started_on, ended_on, '[]') WITH &&
                );

                -- Invariant T3 : Un seul exercice vivant (open ou closing) par résidence à la fois
                CREATE UNIQUE INDEX unique_active_exercise_per_residence
                ON exercises (residence_id)
                WHERE state IN ('open', 'closing');
            SQL);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared(<<<'SQL'
                DROP INDEX IF EXISTS unique_active_exercise_per_residence;
                ALTER TABLE exercises DROP CONSTRAINT IF EXISTS exercises_no_overlap;
            SQL);
        }

        Schema::dropIfExists('exercises');
    }
};
