<?php

namespace App\Enums;

enum SystemRole: string
{
    case Admin = 'admin';
    case User = 'user';
    case Auditor = 'auditor';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::User => 'User',
            self::Auditor => 'Auditor',
        };
    }

    public function isReadOnly(): bool
    {
        return $this === self::Auditor;
    }
}
