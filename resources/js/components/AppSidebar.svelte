<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import { onMount } from 'svelte';
    import BriefcaseBusiness from '@lucide/svelte/icons/briefcase-business';
    import FolderKanban from '@lucide/svelte/icons/folder-kanban';
    import LayoutDashboard from '@lucide/svelte/icons/layout-dashboard';
    import Palette from '@lucide/svelte/icons/palette';
    import Search from '@lucide/svelte/icons/search';
    import Bell from '@lucide/svelte/icons/bell';
    import Activity from '@lucide/svelte/icons/activity';
    import Flag from '@lucide/svelte/icons/flag';
    import Settings from '@lucide/svelte/icons/settings';
    import ShieldCheck from '@lucide/svelte/icons/shield-check';
    import UserRound from '@lucide/svelte/icons/user-round';
    import WorkspaceSwitcher from '@/components/WorkspaceSwitcher.svelte';
    import AppLogo from '@/components/AppLogo.svelte';
    import type { Snippet } from 'svelte';
    import NavUser from '@/components/NavUser.svelte';
    import { Sidebar, useSidebar } from '@/components/ui/sidebar';
    import { currentUrlState } from '@/lib/currentUrl.svelte';
    import { dashboard } from '@/routes';
    import { search } from '@/routes';
    import { index as notificationsIndex } from '@/routes/notifications';
    import { edit as editAppearance } from '@/routes/appearance';
    import { edit as editProfile } from '@/routes/profile';
    import { edit as editSecurity } from '@/routes/security';
    import WorkspaceSettingsController from '@/actions/App/Http/Controllers/WorkspaceSettingsController';
    import TaskController from '@/actions/App/Http/Controllers/TaskController';
    import ProjectController from '@/actions/App/Http/Controllers/ProjectController';
    import { index as projectsIndex } from '@/routes/workspaces/projects';
    import { show as projectShow } from '@/routes/workspaces/projects';
    import { show as projectSettings } from '@/routes/workspaces/projects/settings';
    import { page } from '@inertiajs/svelte';
    import { toUrl } from '@/lib/utils';
    import CommandPalette from '@/components/CommandPalette.svelte';

    let {
        children,
    }: {
        children?: Snippet;
    } = $props();

    const url = currentUrlState();
    const { isMobile, setOpenMobile } = useSidebar();
    const workspaceContext = $derived(page.props.workspaceContext);
    const projectContext = $derived(page.props.projectContext);
    const unreadCount = $derived(page.props.notifications?.unread_count ?? 0);
    const savedViews = $derived(page.props.savedViews ?? []);
    const activeSavedViewId = $derived(new URL(page.url, 'http://localhost').searchParams.get('view'));
    let currentHash = $state('');
    const isWorkspaceSettings = $derived(url.currentUrl.startsWith('/workspaces/') && url.currentUrl.endsWith('/settings'));
    const isSettings = $derived(url.currentUrl.startsWith('/settings') || isWorkspaceSettings);
    const isMilestoneActive = $derived(currentHash === '#milestones' && url.currentUrl.endsWith('/settings'));
    const isActivityActive = $derived(currentHash === '#activity-log' && url.currentUrl.includes('/projects/'));

    onMount(() => {
        const updateHash = (): void => { currentHash = window.location.hash; };
        updateHash();
        window.addEventListener('hashchange', updateHash);
        return () => window.removeEventListener('hashchange', updateHash);
    });

    function closeMobileNavigation(): void {
        if ($isMobile) {
            setOpenMobile(false);
        }
    }
</script>

