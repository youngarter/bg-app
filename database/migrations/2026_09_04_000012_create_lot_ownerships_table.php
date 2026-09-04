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
        Schema::create('lot_ownerships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('residence_id')->constrained('residences')->cascadeOnDelete();
            $table->foreignId('lot_id')->constrained('lots')->cascadeOnDelete();
            $table->foreignId('owner_id')->constrained('owners')->cascadeOnDelete();
            $table->integer('quote_part'); // points de base (10 000 = 100 %)
            $table->string('nature'); // pleine_propriete, indivision, usufruit, nue_propriete
            $table->date('started_on');
            $table->date('ended_on')->nullable();
            $table->string('document_path')->nullable();
            $table->timestamps();

            $table->index(['lot_id', 'started_on', 'ended_on']);
            $table->index('residence_id');
        });

        // Invariant S3 garanti en base au niveau PostgreSQL : aucun chevauchement de détention pour le même propriétaire sur le même lot
        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared(<<<'SQL'
                CREATE EXTENSION IF NOT EXISTS btree_gist;

                ALTER TABLE lot_ownerships ADD CONSTRAINT lot_ownerships_no_overlap
                EXCLUDE USING gist (
                    lot_id WITH =,
                    owner_id WITH =,
                    daterange(started_on, COALESCE(ended_on, 'infinity'::date), '[]') WITH &&
                );
            SQL);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared('ALTER TABLE lot_ownerships DROP CONSTRAINT IF EXISTS lot_ownerships_no_overlap;');
        }

        Schema::dropIfExists('lot_ownerships');
    }
};
