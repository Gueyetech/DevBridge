<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('feedback_mentor', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('mentor_id')->nullable();
            $table->uuid('utilisateur_id')->nullable();
            $table->tinyInteger('note'); // 1 à 5
            $table->text('commentaire')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feedback_mentor');
    }
};