<Sidebar collapsible="icon" variant="sidebar" class="overflow-hidden">
    <div class="flex h-full min-w-0">
        <nav
            class="flex w-14 shrink-0 flex-col items-center border-r border-sidebar-border bg-sidebar py-2"
            aria-label="Area utama"
        >
            <div class="flex w-full flex-1 flex-col items-center gap-1">
                <Link
                    href={toUrl(dashboard())}
                    class="relative flex size-11 items-center justify-center rounded-md hover:bg-sidebar-accent focus-visible:ring-2 focus-visible:ring-sidebar-ring {isSettings
                        ? 'text-muted-foreground'
                        : 'bg-sidebar-accent text-sidebar-foreground'}"
                    aria-label="Pekerjaan"
                    aria-current={!isSettings ? 'page' : undefined}
                    title="Pekerjaan"
                    onclick={closeMobileNavigation}
                >
                    {#if !isSettings}
                        <span
                            class="absolute inset-y-2 left-0 w-0.5 rounded-r bg-sidebar-foreground"
                            aria-hidden="true"
                        ></span>
                    {/if}
                    <BriefcaseBusiness class="size-5" aria-hidden="true" />
                    <span class="sr-only">Pekerjaan</span>
                </Link>
            </div>

            <Link
                href={toUrl(editProfile())}
                class="relative flex size-11 items-center justify-center rounded-md hover:bg-sidebar-accent focus-visible:ring-2 focus-visible:ring-sidebar-ring {isSettings
                    ? 'bg-sidebar-accent text-sidebar-foreground'
                    : 'text-muted-foreground'}"
                aria-label="Pengaturan"
                aria-current={isSettings ? 'page' : undefined}
                title="Pengaturan"
                onclick={closeMobileNavigation}
            >
                {#if isSettings}
                    <span
                        class="absolute inset-y-2 left-0 w-0.5 rounded-r bg-sidebar-foreground"
                        aria-hidden="true"
                    ></span>
                {/if}
                <Settings class="size-5" aria-hidden="true" />
                <span class="sr-only">Pengaturan</span>
            </Link>
        </nav>

        <div
            class="flex min-w-0 flex-1 flex-col bg-sidebar group-data-[collapsible=icon]:hidden"
        >
            <div class="shrink-0 border-b border-sidebar-border p-2">
                <Link
                    href={toUrl(dashboard())}
                    class="flex min-h-14 items-center px-2.5 pb-3 focus-visible:ring-2 focus-visible:ring-sidebar-ring"
                    aria-label="Beranda ithub"
                    onclick={closeMobileNavigation}
                >
                    <AppLogo />
                </Link>
                <WorkspaceSwitcher />
                <nav class="mt-2 grid gap-1 border-t border-sidebar-border pt-2" aria-label="Akses cepat">
                    <Link
                        href={toUrl(search())}
                        class="flex min-h-10 items-center gap-3 rounded-md px-2.5 text-sm text-muted-foreground hover:bg-sidebar-accent hover:text-sidebar-foreground focus-visible:ring-2 focus-visible:ring-sidebar-ring {url.currentUrl.startsWith('/search') ? 'bg-sidebar-accent font-medium text-sidebar-foreground' : ''}"
                        aria-current={url.currentUrl.startsWith('/search') ? 'page' : undefined}
                        onclick={closeMobileNavigation}
                    >
                        <Search class="size-4 shrink-0" aria-hidden="true" />
                        <span class="min-w-0 flex-1">Cari</span>
                        <kbd class="hidden rounded border border-sidebar-border px-1.5 py-0.5 text-[10px] font-normal text-muted-foreground sm:inline">⌘K</kbd>
                    </Link>
                    <Link
                        href={toUrl(notificationsIndex())}
                        class="flex min-h-10 items-center gap-3 rounded-md px-2.5 text-sm text-muted-foreground hover:bg-sidebar-accent hover:text-sidebar-foreground focus-visible:ring-2 focus-visible:ring-sidebar-ring {url.currentUrl.startsWith('/notifications') ? 'bg-sidebar-accent font-medium text-sidebar-foreground' : ''}"
                        aria-current={url.currentUrl.startsWith('/notifications') ? 'page' : undefined}
                        onclick={closeMobileNavigation}
                    >
                        <Bell class="size-4 shrink-0" aria-hidden="true" />
                        <span class="min-w-0 flex-1">Notifikasi</span>
                        {#if unreadCount > 0}<span class="inline-flex min-w-5 items-center justify-center rounded-full bg-primary px-1.5 py-0.5 text-[10px] font-semibold text-primary-foreground">{unreadCount > 99 ? '99+' : unreadCount}</span>{/if}
                    </Link>
                </nav>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto px-3 py-4">
                {#if isSettings}
                    <p class="px-2 pb-2 text-xs font-medium text-muted-foreground">
                        {isWorkspaceSettings ? 'Pengaturan Workspace' : 'Pengaturan akun'}
                    </p>
                    <nav class="grid gap-1" aria-label={isWorkspaceSettings ? 'Pengaturan Workspace' : 'Pengaturan akun'}>
                        {#if isWorkspaceSettings && workspaceContext.active}
                            <Link
                                href={toUrl(WorkspaceSettingsController.show(workspaceContext.active.id))}
                                class="flex min-h-10 items-center gap-3 rounded-md bg-sidebar-accent px-2.5 text-sm font-medium focus-visible:ring-2 focus-visible:ring-sidebar-ring"
                                aria-current="page"
                                onclick={closeMobileNavigation}
                            >
                                Workspace
                            </Link>
                        {:else}
                            <Link
                            href={toUrl(editProfile())}
                            class="flex min-h-10 items-center gap-3 rounded-md px-2.5 text-sm hover:bg-sidebar-accent focus-visible:ring-2 focus-visible:ring-sidebar-ring {url.isCurrentUrl(
                                editProfile(),
                                url.currentUrl,
                            )
                                ? 'bg-sidebar-accent font-medium'
                                : 'text-muted-foreground'}"
                            aria-current={url.isCurrentUrl(editProfile(), url.currentUrl)
                                ? 'page'
                                : undefined}
                            onclick={closeMobileNavigation}
                        >
                            <UserRound class="size-4" aria-hidden="true" />
                            Profil
                            </Link>
                            <Link
                            href={toUrl(editSecurity())}
                            class="flex min-h-10 items-center gap-3 rounded-md px-2.5 text-sm hover:bg-sidebar-accent focus-visible:ring-2 focus-visible:ring-sidebar-ring {url.isCurrentUrl(
                                editSecurity(),
                                url.currentUrl,
                            )
                                ? 'bg-sidebar-accent font-medium'
                                : 'text-muted-foreground'}"
                            aria-current={url.isCurrentUrl(editSecurity(), url.currentUrl)
                                ? 'page'
                                : undefined}
                            onclick={closeMobileNavigation}
                        >
                            <ShieldCheck class="size-4" aria-hidden="true" />
                            Keamanan
                            </Link>
                            <Link
                            href={toUrl(editAppearance())}
                            class="flex min-h-10 items-center gap-3 rounded-md px-2.5 text-sm hover:bg-sidebar-accent focus-visible:ring-2 focus-visible:ring-sidebar-ring {url.isCurrentUrl(
                                editAppearance(),
                                url.currentUrl,
                            )
                                ? 'bg-sidebar-accent font-medium'
                                : 'text-muted-foreground'}"
                            aria-current={url.isCurrentUrl(editAppearance(), url.currentUrl)
                                ? 'page'
                                : undefined}
                            onclick={closeMobileNavigation}
                        >
                            <Palette class="size-4" aria-hidden="true" />
                            Tampilan
                            </Link>
                        {/if}
                    </nav>
                {:else}
                    {#if projectContext.active}
                        <div class="mb-4 px-2">
                            <p class="truncate text-sm font-semibold" title={projectContext.active.name}>
                                {projectContext.active.name}
                            </p>
                            <p class="mt-0.5 text-xs text-muted-foreground">{projectContext.active.identifier}</p>
                        </div>
                    {:else}
                        <p class="px-2 pb-2 text-xs font-medium text-muted-foreground">
                            Pekerjaan
                        </p>
                    {/if}
                    <nav class="grid gap-1" aria-label="Pekerjaan">
                        {#if projectContext.active}
                            <Link
                                href={toUrl(projectShow([projectContext.active.workspace_id, projectContext.active.id]))}
                                class="flex min-h-10 items-center gap-3 rounded-md px-2.5 text-sm hover:bg-sidebar-accent focus-visible:ring-2 focus-visible:ring-sidebar-ring"
                                aria-current={url.currentUrl.endsWith(`/projects/${projectContext.active.id}`) ? 'page' : undefined}
                                onclick={closeMobileNavigation}
                            >
                                <LayoutDashboard class="size-4" aria-hidden="true" />
                                Ikhtisar
                            </Link>
                            <Link
                                href={toUrl(projectSettings([projectContext.active.workspace_id, projectContext.active.id]))}
                                class="flex min-h-10 items-center gap-3 rounded-md px-2.5 text-sm hover:bg-sidebar-accent focus-visible:ring-2 focus-visible:ring-sidebar-ring"
                                aria-current={url.currentUrl.endsWith('/settings') ? 'page' : undefined}
                                onclick={closeMobileNavigation}
                            >
                                <Settings class="size-4" aria-hidden="true" />
                                Pengaturan Project
                            </Link>
                            <Link
                                href={toUrl(TaskController.index({ workspace: projectContext.active.workspace_id, project: projectContext.active.id }))}
                                class="flex min-h-10 items-center gap-3 rounded-md px-2.5 text-sm hover:bg-sidebar-accent focus-visible:ring-2 focus-visible:ring-sidebar-ring"
                                aria-current={url.currentUrl.includes('/tasks') ? 'page' : undefined}
                                onclick={closeMobileNavigation}
                            >
                                <FolderKanban class="size-4" aria-hidden="true" />
                                Task
                            </Link>
                            <Link
                                href={toUrl(TaskController.board({ workspace: projectContext.active.workspace_id, project: projectContext.active.id }))}
                                class="flex min-h-10 items-center gap-3 rounded-md px-2.5 text-sm hover:bg-sidebar-accent focus-visible:ring-2 focus-visible:ring-sidebar-ring"
                                aria-current={url.currentUrl.endsWith('/board') ? 'page' : undefined}
                                onclick={closeMobileNavigation}
                            >
                                <FolderKanban class="size-4" aria-hidden="true" />
                                Board
                            </Link>
                            <Link
                                href={toUrl(ProjectController.dashboard({ workspace: projectContext.active.workspace_id, project: projectContext.active.id }))}
                                class="flex min-h-10 items-center gap-3 rounded-md px-2.5 text-sm hover:bg-sidebar-accent focus-visible:ring-2 focus-visible:ring-sidebar-ring"
                                aria-current={url.currentUrl.endsWith('/dashboard') ? 'page' : undefined}
                                onclick={closeMobileNavigation}
                            >
                                <LayoutDashboard class="size-4" aria-hidden="true" />
                                Dashboard
                            </Link>
                            <Link
                                href={toUrl(projectSettings([projectContext.active.workspace_id, projectContext.active.id]) + '#milestones')}
                                class="flex min-h-10 items-center gap-3 rounded-md px-2.5 text-sm hover:bg-sidebar-accent focus-visible:ring-2 focus-visible:ring-sidebar-ring {isMilestoneActive ? 'bg-sidebar-accent font-medium' : ''}"
                                aria-current={isMilestoneActive ? 'page' : undefined}
                                onclick={closeMobileNavigation}
                            >
                                <Flag class="size-4" aria-hidden="true" />
                                Milestone
                            </Link>
                            <Link
                                href={toUrl(projectShow([projectContext.active.workspace_id, projectContext.active.id]) + '#activity-log')}
                                class="flex min-h-10 items-center gap-3 rounded-md px-2.5 text-sm hover:bg-sidebar-accent focus-visible:ring-2 focus-visible:ring-sidebar-ring {isActivityActive ? 'bg-sidebar-accent font-medium' : ''}"
                                aria-current={isActivityActive ? 'page' : undefined}
                                onclick={closeMobileNavigation}
                            >
                                <Activity class="size-4" aria-hidden="true" />
                                Activity Log
                            </Link>
                        {:else}
                            <Link
                                href={toUrl(dashboard())}
                                class="flex min-h-10 items-center gap-3 rounded-md bg-sidebar-accent px-2.5 text-sm font-medium focus-visible:ring-2 focus-visible:ring-sidebar-ring"
                                aria-current="page"
                                onclick={closeMobileNavigation}
                            >
                                <LayoutDashboard class="size-4" aria-hidden="true" />
                                Beranda
                            </Link>
                        {/if}
                    </nav>

                    {#if projectContext.active && savedViews.length > 0}
                        <div class="mt-6">
                            <p class="px-2 pb-2 text-xs font-medium text-muted-foreground">Saved View</p>
                            <nav class="grid gap-1" aria-label="Saved View">
                                {#each savedViews as view (view.id)}
                                    <Link
                                        href={toUrl(TaskController.index({ workspace: projectContext.active.workspace_id, project: projectContext.active.id }, { query: { ...view.filters, view: view.id } }))}
                                        class="flex min-h-11 items-center gap-3 rounded-md px-2.5 text-sm hover:bg-sidebar-accent focus-visible:ring-2 focus-visible:ring-sidebar-ring {activeSavedViewId === view.id ? 'bg-sidebar-accent font-medium' : 'text-muted-foreground'}"
                                        aria-current={activeSavedViewId === view.id ? 'page' : undefined}
                                        onclick={closeMobileNavigation}
                                    >
                                        <FolderKanban class="size-4 shrink-0" aria-hidden="true" />
                                        <span class="min-w-0 truncate" title={view.name}>{view.name}</span>
                                    </Link>
                                {/each}
                            </nav>
                        </div>
                    {/if}

                    {#if !projectContext.active}
                    <div class="mt-7">
                        <p class="px-2 pb-2 text-xs font-medium text-muted-foreground">
                            Workspace
                        </p>
                        {#if workspaceContext.active}
                            <Link
                                href={toUrl(projectsIndex(workspaceContext.active.id))}
                                class="flex min-h-10 items-center gap-3 rounded-md px-2.5 text-sm text-muted-foreground hover:bg-sidebar-accent focus-visible:ring-2 focus-visible:ring-sidebar-ring"
                                onclick={closeMobileNavigation}
                            >
                                <FolderKanban class="size-4 shrink-0" aria-hidden="true" />
                                Projects
                            </Link>
                        {:else}
                            <div class="flex items-start gap-3 px-2.5 py-2 text-muted-foreground">
                                <FolderKanban class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                                <p class="text-xs leading-5">Pilih Workspace untuk melihat Project.</p>
                            </div>
                        {/if}
                        {#if workspaceContext.active?.can_manage}
                            <Link
                                href={toUrl(WorkspaceSettingsController.show(workspaceContext.active.id))}
                                class="mt-3 flex min-h-10 items-center gap-3 rounded-md px-2.5 text-sm text-muted-foreground hover:bg-sidebar-accent focus-visible:ring-2 focus-visible:ring-sidebar-ring"
                                onclick={closeMobileNavigation}
                            >
                                <Settings class="size-4" aria-hidden="true" />
                                Pengaturan Workspace
                            </Link>
                        {/if}
                    </div>
                    {/if}
                {/if}
            </div>

            <div class="border-t border-sidebar-border p-2">
                <NavUser />
            </div>
        </div>
    </div>
</Sidebar>
<CommandPalette />
{@render children?.()}
