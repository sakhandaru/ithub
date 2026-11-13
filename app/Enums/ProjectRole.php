<?php

namespace App\Enums;

enum ProjectRole: string
{
    case ProjectAdmin = 'project_admin';
    case Contributor = 'contributor';
    case Commenter = 'commenter';
    case Viewer = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::ProjectAdmin => 'Project Admin',
            self::Contributor => 'Contributor',
            self::Commenter => 'Commenter',
            self::Viewer => 'Viewer',
        };
    }
}
