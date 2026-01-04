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
        Schema::create('demandes_revision_code', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('etudiant_id')->constrained('utilisateurs')->onDelete('cascade');
            $table->foreignUuid('projet_id')->nullable()->constrained('projets')->onDelete('set null');
            $table->foreignUuid('tache_id')->nullable()->constrained('taches')->onDelete('set null');
            $table->string('titre');
            $table->text('description');
            $table->enum('statut', ['en_attente', 'en_cours', 'terminee', 'annulee'])->default('en_attente');
            $table->enum('urgence', ['basse', 'normale', 'haute', 'critique'])->default('normale');
            $table->json('technologies')->nullable();
            $table->json('competences_ciblees')->nullable();
            $table->json('fichiers')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['etudiant_id', 'statut', 'created_at']);
            $table->index(['statut', 'urgence', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demandes_revision_code');
    }
};
