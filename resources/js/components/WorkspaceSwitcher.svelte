<script lang="ts">
    import { Form, page } from '@inertiajs/svelte';
    import Check from 'carbon-icons-svelte/lib/Checkmark.svelte';
    import ChevronsUpDown from 'carbon-icons-svelte/lib/ChevronSort.svelte';
    import Workspace from 'carbon-icons-svelte/lib/Workspace.svelte';
    import ActiveUnitController from '@/actions/App/Http/Controllers/ActiveUnitController';
    import { Button } from '@/components/ui/button';
    import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';

    let { compact = false }: { compact?: boolean } = $props();
    const unitContext = $derived(page.props.unitContext);
</script>

<DropdownMenu class={compact ? undefined : 'w-full'}>
    <DropdownMenuTrigger asChild>
        {#snippet children(props)}
            <Button {...props} variant="ghost" class={compact ? 'size-10 rounded-md p-0 text-muted-foreground hover:bg-sidebar-accent hover:text-sidebar-foreground' : 'h-8 min-h-0 w-full justify-between gap-1 px-1.5 hover:bg-sidebar-accent'} aria-label={`Pilih Unit${unitContext.active ? `: ${unitContext.active.name}` : ''}`} title={`Pilih Unit${unitContext.active ? `: ${unitContext.active.name}` : ''}`}>
                {#if compact}
                    <Workspace class="size-5" aria-hidden="true" />
                {:else}
                    <span class="min-w-0 text-left"><span class="block truncate text-xs font-semibold leading-4">{unitContext.active?.name ?? 'Belum ada Unit'}</span></span>
                    <ChevronsUpDown class="size-3.5 shrink-0 text-muted-foreground" aria-hidden="true" />
                {/if}
            </Button>
        {/snippet}
    </DropdownMenuTrigger>
    <DropdownMenuContent side={compact ? 'right' : 'bottom'} align="start" class="w-64">
        {#if unitContext.available.length > 0}
            <div class="px-2 py-1.5 text-xs font-medium text-muted-foreground">Unit Anda</div>
            {#each unitContext.available as unit (unit.id)}
                <Form {...ActiveUnitController.form(unit.id)} class="w-full">
                    <button type="submit" class="flex min-h-10 w-full cursor-pointer items-center gap-2 rounded-sm px-2 py-1.5 text-left text-sm hover:bg-accent">
                        <span class="min-w-0 flex-1 truncate">{unit.name}</span>
                        {#if unit.id === unitContext.active?.id}<Check class="size-4 shrink-0" aria-label="Unit aktif" />{/if}
                    </button>
                </Form>
            {/each}
        {:else}
            <p class="px-2 py-2 text-sm text-muted-foreground">Belum ada Unit.</p>
        {/if}
    </DropdownMenuContent>
</DropdownMenu>
