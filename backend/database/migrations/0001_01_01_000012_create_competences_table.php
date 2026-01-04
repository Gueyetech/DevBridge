<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nom');
            $table->string('slug')->unique();
            $table->enum('categorie', ['frontend', 'backend', 'base_de_donnees', 'devops', 'outils', 'soft_skills']);
            $table->text('description');
            $table->string('icone')->nullable();
            $table->enum('niveau', ['debutant', 'intermediaire', 'avance'])->default('debutant');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competences');
    }
};
