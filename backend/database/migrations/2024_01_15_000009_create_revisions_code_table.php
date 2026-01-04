<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('revisions_code', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('demande_id')->constrained('demandes_revision_code')->onDelete('cascade');
            $table->foreignUuid('mentor_id')->constrained('utilisateurs')->onDelete('cascade');
            $table->enum('statut', ['acceptee', 'refusee', 'en_cours', 'terminee'])->default('acceptee');
            $table->text('commentaires')->nullable();
            $table->json('points_positifs')->nullable();
            $table->json('points_amelioration')->nullable();
            $table->unsignedTinyInteger('note_generale')->nullable();
            $table->timestamp('accepte_a')->nullable();
            $table->timestamp('refuse_a')->nullable();
            $table->timestamp('termine_a')->nullable();
            $table->timestamps();

            $table->index(['mentor_id', 'statut', 'created_at']);
            $table->index(['demande_id', 'statut']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('revisions_code');
    }
};
