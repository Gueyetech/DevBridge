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
        Schema::create('discussions_forum', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('createur_id')->constrained('utilisateurs')->onDelete('cascade');
            $table->foreignUuid('categorie_id')->nullable()->constrained('categories_forum')->onDelete('set null');
            $table->string('titre');
            $table->text('contenu');
            $table->json('tags')->nullable();
            $table->boolean('est_resolu')->default(false);
            $table->boolean('est_epingle')->default(false);
            $table->boolean('est_verrouille')->default(false);
            $table->unsignedInteger('nombre_vues')->default(0);
            $table->timestamp('dernier_message_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['categorie_id', 'est_epingle', 'dernier_message_at']);
            $table->index(['createur_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discussions_forum');
    }
};
