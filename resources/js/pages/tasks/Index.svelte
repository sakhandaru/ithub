<script module lang="ts">
    import { index } from '@/routes/workspaces/projects/tasks';
    export const layout = { breadcrumbs: [{ title: 'Task', href: index(['', '']) }] };
</script>

<script lang="ts">
    import { Form, Link } from '@inertiajs/svelte';
    import Filter from '@lucide/svelte/icons/filter';
    import ListTodo from '@lucide/svelte/icons/list-todo';
    import Search from '@lucide/svelte/icons/search';
    import Rows3 from '@lucide/svelte/icons/rows-3';
    import X from '@lucide/svelte/icons/x';
    import TaskController from '@/actions/App/Http/Controllers/TaskController';
    import AppHead from '@/components/AppHead.svelte';
    import ProjectContextNav from '@/components/ProjectContextNav.svelte';
    import InputError from '@/components/InputError.svelte';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { toUrl } from '@/lib/utils';

    type Workspace = { id: string; name: string };
    type Project = { id: string; name: string; identifier: string; archived_at: string | null };
    type Option = { value: string; label: string };
    type Person = { id: number; name: string };
    type TaskLabel = { id: string; name: string; color: string | null };
    type Task = {
        id: string; identifier: string; title: string; description: string | null;
        status: string; status_label: string; priority: string | null; priority_label: string | null;
        assignee: Person | null; requester: Person | null; labels: TaskLabel[];
        due_date: string | null; completed_at: string | null;
    };

    type Member = { id: number; name: string }; type Milestone = { id: string; name: string };
    let { workspace, project, tasks, selectedTask, filters, statuses, priorities, filterMembers, filterMilestones, canCreate }: {
        workspace: Workspace; project: Project; tasks: Task[]; selectedTask: Task | null;
        filters: { status: string; priority: string; assignee: string; requester: string; milestone: string; preset: string; no_deadline: boolean; no_evidence: boolean; search: string; sort: string; direction: string };
        statuses: Option[]; priorities: Option[]; filterMembers: Member[]; filterMilestones: Milestone[]; canCreate: boolean;
    } = $props();
    let compact = $state(false);

    const taskListUrl = (query: Record<string, string | number | undefined> = {}): string =>
        toUrl(TaskController.index({ workspace: workspace.id, project: project.id }, { query }));

    const preservedQuery = (): Record<string, string | number | undefined> => ({
        preset: filters.preset || undefined,
        status: filters.status || undefined,
        priority: filters.priority || undefined,
        assignee: filters.assignee || undefined,
        requester: filters.requester || undefined,
        milestone: filters.milestone || undefined,
        no_deadline: filters.no_deadline ? 1 : undefined,
        no_evidence: filters.no_evidence ? 1 : undefined,
        search: filters.search || undefined,
        sort: filters.sort || undefined,
        direction: filters.direction || undefined,
    });

    function closePeek(): string {
        return taskListUrl(preservedQuery());
    }

    const presets = [
        { key: '', label: 'Semua Task' },
        { key: 'mine', label: 'Task saya' },
        { key: 'overdue', label: 'Terlambat' },
        { key: 'blocked', label: 'Blocked' },
        { key: 'unassigned', label: 'Tanpa PIC' },
    ];

    const activeFilterChips = $derived([
        filters.status ? { key: 'status', label: `Status: ${statuses.find((item) => item.value === filters.status)?.label ?? filters.status}` } : null,
        filters.priority ? { key: 'priority', label: `Priority: ${priorities.find((item) => item.value === filters.priority)?.label ?? filters.priority}` } : null,
        filters.assignee ? { key: 'assignee', label: filters.assignee === 'none' ? 'Tanpa PIC' : `PIC: ${filterMembers.find((item) => String(item.id) === filters.assignee)?.name ?? filters.assignee}` } : null,
        filters.milestone ? { key: 'milestone', label: filters.milestone === 'none' ? 'Tanpa milestone' : `Milestone: ${filterMilestones.find((item) => item.id === filters.milestone)?.name ?? filters.milestone}` } : null,
        filters.no_deadline ? { key: 'no_deadline', label: 'Tanpa deadline' } : null,
        filters.no_evidence ? { key: 'no_evidence', label: 'Tanpa bukti' } : null,
        filters.preset ? { key: 'preset', label: presets.find((preset) => preset.key === filters.preset)?.label ?? filters.preset } : null,
        filters.search ? { key: 'search', label: `Cari: ${filters.search}` } : null,
    ].filter((chip): chip is { key: string; label: string } => chip !== null));

    function clearFilter(key: string): string {
        const query = preservedQuery();
        delete query[key];
        return taskListUrl(query);
    }
