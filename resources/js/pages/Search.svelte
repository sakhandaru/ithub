<script module lang="ts">
    import { dashboard, search } from '@/routes';
    import { index as notificationsIndex } from '@/routes/notifications';
    import { create as createProject, index as projectsIndex } from '@/routes/workspaces/projects';
    export const layout = { breadcrumbs: [{ title: 'Pencarian', href: search() }] };
</script>
<script lang="ts">
    import { Link, router } from '@inertiajs/svelte'; import SearchIcon from '@lucide/svelte/icons/search'; import AppHead from '@/components/AppHead.svelte'; import { Input } from '@/components/ui/input'; import { toUrl } from '@/lib/utils'; import TaskController from '@/actions/App/Http/Controllers/TaskController'; import ProjectController from '@/actions/App/Http/Controllers/ProjectController';
    type Workspace = { id: string; name: string } | null; type Project = { id: string; name: string; identifier: string }; type Task = { id: string; identifier: string; title: string; project: Project | null; status: string };
    let { workspace, query, projects, tasks }: { workspace: Workspace; query: string; projects: Project[]; tasks: Task[] } = $props();
    let selectedIndex = $state(0);
    const resultCount = $derived(projects.length + tasks.length);

    function resultUrl(index: number): string {
        if (index < projects.length) {
            const project = projects[index];
            return toUrl(ProjectController.show({ workspace: workspace?.id ?? '', project: project.id }));
        }

        const task = tasks[index - projects.length];
        return task.project ? toUrl(TaskController.show({ workspace: workspace?.id ?? '', project: task.project.id, task: task.id })) : toUrl(search());
    }

    function handleSearchKeydown(event: KeyboardEvent): void {
        if (resultCount === 0) return;
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            selectedIndex = Math.min(selectedIndex + 1, resultCount - 1);
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            selectedIndex = Math.max(selectedIndex - 1, 0);
        } else if (event.key === 'Enter' && !event.isComposing) {
            event.preventDefault();
            router.visit(resultUrl(selectedIndex));
        }
    }
</script>
<AppHead title="Pencarian" /><div class="mx-auto flex w-full max-w-4xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8"><header><h1 class="text-2xl font-semibold tracking-tight">Pencarian</h1><p class="mt-1 text-sm text-muted-foreground">Cari Project dan Task yang dapat Anda akses. Gunakan ↑ ↓ lalu Enter untuk membuka hasil.</p></header><form method="get" action={toUrl(search())} class="relative"><SearchIcon class="pointer-events-none absolute left-3 top-3 size-4 text-muted-foreground" aria-hidden="true" /><Input name="q" value={query} placeholder="Cari nama, ID, judul, atau deskripsi" class="min-h-11 pl-9" autofocus onkeydown={handleSearchKeydown} aria-activedescendant={query && resultCount > 0 ? `search-result-${selectedIndex}` : undefined} /></form>{#if query === ''}<section class="rounded-lg border bg-card p-4"><h2 class="text-sm font-semibold">Aksi cepat</h2><p class="mt-1 text-sm text-muted-foreground">Buka konteks kerja tanpa meninggalkan pencarian.</p><div class="mt-4 grid gap-2 sm:grid-cols-2">{#if workspace}<Link href={toUrl(dashboard())} class="rounded-md border px-3 py-3 text-sm hover:bg-muted/40 focus-visible:ring-2 focus-visible:ring-ring">Dashboard</Link><Link href={toUrl(projectsIndex({ workspace: workspace.id }))} class="rounded-md border px-3 py-3 text-sm hover:bg-muted/40 focus-visible:ring-2 focus-visible:ring-ring">Semua Project</Link><Link href={toUrl(createProject({ workspace: workspace.id }))} class="rounded-md border px-3 py-3 text-sm hover:bg-muted/40 focus-visible:ring-2 focus-visible:ring-ring">Buat Project</Link>{/if}<Link href={toUrl(notificationsIndex())} class="rounded-md border px-3 py-3 text-sm hover:bg-muted/40 focus-visible:ring-2 focus-visible:ring-ring">Notifikasi</Link></div></section>{:else if projects.length === 0 && tasks.length === 0}<p class="rounded-lg border border-dashed px-6 py-10 text-center text-sm text-muted-foreground">Tidak ada hasil untuk “{query}”.</p>{:else}<div class="grid gap-6 sm:grid-cols-2"><section class="rounded-lg border bg-card"><h2 class="border-b px-4 py-3 text-sm font-semibold">Project</h2><div class="divide-y">{#each projects as project, index (project.id)}<Link id={'search-result-' + index} aria-selected={selectedIndex === index} href={toUrl(ProjectController.show({ workspace: workspace?.id ?? '', project: project.id }))} class="block px-4 py-3 hover:bg-muted/40 {selectedIndex === index ? 'bg-muted/60' : ''}"><p class="font-mono text-xs text-muted-foreground">{project.identifier}</p><p class="mt-1 text-sm font-medium">{project.name}</p></Link>{/each}</div></section><section class="rounded-lg border bg-card"><h2 class="border-b px-4 py-3 text-sm font-semibold">Task</h2><div class="divide-y">{#each tasks as task, index (task.id)}{@const resultIndex = projects.length + index}<Link id={'search-result-' + resultIndex} aria-selected={selectedIndex === resultIndex} href={task.project ? toUrl(TaskController.show({ workspace: workspace?.id ?? '', project: task.project.id, task: task.id })) : toUrl(search())} class="block px-4 py-3 hover:bg-muted/40 {selectedIndex === resultIndex ? 'bg-muted/60' : ''}"><p class="font-mono text-xs text-muted-foreground">{task.identifier}</p><p class="mt-1 text-sm font-medium">{task.title}</p><p class="mt-1 text-xs text-muted-foreground">{task.project?.identifier ?? 'Project tidak tersedia'}</p></Link>{/each}</div></section></div>{/if}</div>
