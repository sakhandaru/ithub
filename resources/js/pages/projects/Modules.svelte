<script module lang="ts">
    import { modules as modulesRoute } from '@/routes/workspaces/projects';
    export const layout = { breadcrumbs: [{ title: 'Modul', href: modulesRoute(['', '']) }] };
</script>

<script lang="ts">
    import { Form, Link, page } from '@inertiajs/svelte';
    import Add from 'carbon-icons-svelte/lib/Add.svelte';
    import Grid from 'carbon-icons-svelte/lib/Grid.svelte';
    import TrashCan from 'carbon-icons-svelte/lib/TrashCan.svelte';
    import TextAlignLeft from 'carbon-icons-svelte/lib/TextAlignLeft.svelte';
    import ModuleController from '@/actions/App/Http/Controllers/ModuleController';
    import ProjectController from '@/actions/App/Http/Controllers/ProjectController';
    import TaskController from '@/actions/App/Http/Controllers/TaskController';
    import AppHead from '@/components/AppHead.svelte';
    import InputError from '@/components/InputError.svelte';
    import PageHeader from '@/components/PageHeader.svelte';
    import { Button } from '@/components/ui/button';
    import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogTitle } from '@/components/ui/dialog';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Select, SelectContent, SelectItem, SelectTrigger } from '@/components/ui/select';
    import { projectBreadcrumbs } from '@/lib/page-header';
    import { toUrl } from '@/lib/utils';

    type Workspace = { id: string; name: string };
    type Project = { id: string; name: string; identifier: string; archived_at: string | null; archived: boolean };
    type Module = { id: string; name: string; description: string | null; tasks_count: number; completed_tasks_count: number; status_counts: Record<string, number> };
    type Release = { id: string; name: string };
    let { workspace, project, modules, releases, canManage }: { workspace: Workspace; project: Project; modules: Module[]; releases: Release[]; canManage: boolean } = $props();
    let createModuleOpen = $state(false);
    let selectedRelease = $state('');
    const progressSegmentCount = 90;
    const filteredModules = modules;
    const statusColors: Record<string, string> = { backlog: 'bg-slate-400', todo: 'bg-indigo-500', in_progress: 'bg-amber-500', review: 'bg-violet-500', done: 'bg-emerald-500', blocked: 'bg-rose-500', canceled: 'bg-slate-700' };
    const statusOrder = ['backlog', 'todo', 'in_progress', 'review', 'done', 'blocked', 'canceled'];
    const statusLabels: Record<string, string> = { backlog: 'Backlog', todo: 'Todo', in_progress: 'In Progress', review: 'Review', done: 'Done', blocked: 'Blocked', canceled: 'Canceled' };
    const statusBadgeColors: Record<string, string> = { backlog: 'text-slate-600 dark:text-slate-300', todo: 'text-indigo-700 dark:text-indigo-300', in_progress: 'text-amber-700 dark:text-amber-300', review: 'text-violet-700 dark:text-violet-300', done: 'text-emerald-700 dark:text-emerald-300', blocked: 'text-rose-700 dark:text-rose-300', canceled: 'text-slate-700 dark:text-slate-300' };
    const activeStatuses = (module: Module): string[] => statusOrder.filter((status) => module.status_counts[status] > 0);
    function confirmRemoval(event: SubmitEvent, name: string): void { if (!window.confirm(`Hapus modul ${name}?`)) event.preventDefault(); }
</script>

