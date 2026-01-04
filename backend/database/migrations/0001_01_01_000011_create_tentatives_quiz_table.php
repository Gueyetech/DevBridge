<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tentatives_quiz', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('utilisateur_id')->constrained('utilisateurs')->onDelete('cascade');
            $table->foreignUuid('quiz_id')->constrained('quiz')->onDelete('cascade');
            $table->integer('score')->default(0);
            $table->integer('score_maximum');
            $table->boolean('est_reussi')->default(false);
            $table->integer('temps_passe_secondes')->default(0);
            $table->json('reponses')->nullable(); // Stockage des réponses
            $table->timestamp('commence_a')->nullable();
            $table->timestamp('termine_a')->nullable();
            $table->timestamps();
            
            $table->index(['utilisateur_id', 'quiz_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tentatives_quiz');
    }
};
