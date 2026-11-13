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

    public function label(): string
    {
        return match ($this) {
            self::Request => 'Permintaan',
            self::Backlog => 'Backlog',
            self::Todo => 'Todo',
            self::InProgress => 'In Progress',
            self::InReview => 'In Review',
            self::Done => 'Done',
            self::Blocked => 'Blocked',
            self::Canceled => 'Canceled',
        };
    }
}
