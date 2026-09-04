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
        Schema::create('license_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('license_id')->constrained('licenses')->cascadeOnDelete();
            $table->string('type'); // created, renewed, suspended, reactivated, expired
            $table->dateTime('effective_at');
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared(<<<'SQL'
                CREATE OR REPLACE FUNCTION prevent_modification_on_license_events()
                RETURNS TRIGGER AS $$
                BEGIN
                    RAISE EXCEPTION 'Table license_events is insert-only. Modifications and deletions are prohibited.';
                END;
                $$ LANGUAGE plpgsql;

                DROP TRIGGER IF EXISTS trg_license_events_insert_only ON license_events;
                CREATE TRIGGER trg_license_events_insert_only
                BEFORE UPDATE OR DELETE ON license_events
                FOR EACH ROW EXECUTE FUNCTION prevent_modification_on_license_events();
            SQL);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared('DROP TRIGGER IF EXISTS trg_license_events_insert_only ON license_events;');
            DB::unprepared('DROP FUNCTION IF EXISTS prevent_modification_on_license_events();');
        }

        Schema::dropIfExists('license_events');
    }
};
