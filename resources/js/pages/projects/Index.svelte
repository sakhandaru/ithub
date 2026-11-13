<script module lang="ts">
    import { index } from '@/routes/workspaces/projects';

    export const layout = {
        breadcrumbs: [{ title: 'Projects', href: index('') }],
    };
</script>

<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import Archive from '@lucide/svelte/icons/archive';
    import FolderKanban from '@lucide/svelte/icons/folder-kanban';
    import Plus from '@lucide/svelte/icons/plus';
    import ProjectController from '@/actions/App/Http/Controllers/ProjectController';
    import AppHead from '@/components/AppHead.svelte';
    import { Button } from '@/components/ui/button';
    import { Badge } from '@/components/ui/badge';
    import { toUrl } from '@/lib/utils';
    import type { ProjectSummary } from '@/types';

    type Workspace = { id: string; name: string };
    let { workspace, projects, showArchived }: { workspace: Workspace; projects: ProjectSummary[]; showArchived: boolean } = $props();
</script>

<AppHead title="Projects" />

<div class="flex min-w-0 flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
    <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm text-muted-foreground">{workspace.name}</p>
            <h1 class="mt-1 text-xl font-semibold tracking-tight">{showArchived ? 'Project arsip' : 'Projects'}</h1>
            <p class="mt-1 text-sm leading-6 text-muted-foreground">Konteks utama untuk mengelola kumpulan Task.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <Button variant="outline" class="min-h-11" asChild>
                {#snippet children(props)}
                    <Link href={toUrl(ProjectController.index(workspace.id, { query: showArchived ? {} : { archived: 1 } }))} {...props}>
                        <Archive class="size-4" aria-hidden="true" />
                        {showArchived ? 'Project aktif' : 'Lihat arsip'}
                    </Link>
                {/snippet}
            </Button>
            {#if !showArchived}
                <Button class="min-h-11" asChild>
                    {#snippet children(props)}
                        <Link href={toUrl(ProjectController.create(workspace.id))} {...props}>
                            <Plus class="size-4" aria-hidden="true" />
                            Buat Project
                        </Link>
                    {/snippet}
                </Button>
            {/if}
        </div>
    </header>

    {#if projects.length === 0}
        <section class="flex min-h-64 items-center justify-center rounded-lg border border-dashed bg-card px-6 py-12 text-center">
            <div class="max-w-md">
                <div class="mx-auto flex size-11 items-center justify-center rounded-md border bg-muted/40">
                    <FolderKanban class="size-5 text-muted-foreground" aria-hidden="true" />
                </div>
                <h2 class="mt-4 text-sm font-semibold">{showArchived ? 'Belum ada Project arsip' : 'Belum ada Project'}</h2>
                <p class="mt-1 text-sm leading-6 text-muted-foreground">{showArchived ? 'Project yang diarsipkan akan muncul di sini.' : 'Buat Project pertama untuk mulai mengelompokkan Task.'}</p>
            </div>
        </section>
    {:else}
        <section class="overflow-x-auto rounded-lg border bg-card">
            <table class="w-full min-w-[56rem] text-left text-sm">
                <caption class="sr-only">Daftar Project</caption>
                <thead class="bg-muted/40 text-xs text-muted-foreground">
                    <tr>
                        <th class="px-4 py-3 font-medium">Project</th>
                        <th class="px-4 py-3 font-medium">Unit</th>
                        <th class="px-4 py-3 font-medium">Lead</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium">Visibility</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    {#each projects as project (project.id)}
                        <tr class="hover:bg-muted/20">
                            <td class="px-4 py-3">
                                <Link href={toUrl(ProjectController.show({ workspace: workspace.id, project: project.id }))} class="group block min-h-10 rounded-sm py-1 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                    <span class="font-medium group-hover:underline">{project.name}</span>
                                    <span class="ml-2 font-mono text-xs text-muted-foreground">{project.identifier}</span>
                                </Link>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">{project.unit?.name ?? '—'}</td>
                            <td class="px-4 py-3 text-muted-foreground">{project.project_lead?.name ?? 'Belum ditentukan'}</td>
                            <td class="px-4 py-3"><Badge variant="secondary">{project.status_label}</Badge></td>
                            <td class="px-4 py-3 text-muted-foreground">{project.visibility_label}</td>
                        </tr>
                    {/each}
                </tbody>
            </table>
        </section>
    {/if}
</div>
