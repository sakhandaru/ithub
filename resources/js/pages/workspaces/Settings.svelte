<script module lang="ts">
    export const layout = {
        breadcrumbs: [{ title: 'Pengaturan Workspace' }],
    };
</script>

<script lang="ts">
    import { Form, Link } from '@inertiajs/svelte';
    import { createTable, tableFeatures } from '@tanstack/svelte-table';
    import type { ColumnDef } from '@tanstack/svelte-table';
    import ChevronDown from 'carbon-icons-svelte/lib/ChevronDown.svelte';
    import Trash2 from 'carbon-icons-svelte/lib/TrashCan.svelte';
    import UnitController from '@/actions/App/Http/Controllers/UnitController';
    import UnitMemberController from '@/actions/App/Http/Controllers/UnitMemberController';
    import WorkspaceMemberController from '@/actions/App/Http/Controllers/WorkspaceMemberController';
    import WorkspaceSettingsActions from '@/actions/App/Http/Controllers/WorkspaceSettingsController';
    import AppHead from '@/components/AppHead.svelte';
    import DataTable from '@/components/DataTable.svelte';
    import PageHeader from '@/components/PageHeader.svelte';
    import InputError from '@/components/InputError.svelte';
    import UserAvatar from '@/components/UserAvatar.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Select, SelectContent, SelectItem, SelectTrigger } from '@/components/ui/select';
    import { toUrl } from '@/lib/utils';
    import { dashboard } from '@/routes';
    import type { WorkspaceMember, WorkspaceRoleOption, WorkspaceUnit } from '@/types';

    type Workspace = { id: string; name: string; timezone: string };
    type SettingsSection = 'general' | 'members' | 'units' | 'access';

    let {
        workspace,
        section = 'general',
        memberships = [],
        units = [],
        roles = [],
        timezones = [],
    }: {
        workspace: Workspace;
        section?: SettingsSection;
        memberships: WorkspaceMember[];
        units: WorkspaceUnit[];
        roles: WorkspaceRoleOption[];
        timezones: string[];
        canAssignOwner?: boolean;
    } = $props();

    const activeSection = $derived(section as SettingsSection);
    const settingsUrl = (target: SettingsSection): string => toUrl(WorkspaceSettingsActions.show(workspace.id, { query: { section: target } }));
    const assignableRoles = $derived(roles);
    let selectedTimezone = $state('');
    let newWorkspaceRole = $state('');
    let workspaceMemberRoles = $state<Record<string, string>>({});
    let unitMemberSelections = $state<Record<string, string>>({});
    let settingsInitialized = false;
    $effect(() => {
        if (settingsInitialized) return;
        selectedTimezone = workspace.timezone;
        newWorkspaceRole = roles[0]?.value ?? '';
        settingsInitialized = true;
    });
    const workspaceMemberFeatures = tableFeatures({});
    const workspaceMemberColumns: ColumnDef<typeof workspaceMemberFeatures, WorkspaceMember>[] = [
        { accessorFn: (member) => member.user.name, id: 'member', header: 'Anggota' },
        { accessorKey: 'role_label', header: 'Role' },
        { id: 'actions', header: 'Aksi' },
    ];
    const workspaceMemberTable = createTable({
        features: workspaceMemberFeatures,
        columns: workspaceMemberColumns,
        get data() { return memberships; },
        getRowId: (member) => member.id,
    });

    function confirmRemoval(message: string): boolean {
        return window.confirm(message);
    }

    function memberHeaderClass(headerId: string): string {
        return headerId === 'actions' ? 'px-4 py-3 text-right font-medium' : 'px-4 py-3 font-medium';
    }

    function workspaceMemberRole(member: WorkspaceMember): string {
        return workspaceMemberRoles[member.id] ?? member.role;
    }
</script>

<AppHead title="Pengaturan Workspace" />

