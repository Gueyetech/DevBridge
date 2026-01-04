<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mentorats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('mentor_id')->constrained('utilisateurs')->onDelete('cascade');
            $table->foreignUuid('etudiant_id')->constrained('utilisateurs')->onDelete('cascade');
            $table->enum('statut', ['demande', 'accepte', 'en_cours', 'termine', 'annule']);
            $table->text('message_demande')->nullable();
            $table->text('message_acceptation')->nullable();
            $table->json('competences_ciblees')->nullable();
            $table->json('objectifs')->nullable();
            $table->timestamp('demande_a')->useCurrent();
            $table->timestamp('accepte_a')->nullable();
            $table->timestamp('termine_a')->nullable();
            $table->timestamps();
            
            $table->unique(['mentor_id', 'etudiant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mentorats');
    }
};
