<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use App\Enums\ItemStatus;
use App\Enums\ItemType;
use Database\Factories\LostFoundItemFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Objeto perdido ou encontrado.
 * (Não se chama "Object" porque essa palavra é reservada no PHP.)
 */
class LostFoundItem extends Model
{
    /** @use HasFactory<LostFoundItemFactory> */
    use HasFactory;

    /**
     * user_id, status e approval_status NÃO são preenchíveis em massa:
     * são definidos pelo sistema (dono da publicação, moderação e devolução).
     */
    protected $fillable = [
        'category_id',
        'location_id',
        'type',
        'title',
        'description',
        'occurred_at',
    ];

    protected $attributes = [
        'status' => 'active',
        'approval_status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'type' => ItemType::class,
            'status' => ItemStatus::class,
            'approval_status' => ApprovalStatus::class,
            'occurred_at' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ItemPhoto::class);
    }

    public function returnRequests(): HasMany
    {
        return $this->hasMany(ReturnRequest::class);
    }

    public function itemReturn(): HasOne
    {
        return $this->hasOne(ItemReturn::class);
    }

    /**
     * Objeto encontrado, aprovado e ainda disponível: pode receber
     * solicitações de devolução ("Este objeto é meu").
     */
    public function acceptsReturnRequests(): bool
    {
        return $this->type === ItemType::Found
            && $this->approval_status === ApprovalStatus::Approved
            && $this->status === ItemStatus::Active;
    }

    /**
     * Existe alguma solicitação de devolução pendente ou aprovada?
     */
    public function hasOpenReturnRequests(): bool
    {
        return $this->returnRequests()->open()->exists();
    }

    /**
     * Publicação visível para todos os usuários: aprovada e não cancelada.
     */
    public function isPubliclyVisible(): bool
    {
        return $this->approval_status === ApprovalStatus::Approved
            && $this->status !== ItemStatus::Cancelled;
    }

    /**
     * Somente publicações aprovadas pela moderação.
     */
    public function scopeApproved(Builder $query): void
    {
        $query->where('approval_status', ApprovalStatus::Approved);
    }

    /**
     * Mesma regra de isPubliclyVisible(), para consultas (ex.: busca).
     */
    public function scopePubliclyVisible(Builder $query): void
    {
        $query->approved()->where('status', '!=', ItemStatus::Cancelled);
    }

    public function scopeOfType(Builder $query, ItemType $type): void
    {
        $query->where('type', $type);
    }
}
