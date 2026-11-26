<script module lang="ts">
    import { updates } from '@/routes/workspaces/projects';
    export const layout = { breadcrumbs: [{ title: 'Update', href: updates(['', '']) }] };
</script>

<script lang="ts">
    import { Form, page } from '@inertiajs/svelte';
    import RefreshCw from 'carbon-icons-svelte/lib/Renew.svelte';
    import Plus from 'carbon-icons-svelte/lib/Add.svelte';
    import ChevronDown from 'carbon-icons-svelte/lib/ChevronDown.svelte';
    import CalendarDays from 'carbon-icons-svelte/lib/Calendar.svelte';
    import CheckCircle2 from 'carbon-icons-svelte/lib/CheckmarkFilled.svelte';
    import CircleAlert from 'carbon-icons-svelte/lib/WarningAlt.svelte';
    import CircleDashed from 'carbon-icons-svelte/lib/CircleDash.svelte';
    import Ban from 'carbon-icons-svelte/lib/ErrorOutline.svelte';
    import TextAlignLeft from 'carbon-icons-svelte/lib/TextAlignLeft.svelte';
    import ProjectController from '@/actions/App/Http/Controllers/ProjectController';
    import ProjectUpdateController from '@/actions/App/Http/Controllers/ProjectUpdateController';
    import AppHead from '@/components/AppHead.svelte';
    import InputError from '@/components/InputError.svelte';
    import PageHeader from '@/components/PageHeader.svelte';
    import { Button } from '@/components/ui/button';
    import { Dialog, DialogContent, DialogDescription, DialogTitle } from '@/components/ui/dialog';
    import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { getInitials } from '@/lib/initials';
    import { projectBreadcrumbs } from '@/lib/page-header';
    import { toUrl } from '@/lib/utils';

    type Workspace = { id: string; name: string };
    type Project = { id: string; name: string; identifier: string; archived: boolean };
    type Member = { id: number; name: string; avatar?: string | null };
    type Update = { id: string; title: string | null; health: string; period_start: string | null; period_end: string | null; summary: string; accomplishments: string | null; risks: string | null; decisions_needed: string | null; next_steps: string | null; next_update_at: string | null; created_at: string; author: { id: number; name: string } | null; mentioned_users: Member[] };
    let { workspace, project, updates: items, members, canCreate }: { workspace: Workspace; project: Project; updates: Update[]; members: Member[]; canCreate: boolean } = $props();
    let createOpen = $state(false);
    let selectedHealth = $state('on_track');
    const toDateInput = (date: Date): string => [date.getFullYear(), String(date.getMonth() + 1).padStart(2, '0'), String(date.getDate()).padStart(2, '0')].join('-');
    let periodStart = $state(toDateInput(new Date(Date.now() - 6 * 24 * 60 * 60 * 1000)));
    let periodEnd = $state(toDateInput(new Date()));
    let selectedMentionIds = $state<string[]>([]);
    const healthLabels: Record<string, string> = { on_track: 'On track', at_risk: 'At risk', off_track: 'Off track' };
    const healthColors: Record<string, string> = { on_track: 'border-emerald-300 bg-emerald-50 text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300', at_risk: 'border-amber-300 bg-amber-50 text-amber-800 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-300', off_track: 'border-rose-300 bg-rose-50 text-rose-800 dark:border-rose-800 dark:bg-rose-950/40 dark:text-rose-300' };
    function toggleMention(memberId: number): void {
        const id = String(memberId);
        selectedMentionIds = selectedMentionIds.includes(id) ? selectedMentionIds.filter((selectedId) => selectedId !== id) : [...selectedMentionIds, id];
    }
</script>

