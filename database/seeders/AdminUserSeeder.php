<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Conta administrativa APENAS PARA DESENVOLVIMENTO.
 * Em produção, crie o administrador manualmente com uma senha forte.
 */
class AdminUserSeeder extends Seeder
{
    public const DEV_EMAIL = 'admin@unipar-encontra.test';

    public const DEV_PASSWORD = 'password';

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->warn('AdminUserSeeder ignorado em produção.');

            return;
        }

        $admin = User::firstOrNew(['email' => self::DEV_EMAIL]);
        $admin->fill([
            'name' => 'Administrador (dev)',
            'password' => self::DEV_PASSWORD,
        ]);
        $admin->role = UserRole::Admin;
        $admin->email_verified_at ??= now();
        $admin->save();
    }
}
