<?php

namespace App\Support;

use Illuminate\Support\Facades\Context;

final class RequestContext
{
    private const string ActorId = 'actor_id';

    private const string RequestId = 'request_id';

    private const string Timezone = 'timezone';

    public static function assign(string $requestId, int|string|null $actorId, string $timezone): void
    {
        Context::add([
            self::RequestId => $requestId,
            self::ActorId => $actorId,
            self::Timezone => $timezone,
        ]);
    }

    public static function requestId(): ?string
    {
        $requestId = Context::get(self::RequestId);

        return is_string($requestId) ? $requestId : null;
    }

    public static function actorId(): int|string|null
    {
        $actorId = Context::get(self::ActorId);

        return is_int($actorId) || is_string($actorId) ? $actorId : null;
    }

    public static function timezone(): string
    {
        $timezone = Context::get(self::Timezone);

        return is_string($timezone) ? $timezone : 'UTC';
    }
}
