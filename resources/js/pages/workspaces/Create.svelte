<script module lang="ts">
    import { create } from '@/routes/workspaces';

    export const layout = {
        breadcrumbs: [{ title: 'Buat Workspace', href: create() }],
    };
</script>

<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import WorkspaceController from '@/actions/App/Http/Controllers/WorkspaceController';
    import AppHead from '@/components/AppHead.svelte';
    import PageHeader from '@/components/PageHeader.svelte';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';

    let { timezones }: { timezones: string[] } = $props();
</script>

<AppHead title="Buat Workspace" />

<div class="mx-auto flex w-full max-w-2xl flex-col gap-5 px-4 py-5 sm:px-6 lg:px-8">
    <PageHeader
        title="Buat Workspace"
        description="Workspace menjadi rumah kerja untuk anggota, Unit, Project, dan Task Anda."
    />

    <section class="rounded-lg border bg-card p-5 sm:p-6">
        <Form {...WorkspaceController.store.form()} class="grid gap-6">
            {#snippet children({ errors, processing })}
                <div class="grid gap-2">
                    <Label for="name">Nama Workspace</Label>
                    <Input id="name" name="name" required maxlength={120} autocomplete="organization" />
                    <InputError message={errors.name} />
                </div>

                <div class="grid gap-2">
                    <Label for="timezone">Timezone Workspace</Label>
                    <select
                        id="timezone"
                        name="timezone"
                        class="h-11 w-full rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        required
                    >
                        {#each timezones as timezone}
                            <option value={timezone}>{timezone}</option>
                        {/each}
                    </select>
                    <p class="text-xs leading-5 text-muted-foreground">
                        Waktu disimpan konsisten dan ditampilkan mengikuti timezone ini.
                    </p>
                    <InputError message={errors.timezone} />
                </div>

                <div class="flex justify-end">
                    <Button type="submit" class="min-h-11" disabled={processing}>
                        {processing ? 'Membuat Workspace...' : 'Buat Workspace'}
                    </Button>
                </div>
            {/snippet}
        </Form>
    </section>
</div>
