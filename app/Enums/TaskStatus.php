<?php

namespace App\Enums;

enum TaskStatus: string
{
    case Request = 'request';
    case Backlog = 'backlog';
    case Todo = 'todo';
    case InProgress = 'in_progress';
    case InReview = 'in_review';
    case Done = 'done';
    case Blocked = 'blocked';
    case Canceled = 'canceled';
}
