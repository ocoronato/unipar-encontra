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

    protected $fillable = [
        'lost_found_item_id',
        'return_request_id',
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
