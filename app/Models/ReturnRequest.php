<?php

namespace App\Models;

use App\Enums\ItemStatus;
use App\Enums\ReturnRequestStatus;
use Carbon\CarbonInterface;
use Database\Factories\ReturnRequestFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

/**
 * Solicitação de devolução: um usuário afirma que um objeto encontrado é dele.
 */
class ReturnRequest extends Model
{
    /** @use HasFactory<ReturnRequestFactory> */
    use HasFactory;

    /**
     * status e admin_notes são definidos apenas pela administração.
     */
    protected $fillable = [
        'lost_found_item_id',
        'message',
    ];

    protected $attributes = [
        'status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'status' => ReturnRequestStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lostFoundItem(): BelongsTo
    {
        return $this->belongsTo(LostFoundItem::class);
    }

    public function itemReturn(): HasOne
    {
        return $this->hasOne(ItemReturn::class);
    }

    /**
     * Solicitações ainda "em aberto" (pendentes ou aprovadas aguardando a entrega).
     */
    public function scopeOpen(Builder $query): void
    {
        $query->whereIn('status', [ReturnRequestStatus::Pending, ReturnRequestStatus::Approved]);
    }

    /*
    |--------------------------------------------------------------------------
    | Fluxo de devolução (as permissões são verificadas antes, na ReturnRequestPolicy)
    |--------------------------------------------------------------------------
    */

    /**
     * Aprova a solicitação: o objeto passa a "em processo de devolução"
     * e deixa de aceitar novas solicitações.
     */
    public function approve(?string $adminNotes = null): void
    {
        DB::transaction(function () use ($adminNotes) {
            $this->forceFill(['status' => ReturnRequestStatus::Approved, 'admin_notes' => $adminNotes])->save();
            $this->lostFoundItem->forceFill(['status' => ItemStatus::InReturnProcess])->save();
        });
    }

    /**
     * Rejeita a solicitação. Se ela estava aprovada (entrega não aconteceu),
     * o objeto volta a ficar disponível.
     */
    public function reject(?string $adminNotes = null): void
    {
        DB::transaction(function () use ($adminNotes) {
            $wasApproved = $this->status === ReturnRequestStatus::Approved;

            $this->forceFill(['status' => ReturnRequestStatus::Rejected, 'admin_notes' => $adminNotes])->save();

            if ($wasApproved) {
                $this->lostFoundItem->forceFill(['status' => ItemStatus::Active])->save();
            }
        });
    }

    /**
     * Registra a entrega do objeto ao solicitante (histórico em item_returns).
     * As demais solicitações pendentes do mesmo objeto são rejeitadas.
     */
    public function confirmReturn(User $administrator, CarbonInterface $returnedAt, ?string $notes = null): ItemReturn
    {
        return DB::transaction(function () use ($administrator, $returnedAt, $notes) {
            $itemReturn = new ItemReturn(['returned_at' => $returnedAt, 'notes' => $notes]);
            $itemReturn->lostFoundItem()->associate($this->lostFoundItem);
            $itemReturn->returnRequest()->associate($this);
            $itemReturn->administrator()->associate($administrator);
            $itemReturn->save();

            $this->lostFoundItem->forceFill(['status' => ItemStatus::Returned])->save();

            $this->lostFoundItem->returnRequests()
                ->where('status', ReturnRequestStatus::Pending)
                ->update([
                    'status' => ReturnRequestStatus::Rejected,
                    'admin_notes' => 'O objeto já foi devolvido a outra pessoa.',
                ]);

            return $itemReturn;
        });
    }
}
