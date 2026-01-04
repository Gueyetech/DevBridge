<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('titre');
            $table->string('slug')->unique();
            $table->text('description');
            $table->enum('difficulte', ['debutant', 'intermediaire', 'avance']);
            $table->json('technologies'); // ["React", "Node.js", "MongoDB"]
            $table->enum('statut', ['brouillon', 'ouvert', 'en_cours', 'en_revision', 'termine', 'archive']);
            $table->string('repository_github')->nullable();
            $table->timestamp('date_limite')->nullable();
            $table->integer('nombre_maximum_participants')->default(5);
            $table->integer('points_recompense')->default(100);
            $table->foreignUuid('createur_id')->constrained('utilisateurs')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projets');
    }
};
