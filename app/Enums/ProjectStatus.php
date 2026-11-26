<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case Draft = 'draft';
    case Active = 'active';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Active => 'Aktif',
        };
    }
}
