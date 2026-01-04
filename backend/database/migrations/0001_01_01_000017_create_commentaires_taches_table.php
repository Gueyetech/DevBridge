<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commentaires_taches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tache_id')->constrained('taches')->onDelete('cascade');
            $table->foreignUuid('utilisateur_id')->constrained('utilisateurs')->onDelete('cascade');
            $table->text('contenu');
            $table->foreignUuid('parent_id')->nullable()->constrained('commentaires_taches')->onDelete('cascade');
            $table->boolean('est_resolu')->default(false);
            $table->json('pieces_jointes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commentaires_taches');
    }
};
