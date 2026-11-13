<?php

use App\Http\Controllers\ActiveWorkspaceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UnitMemberController;
use App\Http\Controllers\WorkspaceController;
use App\Http\Controllers\WorkspaceMemberController;
use App\Http\Controllers\WorkspaceSettingsController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(array_merge(['auth'], config('fortify.email_verification') ? ['verified'] : []))->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('workspaces/create', [WorkspaceController::class, 'create'])->name('workspaces.create');
    Route::post('workspaces', [WorkspaceController::class, 'store'])->name('workspaces.store');

    Route::prefix('workspaces/{workspace}')->name('workspaces.')->scopeBindings()->group(function () {
        Route::put('active', ActiveWorkspaceController::class)->name('active.update');
        Route::get('settings', [WorkspaceSettingsController::class, 'show'])->name('settings.show');
        Route::patch('/', [WorkspaceSettingsController::class, 'update'])->name('update');

        Route::post('members', [WorkspaceMemberController::class, 'store'])->name('members.store');
        Route::patch('members/{membership}', [WorkspaceMemberController::class, 'update'])->name('members.update');
        Route::delete('members/{membership}', [WorkspaceMemberController::class, 'destroy'])->name('members.destroy');

        Route::post('units', [UnitController::class, 'store'])->name('units.store');
        Route::patch('units/{unit}', [UnitController::class, 'update'])->name('units.update');
        Route::post('units/{unit}/members', [UnitMemberController::class, 'store'])->name('units.members.store');
        Route::delete('units/{unit}/members/{membership}', [UnitMemberController::class, 'destroy'])->name('units.members.destroy');
    });
});

require __DIR__.'/settings.php';
