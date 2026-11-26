<script module lang="ts">
    export const layout = { breadcrumbs: [{ title: 'Pengaturan Project' }] };
</script>

<script lang="ts">
    import { Form, Link, page } from '@inertiajs/svelte';
    import { createTable, tableFeatures } from '@tanstack/svelte-table';
    import type { ColumnDef } from '@tanstack/svelte-table';
    import Trash2 from 'carbon-icons-svelte/lib/TrashCan.svelte';
    import ProjectMemberController from '@/actions/App/Http/Controllers/ProjectMemberController';
    import ProjectController from '@/actions/App/Http/Controllers/ProjectController';
    import ProjectSettingsController from '@/actions/App/Http/Controllers/ProjectSettingsController';
    import AppHead from '@/components/AppHead.svelte';
    import DataTable from '@/components/DataTable.svelte';
    import PageHeader from '@/components/PageHeader.svelte';
    import InputError from '@/components/InputError.svelte';
    import UserAvatar from '@/components/UserAvatar.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Select, SelectContent, SelectItem, SelectTrigger } from '@/components/ui/select';
    import { formatDateTime } from '@/lib/format-date';
    import { projectBreadcrumbs } from '@/lib/page-header';
    import { toUrl } from '@/lib/utils';
    import type { ProjectMember, ProjectOption } from '@/types';

    type Workspace = { id: string; name: string };
    type Person = { id: number; name: string; email: string; avatar?: string | null };
    type Unit = { id: string; name: string };
    type Project = {
        id: string;
        name: string;
        identifier: string;
        description: string | null;
        unit_id: string;
        project_lead_id: number | null;
        default_assignee_id: number | null;
        start_date: string | null;
        target_date: string | null;
        visibility: string;
        status: string;
        archived: boolean;
    };
    type AuditLog = { id: string; action: string; created_at: string; actor: { id: number; name: string } | null };

    let {
        workspace,
        project,
        units,
        members,
        projectMembers,
        auditLogs,
        canViewAudit,
        roles,
        statuses,
        visibilities,
        canManage,
        canRestore,
    }: {
        workspace: Workspace;
        project: Project;
        units: Unit[];
        members: Person[];
        projectMembers: ProjectMember[];
        auditLogs: AuditLog[];
        canViewAudit: boolean;
        roles: ProjectOption[];
        statuses: ProjectOption[];
        visibilities: ProjectOption[];
        canManage: boolean;
        canRestore: boolean;
    } = $props();
    const workspaceTimezone = $derived(page.props.workspaceContext?.active?.timezone);
    let selectedUnit = $state('');
    let selectedVisibility = $state('');
    let selectedStatus = $state('');
    let selectedLead = $state('');
    let selectedAssignee = $state('');
    let newProjectMember = $state('');
    let newProjectMemberRole = $state('');
    let projectMemberRoles = $state<Record<string, string>>({});
    let settingsInitialized = false;
    $effect(() => {
        if (settingsInitialized) return;
        selectedUnit = project.unit_id;
        selectedVisibility = project.visibility;
        selectedStatus = project.status;
        selectedLead = project.project_lead_id ? String(project.project_lead_id) : '';
        selectedAssignee = project.default_assignee_id ? String(project.default_assignee_id) : '';
        newProjectMemberRole = roles[0]?.value ?? '';
        settingsInitialized = true;
    });
    const selectedLeadMember = $derived(members.find((member) => String(member.id) === selectedLead));
    const selectedAssigneeMember = $derived(members.find((member) => String(member.id) === selectedAssignee));

    function projectMemberRole(member: ProjectMember): string {
        return projectMemberRoles[member.id] ?? member.role;
    }
    const projectMemberFeatures = tableFeatures({});
    const projectMemberColumns: ColumnDef<typeof projectMemberFeatures, ProjectMember>[] = [
        { accessorFn: (member) => member.user.name, id: 'member', header: 'Anggota' },
        { accessorKey: 'role_label', header: 'Role' },
        { id: 'actions', header: 'Aksi' },
    ];
    const projectMemberTable = createTable({
        features: projectMemberFeatures,
        columns: projectMemberColumns,
        get data() { return projectMembers; },
        getRowId: (member) => member.id,
    });

    function confirmRemoval(event: MouseEvent, name: string): void {
        if (!window.confirm(`Hapus ${name} dari Project?`)) {
            event.preventDefault();
        }
    }

    function memberHeaderClass(headerId: string): string {
        return headerId === 'actions' ? 'px-4 py-3 text-right font-medium' : 'px-4 py-3 font-medium';
    }
