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
        Schema::create('participants_conversations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('conversation_id')->constrained('conversations')->onDelete('cascade');
            $table->foreignUuid('utilisateur_id')->constrained('utilisateurs')->onDelete('cascade');
            $table->timestamp('rejoint_a')->nullable();
            $table->timestamp('quitte_a')->nullable();
            $table->timestamp('dernier_lu_at')->nullable();
            $table->boolean('notifications_actives')->default(true);
            $table->timestamps();

            $table->unique(['conversation_id', 'utilisateur_id']);
            $table->index(['utilisateur_id', 'quitte_a']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('participants_conversations');
    }
};
