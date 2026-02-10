<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('likes_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('message_id')->nullable()->constrained('messages_forum')->onDelete('cascade');
            $table->foreignUuid('discussion_id')->nullable()->constrained('discussions_forum')->onDelete('cascade');
            $table->foreignUuid('utilisateur_id')->constrained('utilisateurs')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['message_id', 'utilisateur_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('likes_messages');
    }
};