<AppHead title={`Modul · ${project.identifier}`} />
<div class="flex min-w-0 flex-col gap-5 px-4 py-5 sm:px-6 lg:px-8">
    <PageHeader breadcrumbs={projectBreadcrumbs(page.props.unitContext?.active?.name ?? workspace.name, page.props.unitContext?.active?.id, toUrl(ProjectController.index(workspace.id)), project.name, toUrl(ProjectController.show({ workspace: workspace.id, project: project.id })), 'modules', modules.length, page.props.projectContext?.active?.icon)}>
        {#snippet actions()}
            {#if canManage && !project.archived}
                <Button variant="outline" class="h-7 shrink-0 gap-1.5 border-border/80 bg-background px-2.5 text-xs text-muted-foreground" onclick={() => { selectedRelease = ''; createModuleOpen = true; }}>
                    <Add class="size-3.5" aria-hidden="true" />Tambah Modul
                </Button>
            {/if}
        {/snippet}
    </PageHeader>
    <Dialog bind:open={createModuleOpen}>
        <DialogContent class="max-h-[92vh] max-w-xl overflow-y-auto border-border bg-background p-0 shadow-lg">
            <div class="px-6 pb-1 pt-5"><DialogTitle class="text-sm font-medium text-muted-foreground">Modul:</DialogTitle><DialogDescription class="sr-only">Kelompokkan pekerjaan dalam Project ini.</DialogDescription></div>
            <Form {...ModuleController.store.form({ workspace: workspace.id, project: project.id })} class="grid gap-4 px-6 pb-5" resetOnSuccess onSuccess={() => { createModuleOpen = false; selectedRelease = ''; }}>
                {#snippet children({ errors, processing })}
                    <div class="grid gap-2"><Label for="module-name" class="sr-only">Nama modul</Label><Input id="module-name" name="name" maxlength={120} required placeholder="Nama modul" autofocus class="h-auto rounded-none border-0 bg-transparent px-0 text-xl font-medium shadow-none focus-visible:ring-0" /><InputError message={errors.name} /></div>
                    <div class="grid gap-3 border-t border-border/70 pt-4">
                        <div class="grid gap-2 sm:grid-cols-[150px_minmax(0,1fr)] sm:items-center"><div class="flex items-center gap-2 text-sm text-muted-foreground"><Grid class="size-4" aria-hidden="true" />Rilis</div><Select value={selectedRelease} onValueChange={(value) => { selectedRelease = value; }}><SelectTrigger id="module-release" class="h-9 w-fit min-w-40 max-w-full justify-self-start rounded-md border border-border/70 bg-transparent px-2 text-sm shadow-none focus:ring-0"><span class={selectedRelease ? 'text-foreground' : 'text-muted-foreground'}>{releases.find((release) => release.id === selectedRelease)?.name ?? 'Tanpa rilis'}</span></SelectTrigger><SelectContent><SelectItem value="">Tanpa rilis</SelectItem>{#each releases as release (release.id)}<SelectItem value={release.id}>{release.name}</SelectItem>{/each}</SelectContent></Select><input type="hidden" name="release_id" value={selectedRelease} /><InputError message={errors.release_id} /></div>
                    </div>
                    <div class="grid gap-1.5"><div class="flex items-center gap-2 text-xs text-muted-foreground"><TextAlignLeft class="size-3.5" aria-hidden="true" /><Label for="module-description" class="font-normal">Deskripsi <span>(opsional)</span></Label></div><textarea id="module-description" name="description" rows={3} maxlength={2000} placeholder="Tambahkan konteks modul..." class="min-h-20 w-full resize-y border-0 bg-transparent px-0 py-1 text-sm leading-6 outline-none placeholder:text-muted-foreground/60 focus-visible:ring-0"></textarea><InputError message={errors.description} /></div>
                    <div class="flex flex-col-reverse gap-2 border-t border-border/70 pt-4 sm:flex-row sm:justify-end"><Button type="button" variant="ghost" onclick={() => { selectedRelease = ''; createModuleOpen = false; }}>Batal</Button><Button type="submit" disabled={processing}>{processing ? 'Membuat...' : 'Buat Modul'}</Button></div>
                {/snippet}
            </Form>
        </DialogContent>
    </Dialog>
    {#if project.archived}<div class="rounded-md border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-100" role="status">Project ini diarsipkan. Modul bersifat read-only.</div>{/if}
        {#if filteredModules.length === 0}<div class="flex min-h-48 flex-col items-center justify-center px-6 py-12 text-center"><Grid class="size-7 text-muted-foreground" aria-hidden="true" /><p class="mt-3 text-sm font-medium">Belum ada Modul</p><p class="mt-1 text-sm text-muted-foreground">Buat modul untuk mengelompokkan pekerjaan dalam Project ini.</p></div>
        {:else}<div class="grid gap-3 p-3 sm:grid-cols-2">{#each filteredModules as module (module.id)}{@const progress = module.tasks_count > 0 ? Math.round((module.completed_tasks_count / module.tasks_count) * 100) : 0}{@const statuses = activeStatuses(module)}<article class="group rounded-lg border border-border/70 bg-card p-4 transition-colors hover:border-border hover:bg-muted/15"><div class="flex items-start justify-between gap-3"><Link href={toUrl(TaskController.index({ workspace: workspace.id, project: project.id }, { query: { module: module.id } }))} class="min-w-0 rounded-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"><div class="flex items-center gap-2"><Grid class="size-4 shrink-0 text-muted-foreground" aria-hidden="true" /><h3 class="truncate text-sm font-semibold">{module.name}</h3></div>{#if module.description}<p class="mt-1 line-clamp-2 text-xs leading-5 text-muted-foreground">{module.description}</p>{/if}</Link>{#if canManage}<Form {...ModuleController.destroy.form({ workspace: workspace.id, project: project.id, module: module.id })} onsubmit={(event) => confirmRemoval(event, module.name)}><Button type="submit" variant="ghost" size="icon" class="size-7 shrink-0 opacity-70 group-hover:opacity-100" aria-label={`Hapus ${module.name}`}><TrashCan class="size-4" aria-hidden="true" /></Button></Form>{/if}</div><Link href={toUrl(TaskController.index({ workspace: workspace.id, project: project.id }, { query: { module: module.id } }))} class="mt-5 block rounded-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"><div class="text-[11px] text-muted-foreground"><span>{module.completed_tasks_count} dari {module.tasks_count} Task selesai</span></div><div class="mt-2 flex items-center gap-2"><div class="flex w-fit max-w-full gap-0.5 overflow-hidden" aria-label={`${progress}% Task selesai`}>{#each Array.from({ length: progressSegmentCount }) as _, index}<span class={`h-6 w-1 shrink-0 rounded-sm ${index < Math.round((progress / 100) * progressSegmentCount) ? 'bg-emerald-500' : 'bg-muted'}`} aria-hidden="true"></span>{/each}</div><span class="shrink-0 text-[11px] font-semibold text-muted-foreground">{progress}%</span></div><div class="mt-2 flex flex-wrap gap-1.5">{#each statuses.slice(0, 3) as status}<span class={`inline-flex min-h-6 items-center gap-1 rounded-md border border-border/70 bg-background px-2 text-[11px] ${statusBadgeColors[status]}`}><span class={`size-1.5 rounded-full ${statusColors[status]}`} aria-hidden="true"></span>{statusLabels[status]} {module.status_counts[status]}</span>{/each}{#if statuses.length > 3}<span class="inline-flex min-h-6 items-center rounded-md border border-border/70 bg-background px-2 text-[11px] text-muted-foreground">+{statuses.length - 3} status</span>{/if}</div></Link></article>{/each}</div>{/if}
</div>
