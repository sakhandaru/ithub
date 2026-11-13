<?php

namespace App\Enums;

enum ProjectRole: string
{
    case ProjectAdmin = 'project_admin';
    case Contributor = 'contributor';
    case Commenter = 'commenter';
    case Viewer = 'viewer';
}
