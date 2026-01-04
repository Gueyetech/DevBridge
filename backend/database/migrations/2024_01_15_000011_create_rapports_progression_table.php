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
        Schema::create('rapports_progression', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('utilisateur_id')->constrained('utilisateurs')->onDelete('cascade');
            $table->enum('type', ['hebdomadaire', 'mensuel', 'trimestriel', 'annuel', 'personnalise'])->default('hebdomadaire');
            $table->timestamp('periode_debut');
            $table->timestamp('periode_fin');
            $table->json('donnees');
            $table->timestamp('genere_a');
            $table->timestamps();

            $table->index(['utilisateur_id', 'type', 'periode_debut']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rapports_progression');
    }
};
