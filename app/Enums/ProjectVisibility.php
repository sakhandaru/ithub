<?php

namespace App\Enums;

enum ProjectVisibility: string
{
    case Workspace = 'workspace';
    case Unit = 'unit';
    case Private = 'private';

    public function label(): string
    {
        return match ($this) {
            self::Workspace => 'Workspace',
            self::Unit => 'Unit',
            self::Private => 'Private',
        };
    }
}
