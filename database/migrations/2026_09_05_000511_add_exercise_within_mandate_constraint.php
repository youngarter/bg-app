<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Invariant T2 : [exercise.started_on, ended_on] ⊆ [mandate.started_on, ended_on].
 *
 * T1 et T3 disposaient déjà d'une garantie PostgreSQL (EXCLUDE, index partiel unique),
 * mais T2 ne vivait qu'au niveau applicatif dans CreateExerciseAction. Tout écrivain qui
 * contournait l'Action — seeder, factory, import — pouvait poser un exercice débordant de
 * son mandat, ce qui s'est effectivement produit sur les données de démonstration.
 *
 * T2 porte sur deux tables : ni un CHECK ni une contrainte d'exclusion ne peuvent l'exprimer.
 * Un trigger est ici la seule garantie structurelle possible.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION exercise_within_mandate() RETURNS trigger AS $$
            DECLARE
                m_started date;
                m_ended   date;
                m_residence bigint;
            BEGIN
                SELECT started_on, ended_on, residence_id
                  INTO m_started, m_ended, m_residence
                  FROM mandates
                 WHERE id = NEW.mandate_id;

                IF NOT FOUND THEN
                    RAISE EXCEPTION 'Invariant T4 : le mandat % est introuvable.', NEW.mandate_id;
                END IF;

                IF m_residence <> NEW.residence_id THEN
                    RAISE EXCEPTION 'Invariant T4 : le mandat % appartient a une autre residence.', NEW.mandate_id;
                END IF;

                IF NEW.started_on < m_started OR NEW.ended_on > m_ended THEN
                    RAISE EXCEPTION 'Invariant T2 : la periode de l''exercice (% -> %) doit etre entierement comprise dans celle de son mandat (% -> %).',
                        NEW.started_on, NEW.ended_on, m_started, m_ended;
                END IF;

                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER exercises_within_mandate
            BEFORE INSERT OR UPDATE OF started_on, ended_on, mandate_id, residence_id ON exercises
            FOR EACH ROW EXECUTE FUNCTION exercise_within_mandate();
        SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS exercises_within_mandate ON exercises;
            DROP FUNCTION IF EXISTS exercise_within_mandate();
        SQL);
    }
};
