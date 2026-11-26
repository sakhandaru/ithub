<script module lang="ts">
    import { show } from '@/routes/workspaces/projects/tasks';

    export const layout = {
        breadcrumbs: [{ title: 'Detail Task', href: show(['', '', '']) }],
    };
</script>

<script lang="ts">
    import { Form, Link, page } from '@inertiajs/svelte';
    import Calendar from 'carbon-icons-svelte/lib/Calendar.svelte';
    import ChevronDown from 'carbon-icons-svelte/lib/ChevronDown.svelte';
    import CircleAlert from 'carbon-icons-svelte/lib/WarningAlt.svelte';
    import CircleDashed from 'carbon-icons-svelte/lib/CircleDash.svelte';
    import Close from 'carbon-icons-svelte/lib/Close.svelte';
    import Document from 'carbon-icons-svelte/lib/Document.svelte';
    import Download from 'carbon-icons-svelte/lib/Download.svelte';
    import Edit from 'carbon-icons-svelte/lib/Edit.svelte';
    import Flag from 'carbon-icons-svelte/lib/Flag.svelte';
    import Grid from 'carbon-icons-svelte/lib/Grid.svelte';
    import LinkIcon from 'carbon-icons-svelte/lib/Link.svelte';
    import Plus from 'carbon-icons-svelte/lib/Add.svelte';
    import TrashCan from 'carbon-icons-svelte/lib/TrashCan.svelte';
    import ProjectAttachmentController from '@/actions/App/Http/Controllers/ProjectAttachmentController';
    import ProjectController from '@/actions/App/Http/Controllers/ProjectController';
    import TaskController from '@/actions/App/Http/Controllers/TaskController';
    import CommentController from '@/actions/App/Http/Controllers/CommentController';
    import AttachmentController from '@/actions/App/Http/Controllers/AttachmentController';
    import TaskRelationController from '@/actions/App/Http/Controllers/TaskRelationController';
    import AppHead from '@/components/AppHead.svelte';
    import InputError from '@/components/InputError.svelte';
    import PageHeader from '@/components/PageHeader.svelte';
    import UserAvatar from '@/components/UserAvatar.svelte';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Select, SelectContent, SelectItem, SelectTrigger } from '@/components/ui/select';
    import { Dialog, DialogContent, DialogDescription, DialogTitle } from '@/components/ui/dialog';
    import { formatDate, formatDateTime } from '@/lib/format-date';
    import { globalHeaderLabels, taskBreadcrumbs } from '@/lib/page-header';
    import { toUrl } from '@/lib/utils';

    type Person = { id: number; name: string; email: string; avatar?: string | null };
    type Option = { value: string; label: string };
    type Task = { id: string; identifier: string; title: string; description: string | null; expected_outcome: string | null; status: string; status_label: string; priority: string | null; priority_label: string | null; assignee: Person | null; requester: Person | null; creator: Person | null; start_date: string | null; due_date: string | null; completed_at: string | null; completion_note: string | null; evidence_url: string | null; blocked_reason: string | null; canceled_reason: string | null; reopen_reason: string | null; created_at: string | null; updated_at: string | null; milestone: { id: string; name: string; target_date: string | null } | null; module: { id: string; name: string; description: string | null } | null; release: { id: string; name: string; status: string; target_date: string | null } | null };
    type CommentItem = { id: string; body: string; created_at: string | null; user: Person | null; can_delete: boolean };
    type AttachmentItem = { id: string; original_name: string; mime_type: string; size: number; created_at: string | null; uploader: Person | null };
    type ActivityItem = { id: string; action: string; description: string | null; created_at: string | null; actor: Person | null };
    type Relation = { id: string; type: string; type_label: string; related_task: Task };
    type RelatedTask = { id: string; identifier: string; title: string; status: string; status_label: string };
    type Milestone = { id: string; name: string; target_date: string | null };
    type Module = { id: string; name: string; description: string | null };
    type Release = { id: string; name: string; status: string; target_date: string | null };

    let { workspace, project, task, members, priorities, comments, attachments, activities, milestones, modules, releases, relations, relatedTasks, canUpdate, canAssign, canComment, canAttach, canManageStructure, embedded = false }: {
        workspace: { id: string; name: string }; project: { id: string; name: string; identifier: string; archived_at: string | null }; task: Task; members: Person[]; statuses: Option[]; transitionStatuses: Option[]; priorities: Option[]; comments: CommentItem[]; attachments: AttachmentItem[]; activities: ActivityItem[]; milestones: Milestone[]; modules: Module[]; releases: Release[]; relations: Relation[]; relatedTasks: RelatedTask[]; canUpdate: boolean; canTransition: boolean; canAssign: boolean; canComment: boolean; canAttach: boolean; canManageStructure: boolean; embedded?: boolean;
    } = $props();
    let editing = $state(false);
    let relationType = $state('related');
    let relationSearch = $state('');
    let relationTaskId = $state('');
    let relationPickerOpen = $state(false);
    let previewOpen = $state(false);
    let previewAttachment = $state<AttachmentItem | null>(null);
    let selectedFileName = $state('');
    const workspaceTimezone = $derived(page.props.workspaceContext?.active?.timezone);
    const filteredRelatedTasks = $derived(relatedTasks.filter((relatedTask) => `${relatedTask.identifier} ${relatedTask.title}`.toLowerCase().includes(relationSearch.trim().toLowerCase())).slice(0, 8));

    function formatSize(size: number): string {
        if (size < 1024) return `${size} B`;
        if (size < 1024 * 1024) return `${Math.round(size / 1024)} KB`;

        return `${(size / (1024 * 1024)).toFixed(1)} MB`;
    }

    function statusColor(status: string): string {
        return { backlog: 'text-slate-600 dark:text-slate-300', todo: 'text-indigo-600 dark:text-indigo-300', in_progress: 'text-amber-600 dark:text-amber-300', review: 'text-violet-600 dark:text-violet-300', done: 'text-emerald-600 dark:text-emerald-300', blocked: 'text-rose-600 dark:text-rose-300', canceled: 'text-slate-600 dark:text-slate-300' }[status] ?? 'text-muted-foreground';
    }

    function priorityColor(priority: string): string {
        return { urgent: 'text-rose-600 dark:text-rose-300', high: 'text-orange-600 dark:text-orange-300', medium: 'text-amber-600 dark:text-amber-300', low: 'text-sky-600 dark:text-sky-300' }[priority] ?? 'text-muted-foreground';
    }

    function openAttachmentPreview(attachment: AttachmentItem): void {
        previewAttachment = attachment;
        previewOpen = true;
    }
