<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('statistiques_utilisateurs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('utilisateur_id')->constrained('utilisateurs')->onDelete('cascade');
            $table->date('date');
            $table->integer('temps_apprentissage_minutes')->default(0);
            $table->integer('quiz_passes')->default(0);
            $table->integer('quiz_reussis')->default(0);
            $table->integer('projets_termines')->default(0);
            $table->integer('points_gagnes')->default(0);
            $table->integer('badges_obtenus')->default(0);
            $table->integer('defis_participe')->default(0);
            $table->integer('sessions_mentorat')->default(0);
            $table->json('metriques_personnalisees')->nullable();
            $table->timestamps();
            
            $table->unique(['utilisateur_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('statistiques_utilisateurs');
    }
};
