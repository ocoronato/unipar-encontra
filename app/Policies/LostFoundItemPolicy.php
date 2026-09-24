<?php

namespace App\Policies;

use App\Enums\ItemStatus;
use App\Models\LostFoundItem;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class LostFoundItemPolicy
{
    /**
     * Publicações aprovadas (e não canceladas) são públicas.
     * As demais só podem ser vistas pelo autor e pela administração.
     */
    public function view(?User $user, LostFoundItem $item): Response
    {
        if ($item->isPubliclyVisible() || ($user !== null && ($user->id === $item->user_id || $user->isAdmin()))) {
            return Response::allow();
        }

        // 404 em vez de 403: não revela que a publicação existe.
        return Response::denyAsNotFound();
    }

    /**
     * Qualquer usuário autenticado pode cadastrar objetos.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * O autor pode editar enquanto o objeto estiver ativo e sem
     * solicitação de devolução em andamento.
     */
    public function update(User $user, LostFoundItem $item): bool
    {
        return $user->id === $item->user_id
            && $item->status === ItemStatus::Active
            && ! $item->hasOpenReturnRequests();
    }

    /**
     * Mesmas regras da edição.
     */
    public function cancel(User $user, LostFoundItem $item): bool
    {
        return $this->update($user, $item);
    }

    /**
     * Aprovar/rejeitar: somente administradores e somente objetos ativos.
     */
    public function moderate(User $user, LostFoundItem $item): bool
    {
        return $user->isAdmin() && $item->status === ItemStatus::Active;
    }
}
