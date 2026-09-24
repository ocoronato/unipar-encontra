<?php

namespace App\Models;

use App\Enums\ReturnRequestStatus;
use Database\Factories\ReturnRequestFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
}
