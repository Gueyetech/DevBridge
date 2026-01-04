<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membres_projets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('projet_id')->constrained('projets')->onDelete('cascade');
            $table->foreignUuid('utilisateur_id')->constrained('utilisateurs')->onDelete('cascade');
            $table->enum('role', ['createur', 'mainteneur', 'contributeur', 'relecteur']);
            $table->timestamp('rejoint_a')->useCurrent();
            $table->integer('score_contribution')->default(0);
            $table->boolean('est_actif')->default(true);
            $table->timestamps();
            
            $table->unique(['projet_id', 'utilisateur_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membres_projets');
    }
};
