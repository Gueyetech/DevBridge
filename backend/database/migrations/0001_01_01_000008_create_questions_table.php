<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('quiz_id')->constrained('quiz')->onDelete('cascade');
            $table->text('texte');
            $table->enum('type', ['choix_unique', 'choix_multiple', 'vrai_faux', 'code']);
            $table->json('options')->nullable(); // ["option1", "option2"]
            $table->json('reponses_correctes')->nullable(); // [0, 2] pour les indices
            $table->text('explication')->nullable();
            $table->integer('points')->default(1);
            $table->integer('ordre')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
