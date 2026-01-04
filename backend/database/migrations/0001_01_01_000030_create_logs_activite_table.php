<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logs_activite', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('utilisateur_id')->nullable()->constrained('utilisateurs')->onDelete('set null');
            $table->string('action');
            $table->string('modele')->nullable();
            $table->uuid('modele_id')->nullable();
            $table->json('donnees_avant')->nullable();
            $table->json('donnees_apres')->nullable();
            $table->json('metadata')->nullable();
            $table->string('adresse_ip')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
            
            $table->index(['utilisateur_id', 'action']);
            $table->index(['modele', 'modele_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logs_activite');
    }
};
