<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sessions_mentorat', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('mentorat_id')->constrained('mentorats')->onDelete('cascade');
            $table->string('titre');
            $table->text('description')->nullable();
            $table->timestamp('date_debut');
            $table->timestamp('date_fin');
            $table->enum('statut', ['planifie', 'en_cours', 'termine', 'annule']);
            $table->string('lien_visioconference')->nullable();
            $table->text('notes')->nullable();
            $table->integer('note_etudiant')->nullable(); // 1-5
            $table->integer('note_mentor')->nullable(); // 1-5
            $table->text('feedback_etudiant')->nullable();
            $table->text('feedback_mentor')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions_mentorat');
    }
};