</script>

<AppHead title={project.identifier + ' · Task'} />

<div class="flex min-w-0 flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
    <header class="flex flex-col gap-4 border-b pb-5 lg:flex-row lg:items-end lg:justify-between">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
                <Link href={toUrl(TaskController.index({ workspace: workspace.id, project: project.id }))} class="hover:text-foreground hover:underline">{project.identifier}</Link>
                <span aria-hidden="true">/</span><span>Task</span>
            </div>
            <h1 class="mt-2 text-2xl font-semibold tracking-tight">{project.name}</h1>
            <p class="mt-1 text-sm text-muted-foreground">Pilih prioritas kerja, buka konteks, lalu lanjutkan Task tanpa kehilangan posisi.</p>
        </div>
        {#if selectedTask}
            <Link href={closePeek()} class="inline-flex min-h-10 items-center gap-2 rounded-md border px-3 text-sm hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring"><X class="size-4" aria-hidden="true" />Tutup detail</Link>
        {/if}
    </header>

    <ProjectContextNav {workspace} {project} active="tasks" taskQuery={preservedQuery()} />

    <div class={selectedTask ? 'grid min-w-0 gap-6 lg:grid-cols-[minmax(0,1fr)_24rem] lg:items-start' : ''}>
        <div class="min-w-0 grid gap-6">

    {#if project.archived_at}
        <div class="rounded-md border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-100" role="status">Project ini diarsipkan. Task bersifat read-only sampai Project dipulihkan.</div>
    {/if}

    {#if canCreate && !project.archived_at}
        <section class="rounded-lg border bg-card p-4">
            <Form {...TaskController.store.form({ workspace: workspace.id, project: project.id })} class="flex flex-col gap-3 sm:flex-row sm:items-end">
                {#snippet children({ errors, processing })}
                    <div class="min-w-0 flex-1">
                        <Label for="quick-task-title">Buat Task</Label>
                        <Input id="quick-task-title" name="title" class="mt-2 min-h-11" placeholder="Apa yang perlu dikerjakan?" required maxlength={200} autocomplete="off" />
                        <InputError message={errors.title} />
                    </div>
                    <Button type="submit" class="min-h-11" disabled={processing}>{processing ? 'Membuat...' : 'Tambah Task'}</Button>
                {/snippet}
            </Form>
        </section>
    {/if}

    <section class="rounded-lg border bg-card p-4">
        <div class="flex flex-col gap-3 border-b pb-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-sm font-semibold">Mulai dari fokus kerja</h2>
                <p class="mt-1 text-xs text-muted-foreground">Preset ini membantu menemukan Task yang perlu ditangani lebih dulu.</p>
            </div>
            <div class="flex flex-wrap gap-2" aria-label="Preset Task">
                {#each presets as preset (preset.key)}
                    <Link href={taskListUrl({ ...preservedQuery(), preset: preset.key || undefined })} class="inline-flex min-h-9 items-center rounded-md border px-3 text-xs font-medium transition-colors hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring {filters.preset === preset.key ? 'border-foreground bg-foreground text-background hover:bg-foreground' : 'bg-background'}" aria-current={filters.preset === preset.key ? 'page' : undefined}>{preset.label}</Link>
                {/each}
            </div>
        </div>
        <form method="get" action={taskListUrl()} class="grid gap-3 sm:grid-cols-2 lg:flex lg:items-end">
            <input type="hidden" name="preset" value={filters.preset} />
            <div class="sm:col-span-2 lg:min-w-64 lg:flex-1">
                <Label for="task-search">Cari Task</Label>
                <div class="relative mt-2"><Search class="pointer-events-none absolute left-3 top-3 size-4 text-muted-foreground" aria-hidden="true" /><Input id="task-search" name="search" value={filters.search} class="min-h-11 pl-9" placeholder="ID atau judul" /></div>
            </div>
            <div><Label for="task-status">Status</Label><select id="task-status" name="status" value={filters.status} class="mt-2 h-11 w-full rounded-md border border-input bg-background px-3 text-sm"><option value="">Semua status</option>{#each statuses as status (status.value)}<option value={status.value}>{status.label}</option>{/each}</select></div>
            <div><Label for="task-priority">Priority</Label><select id="task-priority" name="priority" value={filters.priority} class="mt-2 h-11 w-full rounded-md border border-input bg-background px-3 text-sm"><option value="">Semua priority</option>{#each priorities as priority (priority.value)}<option value={priority.value}>{priority.label}</option>{/each}</select></div>
            <div><Label for="task-assignee">PIC</Label><select id="task-assignee" name="assignee" value={filters.assignee} class="mt-2 h-11 w-full rounded-md border border-input bg-background px-3 text-sm"><option value="">Semua PIC</option><option value="none">Tanpa PIC</option>{#each filterMembers as member (member.id)}<option value={member.id}>{member.name}</option>{/each}</select></div>
            <div><Label for="task-milestone">Milestone</Label><select id="task-milestone" name="milestone" value={filters.milestone} class="mt-2 h-11 w-full rounded-md border border-input bg-background px-3 text-sm"><option value="">Semua milestone</option><option value="none">Tanpa milestone</option>{#each filterMilestones as milestone (milestone.id)}<option value={milestone.id}>{milestone.name}</option>{/each}</select></div>
            <label class="flex min-h-11 items-center gap-2 text-sm"><input type="checkbox" name="no_deadline" value="1" checked={filters.no_deadline} />Tanpa deadline</label>
            <label class="flex min-h-11 items-center gap-2 text-sm"><input type="checkbox" name="no_evidence" value="1" checked={filters.no_evidence} />Tanpa bukti</label>
            <Button type="submit" variant="outline" class="min-h-11"><Filter class="size-4" aria-hidden="true" />Filter</Button>
            <Link href={taskListUrl()} class="min-h-10 text-sm text-muted-foreground hover:text-foreground hover:underline focus-visible:ring-2 focus-visible:ring-ring">Reset filter</Link>
        </form>
    </section>

    {#if activeFilterChips.length > 0}
        <div class="flex flex-wrap items-center gap-2" aria-label="Filter aktif"><span class="text-xs font-medium text-muted-foreground">Filter aktif:</span>{#each activeFilterChips as chip (chip.key)}<Link href={clearFilter(chip.key)} class="inline-flex min-h-8 items-center rounded-full border bg-muted/40 px-3 text-xs hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">{chip.label}<span class="ml-2 text-muted-foreground" aria-hidden="true">×</span><span class="sr-only">Hapus filter {chip.label}</span></Link>{/each}</div>
    {/if}

    {#if tasks.length === 0}
        <section class="flex min-h-64 items-center justify-center rounded-lg border border-dashed bg-card px-6 py-12 text-center"><div class="max-w-md"><ListTodo class="mx-auto size-8 text-muted-foreground" aria-hidden="true" /><h2 class="mt-4 text-sm font-semibold">Belum ada Task yang cocok</h2><p class="mt-1 text-sm leading-6 text-muted-foreground">{activeFilterChips.length > 0 ? 'Coba ubah preset atau filter untuk melihat Task lain.' : 'Buat Task pertama untuk mulai bekerja.'}</p>{#if activeFilterChips.length > 0}<Link href={taskListUrl()} class="mt-4 inline-flex min-h-10 items-center rounded-md border px-3 text-sm hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring">Reset semua filter</Link>{/if}</div></section>
    {:else}
        <section class="overflow-x-auto rounded-lg border bg-card">
            <div class="flex items-center justify-between gap-3 border-b px-4 py-3"><div><h2 class="text-sm font-semibold">Daftar Task</h2><p class="mt-1 text-xs text-muted-foreground">{tasks.length} Task tampil</p></div><Button type="button" variant="outline" size="sm" aria-pressed={compact} onclick={() => { compact = !compact; }}><Rows3 class="size-4" aria-hidden="true" />{compact ? 'Tampilan nyaman' : 'Tampilan ringkas'}</Button></div>
            <table class="w-full min-w-[58rem] text-left {compact ? 'text-xs [&>tbody>tr>td]:px-3 [&>tbody>tr>td]:py-2' : 'text-sm'}"><caption class="sr-only">Daftar Task dalam {project.name}</caption><thead class="bg-muted/40 text-xs text-muted-foreground"><tr><th class="px-4 py-3 font-medium">ID</th><th class="px-4 py-3 font-medium">Task</th><th class="px-4 py-3 font-medium">Status</th><th class="px-4 py-3 font-medium">Priority</th><th class="px-4 py-3 font-medium">PIC</th><th class="px-4 py-3 font-medium">Deadline</th></tr></thead>
                <tbody class="divide-y">{#each tasks as task (task.id)}<tr class="hover:bg-muted/20 {selectedTask?.id === task.id ? 'bg-muted/40' : ''}"><td class="px-4 py-3 align-top font-mono text-xs text-muted-foreground">{task.identifier}</td><td class="px-4 py-3 align-top"><Link href={taskListUrl({ ...preservedQuery(), task: task.id })} class="block min-h-10 rounded-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"><span class="font-medium hover:underline">{task.title}</span>{#if task.labels.length}<span class="mt-1 flex flex-wrap gap-1">{#each task.labels as label (label.id)}<Badge variant="outline" class="text-[11px]">{label.name}</Badge>{/each}</span>{/if}</Link></td><td class="px-4 py-3 align-top"><Badge variant={task.status === 'done' ? 'secondary' : 'outline'}>{task.status_label}</Badge></td><td class="px-4 py-3 align-top text-muted-foreground">{task.priority_label ?? '—'}</td><td class="px-4 py-3 align-top text-muted-foreground">{task.assignee?.name ?? 'Belum ada PIC'}</td><td class="px-4 py-3 align-top text-muted-foreground">{task.due_date ?? '—'}</td></tr>{/each}</tbody>
            </table>
        </section>
    {/if}
        </div>

{#if selectedTask}
    <div class="fixed inset-0 z-40 bg-black/30 lg:hidden" aria-hidden="true"></div>
    <aside class="fixed inset-y-0 right-0 z-50 w-full overflow-y-auto border-l bg-background p-5 shadow-xl sm:max-w-lg lg:sticky lg:top-4 lg:z-auto lg:max-h-[calc(100vh-2rem)] lg:w-auto lg:self-start lg:rounded-lg lg:border lg:p-5 lg:shadow-none" aria-label="Detail Task">
        <div class="flex items-start justify-between gap-3"><div class="min-w-0"><p class="font-mono text-xs text-muted-foreground">{selectedTask.identifier}</p><h2 class="mt-1 text-lg font-semibold">{selectedTask.title}</h2></div><Link href={closePeek()} aria-label="Tutup detail Task" class="rounded-md p-2 hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring"><X class="size-4" aria-hidden="true" /></Link></div>
        <div class="mt-5 flex flex-wrap gap-2"><Badge variant="secondary">{selectedTask.status_label}</Badge>{#if selectedTask.priority_label}<Badge variant="outline">{selectedTask.priority_label}</Badge>{/if}</div>
        <p class="mt-5 whitespace-pre-wrap text-sm leading-6 text-muted-foreground">{selectedTask.description ?? 'Belum ada deskripsi.'}</p>
        <dl class="mt-6 grid gap-4 border-t pt-5 text-sm"><div><dt class="text-xs text-muted-foreground">PIC</dt><dd class="mt-1 font-medium">{selectedTask.assignee?.name ?? 'Belum ada PIC'}</dd></div><div><dt class="text-xs text-muted-foreground">Pemohon</dt><dd class="mt-1 font-medium">{selectedTask.requester?.name ?? '—'}</dd></div><div><dt class="text-xs text-muted-foreground">Deadline</dt><dd class="mt-1 font-medium">{selectedTask.due_date ?? '—'}</dd></div></dl>
        <div class="mt-6 grid gap-2">
            <Link href={toUrl(TaskController.show({ workspace: workspace.id, project: project.id, task: selectedTask.id }))} class="inline-flex min-h-10 items-center justify-center rounded-md bg-primary px-3 text-sm font-medium text-primary-foreground hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring">Buka halaman penuh</Link>
            <Link href={toUrl(TaskController.show({ workspace: workspace.id, project: project.id, task: selectedTask.id }) + '#status-form')} class="inline-flex min-h-10 items-center justify-center rounded-md border px-3 text-sm hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring">Lanjutkan ke aksi Task</Link>
        </div>
    </aside>
{/if}
    </div>
</div>
