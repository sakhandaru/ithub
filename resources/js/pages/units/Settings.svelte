<script module lang="ts">
    export const layout = {
        breadcrumbs: [{ title: 'Setelan Unit' }],
    };
</script>

<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import PageHeader from '@/components/PageHeader.svelte';
    import InputError from '@/components/InputError.svelte';
    import UserAvatar from '@/components/UserAvatar.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import UnitController from '@/actions/App/Http/Controllers/UnitController';
    import { dashboard as unitDashboard } from '@/routes/units';
    import { toUrl } from '@/lib/utils';

    type Unit = { id: string; name: string; workspace_id: string; projects_count: number };
    type Member = { id: string; user: { id: number; name: string; email: string; avatar?: string | null } };
    let { unit, members, canManage }: { unit: Unit; members: Member[]; canManage: boolean } = $props();
</script>

<AppHead title="Setelan Unit" />

<div class="flex min-w-0 flex-col gap-4 px-4 py-4 sm:px-6 lg:px-8">
    <PageHeader breadcrumbs={[{ label: unit.name, href: toUrl(unitDashboard(unit.id)) }, { label: 'Setelan' }]}>
        {#snippet actions()}
            <Button variant="outline" class="h-7 shrink-0 border-border/80 bg-background px-2.5 text-xs text-muted-foreground" asChild>
                {#snippet children(props)}<a href={toUrl(unitDashboard(unit.id))} {...props}>Lihat Unit</a>{/snippet}
            </Button>
        {/snippet}
    </PageHeader>

    <div class="min-w-0 max-w-5xl space-y-4">
        <section class="overflow-hidden rounded-md border border-border/70 bg-card">
            <div class="px-5 py-4">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-base font-semibold tracking-tight">Identitas Unit</h2>
                        <p class="mt-1 text-sm text-muted-foreground">Nama ini tampil di navigasi dan konteks pekerjaan.</p>
                    </div>
                    <span class="hidden text-xs text-muted-foreground sm:block">Unit aktif</span>
                </div>
            </div>

            {#if canManage}
                <Form {...UnitController.update.form({ workspace: unit.workspace_id, unit: unit.id })} class="flex flex-col gap-3 border-t border-border/70 px-5 py-4 sm:flex-row sm:items-end">
                    {#snippet children({ errors, processing, wasSuccessful })}
                        <div class="grid min-w-0 flex-1 gap-1.5">
                            <Label for="unit-name" class="text-xs text-muted-foreground">Nama Unit</Label>
                            <Input id="unit-name" name="name" value={unit.name} required maxlength={80} class="h-9" />
                            <InputError message={errors.name} />
                        </div>
                        <div class="flex items-center gap-3">
                            <Button type="submit" class="h-9 shrink-0" disabled={processing}>{processing ? 'Menyimpan...' : 'Simpan perubahan'}</Button>
                            {#if wasSuccessful}<span class="text-xs text-muted-foreground">Tersimpan</span>{/if}
                        </div>
                    {/snippet}
                </Form>
            {:else}
                <p class="border-t border-border/70 px-5 py-4 text-sm text-muted-foreground">Anda dapat melihat Unit ini, tetapi tidak memiliki akses untuk mengubah identitasnya.</p>
            {/if}
        </section>

        <section class="overflow-hidden rounded-md border border-border/70 bg-card">
            <div class="flex items-start justify-between gap-4 px-5 py-4">
                <div>
                    <h2 class="text-base font-semibold tracking-tight">Anggota Unit</h2>
                    <p class="mt-1 text-sm text-muted-foreground">Orang yang dapat bekerja dalam konteks Unit ini.</p>
                </div>
                <div class="shrink-0 text-right">
                    <p class="text-sm font-medium">{members.length}</p>
                    <p class="text-xs text-muted-foreground">Anggota</p>
                </div>
            </div>

            {#if members.length > 0}
                <div class="grid gap-px border-t border-border/70 bg-border/70 sm:grid-cols-2">
                    {#each members as member (member.id)}
                        <div class="flex min-w-0 items-center gap-3 bg-card px-5 py-3">
                            <UserAvatar user={member.user} size="size-6" label={false} />
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium">{member.user.name}</p>
                                <p class="truncate text-xs text-muted-foreground">{member.user.email}</p>
                            </div>
                        </div>
                    {/each}
                </div>
            {:else}
                <p class="border-t border-border/70 px-5 py-5 text-sm text-muted-foreground">Belum ada anggota di Unit ini.</p>
            {/if}
        </section>
    </div>
</div>
