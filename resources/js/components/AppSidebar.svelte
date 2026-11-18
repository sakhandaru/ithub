<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import { onMount } from 'svelte';
    import BriefcaseBusiness from 'carbon-icons-svelte/lib/Dashboard.svelte';
    import RefreshCw from 'carbon-icons-svelte/lib/Renew.svelte';
    import Folder from 'carbon-icons-svelte/lib/Folder.svelte';
    import LayoutDashboard from 'carbon-icons-svelte/lib/Dashboard.svelte';
    import LayoutGrid from 'carbon-icons-svelte/lib/Grid.svelte';
    import BarChart3 from 'carbon-icons-svelte/lib/ChartBar.svelte';
    import ListTodo from 'carbon-icons-svelte/lib/Task.svelte';
    import ClipboardList from 'carbon-icons-svelte/lib/ListChecked.svelte';
    import Inbox from 'carbon-icons-svelte/lib/Email.svelte';
    import Archive from 'carbon-icons-svelte/lib/Archive.svelte';
    import BookOpen from 'carbon-icons-svelte/lib/Book.svelte';
    import Document from 'carbon-icons-svelte/lib/Document.svelte';
    import Eye from 'carbon-icons-svelte/lib/View.svelte';
    import Plus from 'carbon-icons-svelte/lib/Add.svelte';
    import RotateCcw from 'carbon-icons-svelte/lib/Renew.svelte';
    import Palette from 'carbon-icons-svelte/lib/PaintBrush.svelte';
    import Activity from 'carbon-icons-svelte/lib/Activity.svelte';
    import Flag from 'carbon-icons-svelte/lib/Flag.svelte';
    import Settings from 'carbon-icons-svelte/lib/Settings.svelte';
    import ShieldCheck from 'carbon-icons-svelte/lib/Security.svelte';
    import UserRound from 'carbon-icons-svelte/lib/UserAvatarFilled.svelte';
    import ChevronDown from 'carbon-icons-svelte/lib/ChevronDown.svelte';
    import type { Snippet } from 'svelte';
    import NavUser from '@/components/NavUser.svelte';
    import { Sidebar, SidebarTrigger, useSidebar } from '@/components/ui/sidebar';
    import { currentUrlState } from '@/lib/currentUrl.svelte';
    import { dashboard } from '@/routes';
    import { myTasks as globalMyTasks } from '@/routes';
    import { dashboard as unitDashboard } from '@/routes/units';
    import { edit as editAppearance } from '@/routes/appearance';
    import { edit as editProfile } from '@/routes/profile';
    import { edit as editSecurity } from '@/routes/security';
    import UnitSettingsController from '@/actions/App/Http/Controllers/UnitSettingsController';
    import AdminManagementController from '@/actions/App/Http/Controllers/AdminManagementController';
    import ProjectController from '@/actions/App/Http/Controllers/ProjectController';
    import ProjectAttachmentController from '@/actions/App/Http/Controllers/ProjectAttachmentController';
    import TaskController from '@/actions/App/Http/Controllers/TaskController';
    import { index as projectsIndex } from '@/routes/workspaces/projects';
    import { activity as workspaceActivity } from '@/routes/workspaces';
    import { analytics as unitAnalytics } from '@/routes/workspaces';
    import { cycles, modules, show as projectShow } from '@/routes/workspaces/projects';
    import { activity as projectActivity } from '@/routes/workspaces/projects';
    import { inbox as projectInbox, releases as projectReleases, updates as projectUpdates } from '@/routes/workspaces/projects';
    import { index as projectMilestones } from '@/routes/workspaces/projects/milestones';
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
    const { isMobile, setOpen, setOpenMobile } = useSidebar();
    const unitContext = $derived(page.props.unitContext);
    const projectContext = $derived(page.props.projectContext);
    let expandedProjectId = $state<string | null>(null);
    let projectAccordionInitialized = $state(false);
    const savedViews = $derived(page.props.savedViews ?? []);
    const activeSavedViewId = $derived(new URL(page.url, 'http://localhost').searchParams.get('view'));
    const storedSidebarWidth = typeof window !== 'undefined' ? Number(window.localStorage.getItem('ithub-sidebar-width')) : NaN;
    const initialSidebarWidth = Number.isFinite(storedSidebarWidth) ? Math.max(64, storedSidebarWidth) : 304;
    let sidebarWidth = $state(initialSidebarWidth);
    let expandedSidebarWidth = $state(initialSidebarWidth);
    let sidebarReady = $state(false);
    let resizeStartX = 0;
    let resizeStartWidth = 304;
    let isResizing = $state(false);
    const isWorkspaceSettings = $derived(url.currentUrl.startsWith('/workspaces/') && url.currentUrl.endsWith('/settings'));
    const isUnitSettings = $derived(url.currentUrl.startsWith('/units/') && url.currentUrl.endsWith('/settings'));
    const isAdminManagement = $derived(url.currentUrl.startsWith('/admin'));
    const isSettings = $derived(url.currentUrl.startsWith('/settings') || isWorkspaceSettings || isUnitSettings || isAdminManagement);
    const isAdmin = $derived(page.props.auth?.user?.system_role === 'admin');
    const isDashboardActive = $derived(url.isCurrentUrl(dashboard(), url.currentUrl) && !isSettings);
    const isUnitDashboardActive = $derived(url.currentUrl.startsWith('/units/') && url.currentUrl.endsWith('/dashboard'));
    const isUnitArchiveActive = $derived(new URL(page.url, 'http://localhost').searchParams.get('archived') === '1');
    const isProjectsIndexActive = $derived(url.currentUrl.endsWith('/projects') && !projectContext.active && !isSettings && !isUnitArchiveActive);
    const isTaskView = $derived(url.currentUrl.includes('/tasks') || url.currentUrl.endsWith('/board'));
    const isCyclesActive = $derived(url.currentUrl.endsWith('/cycles'));
    const isModulesActive = $derived(url.currentUrl.endsWith('/modules'));
    const isMilestoneActive = $derived(url.currentUrl.endsWith('/milestones'));
    const isActivityActive = $derived(url.currentUrl.endsWith('/activity') && url.currentUrl.includes('/projects/'));
    const isFilesActive = $derived(url.currentUrl.includes('/projects/') && url.currentUrl.endsWith('/files'));
    const isInboxActive = $derived(url.currentUrl.endsWith('/inbox'));
    const isReleasesActive = $derived(url.currentUrl.endsWith('/releases'));
    const isUpdatesActive = $derived(url.currentUrl.endsWith('/updates'));
    const isWorkspaceActivityActive = $derived(url.currentUrl.endsWith('/activity') && !url.currentUrl.includes('/projects/'));
    const myTasksUnitId = $derived(new URL(url.currentUrl, 'http://localhost').searchParams.get('unit'));
    const isGlobalMyTasksActive = $derived(url.currentUrl.startsWith('/my-tasks') && !myTasksUnitId);
    const isUnitMyTasksActive = $derived(url.currentUrl.startsWith('/my-tasks') && !!myTasksUnitId);
    const isUnitAnalyticsActive = $derived(url.currentUrl.endsWith('/analytics'));
    const contextAreaLabel = $derived(isAdminManagement ? 'Administrasi' : 'Pekerjaan');

    $effect(() => {
        if (!projectAccordionInitialized && projectContext.active?.id) {
            expandedProjectId = projectContext.active.id;
            projectAccordionInitialized = true;
        }
    });

    onMount(() => {
        const storedWidth = Number(localStorage.getItem('ithub-sidebar-width'));
        if (Number.isFinite(storedWidth)) {
            sidebarWidth = Math.max(64, storedWidth);
            expandedSidebarWidth = sidebarWidth;
        }

        sidebarReady = true;
    });

    function startResize(event: PointerEvent): void {
        if ($isMobile) return;
        resizeStartX = event.clientX;
        resizeStartWidth = sidebarWidth;
        isResizing = true;
        (event.currentTarget as HTMLElement).setPointerCapture(event.pointerId);
    }

    function resizeSidebar(event: PointerEvent): void {
        if (!isResizing) return;
        sidebarWidth = Math.max(0, resizeStartWidth + event.clientX - resizeStartX);
    }

    function finishResize(event: PointerEvent): void {
        if (!isResizing) return;
        isResizing = false;
        if (sidebarWidth < 64) {
            sidebarWidth = 64;
            expandedSidebarWidth = sidebarWidth;
            localStorage.setItem('ithub-sidebar-width', String(sidebarWidth));
            setOpen(false);
            (event.currentTarget as HTMLElement).releasePointerCapture(event.pointerId);
            return;
        }

        sidebarWidth = Math.max(64, sidebarWidth);
        expandedSidebarWidth = sidebarWidth;
        localStorage.setItem('ithub-sidebar-width', String(sidebarWidth));
        (event.currentTarget as HTMLElement).releasePointerCapture(event.pointerId);
    }

    function closeMobileNavigation(): void {
        if ($isMobile) {
            setOpenMobile(false);
        }
    }

    function toggleProject(projectId: string): void {
        expandedProjectId = expandedProjectId === projectId ? null : projectId;
    }
