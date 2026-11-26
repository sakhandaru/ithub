<?php

namespace App\Enums;

enum WorkspaceRole: string
{
    case Owner = 'owner';
    case WorkspaceAdmin = 'workspace_admin';
    case Member = 'member';
    case Guest = 'guest';

    public function canManageWorkspace(): bool
    {
        return $this === self::Owner || $this === self::WorkspaceAdmin;
    }

    public function canManageUnits(): bool
    {
        return $this->canManageWorkspace();
    }

    public function canManageMembers(): bool
    {
        return $this->canManageWorkspace();
    }

    public function canCreateProjects(): bool
    {
        return $this->canManageWorkspace();
    }

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Admin Utama',
            self::WorkspaceAdmin => 'Admin Unit',
            self::Member => 'Anggota Unit',
            self::Guest => 'Guest',
        };
    }
}
