<script module lang="ts">
    import AdminManagementController from '@/actions/App/Http/Controllers/AdminManagementController';

    export const layout = {
        breadcrumbs: [{ title: 'Pengaturan', href: AdminManagementController.url('users') }],
    };
</script>

<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import { createTable, tableFeatures } from '@tanstack/svelte-table';
    import type { ColumnDef } from '@tanstack/svelte-table';
    import AppHead from '@/components/AppHead.svelte';
    import PageHeader from '@/components/PageHeader.svelte';
    import InputError from '@/components/InputError.svelte';
    import DataTable from '@/components/DataTable.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import AdminUserRoleController from '@/actions/App/Http/Controllers/AdminUserRoleController';
    import UnitController from '@/actions/App/Http/Controllers/UnitController';

    type User = { id: number; name: string; email: string; system_role: 'admin' | 'user' | 'auditor'; created_at: string };
    type Unit = { id: string; name: string; workspace: { id: string; name: string } | null; memberships_count: number; projects_count: number };
    type AuditLog = { id: string; action: string; created_at: string; actor: { id: number; name: string } | null; workspace: { id: string; name: string } | null };

    let { section, users = [], units = [], auditLogs = [], workspaceId = null }: { section: string; users?: User[]; units?: Unit[]; auditLogs?: AuditLog[]; workspaceId?: string | null } = $props();
    const userTableFeatures = tableFeatures({});
    const userTable = createTable({
        features: userTableFeatures,
        columns: [
            { accessorKey: 'name', header: 'User' },
            { accessorKey: 'system_role', header: 'Role login' },
            { id: 'actions', header: 'Aksi' },
        ] satisfies ColumnDef<typeof userTableFeatures, User>[],
        get data() { return users; },
        getRowId: (user) => String(user.id),
    });
    const sections = [
        { key: 'users', label: 'Manajemen User' },
        { key: 'units', label: 'Manajemen Unit' },
        { key: 'roles', label: 'Role & Akses' },
        { key: 'audit-log', label: 'Audit Log' },
        { key: 'system', label: 'Pengaturan Sistem' },
    ];
</script>

<AppHead title={sections.find((item) => item.key === section)?.label ?? 'Pengaturan'} />

