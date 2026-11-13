<?php

namespace App\Enums;

enum ProjectVisibility: string
{
    case Workspace = 'workspace';
    case Unit = 'unit';
    case Private = 'private';
}
