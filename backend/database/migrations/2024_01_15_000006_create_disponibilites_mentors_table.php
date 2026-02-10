<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disponibilites_mentors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('mentor_id')->constrained('utilisateurs')->onDelete('cascade');
            $table->tinyInteger('jour_semaine')->comment('0=Dimanche, 1=Lundi, ..., 6=Samedi');
            $table->time('heure_debut');
            $table->time('heure_fin');
            $table->string('type', 30)->default('en_ligne')->comment('en_ligne, presentiel, hybride');
            $table->boolean('est_actif')->default(true);
            $table->boolean('recurrent')->default(true);
            $table->date('date_specifique')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['mentor_id', 'jour_semaine']);
            $table->index(['mentor_id', 'est_actif']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disponibilites_mentors');
    }
};
