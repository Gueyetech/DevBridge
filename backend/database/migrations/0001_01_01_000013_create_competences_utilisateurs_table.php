<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competences_utilisateurs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('utilisateur_id')->constrained('utilisateurs')->onDelete('cascade');
            $table->foreignUuid('competence_id')->constrained('competences')->onDelete('cascade');
            $table->integer('niveau_maitrise')->default(1); // 1-5
            $table->timestamp('valide_a')->useCurrent();
            $table->foreignUuid('valide_par')->nullable()->constrained('utilisateurs')->onDelete('set null');
            $table->enum('methode_validation', ['quiz', 'projet', 'mentor', 'examen']);
            $table->json('preuves')->nullable(); // URLs ou références
            $table->timestamps();
            
            $table->unique(['utilisateur_id', 'competence_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competences_utilisateurs');
    }
};
