<script module lang="ts">
    import { show } from '@/routes/workspaces/projects/tasks';

    export const layout = {
        breadcrumbs: [{ title: 'Detail Task', href: show(['', '', '']) }],
    };
</script>

<script lang="ts">
    import { Form, Link } from '@inertiajs/svelte';
    import ArrowLeft from '@lucide/svelte/icons/arrow-left';
    import CheckCircle2 from '@lucide/svelte/icons/check-circle-2';
    import ClipboardList from '@lucide/svelte/icons/clipboard-list';
    import TaskController from '@/actions/App/Http/Controllers/TaskController';
    import TaskTransitionController from '@/actions/App/Http/Controllers/TaskTransitionController';
    import AppHead from '@/components/AppHead.svelte';
    import InputError from '@/components/InputError.svelte';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { toUrl } from '@/lib/utils';

    type Workspace = { id: string; name: string };
    type Project = { id: string; name: string; identifier: string; archived_at: string | null };
    type Person = { id: number; name: string; email: string };
    type Option = { value: string; label: string };
    type Task = {
        id: string; identifier: string; title: string; description: string | null;
        status: string; status_label: string; priority: string | null; priority_label: string | null;
        assignee: Person | null; requester: Person | null; creator: Person | null;
        due_date: string | null; completed_at: string | null; completion_note: string | null;
        evidence_url: string | null; blocked_reason: string | null; canceled_reason: string | null;
        reopen_reason: string | null; labels: Array<{ id: string; name: string; color: string | null }>;
        created_at: string | null; updated_at: string | null;
    };

    let { workspace, project, task, members, statuses, priorities, canUpdate, canTransition, canAssign }: {
        workspace: Workspace; project: Project; task: Task; members: Person[];
        statuses: Option[]; priorities: Option[]; canUpdate: boolean; canTransition: boolean; canAssign: boolean;
    } = $props();
</script>

<AppHead title={task.identifier + ' · ' + task.title} />

