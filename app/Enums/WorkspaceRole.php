<?php

namespace App\Enums;

enum WorkspaceRole: string
{
    case Owner = 'owner';
    case WorkspaceAdmin = 'workspace_admin';
    case Member = 'member';
    case Guest = 'guest';
}
