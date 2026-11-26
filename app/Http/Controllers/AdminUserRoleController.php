<?php

namespace App\Http\Controllers;

use App\Enums\SystemRole;
use App\Http\Requests\UpdateSystemRoleRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AdminUserRoleController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(UpdateSystemRoleRequest $request, User $user): RedirectResponse
    {
        $role = SystemRole::from($request->string('system_role')->toString());

        if ($user->isAdmin() && $role !== SystemRole::Admin && User::query()->where('system_role', SystemRole::Admin->value)->count() === 1) {
            throw ValidationException::withMessages(['system_role' => 'Sistem harus memiliki minimal satu Admin.']);
        }

        $user->update(['system_role' => $role]);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Role login diperbarui.']);

        return back();
    }
}
