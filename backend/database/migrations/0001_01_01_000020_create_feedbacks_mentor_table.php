<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedbacks_mentor', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('mentor_id')->constrained('utilisateurs')->onDelete('cascade');
            $table->foreignUuid('etudiant_id')->constrained('utilisateurs')->onDelete('cascade');
            $table->foreignUuid('projet_id')->nullable()->constrained('projets');
            $table->foreignUuid('tache_id')->nullable()->constrained('taches');
            $table->text('contenu');
            $table->enum('type', ['code', 'conception', 'methodologie', 'soft_skills']);
            $table->json('points_positifs')->nullable();
            $table->json('points_amelioration')->nullable();
            $table->integer('note_generale')->nullable(); // 1-10
            $table->boolean('est_lu')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedbacks_mentor');
    }
};
