<script module lang="ts">
    import { show } from '@/routes/workspaces/projects/settings';
    export const layout = { breadcrumbs: [{ title: 'Pengaturan Project', href: show(['', '']) }] };
</script>

<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import Trash2 from '@lucide/svelte/icons/trash-2';
    import ProjectMemberController from '@/actions/App/Http/Controllers/ProjectMemberController';
    import ProjectSettingsController from '@/actions/App/Http/Controllers/ProjectSettingsController';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import type { ProjectMember, ProjectOption } from '@/types';

    type Workspace = { id: string; name: string };
    type Person = { id: number; name: string; email: string };
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

    let {
        workspace,
        project,
        units,
        members,
        projectMembers,
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
        roles: ProjectOption[];
        statuses: ProjectOption[];
        visibilities: ProjectOption[];
        canManage: boolean;
        canRestore: boolean;
    } = $props();

    function confirmRemoval(event: MouseEvent, name: string): void {
        if (!window.confirm(`Hapus ${name} dari Project?`)) {
            event.preventDefault();
        }
    }
</script>

<AppHead title={`Pengaturan · ${project.identifier}`} />

<div class="flex min-w-0 flex-col gap-8 px-4 py-6 sm:px-6 lg:px-8">
    <Heading title={`Pengaturan ${project.name}`} description="Kelola konfigurasi, akses, dan anggota Project." />

    {#if project.archived}
        <div class="rounded-md border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-100" role="status">
            Project ini diarsipkan dan pengaturan bersifat read-only.
        </div>
    {/if}

    <div class="max-w-4xl space-y-8">
        <section class="rounded-lg border bg-card p-5 sm:p-6">
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
                                    <select id="project-unit" name="unit_id" value={project.unit_id} class="h-11 rounded-md border border-input bg-background px-3 text-sm" required>
                                        {#each units as unit (unit.id)}<option value={unit.id}>{unit.name}</option>{/each}
                                    </select>
                                    <InputError message={errors.unit_id} />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="project-visibility">Visibility</Label>
                                    <select id="project-visibility" name="visibility" value={project.visibility} class="h-11 rounded-md border border-input bg-background px-3 text-sm" required>
                                        {#each visibilities as visibility}<option value={visibility.value}>{visibility.label}</option>{/each}
                                    </select>
                                    <InputError message={errors.visibility} />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="project-status">Status</Label>
                                    <select id="project-status" name="status" value={project.status} class="h-11 rounded-md border border-input bg-background px-3 text-sm" required>
                                        {#each statuses as status}<option value={status.value}>{status.label}</option>{/each}
                                    </select>
                                    <InputError message={errors.status} />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="project-lead">Project Lead</Label>
                                    <select id="project-lead" name="project_lead_id" value={project.project_lead_id ?? ''} class="h-11 rounded-md border border-input bg-background px-3 text-sm" required>
                                        {#each members as member (member.id)}<option value={member.id}>{member.name}</option>{/each}
                                    </select>
                                    <InputError message={errors.project_lead_id} />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="default-assignee">Default Assignee</Label>
                                    <select id="default-assignee" name="default_assignee_id" value={project.default_assignee_id ?? ''} class="h-11 rounded-md border border-input bg-background px-3 text-sm">
                                        <option value="">Tidak ada</option>
                                        {#each members as member (member.id)}<option value={member.id}>{member.name}</option>{/each}
                                    </select>
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

        <section class="rounded-lg border bg-card p-5 sm:p-6">
            <h2 class="text-base font-semibold">Anggota Project</h2>
            <p class="mt-1 text-sm text-muted-foreground">Role Project berbeda dari role Workspace.</p>
            {#if canManage}
                <Form {...ProjectMemberController.store.form({ workspace: workspace.id, project: project.id })} class="mt-5 grid gap-4 rounded-md border bg-muted/20 p-4 sm:grid-cols-[minmax(0,1fr)_12rem_auto] sm:items-end">
                    {#snippet children({ errors, processing })}
                        <div class="grid gap-2">
                            <Label for="member-user">User</Label>
                            <select id="member-user" name="user_id" class="h-11 rounded-md border border-input bg-background px-3 text-sm" required>
                                <option value="">Pilih anggota Workspace</option>
                                {#each members as member (member.id)}<option value={member.id}>{member.name} · {member.email}</option>{/each}
                            </select>
                            <InputError message={errors.user_id} />
                        </div>
                        <div class="grid gap-2">
                            <Label for="member-role">Role</Label>
                            <select id="member-role" name="role" class="h-11 rounded-md border border-input bg-background px-3 text-sm" required>
                                {#each roles as role}<option value={role.value}>{role.label}</option>{/each}
                            </select>
                            <InputError message={errors.role} />
                        </div>
                        <Button type="submit" class="min-h-11" disabled={processing}>Tambah</Button>
                    {/snippet}
                </Form>
            {/if}

            <div class="mt-5 overflow-x-auto rounded-md border">
                <table class="w-full min-w-[42rem] text-left text-sm">
                    <caption class="sr-only">Daftar anggota Project</caption>
                    <thead class="bg-muted/40 text-xs text-muted-foreground"><tr><th class="px-4 py-3 font-medium">Anggota</th><th class="px-4 py-3 font-medium">Role</th><th class="px-4 py-3 text-right font-medium">Aksi</th></tr></thead>
                    <tbody class="divide-y">
                        {#each projectMembers as member (member.id)}
                            <tr>
                                <td class="px-4 py-3"><div class="font-medium">{member.user.name}</div><div class="text-xs text-muted-foreground">{member.user.email}</div></td>
                                <td class="px-4 py-3">
                                    {#if canManage}
                                        <Form {...ProjectMemberController.update.form({ workspace: workspace.id, project: project.id, membership: member.id })} class="flex items-center gap-2">
                                            {#snippet children({ errors, processing })}
                                                <select name="role" value={member.role} class="h-10 rounded-md border border-input bg-background px-3 text-sm" disabled={processing}>{#each roles as role}<option value={role.value}>{role.label}</option>{/each}</select>
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
                        {/each}
                    </tbody>
                </table>
            </div>
        </section>

        {#if canRestore}
            <p class="text-sm text-muted-foreground">Project dapat dipulihkan dari halaman overview ketika sudah siap digunakan kembali.</p>
        {/if}
    </div>
</div>