<AppHead title={`${project.identifier} · Update`} />
<div class="flex min-w-0 flex-col gap-5 px-4 py-5 sm:px-6 lg:px-8">
    <PageHeader breadcrumbs={projectBreadcrumbs(page.props.unitContext?.active?.name ?? workspace.name, page.props.unitContext?.active?.id, toUrl(ProjectController.index(workspace.id)), project.name, toUrl(ProjectController.show({ workspace: workspace.id, project: project.id })), 'updates', undefined, page.props.projectContext?.active?.icon)}>
        {#snippet actions()}
            {#if canCreate}
                <Button type="button" variant="outline" class="h-7 shrink-0 gap-1.5 border-border/80 bg-background px-2.5 text-xs text-muted-foreground" onclick={() => { createOpen = true; }}>
                    <Plus class="size-3.5" aria-hidden="true" />
                    Tulis Update
                </Button>
            {/if}
        {/snippet}
    </PageHeader>
    {#if project.archived}<div class="rounded-md border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-100" role="status">Project ini diarsipkan. Update bersifat read-only.</div>{/if}
    <section aria-label="Riwayat Update">
        {#if items.length === 0}
            <div class="flex min-h-64 flex-col items-center justify-center rounded-lg border border-dashed border-border/80 bg-card px-6 py-12 text-center"><RefreshCw class="size-8 text-muted-foreground" aria-hidden="true" /><h2 class="mt-4 text-sm font-semibold">Belum ada Update</h2><p class="mt-1 text-sm text-muted-foreground">Tulis update pertama untuk memberi konteks kepada tim.</p></div>
        {:else}
            <div class="relative grid gap-4 before:absolute before:bottom-4 before:left-[7px] before:top-4 before:w-px before:bg-border/70">
                {#each items as update (update.id)}
                    <article class="relative pl-7">
                        <span class={`absolute left-0 top-1.5 size-4 rounded-full border-4 border-background ${update.health === 'on_track' ? 'bg-emerald-500' : update.health === 'at_risk' ? 'bg-amber-500' : 'bg-rose-500'}`} aria-hidden="true"></span>
                        <div class="overflow-hidden rounded-lg border border-border/70 bg-card">
                            <header class="flex flex-wrap items-center justify-between gap-3 border-b border-border/70 px-4 py-3 sm:px-5">
                                <div class="flex min-w-0 flex-wrap items-center gap-2">
                                    <h2 class="text-sm font-semibold">{update.title ?? 'Update Project'}</h2>
                                    <span class={`inline-flex items-center gap-1.5 rounded-md border px-2 py-0.5 text-xs font-medium ${healthColors[update.health]}`}>
                                        {#if update.health === 'on_track'}<CheckCircle2 class="size-3.5" aria-hidden="true" />{:else if update.health === 'at_risk'}<CircleAlert class="size-3.5" aria-hidden="true" />{:else}<Ban class="size-3.5" aria-hidden="true" />{/if}
                                        {healthLabels[update.health] ?? update.health}
                                    </span>
                                </div>
                                <span class="text-xs text-muted-foreground">{update.period_start ?? new Date(update.created_at).toLocaleDateString('id-ID')} {#if update.period_end}– {update.period_end}{/if}</span>
                            </header>
                            <div class="max-w-4xl px-4 py-4 sm:px-5">
                                <p class="whitespace-pre-line text-sm leading-6">{update.summary}</p>
                                {#if update.accomplishments || update.risks || update.decisions_needed || update.next_steps}
                                    <div class="mt-5 grid gap-4 border-t border-border/70 pt-4 sm:grid-cols-2">
                                        {#if update.accomplishments}<div><p class="flex items-center gap-1.5 text-xs font-medium text-muted-foreground"><CheckCircle2 class="size-3.5" aria-hidden="true" />Pencapaian</p><p class="mt-1 whitespace-pre-line text-sm leading-6">{update.accomplishments}</p></div>{/if}
                                        {#if update.risks}<div><p class="flex items-center gap-1.5 text-xs font-medium text-muted-foreground"><CircleAlert class="size-3.5" aria-hidden="true" />Risiko</p><p class="mt-1 whitespace-pre-line text-sm leading-6">{update.risks}</p></div>{/if}
                                        {#if update.decisions_needed}<div><p class="flex items-center gap-1.5 text-xs font-medium text-muted-foreground"><CircleDashed class="size-3.5" aria-hidden="true" />Keputusan dibutuhkan</p><p class="mt-1 whitespace-pre-line text-sm leading-6">{update.decisions_needed}</p></div>{/if}
                                        {#if update.next_steps}<div><p class="flex items-center gap-1.5 text-xs font-medium text-muted-foreground"><TextAlignLeft class="size-3.5" aria-hidden="true" />Berikutnya</p><p class="mt-1 whitespace-pre-line text-sm leading-6">{update.next_steps}</p></div>{/if}
                                    </div>
                                {/if}
                            </div>
                            <footer class="flex flex-wrap items-center gap-x-3 gap-y-1 border-t border-border/70 px-4 py-3 text-xs text-muted-foreground sm:px-5">
                                <span>Ditulis oleh {update.author?.name ?? 'User'}</span><span aria-hidden="true">·</span><span class="inline-flex items-center gap-1.5"><CalendarDays class="size-3.5" aria-hidden="true" />Update berikutnya {update.next_update_at ?? 'belum dijadwalkan'}</span>
                                {#if update.mentioned_users.length > 0}<span aria-hidden="true">·</span><span>Bagikan ke {update.mentioned_users.map((user) => user.name).join(', ')}</span>{/if}
                            </footer>
                        </div>
                    </article>
                {/each}
            </div>
        {/if}
    </section>
</div>

<Dialog bind:open={createOpen}>
    <DialogContent class="max-h-[92vh] max-w-xl overflow-y-auto border-border bg-background p-0 shadow-lg">
        <div class="px-6 pb-1 pt-5 sm:px-7">
            <DialogTitle class="text-sm font-medium text-muted-foreground">Update:</DialogTitle>
            <DialogDescription class="sr-only">Bagikan perubahan, risiko, dan langkah berikutnya kepada tim.</DialogDescription>
        </div>
        <Form {...ProjectUpdateController.store.form({ workspace: workspace.id, project: project.id })} class="grid gap-4 px-6 pb-5 sm:px-7" resetOnSuccess onSuccess={() => { createOpen = false; selectedHealth = 'on_track'; selectedMentionIds = []; }}>
            {#snippet children({ errors, processing })}
                <div class="grid gap-1.5">
                    <Label for="update-summary" class="sr-only">Apa yang terjadi?</Label>
                    <textarea id="update-summary" name="summary" rows={4} maxlength={10000} required class="min-h-24 w-full resize-y border-0 bg-transparent px-0 py-1 text-xl font-medium leading-7 outline-none placeholder:text-muted-foreground/60 focus-visible:ring-0" placeholder="Apa yang terjadi sejak update terakhir?"></textarea>
                    <InputError message={errors.summary} />
                </div>
                <div class="grid gap-3 border-t border-border/70 pt-4">
                    <div class="grid gap-2 sm:grid-cols-[150px_minmax(0,1fr)] sm:items-center">
                        <div class="flex items-center gap-2 text-sm text-muted-foreground"><CircleDashed class="size-4" aria-hidden="true" />Kondisi Project</div>
                    <input type="hidden" name="health" value={selectedHealth} />
                    <div class="grid grid-cols-3 gap-1.5" role="group" aria-label="Kondisi Project">
                        {#each Object.entries(healthLabels) as [health, label]}
                            <button type="button" class={`flex h-9 items-center justify-center gap-1.5 rounded-md border px-2 text-xs font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring ${selectedHealth === health ? healthColors[health] : 'border-border/70 bg-transparent text-muted-foreground hover:bg-muted'}`} aria-pressed={selectedHealth === health} onclick={() => { selectedHealth = health; }}>
                                {#if health === 'on_track'}<CheckCircle2 class="size-3.5" aria-hidden="true" />{:else if health === 'at_risk'}<CircleAlert class="size-3.5" aria-hidden="true" />{:else}<Ban class="size-3.5" aria-hidden="true" />{/if}
                                {label}
                            </button>
                        {/each}
                    </div>
                    <InputError message={errors.health} />
                </div>
                <div class="grid gap-2 sm:grid-cols-[150px_minmax(0,1fr)] sm:items-center">
                    <div class="flex items-center gap-2 text-sm text-muted-foreground"><CalendarDays class="size-4" aria-hidden="true" />Periode</div>
                    <div class="flex flex-wrap items-center gap-2">
                        <Input name="period_start" type="date" class="h-8 w-auto border-0 bg-muted/30 px-2 text-sm shadow-none focus-visible:ring-0" bind:value={periodStart} required />
                        <span class="text-muted-foreground">→</span>
                        <Input name="period_end" type="date" class="h-8 w-auto border-0 bg-muted/30 px-2 text-sm shadow-none focus-visible:ring-0" bind:value={periodEnd} required />
                    </div>
                    <div class="sm:col-start-2"><InputError message={errors.period_start} /><InputError message={errors.period_end} /></div>
                </div>
                </div>
                <div class="grid gap-2 border-t border-border/70 pt-4">
                    <div class="flex items-center justify-between gap-3">
                        <Label class="text-sm text-muted-foreground">Bagikan ke</Label>
                        <span class="text-xs text-muted-foreground">Opsional</span>
                    </div>
                    {#each selectedMentionIds as selectedId}
                        {@const selectedMember = members.find((member) => String(member.id) === selectedId)}
                        {#if selectedMember}<input type="hidden" name="mentioned_user_ids[]" value={selectedMember.id} />{/if}
                    {/each}
                    <details class="group rounded-md border border-border/70">
                        <summary class="flex cursor-pointer list-none items-center justify-between px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                            <span>{selectedMentionIds.length > 0 ? `${selectedMentionIds.length} anggota dipilih` : 'Pilih anggota Project'}</span>
                            <ChevronDown class="size-4 text-muted-foreground transition-transform group-open:rotate-180" aria-hidden="true" />
                        </summary>
                        <div class="grid gap-1 border-t border-border/70 p-2">
                            {#each members as member (member.id)}
                                <label class="flex cursor-pointer items-center gap-3 rounded-md px-2 py-2 text-sm hover:bg-muted">
                                    <input type="checkbox" checked={selectedMentionIds.includes(String(member.id))} onchange={() => { toggleMention(member.id); }} class="size-4 rounded border-input text-primary focus:ring-ring" />
                                    <Avatar class="size-8">
                                        {#if member.avatar}<AvatarImage src={member.avatar} alt={member.name} />{/if}
                                        <AvatarFallback class="text-xs">{getInitials(member.name)}</AvatarFallback>
                                    </Avatar>
                                    <span class="min-w-0 truncate">{member.name}</span>
                                </label>
                            {/each}
                        </div>
                    </details>
                    <InputError message={errors.mentioned_user_ids} />
                </div>
                <details class="group border-t border-border/70 pt-4">
                    <summary class="flex cursor-pointer list-none items-center justify-between text-sm font-medium focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"><span class="flex items-center gap-2 text-muted-foreground"><TextAlignLeft class="size-3.5" aria-hidden="true" />Tambahkan detail</span><ChevronDown class="size-4 text-muted-foreground transition-transform group-open:rotate-180" aria-hidden="true" /></summary>
                    <div class="mt-4 grid gap-4">
                        <div class="grid gap-2"><Label for="update-accomplishments">Pencapaian</Label><textarea id="update-accomplishments" name="accomplishments" rows={3} maxlength={10000} class="w-full resize-y rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:ring-2 focus-visible:ring-ring" placeholder="Apa yang selesai atau tercapai?"></textarea><InputError message={errors.accomplishments} /></div>
                        <div class="grid gap-4 sm:grid-cols-2"><div class="grid gap-2"><Label for="update-risks">Hambatan atau risiko</Label><textarea id="update-risks" name="risks" rows={3} maxlength={5000} class="w-full resize-y rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:ring-2 focus-visible:ring-ring"></textarea><InputError message={errors.risks} /></div><div class="grid gap-2"><Label for="update-decisions">Keputusan yang dibutuhkan</Label><textarea id="update-decisions" name="decisions_needed" rows={3} maxlength={5000} class="w-full resize-y rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:ring-2 focus-visible:ring-ring"></textarea><InputError message={errors.decisions_needed} /></div></div>
                        <div class="grid gap-2"><Label for="update-next-steps">Langkah berikutnya</Label><textarea id="update-next-steps" name="next_steps" rows={3} maxlength={5000} class="w-full resize-y rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:ring-2 focus-visible:ring-ring"></textarea><InputError message={errors.next_steps} /></div>
                        <div class="grid gap-2"><Label for="update-next-date">Update berikutnya <span class="font-normal text-muted-foreground">(opsional)</span></Label><Input id="update-next-date" name="next_update_at" type="date" /><InputError message={errors.next_update_at} /></div>
                    </div>
                </details>
                <div class="flex flex-col-reverse gap-2 border-t border-border/70 pt-4 sm:flex-row sm:justify-end"><Button type="button" variant="ghost" onclick={() => { createOpen = false; }}>Batal</Button><Button type="submit" disabled={processing}>{processing ? 'Menyimpan...' : 'Publikasikan Update'}</Button></div>
            {/snippet}
        </Form>
    </DialogContent>
</Dialog>