<div class="flex min-w-0 flex-col gap-5 px-4 py-5 sm:px-6 lg:px-8">
    <PageHeader
        breadcrumbs={[{ label: workspace.name, href: toUrl(dashboard()) }, { label: 'Setelan' }]}
    >
        {#snippet actions()}
            {#each [{ key: 'members', label: 'Anggota' }, { key: 'units', label: 'Unit' }, { key: 'access', label: 'Akses & role' }] as item}
                <Button variant="outline" class="h-7 shrink-0 border-border/80 bg-background px-2.5 text-xs {activeSection === item.key ? 'text-foreground' : 'text-muted-foreground'}" asChild>
                    {#snippet children(props)}<Link href={settingsUrl(item.key as SettingsSection)} {...props}>{item.label}</Link>{/snippet}
                </Button>
            {/each}
        {/snippet}
    </PageHeader>

    <div class="grid min-w-0 gap-5 xl:grid-cols-[minmax(0,1fr)_20rem]">
        <div class="min-w-0 space-y-5">
            {#if activeSection === 'general'}
            <section id="general" class="rounded-lg border border-border/70 bg-card p-4 sm:p-5">
                <div class="mb-5">
                    <h2 class="text-base font-semibold">Umum</h2>
                    <p class="mt-1 text-sm text-muted-foreground">Identitas dan zona waktu Workspace untuk seluruh anggota.</p>
                </div>

                <Form {...WorkspaceSettingsActions.update.form({ workspace: workspace.id })} class="grid gap-4 border-t border-border/70 pt-5">
                    {#snippet children({ errors, processing })}
                        <div class="grid gap-2">
                            <Label for="workspace-name">Nama Workspace</Label>
                            <Input id="workspace-name" name="name" value={workspace.name} required maxlength={120} />
                            <InputError message={errors.name} />
                        </div>
                        <div class="grid gap-2">
                            <Label for="workspace-timezone">Zona waktu</Label>
                            <Select value={selectedTimezone} onValueChange={(value) => { selectedTimezone = value; }}>
                                <SelectTrigger id="workspace-timezone" class="h-9 w-full rounded-md border border-border/70 bg-surface-2/70 px-3 text-sm shadow-none"><span>{selectedTimezone || 'Pilih zona waktu'}</span></SelectTrigger>
                                <SelectContent>{#each timezones as timezone}<SelectItem value={timezone}>{timezone}</SelectItem>{/each}</SelectContent>
                            </Select>
                            <input type="hidden" name="timezone" value={selectedTimezone} required />
                            <InputError message={errors.timezone} />
                        </div>
                        <div class="flex justify-end border-t border-border/70 pt-4">
                            <Button type="submit" class="min-h-9" disabled={processing}>{processing ? 'Menyimpan...' : 'Simpan perubahan'}</Button>
                        </div>
                    {/snippet}
                </Form>
            </section>
            {/if}

            {#if activeSection === 'members'}
            <section id="members" class="rounded-lg border border-border/70 bg-card p-4 sm:p-5">
                <div class="mb-5">
                    <h2 class="text-base font-semibold">Anggota Workspace</h2>
                    <p class="mt-1 text-sm text-muted-foreground">Tambahkan user yang sudah terdaftar dan atur role mereka.</p>
                </div>

                <Form {...WorkspaceMemberController.store.form(workspace.id)} class="mb-6 grid gap-4 rounded-md border bg-muted/20 p-4 sm:grid-cols-[minmax(0,1fr)_12rem_auto] sm:items-end">
                    {#snippet children({ errors, processing })}
                        <div class="grid gap-2">
                            <Label for="member-email">Email user</Label>
                            <Input id="member-email" name="email" type="email" placeholder="nama@contoh.com" required autocomplete="off" />
                            <InputError message={errors.email} />
                        </div>
                        <div class="grid gap-2">
                            <Label for="member-role">Role</Label>
                            <Select value={newWorkspaceRole} onValueChange={(value) => { newWorkspaceRole = value; }}>
                                <SelectTrigger id="member-role" class="h-9 w-full rounded-md border border-border/70 bg-surface-2/70 px-3 text-sm shadow-none"><span>{assignableRoles.find((role) => role.value === newWorkspaceRole)?.label ?? 'Pilih role'}</span></SelectTrigger>
                                <SelectContent>{#each assignableRoles as role}<SelectItem value={role.value}>{role.label}</SelectItem>{/each}</SelectContent>
                            </Select>
                            <input type="hidden" name="role" value={newWorkspaceRole} required />
                            <InputError message={errors.role} />
                        </div>
                        <Button type="submit" class="min-h-11" disabled={processing}>Tambah</Button>
                    {/snippet}
                </Form>

                <DataTable table={workspaceMemberTable} caption="Daftar anggota Workspace" tableClass="w-full min-w-[42rem] text-left text-sm" wrapperClass="rounded-md border" getHeaderClass={memberHeaderClass}>
                    {#snippet children(row)}
                        {@const member = row.original}
                        <tr>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2.5"><UserAvatar user={member.user} size="size-6" label={false} /><div class="min-w-0"><div class="truncate font-medium">{member.user.name}</div>
                                        <div class="text-xs text-muted-foreground">{member.user.email}</div>
                                        </div></div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <Form {...WorkspaceMemberController.update.form({ workspace: workspace.id, membership: member.id })} class="flex items-center gap-2">
                                            {#snippet children({ errors, processing })}
                                                <Select value={workspaceMemberRole(member)} onValueChange={(value) => { workspaceMemberRoles[member.id] = value; }}>
                                                    <SelectTrigger class="h-8 w-36 rounded-md border border-border/70 bg-surface-2/70 px-3 text-xs shadow-none" disabled={processing}><span>{assignableRoles.find((role) => role.value === workspaceMemberRole(member))?.label ?? member.role_label}</span></SelectTrigger>
                                                    <SelectContent>{#each assignableRoles as role}<SelectItem value={role.value}>{role.label}</SelectItem>{/each}</SelectContent>
                                                </Select>
                                                <input type="hidden" name="role" value={workspaceMemberRole(member)} />
                                                <Button type="submit" variant="outline" class="min-h-10" disabled={processing}>Simpan</Button>
                                                <InputError message={errors.role} />
                                            {/snippet}
                                        </Form>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <Form
                                            {...WorkspaceMemberController.destroy.form({ workspace: workspace.id, membership: member.id })}
                                            options={{ preserveScroll: true }}
                                        >
                                            {#snippet children({ processing })}
                                                <Button type="submit" variant="ghost" size="icon" class="min-h-10 min-w-10 text-destructive hover:text-destructive" disabled={processing} aria-label={`Hapus ${member.user.name}`} onclick={(event) => { if (!confirmRemoval(`Hapus ${member.user.name} dari Unit?`)) event.preventDefault(); }}>
                                                    <Trash2 class="size-4" aria-hidden="true" />
                                                </Button>
                                            {/snippet}
                                        </Form>
                                    </td>
                        </tr>
                    {/snippet}
                </DataTable>
            </section>
            {/if}

            {#if activeSection === 'units'}
            <section id="units" class="rounded-lg border border-border/70 bg-card p-4 sm:p-5">
                <div class="mb-5">
                    <h2 class="text-base font-semibold">Unit dalam Workspace</h2>
                    <p class="mt-1 text-sm text-muted-foreground">Unit menjadi grouping organisasi dan filter untuk pekerjaan berikutnya.</p>
                </div>

                <Form {...UnitController.store.form(workspace.id)} class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end">
                    {#snippet children({ errors, processing })}
                        <div class="grid flex-1 gap-2">
                            <Label for="unit-name">Nama Unit baru</Label>
                            <Input id="unit-name" name="name" placeholder="Misalnya Service Desk" required maxlength={80} />
                            <InputError message={errors.name} />
                        </div>
                        <Button type="submit" class="min-h-11" disabled={processing}>Tambah Unit</Button>
                    {/snippet}
                </Form>

                <div class="space-y-4">
                    {#each units as unit (unit.id)}
                        <details class="group rounded-md border" open>
                            <summary class="flex min-h-12 cursor-pointer list-none items-center gap-3 px-4 py-3 [&::-webkit-details-marker]:hidden">
                                <ChevronDown class="size-4 text-muted-foreground transition-transform group-open:rotate-180" aria-hidden="true" />
                                <span class="font-medium">{unit.name}</span>
                                <span class="text-xs text-muted-foreground">{unit.memberships.length} anggota</span>
                            </summary>
                            <div class="border-t px-4 py-4">
                                <Form {...UnitController.update.form({ workspace: workspace.id, unit: unit.id })} class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                                    {#snippet children({ errors, processing })}
                                        <div class="grid flex-1 gap-2">
                                            <Label for={`unit-${unit.id}`}>Nama Unit</Label>
                                            <Input id={`unit-${unit.id}`} name="name" value={unit.name} required maxlength={80} />
                                            <InputError message={errors.name} />
                                        </div>
                                        <Button type="submit" variant="outline" class="min-h-11" disabled={processing}>Simpan nama</Button>
                                    {/snippet}
                                </Form>

                                <div class="space-y-2">
                                    {#each unit.memberships as member (member.id)}
                                        <div class="flex items-center justify-between gap-3 rounded-md bg-muted/30 px-3 py-2 text-sm">
                                            <div class="min-w-0">
                                                <div class="truncate font-medium">{member.user.name}</div>
                                                <div class="truncate text-xs text-muted-foreground">{member.user.email}</div>
                                            </div>
                                            <Form
                                                {...UnitMemberController.destroy.form({ workspace: workspace.id, unit: unit.id, membership: member.id })}
                                                options={{ preserveScroll: true }}
                                            >
                                                {#snippet children({ processing })}
                                                    <Button type="submit" variant="ghost" size="icon" class="min-h-10 min-w-10 text-destructive hover:text-destructive" disabled={processing} aria-label={`Hapus ${member.user.name} dari ${unit.name}`} onclick={(event) => { if (!confirmRemoval(`Hapus ${member.user.name} dari Unit ${unit.name}?`)) event.preventDefault(); }}>
                                                        <Trash2 class="size-4" aria-hidden="true" />
                                                    </Button>
                                                {/snippet}
                                            </Form>
                                        </div>
                                    {/each}
                                </div>

                                <Form {...UnitMemberController.store.form({ workspace: workspace.id, unit: unit.id })} class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                                    {#snippet children({ errors, processing })}
                                        <div class="grid flex-1 gap-2">
                                            <Label for={`member-${unit.id}`}>Tambah anggota Unit</Label>
                                            <select id={`member-${unit.id}`} name="user_id" class="h-11 rounded-md border border-input bg-background px-3 text-sm" required>
                                                <option value="">Pilih anggota Unit</option>
                                                {#each memberships as member (member.id)}
                                                    <option value={member.user.id}>{member.user.name} · {member.user.email}</option>
                                                {/each}
                                            </select>
                                            <InputError message={errors.user_id} />
                                        </div>
                                        <Button type="submit" variant="outline" class="min-h-11" disabled={processing}>Tambah anggota</Button>
                                    {/snippet}
                                </Form>
                            </div>
                        </details>
                    {/each}
                </div>
            </section>
            {/if}

            {#if activeSection === 'access'}
            <section id="access" class="rounded-lg border border-border/70 bg-card p-4 sm:p-5">
                <div class="mb-5">
                    <h2 class="text-base font-semibold">Akses & role</h2>
                    <p class="mt-1 text-sm text-muted-foreground">Role yang tersedia untuk mengatur akses anggota Workspace.</p>
                </div>
                <div class="divide-y divide-border/70 border-y border-border/70">
                    {#each roles as role}
                        <div class="flex items-center justify-between gap-4 py-4">
                            <div><p class="text-sm font-medium">{role.label}</p><p class="mt-1 text-xs text-muted-foreground">Role Workspace</p></div>
                            <span class="rounded-full border border-border/70 px-2.5 py-1 text-xs text-muted-foreground">{role.value}</span>
                        </div>
                    {/each}
                </div>
            </section>
            {/if}
        </div>

        <aside class="hidden xl:block">
            <div class="sticky top-6 rounded-lg border border-border/80 bg-surface-2/40 p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Di halaman ini</p>
                <nav class="mt-3 grid gap-1 text-sm" aria-label="Bagian pengaturan Workspace">
                    {#each [{ key: 'general', label: 'Umum' }, { key: 'members', label: 'Anggota Workspace' }, { key: 'units', label: 'Unit' }, { key: 'access', label: 'Akses & role' }] as item}
                        <Link href={settingsUrl(item.key as SettingsSection)} class="rounded-md px-3 py-2 {activeSection === item.key ? 'bg-muted font-medium' : 'hover:bg-muted'}">{item.label}</Link>
                    {/each}
                </nav>
            </div>
        </aside>
    </div>
</div>
