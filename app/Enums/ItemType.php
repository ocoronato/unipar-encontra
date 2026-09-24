<?php

namespace App\Enums;

enum ItemType: string
{
    case Lost = 'lost';
    case Found = 'found';

    public function label(): string
    {
        return match ($this) {
            self::Lost => 'Perdido',
            self::Found => 'Encontrado',
        };
    }

    /**
     * Cor do badge (Flux) usada em toda a interface.
     */
    public function color(): string
    {
        return match ($this) {
            self::Lost => 'orange',
            self::Found => 'emerald',
        };
    }
}
