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
        Schema::create('item_photos', function (Blueprint $table) {
            $table->id();
            // A foto só existe junto com a publicação.
            $table->foreignId('lost_found_item_id')->constrained()->cascadeOnDelete();
            $table->string('path'); // caminho no disco "public" do Laravel Storage
            $table->string('original_name');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_photos');
    }
};
