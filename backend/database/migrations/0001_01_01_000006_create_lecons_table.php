<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lecons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('module_id')->constrained('modules')->onDelete('cascade');
            $table->string('titre');
            $table->string('slug');
            $table->enum('type_contenu', ['article', 'video', 'exercice', 'projet']);
            $table->text('contenu')->nullable(); // Markdown ou HTML
            $table->string('url_video')->nullable();
            $table->json('ressources')->nullable(); // ["url1", "url2"]
            $table->integer('ordre')->default(0);
            $table->integer('duree_estimee_minutes');
            $table->boolean('est_gratuit')->default(false);
            $table->timestamps();
            
            $table->unique(['module_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lecons');
    }
};
