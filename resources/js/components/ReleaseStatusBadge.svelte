<script lang="ts">
    let { status }: { status: string } = $props();

    const labels: Record<string, string> = { draft: 'Draft', planned: 'Direncanakan', released: 'Dirilis', canceled: 'Dibatalkan' };
    const colors: Record<string, string> = {
        draft: 'border-slate-300 bg-slate-100 text-slate-700 dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-300',
        planned: 'border-amber-300 bg-amber-50 text-amber-800 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-300',
        released: 'border-emerald-300 bg-emerald-50 text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300',
        canceled: 'border-rose-300 bg-rose-50 text-rose-800 dark:border-rose-800 dark:bg-rose-950/40 dark:text-rose-300',
    };
    const dots: Record<string, string> = { draft: 'bg-slate-500', planned: 'bg-amber-500', released: 'bg-emerald-500', canceled: 'bg-rose-500' };
</script>

<span class={`inline-flex min-h-6 items-center gap-1.5 rounded-full border px-2 py-0.5 text-xs font-medium ${colors[status] ?? 'border-border/70 bg-background text-foreground'}`}>
    <span class={`size-1.5 rounded-full ${dots[status] ?? 'bg-muted-foreground'}`} aria-hidden="true"></span>
    {labels[status] ?? status}
</span>
