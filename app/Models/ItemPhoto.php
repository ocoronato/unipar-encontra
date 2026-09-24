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

    /** Quantidade máxima de fotos por objeto. */
    public const MAX_PER_ITEM = 5;

    /** Tamanho máximo de cada foto, em kilobytes (5 MB). */
    public const MAX_SIZE_KB = 5120;

    /**
     * Regras de validação de cada foto enviada.
     * SVG não é aceito (pode conter scripts) e o limite de dimensões
     * evita imagens gigantes que consumiriam muita memória no servidor.
     */
    public static function rules(): array
    {
        return [
            'image',
            'mimes:jpg,jpeg,png,webp',
            'max:'.self::MAX_SIZE_KB,
            'dimensions:max_width=6000,max_height=6000',
        ];
    }

    protected $fillable = [
        'path',
        'original_name',
    ];

    /**
     * Ao excluir a foto do banco, o arquivo também é apagado do Storage.
     */
    protected static function booted(): void
    {
        static::deleted(fn (ItemPhoto $photo) => Storage::disk('public')->delete($photo->path));
    }

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
