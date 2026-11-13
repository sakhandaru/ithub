<?php

use App\Models\User;
use App\Support\RequestContext;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

test('web requests receive a generated request identifier', function () {
    $response = $this->withHeader('X-Request-ID', 'untrusted-request-id')->get(route('home'));

    $requestId = $response->headers->get('X-Request-ID');

    $response->assertOk();
    expect($requestId)
        ->toBeString()
        ->not->toBe('untrusted-request-id');
    expect(Str::isUlid($requestId))->toBeTrue();
});

test('request context exposes the actor and default workspace timezone', function () {
    Route::middleware('web')->get('/_test/request-context', fn () => [
        'request_id' => RequestContext::requestId(),
        'actor_id' => RequestContext::actorId(),
        'timezone' => RequestContext::timezone(),
    ]);

    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/_test/request-context');

    $response
        ->assertOk()
        ->assertJson([
            'actor_id' => $user->id,
            'timezone' => 'Asia/Jakarta',
        ]);

    expect($response->json('request_id'))->toBe($response->headers->get('X-Request-ID'));
});
