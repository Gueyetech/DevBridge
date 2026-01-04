<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('participations_defis', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('utilisateur_id')->constrained('utilisateurs')->onDelete('cascade');
            $table->foreignUuid('defi_id')->constrained('defis')->onDelete('cascade');
            $table->enum('statut', ['inscrit', 'en_cours', 'soumis', 'evalue', 'gagnant']);
            $table->string('solution_url')->nullable();
            $table->text('description_solution')->nullable();
            $table->integer('score')->nullable();
            $table->text('feedback_jury')->nullable();
            $table->timestamp('inscrit_a')->useCurrent();
            $table->timestamp('soumis_a')->nullable();
            $table->timestamps();
            
            $table->unique(['utilisateur_id', 'defi_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participations_defis');
    }
};
