<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('badges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nom');
            $table->string('slug')->unique();
            $table->text('description');
            $table->string('icone');
            $table->enum('rarete', ['commun', 'peu_commun', 'rare', 'epique', 'legendaire']);
            $table->json('conditions_obtention')->nullable();
            $table->integer('points_recompense')->default(50);
            $table->integer('experience_recompense')->default(100);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('badges');
    }
};
