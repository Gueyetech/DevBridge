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
        Schema::create('conversations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('createur_id')->constrained('utilisateurs')->onDelete('cascade');
            $table->string('titre')->nullable();
            $table->enum('type', ['privee', 'groupe'])->default('privee');
            $table->timestamp('dernier_message_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['createur_id', 'dernier_message_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
