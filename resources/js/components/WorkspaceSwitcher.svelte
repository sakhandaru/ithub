<script lang="ts">
    import { Form, page } from '@inertiajs/svelte';
    import Check from 'carbon-icons-svelte/lib/Checkmark.svelte';
    import ChevronsUpDown from 'carbon-icons-svelte/lib/ChevronSort.svelte';
    import ActiveUnitController from '@/actions/App/Http/Controllers/ActiveUnitController';
    import { Button } from '@/components/ui/button';
    import {
        DropdownMenu,
        DropdownMenuContent,
        DropdownMenuTrigger,
    } from '@/components/ui/dropdown-menu';

    const unitContext = $derived(page.props.unitContext);
</script>

<DropdownMenu>
    <DropdownMenuTrigger asChild>
        {#snippet children(props)}
            <Button
                {...props}
                variant="ghost"
                class="min-h-9 w-full justify-between px-2 hover:bg-sidebar-accent"
                aria-label="Pilih Unit"
            >
                <span class="min-w-0 text-left">
                    <span class="block truncate text-[13px] font-semibold">
                        {unitContext.active?.name ?? 'Belum ada Unit'}
                    </span>
                    <span class="block truncate text-[10px] text-muted-foreground">
                        {unitContext.active?.role_label ?? 'Mulai dari sini'}
                    </span>
                </span>
                <ChevronsUpDown class="size-4 shrink-0 text-muted-foreground" aria-hidden="true" />
            </Button>
        {/snippet}
    </DropdownMenuTrigger>
    <DropdownMenuContent side="bottom" align="start" class="w-64">
        {#if unitContext.available.length > 0}
            <div class="px-2 py-1.5 text-xs font-medium text-muted-foreground">Unit Anda</div>
            {#each unitContext.available as unit (unit.id)}
                <Form {...ActiveUnitController.form(unit.id)} class="w-full">
                    <button
                        type="submit"
                        class="flex min-h-10 w-full cursor-pointer items-center gap-2 rounded-sm px-2 py-1.5 text-left text-sm hover:bg-accent"
                    >
                        <span class="min-w-0 flex-1 truncate">{unit.name}</span>
                        {#if unit.id === unitContext.active?.id}
                            <Check class="size-4 shrink-0" aria-label="Unit aktif" />
                        {/if}
                    </button>
                </Form>
            {/each}
        {:else}
            <p class="px-2 py-2 text-sm text-muted-foreground">Belum ada Unit.</p>
        {/if}
    </DropdownMenuContent>
</DropdownMenu>
