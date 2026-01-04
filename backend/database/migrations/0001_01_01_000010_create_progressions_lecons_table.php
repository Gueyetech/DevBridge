<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('progressions_lecons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('utilisateur_id')->constrained('utilisateurs')->onDelete('cascade');
            $table->foreignUuid('lecon_id')->constrained('lecons')->onDelete('cascade');
            $table->boolean('est_termine')->default(false);
            $table->integer('temps_passe_secondes')->default(0);
            $table->timestamp('commence_a')->nullable();
            $table->timestamp('termine_a')->nullable();
            $table->timestamps();
            
            $table->unique(['utilisateur_id', 'lecon_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('progressions_lecons');
    }
};
