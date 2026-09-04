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
        Schema::create('lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('residence_id')->constrained('residences')->cascadeOnDelete();
            $table->string('reference');
            $table->string('type'); // appartement, magasin, bureau, parking, cave, local_technique, autre
            $table->string('batiment')->nullable();
            $table->string('etage')->nullable();
            $table->decimal('superficie', 8, 2)->nullable();
            $table->integer('tantiemes');
            $table->timestamps();

            $table->unique(['residence_id', 'reference']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lots');
    }
};