</script>

<div class="flex min-h-0 min-w-0 flex-1 {isResizing ? 'sidebar-resizing' : ''} {sidebarReady ? '' : 'invisible'}" style={`--sidebar-width: ${sidebarWidth}px;`}>
        <nav
            class="hidden w-14 shrink-0 flex-col items-center bg-muted py-2 md:flex"
            aria-label="Area utama"
        >
            <div class="flex w-full flex-1 flex-col items-center gap-1">
                <Link
                    href={toUrl(dashboard())}
                    class="relative flex size-10 items-center justify-center rounded-md hover:bg-sidebar-accent/50 focus-visible:ring-2 focus-visible:ring-sidebar-ring {isSettings
                        ? 'text-muted-foreground'
                        : 'text-sidebar-foreground'}"
                    aria-label="Pekerjaan"
                    aria-current={!isSettings ? 'page' : undefined}
                    title="Pekerjaan"
                    onclick={closeMobileNavigation}
                >
                    {#if !isSettings}
                        <span
                            class="absolute inset-y-2 left-0 w-0.5 rounded-r bg-sidebar-primary"
                            aria-hidden="true"
                        ></span>
                    {/if}
                    <BriefcaseBusiness class="size-5" aria-hidden="true" />
                    <span class="sr-only">Pekerjaan</span>
                </Link>
                {#if isAdmin}
                    <Link
                        href={toUrl(AdminManagementController.url('users'))}
                        class="relative flex size-10 items-center justify-center rounded-md hover:bg-sidebar-accent/50 focus-visible:ring-2 focus-visible:ring-sidebar-ring {isAdminManagement
                            ? 'text-sidebar-foreground'
                            : 'text-muted-foreground'}"
                        aria-label="Administrasi"
                        aria-current={isAdminManagement ? 'page' : undefined}
                        title="Administrasi"
                        onclick={closeMobileNavigation}
                    >
                        {#if isAdminManagement}
                            <span class="absolute inset-y-2 left-0 w-0.5 rounded-r bg-sidebar-primary" aria-hidden="true"></span>
                        {/if}
                        <ShieldCheck class="size-5" aria-hidden="true" />
                        <span class="sr-only">Admin</span>
                    </Link>
                {/if}
            </div>

            <Link
                href={toUrl(editProfile())}
                class="relative flex size-10 items-center justify-center rounded-md hover:bg-sidebar-accent/50 focus-visible:ring-2 focus-visible:ring-sidebar-ring {isSettings
                    ? 'text-sidebar-foreground'
                    : 'text-muted-foreground'}"
                aria-label="Pengaturan"
                aria-current={isSettings ? 'page' : undefined}
                title="Pengaturan"
                onclick={closeMobileNavigation}
            >
                {#if isSettings}
                    <span
                        class="absolute inset-y-2 left-0 w-0.5 rounded-r bg-sidebar-primary"
                        aria-hidden="true"
                    ></span>
                {/if}
                <Settings class="size-5" aria-hidden="true" />
                <span class="sr-only">Pengaturan</span>
            </Link>
        </nav>

        <div class="flex min-h-0 min-w-0 flex-1 overflow-hidden md:mt-0 md:mr-3 md:mb-3 md:ml-0 md:rounded-xl md:border md:border-border/80 md:bg-card md:shadow-sm">
            <Sidebar
                collapsible="icon"
                variant="sidebar"
                class="ithub-app-sidebar h-full min-h-0 overflow-hidden group-data-[state=collapsed]:!w-0"
            >

        <div
            class="ithub-context-sidebar relative flex h-full min-h-0 min-w-0 flex-1 flex-col bg-card group-data-[collapsible=icon]:hidden"
        >
            <header class="flex h-11 shrink-0 items-center gap-1 border-b border-border/80 px-1.5" aria-label={`Header ${contextAreaLabel}`}>
                <p class="min-w-0 flex-1 truncate px-1 text-sm font-semibold" title={contextAreaLabel}>{contextAreaLabel}</p>
                <SidebarTrigger class="size-8 shrink-0 rounded-md text-muted-foreground hover:bg-transparent hover:text-sidebar-foreground" />
                {#if isAdminManagement}
                    <Link href={toUrl(AdminManagementController.url('system'))} class="inline-flex size-8 shrink-0 items-center justify-center rounded-md text-muted-foreground hover:bg-sidebar-accent hover:text-sidebar-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sidebar-ring" aria-label="Pengaturan Administrasi" title="Pengaturan Administrasi" onclick={closeMobileNavigation}>
                        <Settings class="size-4" aria-hidden="true" />
                    </Link>
                {:else if projectContext.active}
                    <Link href={toUrl(projectSettings([projectContext.active.workspace_id, projectContext.active.id]))} class="inline-flex size-8 shrink-0 items-center justify-center rounded-md text-muted-foreground hover:bg-sidebar-accent hover:text-sidebar-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sidebar-ring" aria-label="Pengaturan Project" title="Pengaturan Project" onclick={closeMobileNavigation}>
                        <Settings class="size-4" aria-hidden="true" />
                    </Link>
                {:else if unitContext.active}
                    <Link href={toUrl(UnitSettingsController.url(unitContext.active.id))} class="inline-flex size-8 shrink-0 items-center justify-center rounded-md text-muted-foreground hover:bg-sidebar-accent hover:text-sidebar-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sidebar-ring" aria-label="Pengaturan Unit" title="Pengaturan Unit" onclick={closeMobileNavigation}>
                        <Settings class="size-4" aria-hidden="true" />
                    </Link>
                {:else}
                    <Link href={toUrl(editProfile())} class="inline-flex size-8 shrink-0 items-center justify-center rounded-md text-muted-foreground hover:bg-sidebar-accent hover:text-sidebar-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sidebar-ring" aria-label="Pengaturan akun" title="Pengaturan akun" onclick={closeMobileNavigation}>
                        <Settings class="size-4" aria-hidden="true" />
                    </Link>
                {/if}
            </header>

            <div class="min-h-0 flex-1 overflow-y-auto px-2 py-2">
                {#if isSettings}
                    <p class="px-2 pb-1.5 text-xs font-medium text-muted-foreground">
                        {isAdminManagement ? 'Administrasi' : (isWorkspaceSettings || isUnitSettings) ? 'Pengaturan Unit' : 'Pengaturan akun'}
                    </p>
                    <nav class="grid gap-0.5" aria-label={isAdminManagement ? 'Administrasi' : (isWorkspaceSettings || isUnitSettings) ? 'Pengaturan Unit' : 'Pengaturan akun'}>
                        {#if isAdminManagement}
                            {#each [{ key: 'users', label: 'Manajemen User' }, { key: 'units', label: 'Manajemen Unit' }, { key: 'roles', label: 'Role & Akses' }, { key: 'audit-log', label: 'Audit Log' }, { key: 'system', label: 'Pengaturan Sistem' }] as item}
                                <Link href={toUrl(AdminManagementController.url(item.key))} class="flex min-h-9 items-center gap-2.5 rounded-md px-2 text-sm hover:bg-sidebar-accent focus-visible:ring-2 focus-visible:ring-sidebar-ring {url.currentUrl === `/admin/${item.key}` || (item.key === 'users' && url.currentUrl === '/admin') ? 'bg-sidebar-accent font-medium' : 'text-muted-foreground'}" aria-current={url.currentUrl === `/admin/${item.key}` || (item.key === 'users' && url.currentUrl === '/admin') ? 'page' : undefined} onclick={closeMobileNavigation}>{item.label}</Link>
                            {/each}
                        {:else if (isWorkspaceSettings || isUnitSettings) && unitContext.active}
                            <Link
                                href={toUrl(UnitSettingsController.url(unitContext.active.id))}
                                class="flex min-h-9 items-center gap-2.5 rounded-md bg-sidebar-accent px-2 text-sm font-medium focus-visible:ring-2 focus-visible:ring-sidebar-ring"
                                aria-current="page"
                                onclick={closeMobileNavigation}
                            >
                                Setelan
                            </Link>
                        {:else}
                            <Link
                            href={toUrl(editProfile())}
                            class="flex min-h-9 items-center gap-2.5 rounded-md px-2 text-sm hover:bg-sidebar-accent focus-visible:ring-2 focus-visible:ring-sidebar-ring {url.isCurrentUrl(
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
                            class="flex min-h-9 items-center gap-2.5 rounded-md px-2 text-sm hover:bg-sidebar-accent focus-visible:ring-2 focus-visible:ring-sidebar-ring {url.isCurrentUrl(
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
                            class="flex min-h-9 items-center gap-2.5 rounded-md px-2 text-sm hover:bg-sidebar-accent focus-visible:ring-2 focus-visible:ring-sidebar-ring {url.isCurrentUrl(
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
                    <nav class="ithub-sidebar-nav grid gap-2" aria-label="Navigasi Pekerjaan">
                        <section class="grid gap-0.5" aria-label="Pekerjaan">
                            <div class="mt-2 mb-2 grid grid-cols-2 gap-1.5">
                                {#if unitContext.active}
                                    <Link href={toUrl(ProjectController.create(unitContext.active.workspace_id))} class="inline-flex max-w-full min-w-0 min-h-9 items-center justify-center gap-1.5 rounded-md border border-sidebar-border/70 px-2 text-xs font-medium text-muted-foreground hover:text-sidebar-foreground focus-visible:ring-2 focus-visible:ring-sidebar-ring" onclick={closeMobileNavigation}>
                                        <Plus class="size-4" aria-hidden="true" />
                                        <span class="truncate">Project</span>
                                    </Link>
                                {:else}
                                    <span class="inline-flex max-w-full min-w-0 min-h-9 items-center justify-center gap-1.5 rounded-md border border-sidebar-border/70 px-2 text-xs text-muted-foreground/60" aria-disabled="true">
                                        <Plus class="size-4" aria-hidden="true" />
                                        <span class="truncate">Project</span>
                                    </span>
                                {/if}
                                {#if projectContext.active}
                                    <Link href={`${toUrl(TaskController.index({ workspace: projectContext.active.workspace_id, project: projectContext.active.id }))}#quick-task`} class="inline-flex max-w-full min-w-0 min-h-9 items-center justify-center gap-1.5 rounded-md border border-sidebar-border/70 px-2 text-xs font-medium text-muted-foreground hover:text-sidebar-foreground focus-visible:ring-2 focus-visible:ring-sidebar-ring" onclick={closeMobileNavigation}>
                                        <Plus class="size-4" aria-hidden="true" />
                                        <span class="truncate">Task</span>
                                    </Link>
                                {:else}
                                    <span class="inline-flex max-w-full min-w-0 min-h-9 items-center justify-center gap-1.5 rounded-md border border-sidebar-border/70 px-2 text-xs text-muted-foreground/60" aria-disabled="true">
                                        <Plus class="size-4" aria-hidden="true" />
                                        <span class="truncate">Task</span>
                                    </span>
                                {/if}
                            </div>
                            <Link href={toUrl(dashboard())} class="flex min-h-9 items-center gap-2.5 rounded-md px-2 text-sm hover:bg-sidebar-accent focus-visible:ring-2 focus-visible:ring-sidebar-ring {isDashboardActive ? 'bg-sidebar-accent font-medium' : 'text-muted-foreground'}" aria-current={isDashboardActive ? 'page' : undefined} onclick={closeMobileNavigation}>
                                <LayoutDashboard class="size-4" aria-hidden="true" />
                                Beranda
                            </Link>
                            <Link href={toUrl(globalMyTasks())} class="flex min-h-9 items-center gap-2.5 rounded-md px-2 text-sm hover:bg-sidebar-accent focus-visible:ring-2 focus-visible:ring-sidebar-ring {isGlobalMyTasksActive ? 'bg-sidebar-accent font-medium' : 'text-muted-foreground'}" aria-current={isGlobalMyTasksActive ? 'page' : undefined} onclick={closeMobileNavigation}>
                                <UserRound class="size-4" aria-hidden="true" />
                                Tugas saya
                            </Link>
                        </section>

                        <section class="grid gap-0.5 pt-2" aria-labelledby="unit-work-label">
                            <p id="unit-work-label" class="px-2 pb-1 text-[13px] font-semibold text-muted-foreground">Unit</p>
                            {#if unitContext.active}
                                <Link href={toUrl(projectsIndex(unitContext.active.workspace_id))} class="flex min-h-10 items-center gap-3 rounded-md px-2.5 text-sm hover:bg-sidebar-accent focus-visible:ring-2 focus-visible:ring-sidebar-ring {isProjectsIndexActive ? 'bg-sidebar-accent font-medium text-sidebar-foreground' : 'text-muted-foreground'}" aria-current={isProjectsIndexActive ? 'page' : undefined} onclick={closeMobileNavigation}>
                                    <Folder class="size-4" aria-hidden="true" />
                                    Projects
                                </Link>
                                <Link href={toUrl(workspaceActivity(unitContext.active.workspace_id))} class="flex min-h-10 items-center gap-3 rounded-md px-2.5 text-sm text-muted-foreground hover:bg-sidebar-accent focus-visible:ring-2 focus-visible:ring-sidebar-ring {isWorkspaceActivityActive ? 'bg-sidebar-accent font-medium text-sidebar-foreground' : ''}" aria-current={isWorkspaceActivityActive ? 'page' : undefined} onclick={closeMobileNavigation}>
                                    <Activity class="size-4" aria-hidden="true" />
                                    Aktivitas
                                </Link>
                            <Link href={toUrl(unitDashboard(unitContext.active.id))} class="flex min-h-10 items-center gap-3 rounded-md px-2.5 text-sm hover:bg-sidebar-accent focus-visible:ring-2 focus-visible:ring-sidebar-ring {isUnitDashboardActive ? 'bg-sidebar-accent font-medium text-sidebar-foreground' : 'text-muted-foreground'}" aria-current={isUnitDashboardActive ? 'page' : undefined} onclick={closeMobileNavigation}>
                                <LayoutDashboard class="size-4" aria-hidden="true" />
                                <span>Dasbor</span>
                            </Link>
                            <Link href={toUrl(unitAnalytics(unitContext.active.workspace_id))} class="flex min-h-10 items-center gap-3 rounded-md px-2.5 text-sm hover:bg-sidebar-accent focus-visible:ring-2 focus-visible:ring-sidebar-ring {isUnitAnalyticsActive ? 'bg-sidebar-accent font-medium text-sidebar-foreground' : 'text-muted-foreground'}" aria-current={isUnitAnalyticsActive ? 'page' : undefined} onclick={closeMobileNavigation}>
                                <BarChart3 class="size-4" aria-hidden="true" />
                                <span>Analitik</span>
                            </Link>
                            <Link href={toUrl(projectsIndex(unitContext.active.workspace_id, { query: { archived: 1 } }))} class="flex min-h-10 items-center gap-3 rounded-md px-2.5 text-sm hover:bg-sidebar-accent focus-visible:ring-2 focus-visible:ring-sidebar-ring {isUnitArchiveActive ? 'bg-sidebar-accent font-medium text-sidebar-foreground' : 'text-muted-foreground'}" aria-current={isUnitArchiveActive ? 'page' : undefined} onclick={closeMobileNavigation}>
                                <Archive class="size-4" aria-hidden="true" />
                                <span>Arsip</span>
                            </Link>
                                {#if unitContext.active.can_manage}
                                    <Link href={toUrl(UnitSettingsController.url(unitContext.active.id))} class="flex min-h-10 items-center gap-3 rounded-md px-2.5 text-sm text-muted-foreground hover:bg-sidebar-accent focus-visible:ring-2 focus-visible:ring-sidebar-ring" onclick={closeMobileNavigation}>
                                        <Settings class="size-4" aria-hidden="true" />
                                        Setelan
                                    </Link>
                                {/if}
                            {:else}
                                <div class="px-2.5 py-2 text-xs leading-5 text-muted-foreground">Pilih Unit untuk melihat Project.</div>
                            {/if}
                        </section>

                        {#if unitContext.active}
                            <section class="grid gap-0.5 pt-2" aria-labelledby="projects-label">
                                <p id="projects-label" class="px-2 pb-1 text-[13px] font-semibold text-muted-foreground">Projects</p>
                                {#if projectContext.available.length > 0}
                                    {#each projectContext.available as project (project.id)}
                                        <div>
                                            <button type="button" class="flex min-h-10 w-full items-center gap-3 rounded-md px-2.5 text-left text-sm text-sidebar-foreground hover:bg-sidebar-accent focus-visible:ring-2 focus-visible:ring-sidebar-ring" aria-expanded={expandedProjectId === project.id} aria-controls={`project-nav-${project.id}`} onclick={() => toggleProject(project.id)}>
                                                <span class="flex size-5 shrink-0 items-center justify-center rounded bg-sidebar-accent text-[9px] font-semibold" aria-hidden="true">{project.identifier.slice(0, 2)}</span>
                                                <span class="min-w-0 truncate" title={project.name}>{project.name}</span>
                                                <ChevronDown class="ml-auto size-4 shrink-0 text-muted-foreground transition-transform {expandedProjectId === project.id ? '' : '-rotate-90'}" aria-hidden="true" />
                                            </button>
                                            {#if expandedProjectId === project.id}
                                                <div id={`project-nav-${project.id}`} class="ml-4 grid gap-0.5 pl-1.5">
                                                    <Link href={toUrl(projectShow([project.workspace_id, project.id]))} class="flex min-h-10 items-center gap-3 rounded-md px-2.5 text-sm hover:bg-sidebar-accent focus-visible:ring-2 focus-visible:ring-sidebar-ring {url.currentUrl === toUrl(projectShow([project.workspace_id, project.id])) ? 'bg-sidebar-accent font-medium text-sidebar-foreground' : 'text-muted-foreground'}" aria-current={url.currentUrl === toUrl(projectShow([project.workspace_id, project.id])) ? 'page' : undefined} onclick={closeMobileNavigation}><LayoutDashboard class="size-4" aria-hidden="true" />Beranda</Link>
                                                    <Link href={toUrl(TaskController.index({ workspace: project.workspace_id, project: project.id }))} class="flex min-h-10 items-center gap-3 rounded-md px-2.5 text-sm hover:bg-sidebar-accent focus-visible:ring-2 focus-visible:ring-sidebar-ring {isTaskView ? 'bg-sidebar-accent font-medium text-sidebar-foreground' : 'text-muted-foreground'}" aria-current={isTaskView ? 'page' : undefined} onclick={closeMobileNavigation}><ListTodo class="size-4" aria-hidden="true" />Task</Link>
                                                    <Link href={toUrl(ProjectAttachmentController.index({ workspace: project.workspace_id, project: project.id }))} class="flex min-h-10 items-center gap-3 rounded-md px-2.5 text-sm hover:bg-sidebar-accent focus-visible:ring-2 focus-visible:ring-sidebar-ring {isFilesActive ? 'bg-sidebar-accent font-medium text-sidebar-foreground' : 'text-muted-foreground'}" aria-current={isFilesActive ? 'page' : undefined} onclick={closeMobileNavigation}><Document class="size-4" aria-hidden="true" />Berkas</Link>
                                                    <Link href={toUrl(cycles({ workspace: project.workspace_id, project: project.id }))} class="flex min-h-10 items-center gap-3 rounded-md px-2.5 text-sm hover:bg-sidebar-accent focus-visible:ring-2 focus-visible:ring-sidebar-ring {isCyclesActive ? 'bg-sidebar-accent font-medium text-sidebar-foreground' : 'text-muted-foreground'}" aria-current={isCyclesActive ? 'page' : undefined} onclick={closeMobileNavigation}><RotateCcw class="size-4" aria-hidden="true" />Siklus</Link>
                                                    <Link href={toUrl(modules({ workspace: project.workspace_id, project: project.id }))} class="flex min-h-10 items-center gap-3 rounded-md px-2.5 text-sm hover:bg-sidebar-accent focus-visible:ring-2 focus-visible:ring-sidebar-ring {isModulesActive ? 'bg-sidebar-accent font-medium text-sidebar-foreground' : 'text-muted-foreground'}" aria-current={isModulesActive ? 'page' : undefined} onclick={closeMobileNavigation}><LayoutGrid class="size-4" aria-hidden="true" />Modul</Link>
                                                    <Link href={toUrl(projectInbox({ workspace: project.workspace_id, project: project.id }))} class="flex min-h-10 items-center gap-3 rounded-md px-2.5 text-sm hover:bg-sidebar-accent focus-visible:ring-2 focus-visible:ring-sidebar-ring {isInboxActive ? 'bg-sidebar-accent font-medium text-sidebar-foreground' : 'text-muted-foreground'}" aria-current={isInboxActive ? 'page' : undefined} onclick={closeMobileNavigation}><Inbox class="size-4" aria-hidden="true" />Inbox</Link>
                                                    <Link href={toUrl(projectReleases({ workspace: project.workspace_id, project: project.id }))} class="flex min-h-10 items-center gap-3 rounded-md px-2.5 text-sm hover:bg-sidebar-accent focus-visible:ring-2 focus-visible:ring-sidebar-ring {isReleasesActive ? 'bg-sidebar-accent font-medium text-sidebar-foreground' : 'text-muted-foreground'}" aria-current={isReleasesActive ? 'page' : undefined} onclick={closeMobileNavigation}><Plus class="size-4" aria-hidden="true" />Rilis</Link>
                                                    <Link href={toUrl(projectUpdates({ workspace: project.workspace_id, project: project.id }))} class="flex min-h-10 items-center gap-3 rounded-md px-2.5 text-sm hover:bg-sidebar-accent focus-visible:ring-2 focus-visible:ring-sidebar-ring {isUpdatesActive ? 'bg-sidebar-accent font-medium text-sidebar-foreground' : 'text-muted-foreground'}" aria-current={isUpdatesActive ? 'page' : undefined} onclick={closeMobileNavigation}><RefreshCw class="size-4" aria-hidden="true" />Update</Link>
                                                    <Link href={toUrl(projectMilestones({ workspace: project.workspace_id, project: project.id }))} class="flex min-h-10 items-center gap-3 rounded-md px-2.5 text-sm hover:bg-sidebar-accent focus-visible:ring-2 focus-visible:ring-sidebar-ring {isMilestoneActive ? 'bg-sidebar-accent font-medium text-sidebar-foreground' : 'text-muted-foreground'}" aria-current={isMilestoneActive ? 'page' : undefined} onclick={closeMobileNavigation}><Flag class="size-4" aria-hidden="true" />Milestone</Link>
                                                    <Link href={toUrl(projectActivity({ workspace: project.workspace_id, project: project.id }))} class="flex min-h-10 items-center gap-3 rounded-md px-2.5 text-sm text-muted-foreground hover:bg-sidebar-accent focus-visible:ring-2 focus-visible:ring-sidebar-ring" aria-current={isActivityActive ? 'page' : undefined} onclick={closeMobileNavigation}><Activity class="size-4" aria-hidden="true" />Aktivitas</Link>
                                                    <Link href={toUrl(projectSettings([project.workspace_id, project.id]))} class="flex min-h-10 items-center gap-3 rounded-md px-2.5 text-sm text-muted-foreground hover:bg-sidebar-accent focus-visible:ring-2 focus-visible:ring-sidebar-ring" aria-current={url.currentUrl.endsWith('/settings') ? 'page' : undefined} onclick={closeMobileNavigation}><Settings class="size-4" aria-hidden="true" />Setelan</Link>
                                                </div>
                                            {/if}
                                        </div>
                                    {/each}
                                {:else}
                                    <div class="px-2.5 py-2 text-xs leading-5 text-muted-foreground">Belum ada Project aktif.</div>
                                {/if}
                            </section>
                        {/if}
                    </nav>

                    {#if projectContext.active && savedViews.length > 0}
                        <div class="mt-4">
                            <p class="px-2 pb-1.5 text-xs font-medium text-muted-foreground">Saved View</p>
                            <nav class="grid gap-0.5" aria-label="Saved View">
                                {#each savedViews as view (view.id)}
                                    <Link
                                        href={toUrl(TaskController.index({ workspace: projectContext.active.workspace_id, project: projectContext.active.id }, { query: { ...view.filters, view: view.id } }))}
                                        class="flex min-h-11 items-center gap-3 rounded-md px-2.5 text-sm hover:bg-sidebar-accent focus-visible:ring-2 focus-visible:ring-sidebar-ring {activeSavedViewId === view.id ? 'bg-sidebar-accent font-medium' : 'text-muted-foreground'}"
                                        aria-current={activeSavedViewId === view.id ? 'page' : undefined}
                                        onclick={closeMobileNavigation}
                                    >
                                        <Eye class="size-4 shrink-0" aria-hidden="true" />
                                        <span class="min-w-0 truncate" title={view.name}>{view.name}</span>
                                    </Link>
                                {/each}
                            </nav>
                        </div>
                    {/if}

                {/if}
            </div>

            <button
                class="absolute inset-y-0 -right-1 z-30 hidden w-2 cursor-col-resize border-0 bg-transparent p-0 md:block"
                type="button"
                aria-label="Ubah lebar sidebar"
                title="Ubah lebar sidebar"
                onpointerdown={startResize}
                onpointermove={resizeSidebar}
                onpointerup={finishResize}
                onkeydown={(event) => {
                    if (event.key === 'ArrowLeft') {
                        if (sidebarWidth <= 64) {
                            setOpen(false);
                        } else {
                            sidebarWidth = Math.max(64, sidebarWidth - 16);
                        }
                    }
                    if (event.key === 'ArrowRight') sidebarWidth += 16;
                }}
            ></button>

            <div class="border-t border-sidebar-border p-2">
                <NavUser />
            </div>
        </div>
            </Sidebar>
            <div class="relative min-h-0 min-w-0 flex-1 overflow-y-auto overflow-x-hidden">
                {@render children?.()}
            </div>
        </div>
</div>
