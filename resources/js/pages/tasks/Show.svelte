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
    import CommentController from '@/actions/App/Http/Controllers/CommentController';
    import AttachmentController from '@/actions/App/Http/Controllers/AttachmentController';
    import TaskRelationController from '@/actions/App/Http/Controllers/TaskRelationController';
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
        parent_task_id: string | null; milestone: { id: string; name: string; target_date: string | null } | null;
    };
    type CommentItem = { id: string; body: string; created_at: string | null; user: Person | null; can_delete: boolean };
    type AttachmentItem = { id: string; original_name: string; mime_type: string; size: number; created_at: string | null; uploader: Person | null };
    type ActivityItem = { id: string; action: string; description: string | null; created_at: string | null; actor: Person | null };
    type Milestone = { id: string; name: string; target_date: string | null };
    type Relation = { id: string; type: string; type_label: string; related_task: Task };

    let { workspace, project, task, members, statuses, transitionStatuses, priorities, comments, attachments, activities, subtasks, milestones, relations, canUpdate, canTransition, canAssign, canComment, canAttach, canManageStructure }: {
        workspace: Workspace; project: Project; task: Task; members: Person[];
        statuses: Option[]; transitionStatuses: Option[]; priorities: Option[]; canUpdate: boolean; canTransition: boolean; canAssign: boolean;
        comments: CommentItem[]; attachments: AttachmentItem[]; activities: ActivityItem[]; canComment: boolean; canAttach: boolean;
        subtasks: Task[]; milestones: Milestone[]; relations: Relation[]; canManageStructure: boolean;
    } = $props();

    const primaryActionLabel = $derived(task.status === 'done' ? 'Buka kembali Task' : task.status === 'blocked' ? 'Lanjutkan Task' : task.status === 'in_progress' ? 'Kirim untuk Review' : task.status === 'in_review' ? 'Perbarui hasil Review' : 'Mulai atau perbarui Task');
</script>

<AppHead title={task.identifier + ' · ' + task.title} />

