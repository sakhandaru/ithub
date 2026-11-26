<script lang="ts">
    import { FlexRender } from '@tanstack/svelte-table';
    import type { Component, Snippet } from 'svelte';

    type DataTableInstance = {
        getHeaderGroups: () => any[];
        getRowModel: () => { rows: any[] };
    };

    let {
        table,
        caption,
        children,
        empty,
        footer,
        leadingHeader,
        headerIcons = {},
        headerActions,
        wrapperClass = 'overflow-x-auto rounded-lg border border-border/80 bg-card',
        tableWrapperClass = 'overflow-x-auto',
        tableClass = 'w-full text-left text-sm',
        headerClass = 'px-4 py-3 font-medium',
        getHeaderClass = () => headerClass,
    }: {
        table: DataTableInstance;
        caption: string;
        children?: Snippet<[any]>;
        empty?: Snippet;
        footer?: Snippet;
        leadingHeader?: Snippet;
        headerIcons?: Record<string, Component>;
        headerActions?: Snippet<[string]>;
        wrapperClass?: string;
        tableWrapperClass?: string;
        tableClass?: string;
        headerClass?: string;
        getHeaderClass?: (headerId: string) => string;
    } = $props();
</script>

<div class={wrapperClass}>
    <div class={tableWrapperClass}>
        <table class={tableClass}>
        <caption class="sr-only">{caption}</caption>
        <thead class="bg-surface-2/70 text-xs text-muted-foreground">
            {#each table.getHeaderGroups() as headerGroup (headerGroup.id)}
                <tr>
                    {@render leadingHeader?.()}
                    {#each headerGroup.headers as header (header.id)}
                        {#if !header.isPlaceholder}
                            {@const HeaderIcon = headerIcons[header.id]}
                            <th class={getHeaderClass(header.id)}>
                                <span class="inline-flex items-center gap-1.5">
                                    {#if HeaderIcon}<HeaderIcon class="size-3.5" aria-hidden="true" />{/if}
                                    <FlexRender header={header} />
                                    {@render headerActions?.(header.id)}
                                </span>
                            </th>
                        {/if}
                    {/each}
                </tr>
            {/each}
        </thead>
        <tbody class="divide-y divide-border/70">
            {#if table.getRowModel().rows.length === 0}
                <tr>
                    <td colspan={table.getHeaderGroups().at(-1)?.headers.length ?? 1}>
                        {#if empty}
                            {@render empty()}
                        {:else}
                            <div class="px-4 py-10 text-center text-sm text-muted-foreground">Belum ada data.</div>
                        {/if}
                    </td>
                </tr>
            {:else}
                {#each table.getRowModel().rows as row (row.id)}
                    {@render children?.(row)}
                {/each}
            {/if}
        </tbody>
        </table>
    </div>
    {@render footer?.()}
</div>
