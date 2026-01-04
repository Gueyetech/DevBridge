<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('badges_utilisateurs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('utilisateur_id')->constrained('utilisateurs')->onDelete('cascade');
            $table->foreignUuid('badge_id')->constrained('badges')->onDelete('cascade');
            $table->timestamp('obtenu_a')->useCurrent();
            $table->text('raison_obtention')->nullable();
            $table->timestamps();
            
            $table->unique(['utilisateur_id', 'badge_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('badges_utilisateurs');
    }
};
