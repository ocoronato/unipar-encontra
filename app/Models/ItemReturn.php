<?php

namespace App\Models;

use Database\Factories\ItemReturnFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro histórico de uma devolução confirmada pela administração.
 */
class ItemReturn extends Model
{
    /** @use HasFactory<ItemReturnFactory> */
    use HasFactory;

    /**
     * Objeto, solicitação e administrador são ligados explicitamente
     * (ver ReturnRequest::confirmReturn()), nunca por dados de formulário.
     */
    protected $fillable = [
        'returned_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'returned_at' => 'datetime',
        ];
    }

    public function lostFoundItem(): BelongsTo
    {
        return $this->belongsTo(LostFoundItem::class);
    }

    public function returnRequest(): BelongsTo
    {
        return $this->belongsTo(ReturnRequest::class);
    }

    public function administrator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'administrator_id');
    }
}
