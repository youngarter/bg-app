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
        Schema::create('mandates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('residence_id')->constrained('residences')->cascadeOnDelete();
            $table->foreignId('syndic_company_id')->constrained('syndic_companies')->restrictOnDelete();
            $table->date('started_on');
            $table->date('ended_on');
            $table->string('state')->default('draft');
            $table->string('pv_ag_reference')->nullable();
            $table->string('pv_ag_document_path')->nullable();
            $table->bigInteger('honoraires')->nullable();
            $table->text('terminated_motif')->nullable();
            $table->string('terminated_document_path')->nullable();
            $table->timestamps();

            $table->index(['residence_id', 'started_on', 'ended_on']);
            $table->index(['residence_id', 'state']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mandates');
    }
};
