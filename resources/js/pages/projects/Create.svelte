<script module lang="ts">
    import { create } from '@/routes/workspaces/projects';

    export const layout = {
        breadcrumbs: [{ title: 'Buat Project', href: create('') }],
    };
</script>

<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import ProjectController from '@/actions/App/Http/Controllers/ProjectController';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import type { ProjectOption } from '@/types';

    type Workspace = { id: string; name: string };
    type Unit = { id: string; name: string };
    type Person = { id: number; name: string; email: string };

    let {
        workspace,
        units,
        members,
        statuses,
        visibilities,
    }: {
        workspace: Workspace;
        units: Unit[];
        members: Person[];
        statuses: ProjectOption[];
        visibilities: ProjectOption[];
    } = $props();
</script>

<AppHead title="Buat Project" />

<div class="mx-auto flex w-full max-w-3xl flex-col gap-8 px-4 py-8 sm:px-6 lg:px-8">
    <Heading title="Buat Project" description={`Buat konteks kerja baru di ${workspace.name}.`} />

    <section class="rounded-lg border bg-card p-5 sm:p-6">
        <Form {...ProjectController.store.form(workspace.id)} class="grid gap-6">
            {#snippet children({ errors, processing })}
                <div class="grid gap-2">
                    <Label for="project-name">Nama Project</Label>
                    <Input id="project-name" name="name" required maxlength={120} autocomplete="off" />
                    <InputError message={errors.name} />
                </div>

                <div class="grid gap-2">
                    <Label for="project-identifier">Project ID</Label>
                    <Input id="project-identifier" name="identifier" required maxlength={12} pattern="[A-Z0-9]+" placeholder="DEV" autocomplete="off" />
                    <p class="text-xs leading-5 text-muted-foreground">Dipakai sebagai prefix Task, misalnya DEV-123.</p>
                    <InputError message={errors.identifier} />
                </div>

                <div class="grid gap-2">
                    <Label for="project-description">Tujuan atau deskripsi</Label>
                    <textarea id="project-description" name="description" rows={4} class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:ring-2 focus-visible:ring-ring"></textarea>
                    <InputError message={errors.description} />
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="project-unit">Unit utama</Label>
                        <select id="project-unit" name="unit_id" class="h-11 rounded-md border border-input bg-background px-3 text-sm" required>
                            <option value="">Pilih Unit</option>
                            {#each units as unit (unit.id)}
                                <option value={unit.id}>{unit.name}</option>
                            {/each}
                        </select>
                        <InputError message={errors.unit_id} />
                    </div>
                    <div class="grid gap-2">
                        <Label for="project-visibility">Visibility</Label>
                        <select id="project-visibility" name="visibility" value="unit" class="h-11 rounded-md border border-input bg-background px-3 text-sm" required>
                            {#each visibilities as visibility}
                                <option value={visibility.value}>{visibility.label}</option>
                            {/each}
                        </select>
                        <InputError message={errors.visibility} />
                    </div>
                    <div class="grid gap-2">
                        <Label for="project-status">Status awal</Label>
                        <select id="project-status" name="status" value="draft" class="h-11 rounded-md border border-input bg-background px-3 text-sm" required>
                            {#each statuses as status}
                                <option value={status.value}>{status.label}</option>
                            {/each}
                        </select>
                        <InputError message={errors.status} />
                    </div>
                    <div class="grid gap-2">
                        <Label for="project-lead">Project Lead</Label>
                        <select id="project-lead" name="project_lead_id" class="h-11 rounded-md border border-input bg-background px-3 text-sm">
                            <option value="">Saya sebagai Project Lead</option>
                            {#each members as member (member.id)}
                                <option value={member.id}>{member.name} · {member.email}</option>
                            {/each}
                        </select>
                        <InputError message={errors.project_lead_id} />
                    </div>
                    <div class="grid gap-2">
                        <Label for="default-assignee">PIC default <span class="font-normal text-muted-foreground">(opsional)</span></Label>
                        <select id="default-assignee" name="default_assignee_id" class="h-11 rounded-md border border-input bg-background px-3 text-sm">
                            <option value="">Tidak ada</option>
                            {#each members as member (member.id)}
                                <option value={member.id}>{member.name} · {member.email}</option>
                            {/each}
                        </select>
                        <InputError message={errors.default_assignee_id} />
                    </div>
                    <div class="grid gap-2">
                        <Label for="start-date">Tanggal mulai <span class="font-normal text-muted-foreground">(opsional)</span></Label>
                        <Input id="start-date" name="start_date" type="date" />
                        <InputError message={errors.start_date} />
                    </div>
                    <div class="grid gap-2">
                        <Label for="target-date">Target selesai <span class="font-normal text-muted-foreground">(opsional)</span></Label>
                        <Input id="target-date" name="target_date" type="date" />
                        <InputError message={errors.target_date} />
                    </div>
                </div>

                <div class="flex justify-end">
                    <Button type="submit" class="min-h-11" disabled={processing}>{processing ? 'Membuat Project...' : 'Buat Project'}</Button>
                </div>
            {/snippet}
        </Form>
    </section>
</div>
