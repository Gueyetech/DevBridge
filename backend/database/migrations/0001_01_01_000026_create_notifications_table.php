<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('utilisateur_id')->constrained('utilisateurs')->onDelete('cascade');
            $table->string('titre');
            $table->text('contenu');
            $table->enum('type', ['systeme', 'mentorat', 'defi', 'projet', 'realisation', 'rappel']);
            $table->json('donnees')->nullable(); // Données supplémentaires
            $table->boolean('est_lu')->default(false);
            $table->timestamp('lu_a')->nullable();
            $table->string('lien_action')->nullable();
            $table->timestamps();
            
            $table->index(['utilisateur_id', 'est_lu']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
