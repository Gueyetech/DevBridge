<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profils', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('utilisateur_id')->constrained('utilisateurs')->onDelete('cascade');
            $table->text('bio')->nullable();
            $table->enum('niveau', ['debutant', 'intermediaire', 'avance'])->default('debutant');
            $table->json('technologies')->nullable();
            $table->string('github_url')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('portfolio_url')->nullable();
            $table->string('ville')->nullable();
            $table->string('pays')->nullable();
            $table->boolean('est_disponible_mentorat')->default(false);
            $table->timestamps();
            
            $table->unique('utilisateur_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profils');
    }
};
