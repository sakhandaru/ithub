<script lang="ts">
    import { onMount } from 'svelte';
    import { router } from '@inertiajs/svelte';
    import Search from 'carbon-icons-svelte/lib/Search.svelte';
    import FolderTree from 'carbon-icons-svelte/lib/FolderTree.svelte';
    import Task from 'carbon-icons-svelte/lib/Task.svelte';
    import { Dialog, DialogContent, DialogDescription, DialogTitle } from '@/components/ui/dialog';
    import { Input } from '@/components/ui/input';
    import ProjectController from '@/actions/App/Http/Controllers/ProjectController';
    import TaskController from '@/actions/App/Http/Controllers/TaskController';
    import { search } from '@/routes';
    import { toUrl } from '@/lib/utils';

    type ProjectResult = { id: string; name: string; identifier: string };
    type TaskResult = { id: string; identifier: string; title: string; project: ProjectResult | null; status: string };
    type SearchResult = { type: 'project' | 'task'; id: string; label: string; detail: string; href: string };
    type SearchResponse = { workspace: { id: string; name: string } | null; projects: ProjectResult[]; tasks: TaskResult[] };

    let { open = $bindable(false) }: { open?: boolean } = $props();
    let query = $state('');
    let results = $state<SearchResult[]>([]);
    let selectedIndex = $state(0);
    let loading = $state(false);
    let requestError = $state(false);

    onMount(() => {
        const handleShortcut = (event: KeyboardEvent): void => {
            if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
                event.preventDefault();
                open = true;
                query = '';
                results = [];
            }
        };
        window.addEventListener('keydown', handleShortcut);
        return () => window.removeEventListener('keydown', handleShortcut);
    });

    $effect(() => {
        const normalizedQuery = query.trim();
        if (!open || normalizedQuery.length === 0) {
            results = [];
            loading = false;
            requestError = false;
            return;
        }

        const controller = new AbortController();
        loading = true;
        requestError = false;

        fetch(toUrl(search({ query: { q: normalizedQuery } })), { headers: { Accept: 'application/json' }, signal: controller.signal })
            .then(async (response): Promise<SearchResponse> => {
                if (!response.ok) throw new Error('Search request failed');
                return response.json() as Promise<SearchResponse>;
            })
            .then((data) => {
                const workspaceId = data.workspace?.id ?? '';
                results = [
                    ...data.projects.map((project): SearchResult => ({ type: 'project', id: project.id, label: project.name, detail: project.identifier, href: toUrl(ProjectController.show({ workspace: workspaceId, project: project.id })) })),
                    ...data.tasks.map((task): SearchResult => ({ type: 'task', id: task.id, label: task.title, detail: `${task.identifier}${task.project ? ` · ${task.project.name}` : ''}`, href: task.project ? toUrl(TaskController.show({ workspace: workspaceId, project: task.project.id, task: task.id })) : toUrl(search()) })),
                ];
                selectedIndex = 0;
            })
            .catch((error: unknown) => {
                if (error instanceof DOMException && error.name === 'AbortError') return;
                requestError = true;
                results = [];
            })
            .finally(() => {
                if (!controller.signal.aborted) loading = false;
            });

        return () => controller.abort();
    });

    function selectResult(result: SearchResult): void {
        open = false;
        router.visit(result.href);
    }

    function handleKeydown(event: KeyboardEvent): void {
        if (event.key === 'ArrowDown' && results.length > 0) {
            event.preventDefault();
            selectedIndex = Math.min(selectedIndex + 1, results.length - 1);
        } else if (event.key === 'ArrowUp' && results.length > 0) {
            event.preventDefault();
            selectedIndex = Math.max(selectedIndex - 1, 0);
        } else if (event.key === 'Enter' && results[selectedIndex]) {
            event.preventDefault();
            selectResult(results[selectedIndex]);
        }
    }
</script>

