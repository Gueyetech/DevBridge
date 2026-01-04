<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('projet_id')->constrained('projets')->onDelete('cascade');
            $table->string('titre');
            $table->text('description');
            $table->enum('statut', ['a_faire', 'en_cours', 'en_revision', 'termine', 'bloque']);
            $table->enum('priorite', ['basse', 'moyenne', 'haute', 'critique']);
            $table->integer('duree_estimee_heures')->nullable();
            $table->integer('duree_reelle_heures')->nullable();
            $table->foreignUuid('assignee_a')->nullable()->constrained('utilisateurs');
            $table->timestamp('date_echeance')->nullable();
            $table->json('tags')->nullable(); // ["bug", "feature", "documentation"]
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taches');
    }
};
