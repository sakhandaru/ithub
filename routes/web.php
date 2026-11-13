<?php

use App\Http\Controllers\ActiveWorkspaceController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjectArchiveController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectMemberController;
use App\Http\Controllers\ProjectSettingsController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskTransitionController;
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

        Route::get('projects', [ProjectController::class, 'index'])->name('projects.index');
        Route::get('projects/create', [ProjectController::class, 'create'])->name('projects.create');
        Route::post('projects', [ProjectController::class, 'store'])->name('projects.store');
        Route::prefix('projects/{project}')->name('projects.')->scopeBindings()->group(function () {
            Route::get('/', [ProjectController::class, 'show'])->name('show');
            Route::get('settings', [ProjectSettingsController::class, 'show'])->name('settings.show');
            Route::get('tasks', [TaskController::class, 'index'])->name('tasks.index');
            Route::get('tasks/{task}', [TaskController::class, 'show'])->name('tasks.show');
            Route::patch('/', [ProjectSettingsController::class, 'update'])->name('update');
            Route::post('members', [ProjectMemberController::class, 'store'])->name('members.store');
            Route::patch('members/{membership}', [ProjectMemberController::class, 'update'])->name('members.update');
            Route::delete('members/{membership}', [ProjectMemberController::class, 'destroy'])->name('members.destroy');
            Route::post('archive', [ProjectArchiveController::class, 'archive'])->name('archive');
            Route::post('restore', [ProjectArchiveController::class, 'restore'])->name('restore');
            Route::post('tasks', [TaskController::class, 'store'])->name('tasks.store');
            Route::patch('tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
            Route::patch('tasks/{task}/assignee', [TaskController::class, 'assign'])->name('tasks.assign');
            Route::post('tasks/{task}/transition', TaskTransitionController::class)->name('tasks.transition');
            Route::post('tasks/{task}/comments', [CommentController::class, 'store'])->name('tasks.comments.store');
            Route::delete('tasks/{task}/comments/{comment}', [CommentController::class, 'destroy'])->name('tasks.comments.destroy');
            Route::post('tasks/{task}/attachments', [AttachmentController::class, 'store'])->name('tasks.attachments.store');
            Route::get('tasks/{task}/attachments/{attachment}', [AttachmentController::class, 'download'])->name('tasks.attachments.download');
            Route::delete('tasks/{task}/attachments/{attachment}', [AttachmentController::class, 'destroy'])->name('tasks.attachments.destroy');
        });
    });
});

require __DIR__.'/settings.php';
