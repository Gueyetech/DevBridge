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
        Schema::create('suivis_discussions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('utilisateur_id')->constrained('utilisateurs')->onDelete('cascade');
            $table->foreignUuid('discussion_id')->constrained('discussions_forum')->onDelete('cascade');
            $table->timestamp('dernier_lu_at')->nullable();
            $table->boolean('notifications_actives')->default(true);
            $table->timestamps();

            $table->unique(['utilisateur_id', 'discussion_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suivis_discussions');
    }
};
