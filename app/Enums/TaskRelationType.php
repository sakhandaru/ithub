<?php

namespace App\Enums;

enum TaskRelationType: string
{
    case Related = 'related';
    case Duplicate = 'duplicate';
    case Blocks = 'blocks';

    public function label(): string
    {
        return match ($this) {
            self::Related => 'Terkait',
            self::Duplicate => 'Duplikat',
            self::Blocks => 'Menghalangi',
        };
    }
}
