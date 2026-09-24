<?php

namespace App\Models;

use Database\Factories\ItemPhotoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ItemPhoto extends Model
{
    /** @use HasFactory<ItemPhotoFactory> */
    use HasFactory;

    protected $fillable = [
        'path',
        'original_name',
    ];

    public function lostFoundItem(): BelongsTo
    {
        return $this->belongsTo(LostFoundItem::class);
    }

    /**
     * URL pública da imagem salva no disco "public".
     */
    public function url(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}
