<?php

namespace App\Policies;

use App\Enums\ReturnRequestStatus;
use App\Models\LostFoundItem;
use App\Models\ReturnRequest;
use App\Models\User;

class ReturnRequestPolicy
{
    /**
     * O solicitante e a administração podem ver a solicitação.
     */
    public function view(User $user, ReturnRequest $returnRequest): bool
    {
        return $user->id === $returnRequest->user_id || $user->isAdmin();
    }

    /**
     * "Este objeto é meu". Uso: $user->can('create', [ReturnRequest::class, $item]).
     *
     * Impede: objeto perdido/não aprovado/devolvido/cancelado, o próprio autor
     * da publicação pedindo o objeto e solicitações duplicadas.
     */
    public function create(User $user, LostFoundItem $item): bool
    {
        return $item->acceptsReturnRequests()
            && $item->user_id !== $user->id
            && ! $item->returnRequests()->open()->where('user_id', $user->id)->exists();
    }

    /**
     * O solicitante pode desistir enquanto a solicitação estiver pendente.
     */
    public function cancel(User $user, ReturnRequest $returnRequest): bool
    {
        return $user->id === $returnRequest->user_id
            && $returnRequest->status === ReturnRequestStatus::Pending;
    }

    /**
     * Aprovar/rejeitar: administradores, em solicitações pendentes
     * e nunca na própria solicitação.
     */
    public function review(User $user, ReturnRequest $returnRequest): bool
    {
        return $user->isAdmin()
            && $returnRequest->status === ReturnRequestStatus::Pending
            && $returnRequest->user_id !== $user->id;
    }
}
