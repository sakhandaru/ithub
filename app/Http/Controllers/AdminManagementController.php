<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Unit;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminManagementController extends Controller
{
    public function __invoke(Request $request, ?string $section = 'users'): Response
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $allowedSections = ['users', 'units', 'roles', 'audit-log', 'system'];
        abort_unless(in_array($section, $allowedSections, true), 404);

        return Inertia::render('admin/Management', [
            'section' => $section,
            'users' => $section === 'users' ? User::query()->orderBy('name')->get(['id', 'name', 'email', 'system_role', 'created_at']) : [],
            'units' => $section === 'units' ? Unit::query()->with('workspace:id,name')->withCount(['memberships', 'projects'])->orderBy('name')->get() : [],
            'workspaceId' => $section === 'units' ? Workspace::query()->value('id') : null,
            'auditLogs' => $section === 'audit-log' ? AuditLog::query()->with(['actor:id,name', 'workspace:id,name'])->latest()->limit(100)->get() : [],
        ]);
    }
}
