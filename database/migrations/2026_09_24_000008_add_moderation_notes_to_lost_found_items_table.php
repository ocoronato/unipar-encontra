<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Motivo da rejeição informado pela moderação (visível para o autor).
     */
    public function up(): void
    {
        Schema::table('lost_found_items', function (Blueprint $table) {
            $table->text('moderation_notes')->nullable()->after('approval_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lost_found_items', function (Blueprint $table) {
            $table->dropColumn('moderation_notes');
        });
    }
};
