<?php

namespace App\Http\Controllers;

use App\Actions\Units\AddUnitMember;
use App\Actions\Units\RemoveUnitMember;
use App\Http\Requests\StoreUnitMemberRequest;
use App\Models\Unit;
use App\Models\UnitMembership;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class UnitMemberController extends Controller
{
    public function store(
        StoreUnitMemberRequest $request,
        Workspace $workspace,
        Unit $unit,
        AddUnitMember $addMember,
    ): RedirectResponse {
        $member = User::query()->findOrFail($request->integer('user_id'));
        $addMember->handle($request->user(), $unit, $member);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Anggota ditambahkan ke Unit.']);

        return back();
    }

    public function destroy(
        Workspace $workspace,
        Unit $unit,
        UnitMembership $membership,
        RemoveUnitMember $removeMember,
    ): RedirectResponse {
        Gate::authorize('delete', $membership);
        $removeMember->handle(request()->user(), $membership);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Anggota dihapus dari Unit.']);

        return back();
    }
}
