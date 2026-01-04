<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inscriptions_parcours', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('utilisateur_id')->constrained('utilisateurs')->onDelete('cascade');
            $table->foreignUuid('parcours_id')->constrained('parcours_apprentissage')->onDelete('cascade');
            $table->integer('progression_pourcentage')->default(0);
            $table->timestamp('inscrit_a')->useCurrent();
            $table->timestamp('commence_a')->nullable();
            $table->timestamp('termine_a')->nullable();
            $table->integer('score_final')->nullable();
            $table->timestamps();
            
            $table->unique(['utilisateur_id', 'parcours_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inscriptions_parcours');
    }
};
