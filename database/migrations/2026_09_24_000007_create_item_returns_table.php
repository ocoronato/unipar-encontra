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
        Schema::create('item_returns', function (Blueprint $table) {
            $table->id();
            // unique: um objeto só pode ser devolvido uma vez.
            $table->foreignId('lost_found_item_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('return_request_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('administrator_id')->constrained('users')->restrictOnDelete();
            $table->dateTime('returned_at')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_returns');
    }
};
