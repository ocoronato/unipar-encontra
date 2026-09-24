<?php

namespace App\Enums;

enum ItemStatus: string
{
    case Active = 'active';
    case InReturnProcess = 'in_return_process';
    case Returned = 'returned';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Ativo',
            self::InReturnProcess => 'Em processo de devolução',
            self::Returned => 'Devolvido',
            self::Cancelled => 'Cancelado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'blue',
            self::InReturnProcess => 'amber',
            self::Returned => 'green',
            self::Cancelled => 'zinc',
        };
    }
}
