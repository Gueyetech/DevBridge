<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('defis', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('titre');
            $table->string('slug')->unique();
            $table->text('description');
            $table->enum('type', ['quotidien', 'hebdomadaire', 'mensuel', 'special']);
            $table->enum('difficulte', ['debutant', 'intermediaire', 'avance']);
            $table->json('technologies')->nullable();
            $table->integer('points_recompense')->default(100);
            $table->integer('experience_recompense')->default(200);
            $table->timestamp('date_debut');
            $table->timestamp('date_fin');
            $table->integer('participants_maximum')->nullable();
            $table->boolean('est_actif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('defis');
    }
};
