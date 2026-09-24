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
        Schema::table('users', function (Blueprint $table) {
            // RA do aluno ou matrícula do funcionário (opcional no cadastro).
            $table->string('registration_number', 20)->nullable()->unique()->after('email');
            $table->string('course', 100)->nullable()->after('registration_number');
            $table->string('role', 20)->default('user')->index()->after('course');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['registration_number']);
            $table->dropIndex(['role']);
            $table->dropColumn(['registration_number', 'course', 'role']);
        });
    }
};
