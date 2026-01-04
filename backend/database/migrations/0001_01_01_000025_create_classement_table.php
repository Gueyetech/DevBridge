<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classement', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('utilisateur_id')->constrained('utilisateurs')->onDelete('cascade');
            $table->integer('position');
            $table->integer('points_totaux')->default(0);
            $table->integer('experience_totale')->default(0);
            $table->integer('badges_obtenus')->default(0);
            $table->integer('projets_termines')->default(0);
            $table->integer('defis_gagnes')->default(0);
            $table->enum('periode', ['quotidien', 'hebdomadaire', 'mensuel', 'annuel', 'global']);
            $table->date('date_reference');
            $table->timestamps();
            
            $table->unique(['utilisateur_id', 'periode', 'date_reference']);
            $table->index(['periode', 'date_reference', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classement');
    }
};
