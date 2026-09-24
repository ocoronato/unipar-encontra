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
        Schema::create('lost_found_items', function (Blueprint $table) {
            $table->id();

            // restrict: publicações fazem parte do histórico e não devem sumir
            // ao excluir usuário, categoria ou local (use "active" para desativar).
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('location_id')->constrained()->restrictOnDelete();

            $table->string('type', 10); // lost | found
            $table->string('title', 150);
            $table->text('description');
            $table->date('occurred_at');
            $table->string('status', 20)->default('active'); // active | in_return_process | returned | cancelled
            $table->string('approval_status', 20)->default('pending'); // pending | approved | rejected
            $table->timestamps();

            // Índices usados pela busca pública e pela moderação.
            $table->index(['approval_status', 'type', 'status']);
            $table->index('occurred_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lost_found_items');
    }
};
