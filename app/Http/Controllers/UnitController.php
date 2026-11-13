<?php

namespace App\Http\Controllers;

use App\Actions\Units\CreateUnit;
use App\Actions\Units\UpdateUnit;
use App\Http\Requests\StoreUnitRequest;
use App\Http\Requests\UpdateUnitRequest;
use App\Models\Unit;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class UnitController extends Controller
{
    public function store(StoreUnitRequest $request, Workspace $workspace, CreateUnit $createUnit): RedirectResponse
    {
        $createUnit->handle($request->user(), $workspace, $request->string('name')->toString());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Unit berhasil dibuat.']);

        return back();
    }

    public function update(UpdateUnitRequest $request, Workspace $workspace, Unit $unit, UpdateUnit $updateUnit): RedirectResponse
    {
        $updateUnit->handle($request->user(), $unit, $request->string('name')->toString());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Nama Unit diperbarui.']);

        return back();
    }
}
