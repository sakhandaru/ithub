<?php

namespace App\Http\Controllers;

use App\Actions\Units\CreateInitialUnit;
use App\Http\Requests\StoreInitialUnitRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class UnitOnboardingController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('units/Create');
    }

    public function store(StoreInitialUnitRequest $request, CreateInitialUnit $createInitialUnit): RedirectResponse
    {
        $createInitialUnit->handle($request->user(), $request->string('name')->toString());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Unit berhasil dibuat.']);

        return to_route('dashboard');
    }
}
