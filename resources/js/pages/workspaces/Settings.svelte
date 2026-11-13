<script module lang="ts">
    import WorkspaceSettingsController from '@/actions/App/Http/Controllers/WorkspaceSettingsController';
    import { dashboard } from '@/routes';

    export const layout = {
        breadcrumbs: [{ title: 'Pengaturan Workspace', href: dashboard() }],
    };
</script>

<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import ChevronDown from '@lucide/svelte/icons/chevron-down';
    import Trash2 from '@lucide/svelte/icons/trash-2';
    import UnitController from '@/actions/App/Http/Controllers/UnitController';
    import UnitMemberController from '@/actions/App/Http/Controllers/UnitMemberController';
    import WorkspaceMemberController from '@/actions/App/Http/Controllers/WorkspaceMemberController';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import type { WorkspaceMember, WorkspaceRoleOption, WorkspaceUnit } from '@/types';

    type Workspace = { id: string; name: string; timezone: string };

    let {
        workspace,
        memberships,
        units,
        roles,
        timezones,
        canAssignOwner,
    }: {
        workspace: Workspace;
        memberships: WorkspaceMember[];
        units: WorkspaceUnit[];
        roles: WorkspaceRoleOption[];
        timezones: string[];
        canAssignOwner: boolean;
    } = $props();

    const assignableRoles = $derived(
        canAssignOwner ? roles : roles.filter((role) => role.value !== 'owner'),
    );

    function confirmRemoval(message: string): boolean {
        return window.confirm(message);
    }
</script>

<AppHead title="Pengaturan Workspace" />

<div class="flex min-w-0 flex-col gap-8 px-4 py-6 sm:px-6 lg:px-8">
    <Heading
        title="Pengaturan Workspace"
        description="Kelola identitas, anggota, Unit, dan timezone Workspace."
    />

    <div class="grid min-w-0 gap-8 xl:grid-cols-[minmax(0,1fr)_20rem]">
        <div class="min-w-0 space-y-8">
            <section id="general" class="rounded-lg border bg-card p-5 sm:p-6">
                <div class="mb-5">
                    <h2 class="text-base font-semibold">General</h2>
                    <p class="mt-1 text-sm text-muted-foreground">Informasi dasar yang digunakan di seluruh Workspace.</p>
                </div>

                <Form {...WorkspaceSettingsController.update.form(workspace.id)} class="grid gap-5">
                    {#snippet children({ errors, processing })}
                        <div class="grid gap-2">
                            <Label for="workspace-name">Nama Workspace</Label>
                            <Input id="workspace-name" name="name" value={workspace.name} required maxlength={120} />
                            <InputError message={errors.name} />
                        </div>

                        <div class="grid gap-2">
                            <Label for="workspace-timezone">Timezone</Label>
                            <select
                                id="workspace-timezone"
                                name="timezone"
                                value={workspace.timezone}
                                class="h-11 w-full rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:ring-2 focus-visible:ring-ring"
                            >
                                {#each timezones as timezone}
                                    <option value={timezone}>{timezone}</option>
                                {/each}
                            </select>
                            <InputError message={errors.timezone} />
                        </div>

                        <div class="flex justify-end">
                            <Button type="submit" class="min-h-11" disabled={processing}>
                                {processing ? 'Menyimpan...' : 'Simpan perubahan'}
                            </Button>
                        </div>
                    {/snippet}
                </Form>
            </section>

            <section id="members" class="rounded-lg border bg-card p-5 sm:p-6">
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
                            <select id="member-role" name="role" class="h-11 rounded-md border border-input bg-background px-3 text-sm" required>
                                {#each assignableRoles as role}
                                    <option value={role.value}>{role.label}</option>
                                {/each}
                            </select>
                            <InputError message={errors.role} />
                        </div>
                        <Button type="submit" class="min-h-11" disabled={processing}>Tambah</Button>
                    {/snippet}
                </Form>

                <div class="overflow-x-auto rounded-md border">
                    <table class="w-full min-w-[42rem] text-left text-sm">
                        <caption class="sr-only">Daftar anggota Workspace</caption>
                        <thead class="bg-muted/40 text-xs text-muted-foreground">
                            <tr>
                                <th class="px-4 py-3 font-medium">Anggota</th>
                                <th class="px-4 py-3 font-medium">Role</th>
                                <th class="px-4 py-3 text-right font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            {#each memberships as member (member.id)}
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="font-medium">{member.user.name}</div>
                                        <div class="text-xs text-muted-foreground">{member.user.email}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <Form {...WorkspaceMemberController.update.form({ workspace: workspace.id, membership: member.id })} class="flex items-center gap-2">
                                            {#snippet children({ errors, processing })}
                                                <select name="role" value={member.role} class="h-10 rounded-md border border-input bg-background px-3 text-sm" disabled={processing}>
                                                    {#each assignableRoles as role}
                                                        <option value={role.value}>{role.label}</option>
                                                    {/each}
                                                    {#if member.role === 'owner' && !canAssignOwner}
                                                        <option value="owner">Owner</option>
                                                    {/if}
                                                </select>
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
                                                <Button type="submit" variant="ghost" size="icon" class="min-h-10 min-w-10 text-destructive hover:text-destructive" disabled={processing} aria-label={`Hapus ${member.user.name}`} onclick={(event) => { if (!confirmRemoval(`Hapus ${member.user.name} dari Workspace?`)) event.preventDefault(); }}>
                                                    <Trash2 class="size-4" aria-hidden="true" />
                                                </Button>
                                            {/snippet}
                                        </Form>
                                    </td>
                                </tr>
                            {/each}
                        </tbody>
                    </table>
                </div>
            </section>

            <section id="units" class="rounded-lg border bg-card p-5 sm:p-6">
                <div class="mb-5">
                    <h2 class="text-base font-semibold">Unit</h2>
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
                                                <option value="">Pilih anggota Workspace</option>
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
        </div>

        <aside class="hidden xl:block">
            <div class="sticky top-6 rounded-lg border bg-card p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Di halaman ini</p>
                <nav class="mt-3 grid gap-1 text-sm" aria-label="Bagian pengaturan Workspace">
                    <a href="#general" class="rounded-md px-3 py-2 hover:bg-muted">General</a>
                    <a href="#members" class="rounded-md px-3 py-2 hover:bg-muted">Anggota Workspace</a>
                    <a href="#units" class="rounded-md px-3 py-2 hover:bg-muted">Unit</a>
                </nav>
            </div>
        </aside>
    </div>
</div>
