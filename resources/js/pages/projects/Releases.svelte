<script module lang="ts">
    import { releases } from '@/routes/workspaces/projects';
    export const layout = { breadcrumbs: [{ title: 'Rilis', href: releases(['', '']) }] };
</script>

<script lang="ts">
    import { Form, page } from '@inertiajs/svelte';
    import Add from 'carbon-icons-svelte/lib/Add.svelte';
    import Calendar from 'carbon-icons-svelte/lib/Calendar.svelte';
    import PackageOpen from 'carbon-icons-svelte/lib/Package.svelte';
    import TextAlignLeft from 'carbon-icons-svelte/lib/TextAlignLeft.svelte';
    import ProjectController from '@/actions/App/Http/Controllers/ProjectController';
    import ReleaseController from '@/actions/App/Http/Controllers/ReleaseController';
    import AppHead from '@/components/AppHead.svelte';
    import InputError from '@/components/InputError.svelte';
    import PageHeader from '@/components/PageHeader.svelte';
    import ReleaseStatusBadge from '@/components/ReleaseStatusBadge.svelte';
    import { Button } from '@/components/ui/button';
    import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogTitle } from '@/components/ui/dialog';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Select, SelectContent, SelectItem, SelectTrigger } from '@/components/ui/select';
    import { projectBreadcrumbs } from '@/lib/page-header';
    import { toUrl } from '@/lib/utils';

    type Workspace = { id: string; name: string };
    type Project = { id: string; name: string; identifier: string; archived: boolean };
    type Release = { id: string; name: string; description: string | null; target_date: string | null; status: string; tasks_count: number };
    type Status = { value: string; label: string };
    let { workspace, project, releases: releaseItems, statuses, canManage }: { workspace: Workspace; project: Project; releases: Release[]; statuses: Status[]; canManage: boolean } = $props();
    let createOpen = $state(false);
    let selectedStatus = $state('planned');
</script>

