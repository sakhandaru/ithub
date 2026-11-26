<?php

use App\Http\Controllers\ActiveUnitController;
use App\Http\Controllers\ActiveWorkspaceController;
use App\Http\Controllers\AdminManagementController;
use App\Http\Controllers\AdminUserRoleController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MilestoneController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\MyTaskController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProjectArchiveController;
use App\Http\Controllers\ProjectAttachmentController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectMemberController;
use App\Http\Controllers\ProjectPlanningController;
use App\Http\Controllers\ProjectSettingsController;
use App\Http\Controllers\ProjectUpdateController;
use App\Http\Controllers\ReleaseController;
use App\Http\Controllers\SavedViewController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskRelationController;
use App\Http\Controllers\TaskTransitionController;
use App\Http\Controllers\UnitAnalyticsController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UnitMemberController;
use App\Http\Controllers\UnitOnboardingController;
use App\Http\Controllers\UnitSettingsController;
use App\Http\Controllers\WorkspaceActivityController;
use App\Http\Controllers\WorkspaceController;
use App\Http\Controllers\WorkspaceMemberController;
use App\Http\Controllers\WorkspaceSettingsController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(array_merge(['auth'], config('fortify.email_verification') ? ['verified'] : []))->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('units/{unit}/dashboard', [DashboardController::class, 'unit'])->name('units.dashboard');
    Route::get('units/{unit}/my-tasks', [MyTaskController::class, 'unit'])->name('units.my-tasks');
    Route::get('search', SearchController::class)->name('search');
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('my-tasks', [MyTaskController::class, 'global'])->name('my-tasks');
    Route::post('notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');

    Route::get('units/create', [UnitOnboardingController::class, 'create'])->name('units.create');
    Route::post('units', [UnitOnboardingController::class, 'store'])->name('units.store');
    Route::put('units/{unit}/active', ActiveUnitController::class)->name('units.active.update');
    Route::get('units/{unit}/settings', UnitSettingsController::class)->name('units.settings');
    Route::get('admin/{section?}', AdminManagementController::class)->name('admin.management');
    Route::put('admin/users/{user}/role', AdminUserRoleController::class)->name('admin.users.role.update');

    Route::get('workspaces/create', [WorkspaceController::class, 'create'])->name('workspaces.create');
    Route::post('workspaces', [WorkspaceController::class, 'store'])->name('workspaces.store');

    Route::prefix('workspaces/{workspace}')->name('workspaces.')->scopeBindings()->group(function () {
        Route::put('active', ActiveWorkspaceController::class)->name('active.update');
        Route::get('settings', [WorkspaceSettingsController::class, 'show'])->name('settings.show');
        Route::get('activity', WorkspaceActivityController::class)->name('activity');
        Route::get('analytics', UnitAnalyticsController::class)->name('analytics');
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
            Route::get('board', [TaskController::class, 'boardRedirect'])->name('board');
            Route::get('calendar', [TaskController::class, 'calendarRedirect'])->name('calendar');
            Route::get('gantt', [TaskController::class, 'ganttRedirect'])->name('gantt');
            Route::get('cycles', [ProjectController::class, 'cycles'])->name('cycles');
            Route::get('modules', [ModuleController::class, 'index'])->name('modules');
            Route::get('activity', [ProjectController::class, 'activity'])->name('activity');
            Route::get('files', [ProjectAttachmentController::class, 'index'])->name('files.index');
            Route::post('files', [ProjectAttachmentController::class, 'store'])->name('files.store');
            Route::get('files/{attachment}/preview', [ProjectAttachmentController::class, 'preview'])->name('files.preview');
            Route::get('files/{attachment}', [ProjectAttachmentController::class, 'download'])->name('files.download');
            Route::delete('files/{attachment}', [ProjectAttachmentController::class, 'destroy'])->name('files.destroy');
            Route::get('inbox', [ProjectPlanningController::class, 'inbox'])->name('inbox');
            Route::get('releases', [ReleaseController::class, 'index'])->name('releases');
            Route::post('releases', [ReleaseController::class, 'store'])->name('releases.store');
            Route::get('releases/{release}', [ReleaseController::class, 'show'])->name('releases.show');
            Route::get('updates', [ProjectUpdateController::class, 'index'])->name('updates');
            Route::post('updates', [ProjectUpdateController::class, 'store'])->name('updates.store');
            Route::get('dashboard', [ProjectController::class, 'dashboard'])->name('dashboard');
            Route::get('tasks/{task}/peek', [TaskController::class, 'peek'])->name('tasks.peek');
            Route::get('tasks/{task}', [TaskController::class, 'show'])->name('tasks.show');
            Route::patch('/', [ProjectSettingsController::class, 'update'])->name('update');
            Route::post('members', [ProjectMemberController::class, 'store'])->name('members.store');
            Route::patch('members/{membership}', [ProjectMemberController::class, 'update'])->name('members.update');
            Route::delete('members/{membership}', [ProjectMemberController::class, 'destroy'])->name('members.destroy');
            Route::post('archive', [ProjectArchiveController::class, 'archive'])->name('archive');
            Route::post('restore', [ProjectArchiveController::class, 'restore'])->name('restore');
            Route::post('tasks', [TaskController::class, 'store'])->name('tasks.store');
            Route::post('tasks/bulk-update', [TaskController::class, 'bulkUpdate'])->name('tasks.bulk-update');
            Route::get('milestones', [MilestoneController::class, 'index'])->name('milestones.index');
            Route::post('milestones', [MilestoneController::class, 'store'])->name('milestones.store');
            Route::patch('milestones/{milestone}', [MilestoneController::class, 'update'])->name('milestones.update');
            Route::delete('milestones/{milestone}', [MilestoneController::class, 'destroy'])->name('milestones.destroy');
            Route::post('modules', [ModuleController::class, 'store'])->name('modules.store');
            Route::delete('modules/{module}', [ModuleController::class, 'destroy'])->name('modules.destroy');
            Route::get('saved-views', [SavedViewController::class, 'index'])->name('saved-views.index');
            Route::post('saved-views', [SavedViewController::class, 'store'])->name('saved-views.store');
            Route::patch('saved-views/{savedView}', [SavedViewController::class, 'update'])->name('saved-views.update');
            Route::delete('saved-views/{savedView}', [SavedViewController::class, 'destroy'])->name('saved-views.destroy');
            Route::patch('tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
            Route::patch('tasks/{task}/assignee', [TaskController::class, 'assign'])->name('tasks.assign');
            Route::post('tasks/{task}/relations', [TaskRelationController::class, 'store'])->name('tasks.relations.store');
            Route::delete('tasks/{task}/relations/{relation}', [TaskRelationController::class, 'destroy'])->name('tasks.relations.destroy');
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