</script>

{#if !embedded}<AppHead title={`${task.identifier} · ${task.title}`} />{/if}

<div class={embedded ? 'min-h-full min-w-0 overflow-x-hidden bg-background' : 'mx-auto min-h-screen w-full max-w-4xl bg-background'}>
    {#if !embedded}<PageHeader breadcrumbs={taskBreadcrumbs(page.props.unitContext?.active?.name ?? workspace.name, page.props.unitContext?.active?.id, toUrl(ProjectController.index(workspace.id)), project.name, toUrl(ProjectController.show({ workspace: workspace.id, project: project.id })), toUrl(TaskController.index({ workspace: workspace.id, project: project.id })), undefined, globalHeaderLabels.task, page.props.projectContext?.active?.icon)} showSidebarTrigger />{/if}

    <header class="border-b border-border/70 px-5 py-4 sm:px-7">
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
                <p class="font-mono text-xs text-muted-foreground">{task.identifier}</p>
                <h1 class="mt-1 text-xl font-semibold leading-tight tracking-tight sm:text-2xl">{task.title}</h1>
            </div>
            <div class="flex shrink-0 items-center gap-1">
                {#if canUpdate}<Button type="button" variant="ghost" size="icon" class="size-9 self-center rounded-md border border-border/70" aria-label={editing ? 'Tutup edit' : 'Edit Task'} title={editing ? 'Tutup edit' : 'Edit Task'} onclick={() => { editing = !editing; }}>{#if editing}<Close class="size-4" aria-hidden="true" />{:else}<Edit class="size-4" aria-hidden="true" />{/if}</Button>{/if}
            </div>
        </div>
        <div class="mt-3 flex flex-wrap items-center gap-1.5 text-[11px]">
            <span class={`inline-flex min-h-6 items-center gap-1 whitespace-nowrap rounded-md border border-border/70 bg-background px-1.5 leading-none ${statusColor(task.status)}`}><span class="size-1.5 rounded-full bg-current" aria-hidden="true"></span>{task.status_label}</span>
            {#if task.priority_label}<span class="inline-flex min-h-6 items-center gap-1 whitespace-nowrap rounded-md border border-border/70 bg-background px-1.5 leading-none text-muted-foreground"><CircleAlert class={`size-3 ${priorityColor(task.priority ?? '')}`} aria-hidden="true" />{task.priority_label}</span>{/if}
            {#if task.milestone}<span class="inline-flex min-h-6 max-w-40 items-center gap-1 whitespace-nowrap rounded-md border border-border/70 bg-background px-1.5 leading-none text-muted-foreground"><Flag class="size-3 shrink-0 text-slate-500 dark:text-slate-400" aria-hidden="true" /><span class="truncate">{task.milestone.name}</span></span>{/if}
            {#if task.module}<span class="inline-flex min-h-6 max-w-40 items-center gap-1 whitespace-nowrap rounded-md border border-border/70 bg-background px-1.5 leading-none text-muted-foreground"><Grid class="size-3 shrink-0 text-slate-500 dark:text-slate-400" aria-hidden="true" /><span class="truncate">{task.module.name}</span></span>{/if}
            {#if task.due_date}<span class="inline-flex min-h-6 items-center gap-1 whitespace-nowrap rounded-md border border-border/70 bg-background px-1.5 leading-none text-muted-foreground"><Calendar class="size-3 shrink-0 text-slate-500 dark:text-slate-400" aria-hidden="true" />{formatDate(task.due_date, workspaceTimezone)}</span>{/if}
            {#if task.assignee}<span class="inline-flex min-h-6 items-center gap-1 whitespace-nowrap rounded-md border border-border/70 bg-background px-1.5 leading-none text-muted-foreground"><UserAvatar user={task.assignee} size="size-4" label={false} />{task.assignee.name}</span>{:else}<span class="inline-flex min-h-6 items-center gap-1 whitespace-nowrap rounded-md border border-dashed border-border/70 px-1.5 leading-none text-muted-foreground"><CircleDashed class="size-3.5 shrink-0" aria-hidden="true" />Belum ada PIC</span>{/if}
        </div>
    </header>

    {#if project.archived_at}<div class="mx-5 mt-4 rounded-md border border-amber-300 bg-amber-50 px-3 py-2 text-xs text-amber-950 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-100" role="status">Project diarsipkan. Task bersifat read-only.</div>{/if}

    <main class="grid w-full min-w-0 max-w-full gap-3 px-5 sm:px-7">
        <section class="border-b border-border/70 py-5" aria-labelledby="description-heading">
            <div class="flex items-center justify-between gap-3"><h2 id="description-heading" class="text-sm font-semibold">Deskripsi</h2></div>
            {#if canUpdate && editing}
                <Form {...TaskController.update.form({ workspace: workspace.id, project: project.id, task: task.id })} class="mt-4 grid gap-4">
                    {#snippet children({ errors, processing })}
                        <div class="grid gap-2"><Label for="task-title">Judul</Label><Input id="task-title" name="title" value={task.title} required maxlength={200} /><InputError message={errors.title} /></div>
                        <div class="grid gap-2"><Label for="task-description">Deskripsi</Label><textarea id="task-description" name="description" rows={6} class="w-full resize-y rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:ring-2 focus-visible:ring-ring">{task.description ?? ''}</textarea><InputError message={errors.description} /></div>
                        <div class="grid gap-2"><Label for="task-expected-outcome">Hasil yang diharapkan</Label><textarea id="task-expected-outcome" name="expected_outcome" rows={3} class="w-full resize-y rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:ring-2 focus-visible:ring-ring">{task.expected_outcome ?? ''}</textarea><InputError message={errors.expected_outcome} /></div>
                        <div class="grid gap-3 sm:grid-cols-2"><select name="priority" value={task.priority ?? ''} class="h-9 rounded-md border border-input bg-background px-2 text-xs"><option value="">Tanpa priority</option>{#each priorities as priority (priority.value)}<option value={priority.value}>{priority.label}</option>{/each}</select><label class="grid gap-1 text-xs text-muted-foreground">Deadline<Input name="due_date" type="date" value={task.due_date ?? ''} class="h-9 text-xs" /></label></div>
                        <div class="flex justify-end"><Button type="submit" size="sm" disabled={processing}>{processing ? 'Menyimpan...' : 'Simpan perubahan'}</Button></div>
                    {/snippet}
                </Form>
            {:else}
                <p class="mt-3 whitespace-pre-wrap text-sm leading-6 text-foreground/80">{task.description ?? 'Belum ada deskripsi.'}</p>
                <div class="mt-5"><p class="text-xs font-medium text-muted-foreground">Hasil yang diharapkan</p><p class="mt-1 whitespace-pre-wrap text-sm leading-6 text-foreground/80">{task.expected_outcome ?? 'Belum ditentukan.'}</p></div>
            {/if}
        </section>

        <section class="rounded-lg border border-border/70 bg-card p-4" aria-labelledby="attachments-heading">
            <div class="flex items-center justify-between gap-3"><div class="flex items-center gap-2"><Document class="size-4 text-muted-foreground" aria-hidden="true" /><h2 id="attachments-heading" class="text-sm font-semibold">Attachment</h2><span class="text-xs text-muted-foreground">{attachments.length}</span></div></div>
            {#if attachments.length > 0}<div class="mt-3 grid min-w-0 gap-2">{#each attachments as attachment (attachment.id)}<div class="group relative min-w-0 overflow-hidden rounded-md border border-border/70 bg-background transition-colors hover:border-primary/40 hover:bg-muted/20"><button type="button" class="block w-full min-w-0 p-2.5 text-left focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-inset" aria-label={`Preview ${attachment.original_name}`} onclick={() => openAttachmentPreview(attachment)}><div class="flex min-w-0 items-center gap-2.5"><span class="flex size-8 shrink-0 items-center justify-center rounded-md bg-muted text-muted-foreground"><Document class="size-4" aria-hidden="true" /></span><span class="min-w-0 pr-10"><span class="block truncate text-xs font-medium">{attachment.original_name}</span><span class="mt-0.5 block truncate text-[11px] text-muted-foreground">{attachment.mime_type} · {formatSize(attachment.size)}</span></span></div></button><div class="absolute right-2 top-2 flex items-center gap-1 opacity-0 transition-opacity group-hover:opacity-100 focus-within:opacity-100"><a href={toUrl(ProjectAttachmentController.download({ workspace: workspace.id, project: project.id, attachment: attachment.id }))} class="inline-flex size-6 items-center justify-center rounded-md border border-border/70 bg-background text-muted-foreground hover:bg-muted hover:text-foreground" aria-label={`Unduh ${attachment.original_name}`} title="Unduh"><Download class="size-3" aria-hidden="true" /></a>{#if canUpdate}<Form {...AttachmentController.destroy.form({ workspace: workspace.id, project: project.id, task: task.id, attachment: attachment.id })}><Button type="submit" variant="ghost" size="icon" class="size-6 border border-border/70 bg-background text-muted-foreground hover:bg-muted hover:text-destructive" aria-label={`Hapus ${attachment.original_name}`} title="Hapus"><TrashCan class="size-3" aria-hidden="true" /></Button></Form>{/if}</div></div>{/each}</div>{:else}<p class="mt-3 text-sm text-muted-foreground">Belum ada attachment.</p>{/if}
            {#if canAttach}<Form {...AttachmentController.store.form({ workspace: workspace.id, project: project.id, task: task.id })} class="mt-3" enctype="multipart/form-data" onSuccess={() => { selectedFileName = ''; }}>
                {#snippet children({ processing })}
                    <label class="flex min-h-9 cursor-pointer items-center gap-2 rounded-md border border-dashed border-border/80 bg-muted/20 px-3 text-xs text-muted-foreground transition-colors hover:border-primary/50 hover:bg-muted/40" aria-busy={processing}><Document class="size-3.5 shrink-0" aria-hidden="true" /><span class="truncate">{processing ? 'Mengunggah...' : selectedFileName || 'Pilih file untuk mengunggah'}</span><Input name="file" type="file" class="sr-only" disabled={processing} onchange={(event) => { const input = event.currentTarget as HTMLInputElement; selectedFileName = input.files?.[0]?.name ?? ''; input.form?.requestSubmit(); }} /></label>
                {/snippet}
            </Form>{/if}
        </section>

        <details class="group w-full min-w-0 max-w-full rounded-lg border border-border/70 bg-card p-4" open>
            <summary class="flex cursor-pointer list-none items-center justify-between gap-3"><span class="flex items-center gap-2"><LinkIcon class="size-4 text-muted-foreground" aria-hidden="true" /><span class="text-sm font-semibold">Relasi Task</span><span class="text-xs text-muted-foreground">{relations.length}</span></span><ChevronDown class="size-4 text-muted-foreground transition-transform group-open:rotate-180" aria-hidden="true" /></summary>
            {#if relations.length > 0}<div class="mt-3 w-full min-w-0 max-w-full divide-y divide-border/60 overflow-hidden rounded-md border border-border/60">{#each relations as relation (relation.id)}<div class="flex w-full min-w-0 items-center justify-between gap-3 overflow-hidden px-3 py-2 hover:bg-muted/40"><Link href={toUrl(TaskController.show({ workspace: workspace.id, project: project.id, task: relation.related_task.id }))} title={`${relation.related_task.identifier} · ${relation.related_task.title}`} class="flex min-w-0 max-w-full flex-1 items-center overflow-hidden text-sm hover:underline"><span class="mr-2 shrink-0 rounded-full bg-muted px-1.5 py-0.5 text-[11px] text-muted-foreground">{relation.type_label}</span><span class="block min-w-0 truncate">{relation.related_task.identifier} · {relation.related_task.title}</span></Link>{#if canManageStructure}<Form {...TaskRelationController.destroy.form({ workspace: workspace.id, project: project.id, task: task.id, relation: relation.id })}><Button type="submit" variant="ghost" size="icon" class="size-7 shrink-0 text-muted-foreground hover:text-destructive" aria-label="Hapus relasi"><TrashCan class="size-3.5" aria-hidden="true" /></Button></Form>{/if}</div>{/each}</div>{:else}<p class="mt-3 text-sm text-muted-foreground">Belum ada relasi.</p>{/if}
            {#if canManageStructure}<Form {...TaskRelationController.store.form({ workspace: workspace.id, project: project.id, task: task.id })} class="mt-3 flex min-w-0 flex-col gap-2" onSuccess={() => { relationSearch = ''; relationTaskId = ''; relationPickerOpen = false; }}>
                <div class="relative min-w-0"><Input value={relationSearch} placeholder="Cari task berdasarkan ID atau judul" required class="h-8 w-full text-sm" oninput={(event) => { relationSearch = (event.currentTarget as HTMLInputElement).value; relationTaskId = ''; relationPickerOpen = true; }} onfocus={() => { relationPickerOpen = true; }} />{#if relationPickerOpen && relationSearch.trim() && !relationTaskId}<div class="absolute inset-x-0 top-full z-20 mt-1 max-h-64 overflow-y-auto rounded-md border border-border bg-popover p-1 shadow-lg">{#if filteredRelatedTasks.length > 0}{#each filteredRelatedTasks as relatedTask (relatedTask.id)}<button type="button" class="flex w-full items-center justify-between gap-3 rounded-sm px-2.5 py-2 text-left text-xs hover:bg-accent" onclick={() => { relationTaskId = relatedTask.id; relationSearch = `${relatedTask.identifier} · ${relatedTask.title}`; relationPickerOpen = false; }}><span class="min-w-0 truncate"><span class="font-mono text-muted-foreground">{relatedTask.identifier}</span> · {relatedTask.title}</span><span class="shrink-0 text-muted-foreground">{relatedTask.status_label}</span></button>{/each}{:else}<p class="px-2.5 py-2 text-xs text-muted-foreground">Task tidak ditemukan.</p>{/if}</div>{/if}<input type="hidden" name="related_task_id" value={relationTaskId} /></div><div class="flex min-w-0 gap-2"><Select value={relationType} onValueChange={(value) => { relationType = value; }}><SelectTrigger class="h-8 min-w-0 flex-1 text-xs"><span>{relationType === 'duplicate' ? 'Duplikat dari' : relationType === 'blocks' ? 'Menghalangi' : 'Terkait dengan'}</span></SelectTrigger><SelectContent><SelectItem value="related">Terkait dengan</SelectItem><SelectItem value="duplicate">Duplikat dari</SelectItem><SelectItem value="blocks">Menghalangi</SelectItem></SelectContent></Select><input type="hidden" name="type" value={relationType} /><Button type="submit" size="sm" class="h-8 shrink-0 px-2.5" disabled={!relationTaskId}><Plus class="size-3.5" aria-hidden="true" />Tambah</Button></div></Form>{/if}
        </details>

        <section class="py-5" aria-labelledby="comments-heading">
            <div class="flex items-center justify-between gap-3"><h2 id="comments-heading" class="text-sm font-semibold">Komentar</h2><span class="text-xs text-muted-foreground">{comments.length}</span></div>
            {#if canComment}<Form {...CommentController.store.form({ workspace: workspace.id, project: project.id, task: task.id })} class="mt-3"><textarea name="body" rows={3} maxlength={10000} placeholder="Tulis komentar..." class="w-full resize-y rounded-md border border-input bg-muted/20 px-3 py-2 text-sm outline-none focus-visible:ring-2 focus-visible:ring-ring"></textarea><div class="mt-2 flex justify-end"><Button type="submit" size="sm">Kirim komentar</Button></div></Form>{/if}
            {#if comments.length > 0}<div class="mt-5 divide-y divide-border/60 border-t border-border/70">{#each comments as comment (comment.id)}<article class="group flex gap-3 py-4"><UserAvatar user={comment.user} size="size-6" label={false} /><div class="min-w-0 flex-1"><div class="flex items-center justify-between gap-3"><div class="flex min-w-0 items-center gap-2"><span class="truncate text-sm font-medium">{comment.user?.name ?? 'Pengguna'}</span><span class="shrink-0 text-xs text-muted-foreground">{formatDateTime(comment.created_at, workspaceTimezone)}</span></div>{#if comment.can_delete}<Form {...CommentController.destroy.form({ workspace: workspace.id, project: project.id, task: task.id, comment: comment.id })}><Button type="submit" variant="ghost" size="sm" class="h-7 px-2 text-xs text-muted-foreground opacity-0 transition-opacity hover:text-destructive group-hover:opacity-100 focus-visible:opacity-100">Hapus</Button></Form>{/if}</div><p class="mt-1 whitespace-pre-wrap text-sm leading-6 text-foreground/80">{comment.body}</p></div></article>{/each}</div>{:else}<p class="mt-4 text-sm text-muted-foreground">Belum ada komentar.</p>{/if}
        </section>

        <details class="group border-t border-border/70 py-5">
            <summary class="flex cursor-pointer list-none items-center justify-between gap-3"><span class="flex items-center gap-2 text-sm font-semibold"><span>Aktivitas</span><span class="font-normal text-muted-foreground">{activities.length}</span></span><ChevronDown class="size-4 text-muted-foreground transition-transform group-open:rotate-180" aria-hidden="true" /></summary>
            {#if activities.length > 0}<ol class="mt-4 ml-3 border-l border-border/70">{#each activities as activity (activity.id)}<li class="relative py-2 pl-5 first:pt-0 last:pb-0"><span class="absolute -left-[5px] top-3 size-2 rounded-full border-2 border-background bg-muted-foreground/60" aria-hidden="true"></span><p class="text-sm leading-5 text-foreground/90">{activity.description ?? activity.action}</p><div class="mt-1 flex items-center gap-2 text-xs text-muted-foreground"><UserAvatar user={activity.actor} size="size-4" label={false} /><span>{activity.actor?.name ?? 'Sistem'}</span><span aria-hidden="true">·</span><span>{formatDateTime(activity.created_at, workspaceTimezone)}</span></div></li>{/each}</ol>{:else}<p class="mt-3 text-sm text-muted-foreground">Belum ada aktivitas.</p>{/if}
        </details>
    </main>
</div>

<Dialog bind:open={previewOpen}>
    <DialogContent class="max-h-[92vh] max-w-5xl overflow-hidden border-border bg-background p-0 shadow-lg">
        {#if previewAttachment}
            <div class="flex items-center justify-between gap-4 border-b border-border/70 px-5 py-4 sm:px-6">
                <div class="min-w-0">
                    <DialogTitle class="truncate text-base font-semibold">{previewAttachment.original_name}</DialogTitle>
                    <DialogDescription class="mt-1 text-xs">{previewAttachment.mime_type} · {formatSize(previewAttachment.size)}</DialogDescription>
                </div>
                <a href={toUrl(ProjectAttachmentController.download({ workspace: workspace.id, project: project.id, attachment: previewAttachment.id }))} class="inline-flex h-8 shrink-0 items-center gap-1.5 rounded-md border border-border/80 px-2.5 text-xs font-medium hover:bg-muted"><Download class="size-3.5" aria-hidden="true" />Unduh</a>
            </div>
            <div class="flex min-h-[24rem] items-center justify-center bg-muted/20 p-4 sm:min-h-[32rem] sm:p-6">
                {#if previewAttachment.mime_type.startsWith('image/')}
                    <img src={toUrl(ProjectAttachmentController.preview({ workspace: workspace.id, project: project.id, attachment: previewAttachment.id }))} alt={previewAttachment.original_name} class="max-h-[68vh] max-w-full rounded-md object-contain shadow-sm" />
                {:else if previewAttachment.mime_type === 'application/pdf' || previewAttachment.mime_type.startsWith('text/')}
                    <iframe src={toUrl(ProjectAttachmentController.preview({ workspace: workspace.id, project: project.id, attachment: previewAttachment.id }))} title={`Preview ${previewAttachment.original_name}`} class="h-[68vh] w-full rounded-md border border-border/70 bg-background"></iframe>
                {:else}
                    <div class="max-w-sm text-center"><Document class="mx-auto size-10 text-muted-foreground" aria-hidden="true" /><p class="mt-3 text-sm font-medium">Preview belum tersedia</p><p class="mt-1 text-xs leading-5 text-muted-foreground">Format ini belum dapat ditampilkan langsung. Unduh berkas untuk membukanya.</p></div>
                {/if}
            </div>
        {/if}
    </DialogContent>
</Dialog>
