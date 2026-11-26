<script module lang="ts">
    import { index } from '@/routes/workspaces/projects/files';

    export const layout = {
        breadcrumbs: [{ title: 'Berkas Project', href: index(['', '']) }],
    };
</script>

<script lang="ts">
    import { Form, Link, page } from '@inertiajs/svelte';
    import type { ComponentProps } from 'svelte';
    import { createPaginatedRowModel, createTable, createTableState, rowPaginationFeature, tableFeatures } from '@tanstack/svelte-table';
    import type { ColumnDef } from '@tanstack/svelte-table';
    import Document from 'carbon-icons-svelte/lib/Document.svelte';
    import Download from 'carbon-icons-svelte/lib/Download.svelte';
    import CheckCircle2 from 'carbon-icons-svelte/lib/CheckmarkFilled.svelte';
    import ListTodo from 'carbon-icons-svelte/lib/Task.svelte';
    import Search from 'carbon-icons-svelte/lib/Search.svelte';
    import CalendarDays from 'carbon-icons-svelte/lib/Calendar.svelte';
    import UserRound from 'carbon-icons-svelte/lib/UserAvatarFilled.svelte';
    import TextAlignLeft from 'carbon-icons-svelte/lib/TextAlignLeft.svelte';
    import Ruler from 'carbon-icons-svelte/lib/Ruler.svelte';
    import OverflowMenuVertical from 'carbon-icons-svelte/lib/OverflowMenuVertical.svelte';
    import View from 'carbon-icons-svelte/lib/View.svelte';
    import TrashCan from 'carbon-icons-svelte/lib/TrashCan.svelte';
    import ChevronLeft from 'carbon-icons-svelte/lib/ChevronLeft.svelte';
    import ChevronRight from 'carbon-icons-svelte/lib/ChevronRight.svelte';
    import ProjectAttachmentController from '@/actions/App/Http/Controllers/ProjectAttachmentController';
    import ProjectController from '@/actions/App/Http/Controllers/ProjectController';
    import TaskController from '@/actions/App/Http/Controllers/TaskController';
    import AppHead from '@/components/AppHead.svelte';
    import UserAvatar from '@/components/UserAvatar.svelte';
    import DataTable from '@/components/DataTable.svelte';
    import PageHeader from '@/components/PageHeader.svelte';
    import TaskDetail from '../tasks/Show.svelte';
    import { Button } from '@/components/ui/button';
    import { Dialog, DialogContent, DialogDescription, DialogTitle } from '@/components/ui/dialog';
    import { Sheet, SheetContent } from '@/components/ui/sheet';
    import { Input } from '@/components/ui/input';
    import { Select, SelectContent, SelectItem, SelectTrigger } from '@/components/ui/select';
    import { projectBreadcrumbs } from '@/lib/page-header';
    import { formatDateTime } from '@/lib/format-date';
    import { toUrl } from '@/lib/utils';

    type Workspace = { id: string; name: string };
    type Project = { id: string; name: string; identifier: string; archived_at: string | null };
    type Person = { id: number; name: string; email: string; avatar?: string | null } | null;
    type FileItem = { id: string; original_name: string; mime_type: string; size: number; created_at: string | null; uploader: Person; task: { id: string; identifier: string; title: string } | null };
    type TaskDetailProps = ComponentProps<typeof TaskDetail>;

    let { workspace, project, files, canManage }: { workspace: Workspace; project: Project; files: FileItem[]; canManage: boolean } = $props();
    const workspaceTimezone = $derived(page.props.workspaceContext?.active?.timezone);
    let search = $state('');
    let previewOpen = $state(false);
    let previewFile = $state<FileItem | null>(null);
    let taskPeekOpen = $state(false);
    let taskPeekLoading = $state(false);
    let taskPeekError = $state('');
    let taskPeekDetail = $state<TaskDetailProps | null>(null);
    let taskPeekRequest = 0;
    const filteredFiles = $derived(files.filter((file) => `${file.original_name} ${file.task?.identifier ?? ''} ${file.task?.title ?? ''}`.toLowerCase().includes(search.trim().toLowerCase())));
    const fileFeatures = tableFeatures({ rowPaginationFeature, paginatedRowModel: createPaginatedRowModel() });
    const fileColumns: ColumnDef<typeof fileFeatures, FileItem>[] = [
        { accessorKey: 'original_name', header: 'Berkas' },
        { accessorKey: 'task', header: 'Task' },
        { accessorKey: 'mime_type', header: 'Tipe' },
        { accessorKey: 'size', header: 'Ukuran' },
        { accessorKey: 'uploader', header: 'Oleh' },
        { accessorKey: 'created_at', header: 'Tanggal' },
        { id: 'actions', header: 'Aksi' },
    ];
    const [filePagination, setFilePagination] = createTableState({ pageIndex: 0, pageSize: 10 });
    const fileHeaderIcons = { original_name: Document, task: ListTodo, mime_type: TextAlignLeft, size: Ruler, uploader: UserRound, created_at: CalendarDays, actions: OverflowMenuVertical };
    const fileTable = createTable({ features: fileFeatures, columns: fileColumns, state: { get pagination() { return filePagination(); } }, onPaginationChange: setFilePagination, get data() { return filteredFiles; }, getRowId: (file) => file.id });

    function formatSize(size: number): string {
        if (size < 1024) return `${size} B`;
        if (size < 1024 * 1024) return `${Math.round(size / 1024)} KB`;

        return `${(size / (1024 * 1024)).toFixed(1)} MB`;
    }

    function openPreview(file: FileItem): void {
        previewFile = file;
        previewOpen = true;
    }

    async function openTaskPeek(file: FileItem): Promise<void> {
        if (!file.task) return;

        const requestId = ++taskPeekRequest;
        taskPeekOpen = true;
        taskPeekLoading = true;
        taskPeekError = '';
        taskPeekDetail = null;

        try {
            const response = await fetch(toUrl(TaskController.peek({ workspace: workspace.id, project: project.id, task: file.task.id })), {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });

            if (!response.ok) throw new Error('Task tidak dapat dimuat.');
            if (requestId === taskPeekRequest) taskPeekDetail = await response.json() as TaskDetailProps;
        } catch {
            if (requestId === taskPeekRequest) taskPeekError = 'Detail Task tidak dapat dimuat. Coba lagi.';
        } finally {
            if (requestId === taskPeekRequest) taskPeekLoading = false;
        }
    }
