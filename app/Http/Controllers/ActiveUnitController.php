<?php

namespace App\Http\Controllers;

use App\Actions\Units\SetActiveUnit;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ActiveUnitController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Unit $unit, SetActiveUnit $setActiveUnit): RedirectResponse
    {
        Gate::authorize('view', $unit);
        $setActiveUnit->handle($request->user(), $unit);

        return to_route('dashboard');
    }
}
