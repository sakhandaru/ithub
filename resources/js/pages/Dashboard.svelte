<script module lang="ts">
    import { dashboard } from '@/routes';

    export const layout = {
        breadcrumbs: [
            {
                title: 'Beranda',
                href: dashboard(),
            },
        ],
    };
</script>

<script lang="ts">
    import Activity from '@lucide/svelte/icons/activity';
    import FolderKanban from '@lucide/svelte/icons/folder-kanban';
    import ListChecks from '@lucide/svelte/icons/list-checks';
    import AppHead from '@/components/AppHead.svelte';
</script>

<AppHead title="Beranda" />

<div class="flex min-h-0 flex-1 flex-col px-4 py-5 sm:px-6 sm:py-6 lg:px-8">
    <header class="mb-6">
        <h1 class="text-xl font-semibold tracking-tight">Beranda</h1>
        <p class="mt-1 max-w-2xl text-sm leading-6 text-muted-foreground">
            Pekerjaan yang ditugaskan kepada Anda dan hal yang perlu ditindaklanjuti.
        </p>
    </header>

    <div class="grid min-h-0 flex-1 gap-4 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <section class="flex min-h-80 flex-col rounded-lg border bg-card">
            <div class="border-b px-4 py-3 sm:px-5">
                <div class="flex items-center gap-2">
                    <ListChecks class="size-4 text-muted-foreground" aria-hidden="true" />
                    <h2 class="text-sm font-semibold">Pekerjaan saya</h2>
                </div>
            </div>

            <div class="flex flex-1 items-center justify-center px-6 py-12 text-center">
                <div class="max-w-sm">
                    <div
                        class="mx-auto flex size-10 items-center justify-center rounded-md border bg-muted/40"
                    >
                        <ListChecks class="size-5 text-muted-foreground" aria-hidden="true" />
                    </div>
                    <h3 class="mt-4 text-sm font-medium">Belum ada task</h3>
                    <p class="mt-1 text-sm leading-6 text-muted-foreground">
                        Task yang ditugaskan kepada Anda akan muncul di sini setelah modul project diaktifkan.
                    </p>
                </div>
            </div>
        </section>

        <div class="grid content-start gap-4 sm:grid-cols-2 lg:grid-cols-1">
            <section class="rounded-lg border bg-card">
                <div class="flex items-center gap-2 border-b px-4 py-3">
                    <FolderKanban class="size-4 text-muted-foreground" aria-hidden="true" />
                    <h2 class="text-sm font-semibold">Project</h2>
                </div>
                <div class="px-4 py-5">
                    <p class="text-sm text-muted-foreground">Belum ada project di workspace ini.</p>
                </div>
            </section>

            <section class="rounded-lg border bg-card">
                <div class="flex items-center gap-2 border-b px-4 py-3">
                    <Activity class="size-4 text-muted-foreground" aria-hidden="true" />
                    <h2 class="text-sm font-semibold">Aktivitas terbaru</h2>
                </div>
                <div class="px-4 py-5">
                    <p class="text-sm text-muted-foreground">Belum ada aktivitas untuk ditampilkan.</p>
                </div>
            </section>
        </div>
    </div>
</div>
