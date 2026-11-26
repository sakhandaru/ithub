<script lang="ts">
    import { avatarColor } from '@/lib/avatar-color';
    import { getInitials } from '@/lib/initials';

    type AvatarUser = { name: string; avatar?: string | null } | null;
    let { user, size = 'size-5', class: className = '', label = true }: { user: AvatarUser; size?: 'size-4' | 'size-5' | 'size-6' | 'size-8'; class?: string; label?: boolean } = $props();
</script>

{#if user}
    <span class="inline-flex min-w-0 items-center gap-1.5 {className}">
        <span class={`flex ${size} shrink-0 items-center justify-center overflow-hidden rounded-full text-[9px] font-semibold ${user.avatar ? 'bg-muted' : avatarColor(user.name)}`}>
            {#if user.avatar}<img src={user.avatar} alt="" class="size-full object-cover" />{:else}{getInitials(user.name)}{/if}
        </span>
        {#if label}<span class="truncate">{user.name}</span>{/if}
    </span>
{:else}
    <span class="text-muted-foreground">—</span>
{/if}