<AppHead title={`${project.identifier} · Rilis`} />
<div class="flex min-w-0 flex-col gap-5 px-4 py-5 sm:px-6 lg:px-8">
    <PageHeader breadcrumbs={projectBreadcrumbs(page.props.unitContext?.active?.name ?? workspace.name, page.props.unitContext?.active?.id, toUrl(ProjectController.index(workspace.id)), project.name, toUrl(ProjectController.show({ workspace: workspace.id, project: project.id })), 'releases', undefined, page.props.projectContext?.active?.icon)}>
        {#snippet actions()}
            {#if canManage && !project.archived}<Button variant="outline" class="h-7 shrink-0 gap-1.5 border-border/80 bg-background px-2.5 text-xs text-muted-foreground" onclick={() => { selectedStatus = statuses[0]?.value ?? 'planned'; createOpen = true; }}><Add class="size-3.5" aria-hidden="true" />Tambah Rilis</Button>{/if}
        {/snippet}
    </PageHeader>
    {#if project.archived}<div class="rounded-md border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-100" role="status">Project ini diarsipkan. Rilis bersifat read-only.</div>{/if}
    {#if releaseItems.length === 0}
        <section class="flex min-h-64 items-center justify-center rounded-lg border border-dashed border-border/80 px-6 py-12 text-center"><div class="max-w-md"><PackageOpen class="mx-auto size-8 text-muted-foreground" aria-hidden="true" /><h2 class="mt-4 text-sm font-semibold">Belum ada Rilis</h2><p class="mt-1 text-sm leading-6 text-muted-foreground">Buat Rilis untuk mengelompokkan Task yang akan masuk versi atau periode tertentu.</p></div></section>
    {:else}
        <div class="flex flex-col gap-2">{#each releaseItems as release (release.id)}<a href={toUrl(ReleaseController.show({ workspace: workspace.id, project: project.id, release: release.id }))} class="block rounded-lg border border-border/70 bg-card px-4 py-3 transition-colors hover:bg-muted/25 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"><div class="flex flex-wrap items-center gap-x-4 gap-y-2"><div class="min-w-0 flex-1 basis-48"><h2 class="truncate text-sm font-semibold">{release.name}</h2>{#if release.description}<p class="mt-1 truncate text-sm text-muted-foreground">{release.description}</p>{/if}</div><div class="flex shrink-0 flex-wrap items-center gap-x-4 gap-y-2 text-xs text-muted-foreground"><span>{release.tasks_count} Task</span>{#if release.target_date}<span>Target {release.target_date}</span>{/if}<ReleaseStatusBadge status={release.status} /></div></div></a>{/each}</div>
    {/if}
</div>

<Dialog bind:open={createOpen}>
    <DialogContent class="max-h-[92vh] max-w-xl overflow-y-auto border-border bg-background p-0 shadow-lg">
        <div class="px-6 pb-1 pt-5"><DialogTitle class="text-sm font-medium text-muted-foreground">Rilis:</DialogTitle><DialogDescription class="sr-only">Buat paket versi atau periode rilis untuk Project ini.</DialogDescription></div>
        <Form {...ReleaseController.store.form({ workspace: workspace.id, project: project.id })} class="grid gap-4 px-6 pb-5" resetOnSuccess onSuccess={() => { createOpen = false; selectedStatus = statuses[0]?.value ?? 'planned'; }}>
            {#snippet children({ errors, processing })}
                <div class="grid gap-2"><Label for="release-name" class="sr-only">Nama atau versi Rilis</Label><Input id="release-name" name="name" maxlength={120} required placeholder="Nama atau versi rilis" autofocus class="h-auto rounded-none border-0 bg-transparent px-0 text-xl font-medium shadow-none focus-visible:ring-0" /><InputError message={errors.name} /></div>
                <div class="grid gap-3 border-t border-border/70 pt-4">
                    <div class="grid gap-2 sm:grid-cols-[150px_minmax(0,1fr)] sm:items-center"><div class="flex items-center gap-2 text-sm text-muted-foreground"><Calendar class="size-4" aria-hidden="true" />Target tanggal</div><Input id="release-target-date" name="target_date" type="date" class="h-8 w-auto border-0 bg-muted/30 px-2 text-sm shadow-none focus-visible:ring-0" /><InputError message={errors.target_date} /></div>
                    <div class="grid gap-2 sm:grid-cols-[150px_minmax(0,1fr)] sm:items-center"><div class="flex items-center gap-2 text-sm text-muted-foreground"><PackageOpen class="size-4" aria-hidden="true" />Status</div><Select value={selectedStatus} onValueChange={(value) => { selectedStatus = value; }}><SelectTrigger id="release-status" class="h-8 w-fit min-w-40 max-w-full justify-self-start rounded-md border border-border/70 bg-transparent px-2 text-sm shadow-none focus:ring-0"><span>{statuses.find((status) => status.value === selectedStatus)?.label ?? 'Pilih status'}</span></SelectTrigger><SelectContent>{#each statuses as status (status.value)}<SelectItem value={status.value}>{status.label}</SelectItem>{/each}</SelectContent></Select><input type="hidden" name="status" value={selectedStatus} required /><InputError message={errors.status} /></div>
                </div>
                <div class="grid gap-1.5"><div class="flex items-center gap-2 text-xs text-muted-foreground"><TextAlignLeft class="size-3.5" aria-hidden="true" /><Label for="release-description" class="font-normal">Catatan rilis <span>(opsional)</span></Label></div><textarea id="release-description" name="description" rows={3} maxlength={2000} class="min-h-20 w-full resize-y border-0 bg-transparent px-0 py-1 text-sm leading-6 outline-none placeholder:text-muted-foreground/60 focus-visible:ring-0" placeholder="Ringkasan perubahan dalam rilis..."></textarea><InputError message={errors.description} /></div>
                <div class="flex flex-col-reverse gap-2 border-t border-border/70 pt-4 sm:flex-row sm:justify-end"><Button type="button" variant="ghost" onclick={() => { createOpen = false; selectedStatus = statuses[0]?.value ?? 'planned'; }}>Batal</Button><Button type="submit" disabled={processing}>{processing ? 'Membuat...' : 'Buat Rilis'}</Button></div>
            {/snippet}
        </Form>
    </DialogContent>
</Dialog>
