<?php

namespace App\Policies;

use App\Enums\ItemStatus;
use App\Enums\ReturnRequestStatus;
use App\Models\LostFoundItem;
use App\Models\ReturnRequest;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/*
| Fluxo da devolução:
| pendente → aprovada (objeto "em processo de devolução") → devolução confirmada (objeto "devolvido")
|          ↘ rejeitada / cancelada pelo solicitante
*/
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
     * As mensagens aparecem para o usuário quando o acesso é negado.
     */
    public function create(User $user, LostFoundItem $item): Response
    {
        if (! $item->acceptsReturnRequests()) {
            return Response::deny('Este objeto não está disponível para solicitações de devolução.');
        }

        if ($item->user_id === $user->id) {
            return Response::deny('Você não pode solicitar a devolução de um objeto que você mesmo cadastrou.');
        }

        // Pendente/aprovada: duplicada. Rejeitada: evita tentativas repetidas até "acertar" a descrição.
        $previous = $item->returnRequests()
            ->where('user_id', $user->id)
            ->whereIn('status', [ReturnRequestStatus::Pending, ReturnRequestStatus::Approved, ReturnRequestStatus::Rejected])
            ->latest()
            ->first();

        return match ($previous?->status) {
            null => Response::allow(),
            ReturnRequestStatus::Rejected => Response::deny('Sua solicitação para este objeto já foi analisada e rejeitada. Em caso de dúvida, procure a administração.'),
            default => Response::deny('Você já possui uma solicitação em andamento para este objeto.'),
        };
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
     * Aprovar: solicitação pendente de um objeto ainda disponível.
     */
    public function approve(User $user, ReturnRequest $returnRequest): bool
    {
        return $this->isReviewer($user, $returnRequest)
            && $returnRequest->status === ReturnRequestStatus::Pending
            && $returnRequest->lostFoundItem->status === ItemStatus::Active;
    }

    /**
     * Rejeitar: solicitação pendente, ou aprovada cuja entrega ainda não aconteceu
     * (ex.: o solicitante não compareceu). Nesse caso o objeto volta a ficar disponível.
     */
    public function reject(User $user, ReturnRequest $returnRequest): bool
    {
        return $this->isReviewer($user, $returnRequest)
            && ($returnRequest->status === ReturnRequestStatus::Pending || $this->awaitsDelivery($returnRequest));
    }

    /**
     * Confirmar a devolução: solicitação aprovada e objeto ainda não entregue.
     */
    public function confirmReturn(User $user, ReturnRequest $returnRequest): bool
    {
        return $this->isReviewer($user, $returnRequest) && $this->awaitsDelivery($returnRequest);
    }

    /**
     * Administrador que não é o próprio solicitante.
     */
    private function isReviewer(User $user, ReturnRequest $returnRequest): bool
    {
        return $user->isAdmin() && $returnRequest->user_id !== $user->id;
    }

    private function awaitsDelivery(ReturnRequest $returnRequest): bool
    {
        return $returnRequest->status === ReturnRequestStatus::Approved
            && $returnRequest->lostFoundItem->status === ItemStatus::InReturnProcess
            && ! $returnRequest->itemReturn()->exists();
    }
}