<div class="mx-auto flex w-full max-w-5xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
    <header class="flex flex-col gap-4 border-b pb-5 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <Link href={toUrl(TaskController.index({ workspace: workspace.id, project: project.id }))} class="inline-flex min-h-9 items-center gap-2 text-sm text-muted-foreground hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring"><ArrowLeft class="size-4" aria-hidden="true" />Kembali ke Task</Link>
            <div class="mt-4 flex items-start gap-3"><div class="mt-1 flex size-10 shrink-0 items-center justify-center rounded-md border bg-muted/40"><ClipboardList class="size-5 text-muted-foreground" aria-hidden="true" /></div><div class="min-w-0"><p class="font-mono text-xs text-muted-foreground">{task.identifier}</p><h1 class="mt-1 text-2xl font-semibold tracking-tight">{task.title}</h1><p class="mt-1 text-sm text-muted-foreground">{project.name} · {project.identifier}</p></div></div>
        </div>
        <div class="flex flex-wrap gap-2"><Badge variant={task.status === 'done' ? 'secondary' : 'outline'}>{task.status_label}</Badge>{#if task.priority_label}<Badge variant="outline">{task.priority_label}</Badge>{/if}</div>
    </header>

    {#if project.archived_at}
        <div class="rounded-md border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-100" role="status">Project ini diarsipkan. Task bersifat read-only sampai Project dipulihkan.</div>
    {/if}

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_19rem]">
        <div class="grid gap-6">
            <section class="rounded-lg border bg-card p-5 sm:p-6">
                <h2 class="text-sm font-semibold">Detail Task</h2>
                {#if canUpdate}
                    <Form {...TaskController.update.form({ workspace: workspace.id, project: project.id, task: task.id })} class="mt-5 grid gap-5">
                        {#snippet children({ errors, processing })}
                            <div class="grid gap-2"><Label for="task-title">Title</Label><Input id="task-title" name="title" value={task.title} required maxlength={200} /><InputError message={errors.title} /></div>
                            <div class="grid gap-2"><Label for="task-description">Description</Label><textarea id="task-description" name="description" rows={7} class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:ring-2 focus-visible:ring-ring">{task.description ?? ''}</textarea><InputError message={errors.description} /></div>
                            <div class="grid gap-4 sm:grid-cols-2"><div class="grid gap-2"><Label for="task-priority">Priority</Label><select id="task-priority" name="priority" value={task.priority ?? ''} class="h-11 rounded-md border border-input bg-background px-3 text-sm"><option value="">Tidak ada</option>{#each priorities as priority (priority.value)}<option value={priority.value}>{priority.label}</option>{/each}</select><InputError message={errors.priority} /></div><div class="grid gap-2"><Label for="task-due-date">Deadline</Label><Input id="task-due-date" name="due_date" type="date" value={task.due_date ?? ''} /><InputError message={errors.due_date} /></div></div>
                            <div class="flex justify-end"><Button type="submit" disabled={processing}>{processing ? 'Menyimpan...' : 'Simpan perubahan'}</Button></div>
                        {/snippet}
                    </Form>
                {:else}
                    <p class="mt-5 whitespace-pre-wrap text-sm leading-7 text-muted-foreground">{task.description ?? 'Belum ada deskripsi.'}</p>
                {/if}
            </section>

            {#if canTransition}
                <section class="rounded-lg border bg-card p-5 sm:p-6">
                    <div class="flex items-center gap-2"><CheckCircle2 class="size-4 text-muted-foreground" aria-hidden="true" /><h2 class="text-sm font-semibold">Perbarui status</h2></div>
                    <Form {...TaskTransitionController.form({ workspace: workspace.id, project: project.id, task: task.id })} class="mt-5 grid gap-5">
                        {#snippet children({ errors, processing })}
                            <div class="grid gap-2"><Label for="transition-status">Status berikutnya</Label><select id="transition-status" name="status" value={task.status} class="h-11 rounded-md border border-input bg-background px-3 text-sm">{#each statuses as status (status.value)}<option value={status.value}>{status.label}</option>{/each}</select><InputError message={errors.status} /></div>
                            <div class="grid gap-2"><Label for="transition-reason">Alasan <span class="font-normal text-muted-foreground">(wajib untuk Blocked, Canceled, dan Reopen)</span></Label><textarea id="transition-reason" name="reason" rows={3} class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:ring-2 focus-visible:ring-ring"></textarea><InputError message={errors.reason} /></div>
                            <div class="grid gap-2"><Label for="completion-note">Catatan penyelesaian atau URL bukti</Label><textarea id="completion-note" name="completion_note" rows={3} class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:ring-2 focus-visible:ring-ring">{task.completion_note ?? ''}</textarea><InputError message={errors.completion_note} /></div>
                            <div class="flex justify-end"><Button type="submit" disabled={processing}>{processing ? 'Memperbarui...' : 'Perbarui status'}</Button></div>
                        {/snippet}
                    </Form>
                </section>
            {/if}
        </div>

        <aside class="rounded-lg border bg-card p-5">
            <h2 class="text-sm font-semibold">Properti</h2>
            {#if canAssign}
                <Form {...TaskController.assign.form({ workspace: workspace.id, project: project.id, task: task.id })} class="mt-5 grid gap-2">
                    {#snippet children({ errors, processing })}
                        <Label for="task-assignee">PIC Task</Label>
                        <select id="task-assignee" name="assignee_id" value={task.assignee?.id ?? ''} class="h-11 rounded-md border border-input bg-background px-3 text-sm"><option value="">Belum ada PIC</option>{#each members as member (member.id)}<option value={member.id}>{member.name}</option>{/each}</select>
                        <InputError message={errors.assignee_id} />
                        <Button type="submit" variant="outline" disabled={processing}>{processing ? 'Menyimpan...' : 'Simpan PIC'}</Button>
                    {/snippet}
                </Form>
            {:else}
                <dl class="mt-5 grid gap-4 text-sm"><div><dt class="text-xs text-muted-foreground">PIC Task</dt><dd class="mt-1 font-medium">{task.assignee?.name ?? 'Belum ada PIC'}</dd></div></dl>
            {/if}
            <dl class="mt-6 grid gap-4 border-t pt-5 text-sm"><div><dt class="text-xs text-muted-foreground">Pemohon</dt><dd class="mt-1 font-medium">{task.requester?.name ?? '—'}</dd></div><div><dt class="text-xs text-muted-foreground">Dibuat oleh</dt><dd class="mt-1 font-medium">{task.creator?.name ?? '—'}</dd></div><div><dt class="text-xs text-muted-foreground">Deadline</dt><dd class="mt-1 font-medium">{task.due_date ?? '—'}</dd></div><div><dt class="text-xs text-muted-foreground">Dibuat</dt><dd class="mt-1 font-medium">{task.created_at ?? '—'}</dd></div></dl>
        </aside>
    </div>
</div>
