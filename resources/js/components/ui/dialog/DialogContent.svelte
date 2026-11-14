<script lang="ts">
    import type { Snippet } from 'svelte';
    import { getContext, tick } from 'svelte';
    import { cn } from '@/lib/utils';
    import { DIALOG_CONTEXT, type DialogContext } from './context';

    let { class: className = '', children }: { class?: string; children?: Snippet } = $props();

    const { open, setOpen } = getContext<DialogContext>(DIALOG_CONTEXT);
    let dialogElement = $state<HTMLDivElement>();
    let previousActiveElement: HTMLElement | null = null;

    const close = () => setOpen(false);
    const handleKeydown = (event: KeyboardEvent): void => {
        if (event.key === 'Escape' && open()) {
            event.preventDefault();
            close();
        }
        if (event.key === 'Tab' && dialogElement) {
            const focusable = [...dialogElement.querySelectorAll<HTMLElement>('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])')];
            if (focusable.length === 0) return;
            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
            else if (! event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
        }
    };

    $effect(() => {
        if (open()) {
            previousActiveElement = document.activeElement as HTMLElement | null;
            tick().then(() => dialogElement?.querySelector<HTMLElement>('input, button, a[href]')?.focus());
        } else if (previousActiveElement) {
            previousActiveElement.focus();
            previousActiveElement = null;
        }
    });
</script>

<svelte:window onkeydown={handleKeydown} />

{#if open()}
    <div class="fixed inset-0 z-50 flex items-center justify-center">
        <button
            type="button"
            class="fixed inset-0 bg-black/50"
            aria-label="Tutup dialog"
            onclick={close}
        ></button>
        <div
            bind:this={dialogElement}
            class={cn(
                'relative z-10 w-full max-w-lg rounded-lg border bg-background p-6 shadow-lg',
                className,
            )}
            role="dialog"
            aria-modal="true"
            aria-labelledby="dialog-title"
            aria-describedby="dialog-description"
            tabindex="-1"
        >
            {@render children?.()}
        </div>
    </div>
{/if}
