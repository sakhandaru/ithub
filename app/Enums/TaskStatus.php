<?php

namespace App\Enums;

enum TaskStatus: string
{
    case Backlog = 'backlog';
    case Todo = 'todo';
    case InProgress = 'in_progress';
    case Review = 'review';
    case Done = 'done';
    case Blocked = 'blocked';
    case Canceled = 'canceled';

    public function label(): string
    {
        return match ($this) {
            self::Backlog => 'Backlog',
            self::Todo => 'Todo',
            self::InProgress => 'In Progress',
            self::Review => 'Review',
            self::Done => 'Done',
            self::Blocked => 'Blocked',
            self::Canceled => 'Canceled',
        };
    }
}
