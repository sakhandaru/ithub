<?php

namespace App\Http\Middleware;

use App\Enums\SystemRole;
use App\Enums\WorkspaceRole;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'workspaceContext' => fn (): array => $this->workspaceContext($request->user()),
            'unitContext' => fn (): array => $this->unitContext($request->user()),
            'projectContext' => fn (): array => $this->projectContext($request->route('project'), $request->user()),
            'notifications' => fn (): array => $request->user() ? [
                'unread_count' => $request->user()->unreadNotifications()->count(),
                'items' => $request->user()->notifications()
                    ->latest()
                    ->limit(5)
                    ->get()
                    ->map(fn ($notification): array => [
                        'id' => (string) $notification->id,
                        'title' => $notification->data['title'] ?? 'Notifikasi baru',
                        'message' => $notification->data['message'] ?? '',
                        'url' => $notification->data['url'] ?? route('notifications.index'),
                        'read_at' => $notification->read_at?->toIso8601String(),
                        'created_at' => $notification->created_at?->toIso8601String(),
                    ])
                    ->values()
                    ->all(),
            ] : ['unread_count' => 0, 'items' => []],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /** @return array{active: array{id: string, name: string, timezone: string, role: string, can_manage: bool}|null, available: list<array{id: string, name: string, role: string}>} */
    private function workspaceContext(?User $user): array
    {
        if (! $user) {
            return ['active' => null, 'available' => []];
        }

        $memberships = $user->workspaceMemberships()
            ->with('workspace:id,name,timezone')
            ->get()
            ->sortBy(fn ($membership) => $membership->workspace->name)
            ->values();

        $activeMembership = $memberships->firstWhere('workspace_id', $user->active_workspace_id);
        $activeRole = $activeMembership ? WorkspaceRole::from((string) $activeMembership->getRawOriginal('role')) : null;

        return [
            'active' => $activeMembership ? [
                'id' => (string) $activeMembership->workspace->id,
                'name' => $activeMembership->workspace->name,
                'timezone' => $activeMembership->workspace->timezone,
                'role' => $activeRole->value,
                'can_manage' => $activeRole->canManageWorkspace(),
            ] : null,
            'available' => array_values($memberships->map(fn ($membership) => [
                'id' => (string) $membership->workspace->id,
                'name' => $membership->workspace->name,
                'role' => WorkspaceRole::from((string) $membership->getRawOriginal('role'))->value,
            ])->all()),
        ];
    }

    /** @return array{active: array{id: string, workspace_id: string, name: string, role: string|null, role_label: string|null, can_manage: bool}|null, available: list<array{id: string, workspace_id: string, name: string}>} */
    private function unitContext(?User $user): array
    {
        if (! $user) {
            return ['active' => null, 'available' => []];
        }

        $memberships = $user->unitMemberships()->with('unit:id,name,workspace_id')->get()->sortBy(fn ($membership) => $membership->unit->name)->values();
        $activeMembership = $memberships->firstWhere('unit_id', $user->active_unit_id);
        $role = $user->system_role ?? SystemRole::User;

        return [
            'active' => $activeMembership ? [
                'id' => (string) $activeMembership->unit->id,
                'workspace_id' => (string) $activeMembership->unit->workspace_id,
                'name' => $activeMembership->unit->name,
                'role' => $role->value,
                'role_label' => $role->label(),
                'can_manage' => $user->isAdmin(),
            ] : null,
            'available' => array_values($memberships->map(fn ($membership) => [
                'id' => (string) $membership->unit->id,
                'workspace_id' => (string) $membership->unit->workspace_id,
                'name' => $membership->unit->name,
            ])->all()),
        ];
    }

    /** @return array{active: array{id: string, workspace_id: string, name: string, identifier: string, archived: bool}|null, available: list<array{id: string, workspace_id: string, name: string, identifier: string}>} */
    private function projectContext(mixed $project, ?User $user): array
    {
        if (! $user) {
            return ['active' => null, 'available' => []];
        }

        $activeUnit = $user->activeUnit;
        $available = $activeUnit
            ? $activeUnit->projects()
                ->visibleTo($user)
                ->whereNull('archived_at')
                ->orderBy('name')
                ->get(['id', 'workspace_id', 'name', 'identifier'])
                ->map(fn (Project $availableProject): array => [
                    'id' => (string) $availableProject->id,
                    'workspace_id' => (string) $availableProject->workspace_id,
                    'name' => $availableProject->name,
                    'identifier' => $availableProject->identifier,
                ])
                ->values()
                ->all()
            : [];

        if (! $project instanceof Project) {
            return ['active' => null, 'available' => $available];
        }

        return [
            'active' => [
                'id' => (string) $project->id,
                'workspace_id' => (string) $project->workspace_id,
                'name' => $project->name,
                'identifier' => $project->identifier,
                'archived' => $project->isArchived(),
            ],
            'available' => $available,
        ];
    }
}