<Dialog bind:open onOpenChange={(value) => { open = value; }}>
    <DialogContent class="top-[12vh] max-w-[42rem] translate-y-0 overflow-hidden rounded-lg border-border/80 bg-popover p-0 text-popover-foreground shadow-2xl sm:top-[15vh]">
        <DialogTitle class="sr-only">Cari dan buka</DialogTitle>
        <DialogDescription class="sr-only">Cari Task atau Project.</DialogDescription>
        <form onsubmit={(event) => event.preventDefault()}>
            <div class="flex h-[3.75rem] items-center gap-3 border-b border-border/70 px-4">
                <Search class="size-[1.125rem] shrink-0 text-muted-foreground" aria-hidden="true" />
                <Input bind:value={query} onkeydown={handleKeydown} class="h-full min-h-0 flex-1 border-0 bg-transparent px-0 text-[15px] shadow-none outline-none placeholder:text-muted-foreground/70 focus-visible:ring-0" placeholder="Cari pekerjaan..." autofocus aria-activedescendant={results[selectedIndex] ? `spotlight-result-${results[selectedIndex].id}` : undefined} />
                <kbd class="hidden rounded border border-border/80 bg-muted/40 px-1.5 py-0.5 font-mono text-[10px] text-muted-foreground sm:inline">ESC</kbd>
            </div>
        </form>

        {#if query.trim().length === 0}
            <div class="px-4 py-4">
                <p class="text-xs font-medium text-muted-foreground">Cari di pekerjaan Anda</p>
                <p class="mt-1 text-xs text-muted-foreground/70">Temukan Project atau Task berdasarkan nama, ID, dan judul.</p>
            </div>
        {:else if loading}
            <div class="px-4 py-5 text-sm text-muted-foreground">Mencari...</div>
        {:else if requestError}
            <div class="px-4 py-5 text-sm text-muted-foreground">Pencarian gagal. Coba lagi.</div>
        {:else if results.length === 0}
            <div class="px-4 py-5 text-sm text-muted-foreground">Tidak ada hasil untuk “{query}”.</div>
        {:else}
            <div class="max-h-[min(24rem,50vh)] overflow-y-auto p-2" role="listbox" aria-label="Hasil pencarian">
                {#each results as result, index (result.type + result.id)}
                    {#if index === 0 || results[index - 1].type !== result.type}
                        <p class="px-2 pb-1 pt-2 text-[11px] font-medium text-muted-foreground first:pt-1">{result.type === 'project' ? 'Project' : 'Task'}</p>
                    {/if}
                    <button id={`spotlight-result-${result.id}`} type="button" role="option" aria-selected={selectedIndex === index} class="group flex min-h-12 w-full items-center gap-3 rounded-md px-3 text-left transition-colors hover:bg-accent/70 focus-visible:bg-accent/70 focus-visible:outline-none {selectedIndex === index ? 'bg-accent text-accent-foreground' : ''}" onclick={() => selectResult(result)} onmouseenter={() => { selectedIndex = index; }}>
                        <span class="flex size-7 shrink-0 items-center justify-center rounded-md border border-border/70 bg-background text-muted-foreground group-hover:border-border {selectedIndex === index ? 'border-border bg-background/70' : ''}">
                            {#if result.type === 'project'}<FolderTree class="size-3.5" aria-hidden="true" />{:else}<Task class="size-3.5" aria-hidden="true" />{/if}
                        </span>
                        <span class="min-w-0 flex-1"><span class="block truncate text-[13px] font-medium">{result.label}</span><span class="block truncate text-xs text-muted-foreground">{result.detail}</span></span>
                        {#if selectedIndex === index}<kbd class="hidden rounded border border-border/70 bg-background/60 px-1.5 py-0.5 font-mono text-[10px] text-muted-foreground sm:inline">Enter</kbd>{/if}
                    </button>
                {/each}
            </div>
            <div class="flex h-10 items-center justify-between border-t border-border/70 px-4 text-[11px] text-muted-foreground"><span><kbd class="mr-1 rounded border border-border/70 px-1 py-0.5 font-mono">↑↓</kbd> pilih</span><span><kbd class="mr-1 rounded border border-border/70 px-1 py-0.5 font-mono">↵</kbd> buka</span></div>
        {/if}
    </DialogContent>
</Dialog>
