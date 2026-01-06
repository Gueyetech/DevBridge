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
        Schema::create('sauvegardes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nom')->nullable();
            $table->string('chemin')->nullable();
            $table->bigInteger('taille')->default(0);
            $table->string('statut')->default('en_attente');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sauvegardes');
    }
};
