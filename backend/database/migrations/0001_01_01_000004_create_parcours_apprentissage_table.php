<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parcours_apprentissage', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('titre');
            $table->string('slug')->unique();
            $table->text('description');
            $table->string('technologie'); // "HTML/CSS", "JavaScript", "PHP", etc.
            $table->enum('difficulte', ['debutant', 'intermediaire', 'avance']);
            $table->integer('duree_estimee_heures');
            $table->boolean('est_publie')->default(false);
            $table->integer('ordre')->default(0);
            $table->json('prerequis')->nullable();
            $table->string('image')->nullable();
            $table->json('competences_acquises')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parcours_apprentissage');
    }
};
