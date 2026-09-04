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
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('residence_id')->unique()->constrained('residences')->cascadeOnDelete();
            $table->string('plan')->default('standard');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->integer('grace_days')->default(30);
            $table->string('status')->default('active'); // active, grace, read_only, suspended
            $table->string('payer')->default('copropriete'); // syndic, copropriete
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('licenses');
    }
};
