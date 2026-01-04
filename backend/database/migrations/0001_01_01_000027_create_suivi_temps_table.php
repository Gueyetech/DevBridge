<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suivi_temps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('utilisateur_id')->constrained('utilisateurs')->onDelete('cascade');
            $table->enum('type_activite', ['lecon', 'projet', 'defi', 'quiz', 'mentorat', 'forum']);
            // Relation polymorphique - utiliser uuidMorphs dans le modèle Eloquent
            $table->uuid('activite_id')->nullable();
            $table->string('type_activite_morph')->nullable();
            $table->timestamp('debut_a');
            $table->timestamp('fin_a')->nullable();
            $table->integer('duree_secondes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['utilisateur_id', 'type_activite']);
            $table->index(['debut_a', 'fin_a']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suivi_temps');
    }
};