<div class="mx-auto flex w-full max-w-5xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
    <header class="flex flex-col gap-4 border-b pb-5 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <Link href={toUrl(TaskController.index({ workspace: workspace.id, project: project.id }))} class="inline-flex min-h-9 items-center gap-2 text-sm text-muted-foreground hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring"><ArrowLeft class="size-4" aria-hidden="true" />Kembali ke Task</Link>
            <div class="mt-4 flex items-start gap-3"><div class="mt-1 flex size-10 shrink-0 items-center justify-center rounded-md border bg-muted/40"><ClipboardList class="size-5 text-muted-foreground" aria-hidden="true" /></div><div class="min-w-0"><p class="font-mono text-xs text-muted-foreground">{task.identifier}</p><h1 class="mt-1 text-2xl font-semibold tracking-tight">{task.title}</h1><p class="mt-1 text-sm text-muted-foreground">{project.name} · {project.identifier}</p></div></div>
        </div>
        <div class="flex flex-wrap items-center gap-2 sm:justify-end"><Badge variant={task.status === 'done' ? 'secondary' : 'outline'}>{task.status_label}</Badge>{#if task.priority_label}<Badge variant="outline">{task.priority_label}</Badge>{/if}{#if canTransition && transitionStatuses.length > 0}<a href="#status-form" class="inline-flex min-h-10 items-center rounded-md bg-primary px-3 text-sm font-medium text-primary-foreground hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">{primaryActionLabel}</a>{/if}</div>
    </header>

    <ProjectContextNav {workspace} {project} active="tasks" />

    <div class="sticky top-12 z-20 -mx-4 flex items-center gap-3 border-y bg-background/95 px-4 py-3 shadow-sm backdrop-blur sm:-mx-6 sm:px-6 lg:hidden" aria-label="Ringkasan Task">
        <div class="min-w-0 flex-1">
            <p class="font-mono text-[11px] text-muted-foreground">{task.identifier}</p>
            <p class="truncate text-sm font-semibold">{task.title}</p>
            <p class="mt-0.5 truncate text-xs text-muted-foreground">{task.assignee?.name ?? 'Belum ada PIC'} · {task.due_date ?? 'Tanpa deadline'}</p>
        </div>
        {#if canTransition && transitionStatuses.length > 0}<a href="#status-form" class="inline-flex min-h-10 shrink-0 items-center rounded-md bg-primary px-3 text-xs font-medium text-primary-foreground hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">Lanjutkan</a>{/if}
    </div>

    {#if project.archived_at}
        <div class="rounded-md border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-100" role="status">Project ini diarsipkan. Task bersifat read-only sampai Project dipulihkan.</div>
    {/if}

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_19rem]">
        <div class="grid gap-6">
            <section class="rounded-lg border bg-card p-5 sm:p-6">
                <div class="flex items-start justify-between gap-4"><div><h2 class="text-sm font-semibold">Deskripsi dan konteks</h2><p class="mt-1 text-xs text-muted-foreground">Informasi yang membantu pekerjaan ini dikerjakan dengan benar.</p></div>{#if task.milestone}<Badge variant="outline">{task.milestone.name}</Badge>{/if}</div>
                {#if canUpdate}
                    <Form {...TaskController.update.form({ workspace: workspace.id, project: project.id, task: task.id })} class="mt-5 grid gap-5">
                        {#snippet children({ errors, processing })}
                            <div class="grid gap-2"><Label for="task-title">Title</Label><Input id="task-title" name="title" value={task.title} required maxlength={200} /><InputError message={errors.title} /></div>
                            <div class="grid gap-2"><Label for="task-description">Description</Label><textarea id="task-description" name="description" rows={7} class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:ring-2 focus-visible:ring-ring">{task.description ?? ''}</textarea><InputError message={errors.description} /></div>
                            <div class="grid gap-4 sm:grid-cols-2"><div class="grid gap-2"><Label for="task-priority">Priority</Label><select id="task-priority" name="priority" value={task.priority ?? ''} class="h-11 rounded-md border border-input bg-background px-3 text-sm"><option value="">Tidak ada</option>{#each priorities as priority (priority.value)}<option value={priority.value}>{priority.label}</option>{/each}</select><InputError message={errors.priority} /></div><div class="grid gap-2"><Label for="task-due-date">Deadline</Label><Input id="task-due-date" name="due_date" type="date" value={task.due_date ?? ''} /><InputError message={errors.due_date} /></div></div>
                            <div class="grid gap-2"><Label for="task-milestone">Milestone</Label><select id="task-milestone" name="milestone_id" value={task.milestone?.id ?? ''} class="h-11 rounded-md border border-input bg-background px-3 text-sm"><option value="">Tanpa milestone</option>{#each milestones as milestone (milestone.id)}<option value={milestone.id}>{milestone.name}</option>{/each}</select><InputError message={errors.milestone_id} /></div>
                            <div class="flex justify-end"><Button type="submit" disabled={processing}>{processing ? 'Menyimpan...' : 'Simpan perubahan'}</Button></div>
                        {/snippet}
                    </Form>
                {:else}
                    <p class="mt-5 whitespace-pre-wrap text-sm leading-7 text-muted-foreground">{task.description ?? 'Belum ada deskripsi.'}</p>
                {/if}
            </section>

            <section class="rounded-lg border bg-card p-5 sm:p-6">
                <div class="flex items-center justify-between gap-3"><div><h2 class="text-sm font-semibold">Sub-task</h2><p class="mt-1 text-xs text-muted-foreground">{subtasks.length} pekerjaan turunan</p></div></div>
                {#if subtasks.length === 0}<p class="mt-4 text-sm text-muted-foreground">Belum ada Sub-task.</p>{:else}<div class="mt-4 divide-y rounded-md border">{#each subtasks as subtask (subtask.id)}<Link href={toUrl(TaskController.show({ workspace: workspace.id, project: project.id, task: subtask.id }))} class="flex items-center justify-between gap-3 px-4 py-3 text-sm hover:bg-muted/40"><span><span class="font-mono text-xs text-muted-foreground">{subtask.identifier}</span> {subtask.title}</span><Badge variant="outline">{subtask.status_label}</Badge></Link>{/each}</div>{/if}
                {#if canManageStructure}<Form {...TaskController.store.form({ workspace: workspace.id, project: project.id })} class="mt-4 flex gap-2"><Input name="title" placeholder="Judul Sub-task" required maxlength={200} /><input type="hidden" name="parent_task_id" value={task.id} /><Button type="submit">Tambah</Button></Form>{/if}
            </section>

            <section class="rounded-lg border bg-card p-5 sm:p-6">
                <div class="flex items-center justify-between gap-3"><div><h2 class="text-sm font-semibold">Relasi Task</h2><p class="mt-1 text-xs text-muted-foreground">{relations.length} relasi ke Task lain dalam Project.</p></div></div>
                {#if relations.length === 0}<p class="mt-4 text-sm text-muted-foreground">Belum ada relasi.</p>{:else}<div class="mt-4 divide-y rounded-md border">{#each relations as relation (relation.id)}<div class="flex items-center justify-between gap-3 px-4 py-3 text-sm"><Link href={toUrl(TaskController.show({ workspace: workspace.id, project: project.id, task: relation.related_task.id }))} class="min-w-0 hover:underline"><span class="text-xs text-muted-foreground">{relation.type_label}</span> · {relation.related_task.identifier} {relation.related_task.title}</Link>{#if canManageStructure}<Form {...TaskRelationController.destroy.form({ workspace: workspace.id, project: project.id, task: task.id, relation: relation.id })}><Button type="submit" variant="ghost" size="sm">Hapus</Button></Form>{/if}</div>{/each}</div>{/if}
                {#if canManageStructure}<Form {...TaskRelationController.store.form({ workspace: workspace.id, project: project.id, task: task.id })} class="mt-4 grid gap-3 sm:grid-cols-[1fr_auto_auto]"><Input name="related_task_id" placeholder="ID Task terkait" required /><select name="type" class="h-11 rounded-md border border-input bg-background px-3 text-sm"><option value="related">Terkait</option><option value="duplicate">Duplikat</option><option value="blocks">Menghalangi</option></select><Button type="submit">Tambah relasi</Button></Form>{/if}
            </section>

            {#if canTransition && transitionStatuses.length > 0}
                <section id="status-form" class="scroll-mt-6 rounded-lg border border-primary/30 bg-primary/[0.03] p-5 sm:p-6">
                    <div class="flex items-start gap-3"><CheckCircle2 class="mt-0.5 size-4 text-primary" aria-hidden="true" /><div><h2 class="text-sm font-semibold">{primaryActionLabel}</h2><p class="mt-1 text-xs text-muted-foreground">Perubahan ini akan tercatat di Activity Log dan Audit Log.</p></div></div>
                    <Form {...TaskTransitionController.form({ workspace: workspace.id, project: project.id, task: task.id })} class="mt-5 grid gap-5">
                        {#snippet children({ errors, processing })}
                            <div class="grid gap-2"><Label for="transition-status">Status berikutnya</Label><select id="transition-status" name="status" value={transitionStatuses[0]?.value ?? ''} class="h-11 rounded-md border border-input bg-background px-3 text-sm">{#each transitionStatuses as status (status.value)}<option value={status.value}>{status.label}</option>{/each}</select><InputError message={errors.status} /></div>
                            <div class="grid gap-2"><Label for="transition-reason">Alasan <span class="font-normal text-muted-foreground">(wajib untuk Blocked, Canceled, dan Reopen)</span></Label><textarea id="transition-reason" name="reason" rows={3} class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:ring-2 focus-visible:ring-ring"></textarea><InputError message={errors.reason} /></div>
                            <div class="grid gap-2"><Label for="completion-note">Catatan penyelesaian atau URL bukti</Label><textarea id="completion-note" name="completion_note" rows={3} class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:ring-2 focus-visible:ring-ring">{task.completion_note ?? ''}</textarea><InputError message={errors.completion_note} /></div>
                            <div class="flex justify-end"><Button type="submit" disabled={processing}>{processing ? 'Memperbarui...' : 'Perbarui status'}</Button></div>
                        {/snippet}
                    </Form>
                </section>
            {/if}

            <section class="rounded-lg border bg-card p-5 sm:p-6">
                <h2 class="text-sm font-semibold">Komentar</h2>
                {#if canComment}
                    <Form {...CommentController.store.form({ workspace: workspace.id, project: project.id, task: task.id })} class="mt-5 grid gap-3">
                        {#snippet children({ errors, processing })}
                            <textarea name="body" rows={4} maxlength={10000} placeholder="Tulis konteks atau pembaruan..." class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:ring-2 focus-visible:ring-ring"></textarea>
                            <InputError message={errors.body} />
                            <div class="flex justify-end"><Button type="submit" disabled={processing}>{processing ? 'Menambahkan...' : 'Tambah komentar'}</Button></div>
                        {/snippet}
                    </Form>
                {/if}
                <div class="mt-6 grid gap-4 border-t pt-5">
                    {#if comments.length === 0}
                        <p class="text-sm text-muted-foreground">Belum ada komentar.</p>
                    {:else}
                        {#each comments as comment (comment.id)}
                            <article class="rounded-md border bg-muted/20 p-4">
                                <div class="flex items-center justify-between gap-3"><p class="text-sm font-medium">{comment.user?.name ?? 'Pengguna'}</p>{#if comment.can_delete}<Form {...CommentController.destroy.form({ workspace: workspace.id, project: project.id, task: task.id, comment: comment.id })}><Button type="submit" variant="ghost" size="sm">Hapus</Button></Form>{/if}</div>
                                <p class="mt-2 whitespace-pre-wrap text-sm leading-6 text-muted-foreground">{comment.body}</p>
                                <p class="mt-2 text-xs text-muted-foreground">{comment.created_at ?? ''}</p>
                            </article>
                        {/each}
                    {/if}
                </div>
            </section>

            <section class="rounded-lg border bg-card p-5 sm:p-6">
                <h2 class="text-sm font-semibold">Bukti dan attachment</h2>
                {#if canAttach}
                    <Form {...AttachmentController.store.form({ workspace: workspace.id, project: project.id, task: task.id })} class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-end" enctype="multipart/form-data">
                        {#snippet children({ errors, processing })}
                            <div class="min-w-0 flex-1"><Label for="task-attachment">File</Label><Input id="task-attachment" name="file" type="file" class="mt-2 min-h-11" /><InputError message={errors.file} /></div>
                            <Button type="submit" variant="outline" disabled={processing}>{processing ? 'Mengunggah...' : 'Unggah file'}</Button>
                        {/snippet}
                    </Form>
                {/if}
                {#if attachments.length === 0}<p class="mt-5 text-sm text-muted-foreground">Belum ada attachment.</p>{:else}<ul class="mt-5 grid gap-2 border-t pt-5">{#each attachments as attachment (attachment.id)}<li class="flex flex-wrap items-center justify-between gap-3 rounded-md border px-3 py-3 text-sm"><div><a href={toUrl(AttachmentController.download({ workspace: workspace.id, project: project.id, task: task.id, attachment: attachment.id }))} class="font-medium hover:underline">{attachment.original_name}</a><p class="mt-1 text-xs text-muted-foreground">{attachment.mime_type} · {attachment.uploader?.name ?? 'Pengguna'}</p></div>{#if canUpdate}<Form {...AttachmentController.destroy.form({ workspace: workspace.id, project: project.id, task: task.id, attachment: attachment.id })}><Button type="submit" variant="ghost" size="sm">Hapus</Button></Form>{/if}</li>{/each}</ul>{/if}
            </section>

            <section class="rounded-lg border bg-card p-5 sm:p-6">
                <h2 class="text-sm font-semibold">Activity Log</h2>
                {#if activities.length === 0}<p class="mt-5 text-sm text-muted-foreground">Belum ada aktivitas.</p>{:else}<ol class="mt-5 grid gap-4 border-l pl-4">{#each activities as activity (activity.id)}<li><p class="text-sm">{activity.description ?? activity.action}</p><p class="mt-1 text-xs text-muted-foreground">{activity.actor?.name ?? 'Sistem'} · {activity.created_at ?? ''}</p></li>{/each}</ol>{/if}
            </section>
        </div>

        <aside class="rounded-lg border bg-card p-5 lg:sticky lg:top-4 lg:self-start">
            <div class="flex items-center justify-between gap-3"><h2 class="text-sm font-semibold">Context Task</h2><span class="font-mono text-[11px] text-muted-foreground">{task.identifier}</span></div>
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
