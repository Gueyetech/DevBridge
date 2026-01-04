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
        Schema::create('messages_forum', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('discussion_id')->constrained('discussions_forum')->onDelete('cascade');
            $table->foreignUuid('utilisateur_id')->constrained('utilisateurs')->onDelete('cascade');
            $table->foreignUuid('parent_id')->nullable()->constrained('messages_forum')->onDelete('cascade');
            $table->text('contenu');
            $table->boolean('est_premier_message')->default(false);
            $table->boolean('est_solution')->default(false);
            $table->unsignedInteger('nombre_likes')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['discussion_id', 'created_at']);
            $table->index(['utilisateur_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages_forum');
    }
};
