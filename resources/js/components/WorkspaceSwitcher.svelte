<script lang="ts">
    import { Form, Link, page } from '@inertiajs/svelte';
    import Check from '@lucide/svelte/icons/check';
    import ChevronsUpDown from '@lucide/svelte/icons/chevrons-up-down';
    import Plus from '@lucide/svelte/icons/plus';
    import ActiveWorkspaceController from '@/actions/App/Http/Controllers/ActiveWorkspaceController';
    import WorkspaceSettingsController from '@/actions/App/Http/Controllers/WorkspaceSettingsController';
    import { Button } from '@/components/ui/button';
    import {
        DropdownMenu,
        DropdownMenuContent,
        DropdownMenuSeparator,
        DropdownMenuTrigger,
    } from '@/components/ui/dropdown-menu';
    import { create as createWorkspace } from '@/routes/workspaces';
    import { toUrl } from '@/lib/utils';

    const workspaceContext = $derived(page.props.workspaceContext);
</script>

<DropdownMenu>
    <DropdownMenuTrigger asChild>
        {#snippet children(props)}
            <Button
                {...props}
                variant="ghost"
                class="min-h-10 w-full justify-between px-2.5 hover:bg-sidebar-accent"
                aria-label="Pilih Workspace"
            >
                <span class="min-w-0 text-left">
                    <span class="block truncate text-sm font-semibold">
                        {workspaceContext.active?.name ?? 'Belum ada Workspace'}
                    </span>
                    <span class="block truncate text-[11px] text-muted-foreground">
                        {workspaceContext.active?.role ?? 'Mulai dari sini'}
                    </span>
                </span>
                <ChevronsUpDown class="size-4 shrink-0 text-muted-foreground" aria-hidden="true" />
            </Button>
        {/snippet}
    </DropdownMenuTrigger>
    <DropdownMenuContent side="bottom" align="start" class="w-64">
        {#if workspaceContext.available.length > 0}
            <div class="px-2 py-1.5 text-xs font-medium text-muted-foreground">Workspace Anda</div>
            {#each workspaceContext.available as workspace (workspace.id)}
                <Form {...ActiveWorkspaceController.form(workspace.id)} class="w-full">
                    <button
                        type="submit"
                        class="flex min-h-10 w-full cursor-pointer items-center gap-2 rounded-sm px-2 py-1.5 text-left text-sm hover:bg-accent"
                    >
                        <span class="min-w-0 flex-1 truncate">{workspace.name}</span>
                        {#if workspace.id === workspaceContext.active?.id}
                            <Check class="size-4 shrink-0" aria-label="Workspace aktif" />
                        {/if}
                    </button>
                </Form>
            {/each}
        {:else}
            <p class="px-2 py-2 text-sm text-muted-foreground">Belum ada Workspace.</p>
        {/if}

        <DropdownMenuSeparator />

        <Link
            href={toUrl(createWorkspace())}
            class="flex min-h-10 items-center gap-2 rounded-sm px-2 py-1.5 text-sm hover:bg-accent"
        >
            <Plus class="size-4" aria-hidden="true" />
            Buat Workspace
        </Link>

        {#if workspaceContext.active?.can_manage}
            <Link
                href={toUrl(WorkspaceSettingsController.show(workspaceContext.active.id))}
                class="flex min-h-10 items-center gap-2 rounded-sm px-2 py-1.5 text-sm hover:bg-accent"
            >
                Pengaturan Workspace
            </Link>
        {/if}
    </DropdownMenuContent>
</DropdownMenu>