</script>

<AppHead title={`${project.identifier} · Berkas`} />

<div class="flex min-w-0 flex-col gap-5 px-4 py-5 sm:px-6 lg:px-8">
    <PageHeader breadcrumbs={projectBreadcrumbs(page.props.unitContext?.active?.name ?? workspace.name, page.props.unitContext?.active?.id, toUrl(ProjectController.index(workspace.id)), project.name, toUrl(ProjectController.show({ workspace: workspace.id, project: project.id })), 'files', files.length, page.props.projectContext?.active?.icon)}>
        {#snippet actions()}
                        <label class="relative block h-7 w-full sm:w-72"><Search class="pointer-events-none absolute left-2.5 top-1.5 size-3.5 text-muted-foreground" aria-hidden="true" /><span class="sr-only">Cari berkas</span><Input bind:value={search} type="search" placeholder="Cari berkas..." class="h-7 border-border/80 bg-background pl-8 text-xs" /></label>
        {/snippet}
    </PageHeader>

    {#if project.archived_at}
        <div class="rounded-md border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-100" role="status">Project ini diarsipkan. Berkas masih dapat diunduh, tetapi tidak dapat diubah.</div>
    {/if}

    <section class="min-w-0 overflow-hidden rounded-lg border border-border/80 bg-card">
        <DataTable table={fileTable} caption="Daftar Berkas Project" headerIcons={fileHeaderIcons} wrapperClass="overflow-x-auto" tableClass="w-full min-w-[58rem] border-collapse text-left text-xs" headerClass="border-b border-border/70 bg-muted/25 px-3 py-2.5 text-xs font-medium text-muted-foreground first:border-l-0 border-l border-border/60">
                {#snippet empty()}
                    <div class="flex min-h-40 flex-col items-center justify-center px-6 py-10 text-center"><Document class="size-7 text-muted-foreground" aria-hidden="true" /><p class="mt-3 text-sm font-medium">Belum ada Berkas</p><p class="mt-1 max-w-sm text-sm leading-6 text-muted-foreground">Attachment dari Task dalam Project ini akan muncul di tabel ini.</p></div>
                {/snippet}
                {#snippet children(row)}
                    {@const file = row.original}
                    <tr class="border-b border-border/60 transition-colors last:border-b-0 hover:bg-muted/25">
                        <td class="px-2.5 py-2.5"><a href={toUrl(ProjectAttachmentController.download({ workspace: workspace.id, project: project.id, attachment: file.id }))} class="inline-flex max-w-56 items-center gap-2 font-medium hover:underline" title={file.original_name}><Document class="size-4 shrink-0 text-muted-foreground" aria-hidden="true" /><span class="truncate">{file.original_name}</span></a></td>
                        <td class="w-[32rem] max-w-[32rem] border-l border-border/60 px-2.5 py-2.5">{#if file.task}<button type="button" class="flex min-w-0 max-w-full items-center gap-2 text-left hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" title={`Buka detail ${file.task.identifier}`} onclick={() => { openTaskPeek(file); }}><span class="shrink-0 font-mono text-[11px] text-muted-foreground">{file.task.identifier}</span><span class="min-w-0 truncate text-xs">{file.task.title}</span></button>{:else}<span class="text-muted-foreground">Tanpa Task</span>{/if}</td>
                        <td class="w-32 whitespace-nowrap border-l border-border/60 px-2.5 py-2.5 text-muted-foreground">{file.mime_type}</td>
                        <td class="border-l border-border/60 px-2.5 py-2.5 text-muted-foreground">{formatSize(file.size)}</td>
                        <td class="border-l border-border/60 px-2.5 py-2.5 text-muted-foreground">{#if file.uploader}<span class="inline-flex max-w-40 items-center gap-1.5 rounded-full border border-border/70 px-1.5 py-0.5 text-[13px] leading-5"><UserAvatar user={file.uploader} size="size-4" label={false} /><span class="truncate">{file.uploader.name}</span></span>{:else}<span>Pengguna</span>{/if}</td>
                        <td class="border-l border-border/60 px-2.5 py-2.5 text-muted-foreground">{formatDateTime(file.created_at, workspaceTimezone)}</td>
                        <td class="border-l border-border/60 px-2.5 py-2.5 text-right"><div class="inline-flex items-center gap-1"><button type="button" class="inline-flex size-7 items-center justify-center rounded-md hover:bg-muted" aria-label={`Lihat ${file.original_name}`} title="Lihat" onclick={() => { openPreview(file); }}><View class="size-3.5" aria-hidden="true" /></button><a href={toUrl(ProjectAttachmentController.download({ workspace: workspace.id, project: project.id, attachment: file.id }))} class="inline-flex size-7 items-center justify-center rounded-md hover:bg-muted" aria-label={`Unduh ${file.original_name}`} title="Unduh"><Download class="size-3.5" aria-hidden="true" /></a>{#if canManage}<Form {...ProjectAttachmentController.destroy.form({ workspace: workspace.id, project: project.id, attachment: file.id })}><Button type="submit" variant="ghost" size="icon" class="size-7" aria-label={`Hapus ${file.original_name}`} title="Hapus"><TrashCan class="size-3.5" aria-hidden="true" /></Button></Form>{/if}</div></td>
                    </tr>
                {/snippet}
                {#snippet footer()}
                    {@const pagination = filePagination()}
                    {@const firstFile = filteredFiles.length === 0 ? 0 : pagination.pageIndex * pagination.pageSize + 1}
                    {@const lastFile = Math.min((pagination.pageIndex + 1) * pagination.pageSize, filteredFiles.length)}
                    <div class="flex flex-col gap-2 border-t border-border/70 px-3 py-2.5 text-[11px] text-muted-foreground sm:flex-row sm:items-center sm:justify-between"><span>Menampilkan {firstFile}–{lastFile} dari {filteredFiles.length} Berkas</span><div class="flex items-center gap-2"><span class="hidden items-center gap-1.5 sm:inline-flex"><CheckCircle2 class="size-3.5" aria-hidden="true" /> Data diperbarui dari Project</span><Select value={String(pagination.pageSize)} onValueChange={(value) => fileTable.setPageSize(Number(value))}><SelectTrigger class="h-8 min-w-[118px] px-2 text-xs" aria-label="Berkas per halaman">{pagination.pageSize} / halaman</SelectTrigger><SelectContent><SelectItem value="10">10 / halaman</SelectItem><SelectItem value="25">25 / halaman</SelectItem><SelectItem value="50">50 / halaman</SelectItem></SelectContent></Select><span class="whitespace-nowrap">{pagination.pageIndex + 1} / {fileTable.getPageCount()}</span><button type="button" class="inline-flex size-8 items-center justify-center rounded-md border border-border/80 hover:bg-muted disabled:pointer-events-none disabled:opacity-40" aria-label="Halaman sebelumnya" disabled={!fileTable.getCanPreviousPage()} onclick={() => fileTable.previousPage()}><ChevronLeft class="size-4" /></button><button type="button" class="inline-flex size-8 items-center justify-center rounded-md border border-border/80 hover:bg-muted disabled:pointer-events-none disabled:opacity-40" aria-label="Halaman berikutnya" disabled={!fileTable.getCanNextPage()} onclick={() => fileTable.nextPage()}><ChevronRight class="size-4" /></button></div></div>
                {/snippet}
            </DataTable>
    </section>
</div>

<Dialog bind:open={previewOpen}>
    <DialogContent class="max-h-[92vh] max-w-5xl overflow-hidden border-border bg-background p-0 shadow-lg">
        {#if previewFile}
            <div class="flex items-center justify-between gap-4 border-b border-border/70 px-5 py-4 sm:px-6">
                <div class="min-w-0">
                    <DialogTitle class="truncate text-base font-semibold">{previewFile.original_name}</DialogTitle>
                    <DialogDescription class="mt-1 text-xs">{previewFile.mime_type} · {formatSize(previewFile.size)}</DialogDescription>
                </div>
                <a href={toUrl(ProjectAttachmentController.download({ workspace: workspace.id, project: project.id, attachment: previewFile.id }))} class="inline-flex h-8 shrink-0 items-center gap-1.5 rounded-md border border-border/80 px-2.5 text-xs font-medium hover:bg-muted"><Download class="size-3.5" aria-hidden="true" />Unduh</a>
            </div>
            <div class="flex min-h-[24rem] items-center justify-center bg-muted/20 p-4 sm:min-h-[32rem] sm:p-6">
                {#if previewFile.mime_type.startsWith('image/')}
                    <img src={toUrl(ProjectAttachmentController.preview({ workspace: workspace.id, project: project.id, attachment: previewFile.id }))} alt={previewFile.original_name} class="max-h-[68vh] max-w-full rounded-md object-contain shadow-sm" />
                {:else if previewFile.mime_type === 'application/pdf' || previewFile.mime_type.startsWith('text/')}
                    <iframe src={toUrl(ProjectAttachmentController.preview({ workspace: workspace.id, project: project.id, attachment: previewFile.id }))} title={`Preview ${previewFile.original_name}`} class="h-[68vh] w-full rounded-md border border-border/70 bg-background"></iframe>
                {:else}
                    <div class="max-w-sm text-center"><Document class="mx-auto size-10 text-muted-foreground" aria-hidden="true" /><p class="mt-3 text-sm font-medium">Preview belum tersedia</p><p class="mt-1 text-xs leading-5 text-muted-foreground">Format ini belum dapat ditampilkan langsung. Unduh berkas untuk membukanya.</p></div>
                {/if}
            </div>
        {/if}
    </DialogContent>
</Dialog>

<Sheet open={taskPeekOpen} onOpenChange={(open) => { taskPeekOpen = open; if (!open) { taskPeekDetail = null; taskPeekError = ''; } }}>
    <SheetContent side="right" class="box-border w-full max-w-[100vw] overflow-x-hidden overflow-y-auto p-0 sm:max-w-xl">
        {#if taskPeekLoading}
            <div class="flex min-h-64 items-center justify-center p-6 text-sm text-muted-foreground">Memuat detail Task...</div>
        {:else if taskPeekError}
            <div class="flex min-h-64 items-center justify-center p-6 text-center text-sm text-destructive">{taskPeekError}</div>
        {:else if taskPeekDetail}
            <TaskDetail {...taskPeekDetail} embedded />
        {/if}
    </SheetContent>
</Sheet>
