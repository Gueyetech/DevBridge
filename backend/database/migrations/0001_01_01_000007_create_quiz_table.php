<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('module_id')->nullable()->constrained('modules')->onDelete('cascade');
            $table->foreignUuid('lecon_id')->nullable()->constrained('lecons')->onDelete('cascade');
            $table->string('titre');
            $table->text('description')->nullable();
            $table->integer('duree_limite_minutes')->nullable();
            $table->integer('score_minimum_reussite')->default(70);
            $table->integer('tentatives_maximum')->default(3);
            $table->boolean('est_actif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz');
    }
};
