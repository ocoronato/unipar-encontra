<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Promover/rebaixar administrador. Um administrador não pode alterar
     * o próprio perfil (evita que o sistema fique sem administradores por engano).
     */
    public function changeRole(User $user, User $target): bool
    {
        return $user->isAdmin() && $user->id !== $target->id;
    }
}