</script>

<AppHead title={`Pengaturan · ${project.identifier}`} />

<div class="flex min-w-0 flex-col gap-5 px-4 py-5 sm:px-6 lg:px-8">
    <PageHeader breadcrumbs={projectBreadcrumbs(page.props.unitContext?.active?.name ?? workspace.name, page.props.unitContext?.active?.id, toUrl(ProjectController.index(workspace.id)), project.name, toUrl(ProjectController.show({ workspace: workspace.id, project: project.id })), 'settings', undefined, page.props.projectContext?.active?.icon)}>
        {#snippet actions()}
            <Button variant="outline" class="h-7 shrink-0 gap-1.5 border-border/80 bg-background px-2.5 text-xs text-muted-foreground" asChild>
                {#snippet children(props)}
                    <Link href={toUrl(ProjectController.show({ workspace: workspace.id, project: project.id }))} {...props}>Lihat Project</Link>
                {/snippet}
            </Button>
        {/snippet}
    </PageHeader>

    {#if project.archived}
        <div class="rounded-md border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-100" role="status">
            Project ini diarsipkan dan pengaturan bersifat read-only.
        </div>
    {/if}

    <div class="max-w-4xl space-y-5">
        <section class="rounded-lg border border-border/70 bg-card p-4 sm:p-5">
            <h2 class="text-base font-semibold">General</h2>
            <p class="mt-1 text-sm text-muted-foreground">Project ID tidak dapat diubah setelah Project dibuat.</p>
            <div class="mt-5 grid gap-5">
                <div class="grid gap-2">
                    <Label for="project-id">Project ID</Label>
                    <Input id="project-id" value={project.identifier} disabled />
                </div>

                {#if canManage}
                    <Form {...ProjectSettingsController.update.form({ workspace: workspace.id, project: project.id })} class="grid gap-5">
                        {#snippet children({ errors, processing })}
                            <div class="grid gap-2">
                                <Label for="project-name">Nama Project</Label>
                                <Input id="project-name" name="name" value={project.name} required maxlength={120} />
                                <InputError message={errors.name} />
                            </div>
                            <div class="grid gap-2">
                                <Label for="project-description">Tujuan atau deskripsi</Label>
                                <textarea id="project-description" name="description" rows={4} class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:ring-2 focus-visible:ring-ring">{project.description ?? ''}</textarea>
                                <InputError message={errors.description} />
                            </div>
                            <div class="grid gap-5 sm:grid-cols-2">
                                <div class="grid gap-2">
                                    <Label for="project-unit">Unit utama</Label>
                                    <Select value={selectedUnit} onValueChange={(value) => { selectedUnit = value; }}><SelectTrigger id="project-unit" class="h-8 w-full rounded-md border border-border/70 bg-surface-2/70 px-3 text-xs shadow-none focus:ring-2 focus:ring-ring"><span>{units.find((unit) => unit.id === selectedUnit)?.name ?? 'Pilih Unit'}</span></SelectTrigger><SelectContent>{#each units as unit (unit.id)}<SelectItem value={unit.id}>{unit.name}</SelectItem>{/each}</SelectContent></Select><input type="hidden" name="unit_id" value={selectedUnit} required />
                                    <InputError message={errors.unit_id} />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="project-visibility">Visibility</Label>
                                    <Select value={selectedVisibility} onValueChange={(value) => { selectedVisibility = value; }}><SelectTrigger id="project-visibility" class="h-8 w-full rounded-md border border-border/70 bg-surface-2/70 px-3 text-xs shadow-none focus:ring-2 focus:ring-ring"><span>{visibilities.find((visibility) => visibility.value === selectedVisibility)?.label ?? 'Pilih visibility'}</span></SelectTrigger><SelectContent>{#each visibilities as visibility}<SelectItem value={visibility.value}>{visibility.label}</SelectItem>{/each}</SelectContent></Select><input type="hidden" name="visibility" value={selectedVisibility} required />
                                    <InputError message={errors.visibility} />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="project-status">Status</Label>
                                    <Select value={selectedStatus} onValueChange={(value) => { selectedStatus = value; }}><SelectTrigger id="project-status" class="h-8 w-full rounded-md border border-border/70 bg-surface-2/70 px-3 text-xs shadow-none focus:ring-2 focus:ring-ring"><span>{statuses.find((status) => status.value === selectedStatus)?.label ?? 'Pilih status'}</span></SelectTrigger><SelectContent>{#each statuses as status}<SelectItem value={status.value}>{status.label}</SelectItem>{/each}</SelectContent></Select><input type="hidden" name="status" value={selectedStatus} required />
                                    <InputError message={errors.status} />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="project-lead">Project Lead</Label>
                                    <Select value={selectedLead} onValueChange={(value) => { selectedLead = value; }}><SelectTrigger id="project-lead" class="h-8 w-full rounded-md border border-border/70 bg-surface-2/70 px-3 text-xs shadow-none focus:ring-2 focus:ring-ring"><span class="flex min-w-0 items-center gap-1.5">{#if selectedLeadMember}<UserAvatar user={selectedLeadMember} size="size-4" label={false} /><span class="truncate">{selectedLeadMember.name}</span>{:else}<span class="text-muted-foreground">Pilih Project Lead</span>{/if}</span></SelectTrigger><SelectContent>{#each members as member (member.id)}<SelectItem value={String(member.id)}><span class="inline-flex items-center gap-1.5"><UserAvatar user={member} size="size-4" label={false} />{member.name}</span></SelectItem>{/each}</SelectContent></Select><input type="hidden" name="project_lead_id" value={selectedLead} required />
                                    <InputError message={errors.project_lead_id} />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="default-assignee">PIC default</Label>
                                    <Select value={selectedAssignee} onValueChange={(value) => { selectedAssignee = value; }}><SelectTrigger id="default-assignee" class="h-8 w-full rounded-md border border-border/70 bg-surface-2/70 px-3 text-xs shadow-none focus:ring-2 focus:ring-ring"><span class="flex min-w-0 items-center gap-1.5">{#if selectedAssigneeMember}<UserAvatar user={selectedAssigneeMember} size="size-4" label={false} /><span class="truncate">{selectedAssigneeMember.name}</span>{:else}<span class="text-muted-foreground">Tidak ada PIC</span>{/if}</span></SelectTrigger><SelectContent><SelectItem value="">Tidak ada PIC</SelectItem>{#each members as member (member.id)}<SelectItem value={String(member.id)}><span class="inline-flex items-center gap-1.5"><UserAvatar user={member} size="size-4" label={false} />{member.name}</span></SelectItem>{/each}</SelectContent></Select><input type="hidden" name="default_assignee_id" value={selectedAssignee} />
                                    <InputError message={errors.default_assignee_id} />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="start-date">Tanggal mulai</Label>
                                    <Input id="start-date" name="start_date" type="date" value={project.start_date ?? ''} />
                                    <InputError message={errors.start_date} />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="target-date">Target selesai</Label>
                                    <Input id="target-date" name="target_date" type="date" value={project.target_date ?? ''} />
                                    <InputError message={errors.target_date} />
                                </div>
                            </div>
                            <div class="flex justify-end"><Button type="submit" class="min-h-11" disabled={processing}>{processing ? 'Menyimpan...' : 'Simpan perubahan'}</Button></div>
                        {/snippet}
                    </Form>
                {/if}
            </div>
        </section>

    {#if canViewAudit}
        <section class="rounded-lg border border-border/70 bg-card p-4 sm:p-5">
            <h2 class="text-base font-semibold">Audit Log</h2>
            <p class="mt-1 text-sm text-muted-foreground">Perubahan penting Project dan membership.</p>
            {#if auditLogs.length === 0}<p class="mt-5 text-sm text-muted-foreground">Belum ada Audit Log.</p>{:else}<ol class="mt-5 grid gap-3 border-l pl-4">{#each auditLogs as log (log.id)}<li><p class="text-sm font-medium">{log.action}</p><p class="mt-1 text-xs text-muted-foreground">{log.actor?.name ?? 'Sistem'} · {formatDateTime(log.created_at, workspaceTimezone)}</p></li>{/each}</ol>{/if}
        </section>
    {/if}

        <section class="rounded-lg border border-border/70 bg-card p-4 sm:p-5">
            <h2 class="text-base font-semibold">Anggota Project</h2>
            <p class="mt-1 text-sm text-muted-foreground">Role Project berbeda dari role Workspace.</p>
            {#if canManage}
                <Form {...ProjectMemberController.store.form({ workspace: workspace.id, project: project.id })} class="mt-5 grid gap-4 rounded-md border bg-muted/20 p-4 sm:grid-cols-[minmax(0,1fr)_12rem_auto] sm:items-end">
                    {#snippet children({ errors, processing })}
                        <div class="grid gap-2">
                            <Label for="member-user">User</Label>
                            <Select value={newProjectMember} onValueChange={(value) => { newProjectMember = value; }}>
                                <SelectTrigger id="member-user" class="h-9 w-full rounded-md border border-border/70 bg-surface-2/70 px-3 text-sm shadow-none"><span class="flex min-w-0 items-center gap-2">{#if newProjectMember}{@const selectedMember = members.find((member) => String(member.id) === newProjectMember)}{#if selectedMember}<UserAvatar user={selectedMember} size="size-5" label={false} /><span class="truncate">{selectedMember.name}</span>{/if}{:else}<span class="text-muted-foreground">Pilih anggota Workspace</span>{/if}</span></SelectTrigger>
                                <SelectContent>{#each members as member (member.id)}<SelectItem value={String(member.id)}><span class="inline-flex items-center gap-2"><UserAvatar user={member} size="size-5" label={false} />{member.name}</span></SelectItem>{/each}</SelectContent>
                            </Select>
                            <input type="hidden" name="user_id" value={newProjectMember} required />
                            <InputError message={errors.user_id} />
                        </div>
                        <div class="grid gap-2">
                            <Label for="member-role">Role</Label>
                            <Select value={newProjectMemberRole} onValueChange={(value) => { newProjectMemberRole = value; }}>
                                <SelectTrigger id="member-role" class="h-9 w-full rounded-md border border-border/70 bg-surface-2/70 px-3 text-sm shadow-none"><span>{roles.find((role) => role.value === newProjectMemberRole)?.label ?? 'Pilih role'}</span></SelectTrigger>
                                <SelectContent>{#each roles as role}<SelectItem value={role.value}>{role.label}</SelectItem>{/each}</SelectContent>
                            </Select>
                            <input type="hidden" name="role" value={newProjectMemberRole} required />
                            <InputError message={errors.role} />
                        </div>
                        <Button type="submit" class="min-h-11" disabled={processing}>Tambah</Button>
                    {/snippet}
                </Form>
            {/if}

            <DataTable table={projectMemberTable} caption="Daftar anggota Project" tableClass="w-full min-w-[42rem] text-left text-sm" wrapperClass="mt-5" getHeaderClass={memberHeaderClass}>
                {#snippet children(row)}
                    {@const member = row.original}
                    <tr>
                                <td class="px-4 py-3"><div class="flex items-center gap-2.5"><UserAvatar user={member.user} size="size-6" label={false} /><div class="min-w-0"><div class="truncate font-medium">{member.user.name}</div><div class="truncate text-xs text-muted-foreground">{member.user.email}</div></div></div></td>
                                <td class="px-4 py-3">
                                    {#if canManage}
                                        <Form {...ProjectMemberController.update.form({ workspace: workspace.id, project: project.id, membership: member.id })} class="flex items-center gap-2">
                                            {#snippet children({ errors, processing })}
                                                <Select value={projectMemberRole(member)} onValueChange={(value) => { projectMemberRoles[member.id] = value; }}>
                                                    <SelectTrigger class="h-8 w-36 rounded-md border border-border/70 bg-surface-2/70 px-3 text-xs shadow-none" disabled={processing}><span>{roles.find((role) => role.value === projectMemberRole(member))?.label ?? member.role_label}</span></SelectTrigger>
                                                    <SelectContent>{#each roles as role}<SelectItem value={role.value}>{role.label}</SelectItem>{/each}</SelectContent>
                                                </Select>
                                                <input type="hidden" name="role" value={projectMemberRole(member)} />
                                                <Button type="submit" variant="outline" class="min-h-10" disabled={processing}>Simpan</Button>
                                                <InputError message={errors.role} />
                                            {/snippet}
                                        </Form>
                                    {:else}<span>{member.role_label}</span>{/if}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    {#if canManage}
                                        <Form {...ProjectMemberController.destroy.form({ workspace: workspace.id, project: project.id, membership: member.id })} options={{ preserveScroll: true }}>
                                            {#snippet children({ processing })}
                                                <Button type="submit" variant="ghost" size="icon" class="min-h-10 min-w-10 text-destructive hover:text-destructive" disabled={processing} aria-label={`Hapus ${member.user.name}`} onclick={(event) => confirmRemoval(event, member.user.name)}><Trash2 class="size-4" aria-hidden="true" /></Button>
                                            {/snippet}
                                        </Form>
                                    {/if}
                                </td>
                    </tr>
                {/snippet}
            </DataTable>
        </section>

        {#if canRestore}
            <p class="text-sm text-muted-foreground">Project dapat dipulihkan dari halaman overview ketika sudah siap digunakan kembali.</p>
        {/if}
    </div>
</div>
