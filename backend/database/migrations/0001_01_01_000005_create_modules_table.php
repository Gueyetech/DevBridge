<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parcours_id')->constrained('parcours_apprentissage')->onDelete('cascade');
            $table->string('titre');
            $table->string('slug');
            $table->text('description');
            $table->integer('ordre')->default(0);
            $table->integer('duree_estimee_minutes');
            $table->json('objectifs')->nullable();
            $table->timestamps();
            
            $table->unique(['parcours_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};
