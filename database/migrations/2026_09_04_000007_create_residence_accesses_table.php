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
        Schema::create('residence_accesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('residence_id')->constrained('residences')->cascadeOnDelete();
            $table->foreignId('syndic_company_id')->constrained('syndic_companies')->cascadeOnDelete();
            $table->string('status')->default('active'); // active, revoked
            $table->dateTime('granted_at');
            $table->foreignId('granted_by_admin_id')->constrained('users');
            $table->dateTime('revoked_at')->nullable();
            $table->foreignId('revoked_by_admin_id')->nullable()->constrained('users');
            $table->text('revoked_motif')->nullable();
            $table->string('revoked_document_path')->nullable();
            $table->dateTime('export_generated_at')->nullable();
            $table->timestamps();

            $table->index(['residence_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('residence_accesses');
    }
};
