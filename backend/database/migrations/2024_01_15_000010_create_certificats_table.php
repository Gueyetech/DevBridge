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
        Schema::create('certificats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('utilisateur_id')->constrained('utilisateurs')->onDelete('cascade');
            $table->foreignUuid('competence_id')->nullable()->constrained('competences')->onDelete('set null');
            $table->foreignUuid('parcours_id')->nullable()->constrained('parcours_apprentissage')->onDelete('set null');
            $table->foreignUuid('valide_par')->nullable()->constrained('utilisateurs')->onDelete('set null');
            $table->enum('type', ['competence', 'parcours', 'projet', 'defi'])->default('competence');
            $table->string('code_verification')->unique();
            $table->timestamp('date_emission');
            $table->timestamp('date_expiration')->nullable();
            $table->unsignedInteger('nombre_telechargements')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['utilisateur_id', 'type', 'date_emission']);
            $table->index(['code_verification']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificats');
    }
};