<div class="flex min-w-0 flex-col gap-5 px-4 py-5 sm:px-6 lg:px-8">
    <PageHeader title={sections.find((item) => item.key === section)?.label ?? 'Pengaturan'} description="Kelola struktur akses dan tata kelola ithub." />

    <div class="min-w-0">
        <section class="min-w-0 rounded-lg border border-border/80 bg-card p-4 sm:p-6">
            {#if section === 'users'}
                <div class="mb-5"><h2 class="text-base font-semibold">Akun dan role login</h2><p class="mt-1 text-sm text-muted-foreground">Tentukan siapa yang dapat mengelola sistem dan siapa yang dapat melakukan audit.</p></div>
                <DataTable table={userTable} caption="Daftar User" tableClass="w-full min-w-[42rem] text-left text-sm" wrapperClass="rounded-md border">
                    {#snippet children(row)}
                        {@const user = row.original}
                        <tr>
                            <td class="px-4 py-3"><div class="font-medium">{user.name}</div><div class="text-xs text-muted-foreground">{user.email}</div></td>
                            <td class="px-4 py-3 text-sm text-muted-foreground">{user.system_role === 'admin' ? 'Admin' : user.system_role === 'auditor' ? 'Auditor' : 'User'}</td>
                            <td class="px-4 py-3 text-right"><Form {...AdminUserRoleController.form(user.id)} class="inline-flex items-center gap-2">{#snippet children({ processing, errors })}<select name="system_role" value={user.system_role} class="h-10 rounded-md border border-input bg-background px-3 text-sm" disabled={processing}><option value="admin">Admin</option><option value="user">User</option><option value="auditor">Auditor</option></select><Button type="submit" variant="outline" class="min-h-10" disabled={processing}>Simpan</Button><InputError message={errors.system_role} />{/snippet}</Form></td>
                        </tr>
                    {/snippet}
                </DataTable>
            {:else if section === 'units'}
                <div class="mb-5"><h2 class="text-base font-semibold">Struktur Unit</h2><p class="mt-1 text-sm text-muted-foreground">Unit adalah pengelompokan organisasi utama ithub.</p></div>
                {#if workspaceId}
                    <Form {...UnitController.store.form(workspaceId)} class="mb-5 flex flex-col gap-3 rounded-md border bg-muted/20 p-4 sm:flex-row sm:items-end">
                        {#snippet children({ errors, processing })}
                            <div class="grid flex-1 gap-2"><Label for="new-unit-name">Nama Unit baru</Label><Input id="new-unit-name" name="name" placeholder="Misalnya Service Desk" required maxlength={80} /><InputError message={errors.name} /></div>
                            <Button type="submit" class="min-h-11" disabled={processing}>Tambah Unit</Button>
                        {/snippet}
                    </Form>
                {/if}
                {#if units.length === 0}
                    <div class="rounded-md border border-dashed px-4 py-10 text-center text-sm text-muted-foreground">Belum ada Unit.</div>
                {:else}
                    <div class="grid gap-3">
                        {#each units as unit (unit.id)}
                            <div class="rounded-md border px-4 py-3">
                                <div class="flex flex-wrap items-center justify-between gap-4">
                                    <div><p class="font-medium">{unit.name}</p><p class="text-xs text-muted-foreground">{unit.memberships_count} anggota · {unit.projects_count} Project · {unit.workspace?.name ?? 'Internal'}</p></div>
                                </div>
                                {#if unit.workspace}
                                    <Form {...UnitController.update.form({ workspace: unit.workspace.id, unit: unit.id })} class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-end">
                                        {#snippet children({ errors, processing })}
                                            <div class="grid flex-1 gap-2"><Label for={`unit-${unit.id}`}>Ubah nama Unit</Label><Input id={`unit-${unit.id}`} name="name" value={unit.name} required maxlength={80} /><InputError message={errors.name} /></div>
                                            <Button type="submit" variant="outline" class="min-h-11" disabled={processing}>Simpan nama</Button>
                                        {/snippet}
                                    </Form>
                                {/if}
                            </div>
                        {/each}
                    </div>
                {/if}
            {:else if section === 'roles'}
                <div class="mb-5"><h2 class="text-base font-semibold">Role & Akses</h2><p class="mt-1 text-sm text-muted-foreground">Role login bersifat global. Role Project tetap ditentukan di dalam Project.</p></div>
                <div class="grid gap-3">{#each [{ name: 'Admin', description: 'Mengelola User, Unit, membership, permission, dan konfigurasi sistem.' }, { name: 'User', description: 'Bekerja berdasarkan akses Unit dan role Project.' }, { name: 'Auditor', description: 'Read-only untuk pemeriksaan, histori perubahan, dan bukti audit.' }] as role}<div class="rounded-md border px-4 py-3"><p class="font-medium">{role.name}</p><p class="mt-1 text-sm text-muted-foreground">{role.description}</p></div>{/each}</div>
            {:else if section === 'audit-log'}
                <div class="mb-5"><h2 class="text-base font-semibold">Audit Log</h2><p class="mt-1 text-sm text-muted-foreground">Perubahan penting yang dapat ditelusuri untuk kebutuhan tata kelola dan audit.</p></div>
                {#if auditLogs.length === 0}<div class="rounded-md border border-dashed px-4 py-10 text-center text-sm text-muted-foreground">Belum ada Audit Log.</div>{:else}<div class="grid gap-3">{#each auditLogs as log (log.id)}<div class="flex flex-wrap items-start justify-between gap-3 rounded-md border px-4 py-3"><div><p class="font-medium">{log.action}</p><p class="mt-1 text-sm text-muted-foreground">{log.actor?.name ?? 'Sistem'} · {log.workspace?.name ?? 'Global'}</p></div><time class="text-xs text-muted-foreground">{log.created_at}</time></div>{/each}</div>{/if}
            {:else}
                <div class="mb-5"><h2 class="text-base font-semibold">Pengaturan Sistem</h2><p class="mt-1 text-sm text-muted-foreground">Konfigurasi global akan dikelola dari area ini.</p></div>
                <div class="rounded-md border border-dashed px-4 py-10 text-center text-sm text-muted-foreground">Belum ada konfigurasi sistem yang perlu diubah.</div>
            {/if}
        </section>
    </div>
</div>
