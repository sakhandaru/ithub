<script lang="ts">
    import { page } from '@inertiajs/svelte';
    import ChevronsUpDown from 'carbon-icons-svelte/lib/ChevronSort.svelte';
    import {
        DropdownMenu,
        DropdownMenuContent,
        DropdownMenuTrigger,
    } from '@/components/ui/dropdown-menu';
    import {
        SidebarMenu,
        SidebarMenuButton,
        SidebarMenuItem,
        useSidebar,
    } from '@/components/ui/sidebar';
    import UserInfo from '@/components/UserInfo.svelte';
    import UserMenuContent from '@/components/UserMenuContent.svelte';

    const user = $derived(page.props.auth.user);
    const { isMobile, state: sidebarState } = useSidebar();
</script>

{#if user}
    <SidebarMenu>
        <SidebarMenuItem>
            <DropdownMenu class="w-full">
                <DropdownMenuTrigger asChild>
                    {#snippet children(props)}
                        <SidebarMenuButton
                            asChild
                            size="lg"
                            class="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
                        >
                            {#snippet children(buttonProps)}
                                <button
                                    {...buttonProps}
                                    {...props}
                                    class={buttonProps.class}
                                    type="button"
                                    data-test="sidebar-menu-button"
                                >
                                    <UserInfo {user} />
                                    <ChevronsUpDown class="ml-auto size-4" />
                                </button>
                            {/snippet}
                        </SidebarMenuButton>
                    {/snippet}
                </DropdownMenuTrigger>
                <DropdownMenuContent
                    class="w-full min-w-0 rounded-lg"
                    side={$sidebarState === 'collapsed' && !$isMobile
                        ? 'left'
                        : 'top'}
                    align="end"
                    sideOffset={4}
                >
                    <UserMenuContent {user} />
                </DropdownMenuContent>
            </DropdownMenu>
        </SidebarMenuItem>
    </SidebarMenu>
{/if}
