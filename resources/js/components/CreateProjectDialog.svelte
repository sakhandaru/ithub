<script lang="ts">
    import { Form, page } from '@inertiajs/svelte';
    import ProjectController from '@/actions/App/Http/Controllers/ProjectController';
    import InputError from '@/components/InputError.svelte';
    import UserAvatar from '@/components/UserAvatar.svelte';
    import { Button } from '@/components/ui/button';
    import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogTitle } from '@/components/ui/dialog';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Select, SelectContent, SelectItem, SelectTrigger } from '@/components/ui/select';
    import type { ProjectOption } from '@/types';

    type Workspace = { id: string; name: string };
    type Unit = { id: string; name: string };
    type Person = { id: number; name: string; email: string; avatar?: string | null };
    type EmojiGroup = { label: string; items: string[] };

    const emojiGroups: EmojiGroup[] = [
        { label: 'Sering dipakai', items: ['📁', '🚀', '💡', '🎯', '⭐', '🔥', '✅', '⚡', '💬', '🔔', '❤️', '👏', '🙌', '👀', '🙏', '✨', '🎉', '💯'] },
        { label: 'Pekerjaan', items: ['💻', '🖥️', '📱', '⌨️', '🖱️', '🛠️', '🔧', '🔨', '⚙️', '🧰', '🔩', '📊', '📈', '📉', '🗂️', '📋', '📌', '📝', '📎', '📦', '🏷️', '🔒', '🔑', '🧩', '🧪', '🗃️', '🗄️', '📂', '📄', '📑', '📰', '📚', '🖨️', '💾', '💿', '🔌', '🔋', '🌐', '🛰️', '📡', '🛜', '🧱', '🏗️', '🧬', '🧠', '🤖', '🐛', '🚀', '🧭', '🎨', '✏️', '🖌️', '🖍️', '📐', '📏', '💬', '📣', '📞', '📧', '🤝', '👥', '🧑‍💻', '👩‍💻', '🧑‍🔧', '🧑‍🎨', '🔍', '🔬', '📅', '⏱️', '⏳', '✅', '☑️', '❌', '⚠️', '🚧', '🎯', '🏆', '💡', '🔥', '⭐', '💯'] },
        { label: 'Aktivitas', items: ['🏃', '🚶', '🏋️', '🚲', '✈️', '🚗', '🏠', '🌍', '🌐', '📅', '⏰', '⌛', '☕', '🍽️', '🎓', '🏆', '🎨', '🎵', '📚', '💰'] },
        { label: 'Simbol', items: ['🌱', '🌿', '🌳', '🌈', '☀️', '🌙', '⭐', '🌟', '❄️', '🌊', '🔴', '🟠', '🟡', '🟢', '🔵', '🟣', '⚫', '⚪', '🔺', '🔷'] },
        { label: 'Wajah & orang', items: ['😀', '😃', '😄', '😁', '😅', '😂', '🤣', '😊', '🙂', '🙃', '😉', '😍', '🤩', '🤔', '😎', '🥳', '😴', '😮', '😢', '😡', '🤝', '👍', '👎', '👋', '💪', '🧑‍💻'] },
        { label: 'Makanan', items: ['🍎', '🍊', '🍋', '🍉', '🍇', '🍓', '🍒', '🥑', '🥕', '🌽', '🍔', '🍕', '🍜', '🍣', '🍰', '🍩', '🍪', '🍫', '🍵', '🍺'] },
    ];

    let {
        open = $bindable(false),
        workspace,
        units,
        members,
        statuses,
        visibilities,
    }: {
        open?: boolean;
        workspace: Workspace;
        units: Unit[];
        members: Person[];
        statuses: ProjectOption[];
        visibilities: ProjectOption[];
    } = $props();

    let selectedIcon = $state('📁');
    let emojiOpen = $state(false);
    let emojiPicker: HTMLDivElement;
    let selectedUnit = $state('');
    let selectedStatus = $state('draft');
    let selectedVisibility = $state('unit');
    let selectedLead = $state('self');
    let selectedAssignee = $state('none');

    function filteredEmojiGroups(): EmojiGroup[] {
        return emojiGroups;
    }

    function chooseEmoji(emoji: string): void {
        selectedIcon = emoji;
        emojiOpen = false;
    }

    function closeEmojiPicker(event: MouseEvent): void {
        if (emojiOpen && emojiPicker && !emojiPicker.contains(event.target as Node)) {
            emojiOpen = false;
        }
    }

    function handleEscape(event: KeyboardEvent): void {
        if (event.key === 'Escape' && emojiOpen) {
            event.stopPropagation();
            emojiOpen = false;
        }
    }

    function emojiItems(): string[] {
        const groups = filteredEmojiGroups();

        return [...new Set(groups.flatMap((group) => group.items))];
    }

    function resetProjectForm(): void {
        selectedIcon = '📁';
        selectedUnit = '';
        selectedStatus = 'draft';
        selectedVisibility = 'unit';
        selectedLead = 'self';
        selectedAssignee = 'none';
        emojiOpen = false;
    }

    function selectedLeadMember(): Person | undefined {
        return members.find((member) => String(member.id) === selectedLead);
    }

    function selectedAssigneeMember(): Person | undefined {
        return members.find((member) => String(member.id) === selectedAssignee);
    }

    const leadMember = $derived(selectedLeadMember());
    const assigneeMember = $derived(selectedAssigneeMember());
</script>

<svelte:window onclick={closeEmojiPicker} onkeydown={handleEscape} />

<Dialog bind:open>
    <DialogContent class="max-h-[92vh] max-w-lg overflow-y-auto border-border bg-background p-0 shadow-lg">
        <div class="px-6 pb-1 pt-5 sm:px-7">
            <DialogTitle class="sr-only">Buat Project</DialogTitle>
            <DialogDescription class="sr-only">Buat konteks kerja baru di {workspace.name}.</DialogDescription>
        </div>

        <Form {...ProjectController.store.form(workspace.id)} class="grid gap-4 px-6 pb-5 sm:px-7" resetOnSuccess onSuccess={resetProjectForm}>
            {#snippet children({ errors, processing })}
                <div class="grid gap-2">
                    <Label for="project-name" class="sr-only">Nama Project</Label>
                    <div class="flex items-center gap-2">
                        <div class="relative shrink-0" bind:this={emojiPicker}>
                            <input type="hidden" name="icon" value={selectedIcon} />
                            <button id="project-icon" type="button" class="flex size-9 items-center justify-center rounded-md bg-[#2373F4] text-2xl outline-none transition-colors hover:bg-[#1D63D8] focus-visible:ring-2 focus-visible:ring-[#2373F4] focus-visible:ring-offset-2 focus-visible:ring-offset-background" aria-label="Pilih emoji Project" aria-haspopup="listbox" aria-expanded={emojiOpen} aria-controls="project-emoji-options" onclick={() => { emojiOpen = !emojiOpen; }}>
                                <span aria-hidden="true">{selectedIcon}</span>
                            </button>
                            {#if emojiOpen}
                                <div id="project-emoji-options" class="absolute left-0 top-full z-30 mt-2 w-[min(20rem,calc(100vw-3rem))] overflow-hidden rounded-lg border border-border bg-popover text-popover-foreground shadow-lg" role="listbox" aria-label="Emoji Project">
                                    <div class="max-h-56 overflow-y-auto p-3">
                                        <div class="grid grid-cols-8 gap-1.5 sm:grid-cols-10">
                                            {#each emojiItems() as emoji (emoji)}
                                                <button type="button" role="option" aria-label={`Pilih ${emoji}`} aria-selected={selectedIcon === emoji} class={`flex size-8 items-center justify-center rounded-md text-lg transition-colors hover:bg-muted focus-visible:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring ${selectedIcon === emoji ? 'bg-primary/10 ring-1 ring-primary/30' : ''}`} onclick={() => chooseEmoji(emoji)} title={emoji}>{emoji}</button>
                                            {/each}
                                        </div>
                                    </div>
                                </div>
                            {/if}
                        </div>
                        <Input id="project-name" name="name" class="h-auto min-w-0 flex-1 rounded-none border-0 bg-transparent px-0 text-2xl font-medium shadow-none focus-visible:ring-0" required maxlength={120} autocomplete="off" autofocus placeholder="Nama Project" />
                    </div>
                    <InputError message={errors.name} />
                    <InputError message={errors.icon} />
                </div>

                <div class="grid gap-3 border-t border-border/70 pt-4">
                    <div class="grid gap-2 sm:grid-cols-[150px_minmax(0,1fr)] sm:items-center">
                        <Label for="project-identifier" class="text-sm font-normal text-muted-foreground">Project ID</Label>
                        <div>
                            <Input id="project-identifier" name="identifier" class="h-8 bg-surface-2/70 font-mono text-xs" required maxlength={12} pattern="[A-Z0-9]+" placeholder="DEV" autocomplete="off" />
                            <p class="mt-1 text-xs text-muted-foreground">Prefix Task, misalnya DEV-123.</p>
                            <InputError message={errors.identifier} />
                        </div>
                    </div>

                    <div class="grid gap-2 sm:grid-cols-[150px_minmax(0,1fr)] sm:items-center">
                        <Label for="project-unit" class="text-sm font-normal text-muted-foreground">Unit utama</Label>
                        <Select value={selectedUnit} onValueChange={(value) => { selectedUnit = value; }}>
                            <SelectTrigger id="project-unit" class="h-8 w-full rounded-md border border-border/70 bg-surface-2/70 px-3 text-xs shadow-none focus:ring-2 focus:ring-ring"><span class={selectedUnit ? 'text-foreground' : 'text-muted-foreground'}>{units.find((unit) => unit.id === selectedUnit)?.name ?? 'Pilih Unit'}</span></SelectTrigger>
                            <SelectContent>
                                {#each units as unit (unit.id)}<SelectItem value={unit.id}>{unit.name}</SelectItem>{/each}
                            </SelectContent>
                        </Select>
                        <input type="hidden" name="unit_id" value={selectedUnit} required />
                        <InputError message={errors.unit_id} />
                    </div>

                    <div class="grid gap-2 sm:grid-cols-[150px_minmax(0,1fr)] sm:items-center">
                        <Label for="project-status" class="text-sm font-normal text-muted-foreground">Status awal</Label>
                        <Select value={selectedStatus} onValueChange={(value) => { selectedStatus = value; }}>
                            <SelectTrigger id="project-status" class="h-8 w-fit min-w-40 max-w-full rounded-md border border-border/70 bg-surface-2/70 px-3 text-xs shadow-none focus:ring-2 focus:ring-ring"><span>{statuses.find((status) => status.value === selectedStatus)?.label ?? 'Pilih status'}</span></SelectTrigger>
                            <SelectContent>
                                {#each statuses as status}<SelectItem value={status.value}>{status.label}</SelectItem>{/each}
                            </SelectContent>
                        </Select>
                        <input type="hidden" name="status" value={selectedStatus} required />
                        <InputError message={errors.status} />
                    </div>

                    <div class="grid gap-2 sm:grid-cols-[150px_minmax(0,1fr)] sm:items-center">
                        <Label for="project-visibility" class="text-sm font-normal text-muted-foreground">Visibility</Label>
                        <Select value={selectedVisibility} onValueChange={(value) => { selectedVisibility = value; }}>
                            <SelectTrigger id="project-visibility" class="h-8 w-fit min-w-40 max-w-full rounded-md border border-border/70 bg-surface-2/70 px-3 text-xs shadow-none focus:ring-2 focus:ring-ring"><span>{visibilities.find((visibility) => visibility.value === selectedVisibility)?.label ?? 'Pilih visibility'}</span></SelectTrigger>
                            <SelectContent>
                                {#each visibilities as visibility}<SelectItem value={visibility.value}>{visibility.label}</SelectItem>{/each}
                            </SelectContent>
                        </Select>
                        <input type="hidden" name="visibility" value={selectedVisibility} required />
                        <InputError message={errors.visibility} />
                    </div>

                    <div class="grid gap-2 sm:grid-cols-[150px_minmax(0,1fr)] sm:items-center">
                        <Label for="project-lead" class="text-sm font-normal text-muted-foreground">Project Lead</Label>
                        <Select value={selectedLead} onValueChange={(value) => { selectedLead = value; }}>
                            <SelectTrigger id="project-lead" class="h-8 w-full rounded-md border border-border/70 bg-surface-2/70 px-3 text-xs shadow-none focus:ring-2 focus:ring-ring"><span class="flex min-w-0 items-center gap-1.5">{#if selectedLead === 'self'}<UserAvatar user={page.props.auth?.user ?? null} size="size-5" label={false} /><span>Saya sebagai Project Lead</span>{:else if leadMember}<UserAvatar user={leadMember} size="size-5" label={false} /><span class="truncate">{leadMember.name}</span>{:else}<span class="text-muted-foreground">Pilih Project Lead</span>{/if}</span></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="self"><span class="inline-flex min-h-6 items-center gap-1.5"><span class="flex size-5 items-center justify-center rounded-full bg-primary/15 text-[9px] font-semibold text-primary" aria-hidden="true">SA</span>Saya sebagai Project Lead</span></SelectItem>
                                {#each members as member (member.id)}<SelectItem value={String(member.id)}><span class="inline-flex min-h-6 items-center gap-1.5"><UserAvatar user={member} size="size-5" label={false} />{member.name}</span></SelectItem>{/each}
                            </SelectContent>
                        </Select>
                        <input type="hidden" name="project_lead_id" value={selectedLead === 'self' ? '' : selectedLead} />
                        <InputError message={errors.project_lead_id} />
                    </div>

                    <div class="grid gap-2 sm:grid-cols-[150px_minmax(0,1fr)] sm:items-center">
                        <Label for="default-assignee" class="text-sm font-normal text-muted-foreground">PIC default</Label>
                        <Select value={selectedAssignee} onValueChange={(value) => { selectedAssignee = value; }}>
                            <SelectTrigger id="default-assignee" class="h-8 w-full rounded-md border border-border/70 bg-surface-2/70 px-3 text-xs shadow-none focus:ring-2 focus:ring-ring"><span class="flex min-w-0 items-center gap-1.5">{#if selectedAssignee === 'none'}<span class="flex size-5 shrink-0 items-center justify-center rounded-full border border-dashed border-muted-foreground/50 text-[9px] text-muted-foreground" aria-hidden="true">—</span><span class="text-muted-foreground">Tidak ada PIC</span>{:else if assigneeMember}<UserAvatar user={assigneeMember} size="size-5" label={false} /><span class="truncate">{assigneeMember.name}</span>{:else}<span class="text-muted-foreground">Pilih PIC</span>{/if}</span></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none"><span class="inline-flex min-h-6 items-center gap-1.5"><span class="flex size-5 items-center justify-center rounded-full border border-dashed border-muted-foreground/50 text-[9px] text-muted-foreground" aria-hidden="true">—</span>Tidak ada PIC</span></SelectItem>
                                {#each members as member (member.id)}<SelectItem value={String(member.id)}><span class="inline-flex min-h-6 items-center gap-1.5"><UserAvatar user={member} size="size-5" label={false} />{member.name}</span></SelectItem>{/each}
                            </SelectContent>
                        </Select>
                        <input type="hidden" name="default_assignee_id" value={selectedAssignee === 'none' ? '' : selectedAssignee} />
                        <InputError message={errors.default_assignee_id} />
                    </div>

                    <div class="grid gap-2 sm:grid-cols-[150px_minmax(0,1fr)] sm:items-center">
                        <div class="text-sm text-muted-foreground">Tanggal</div>
                        <div class="flex flex-wrap items-center gap-2">
                            <Input id="start-date" name="start_date" type="date" class="h-8 w-auto border-0 bg-muted/30 px-2 text-sm shadow-none focus-visible:ring-0" />
                            <span class="text-muted-foreground">→</span>
                            <Input id="target-date" name="target_date" type="date" class="h-8 w-auto border-0 bg-muted/30 px-2 text-sm shadow-none focus-visible:ring-0" />
                        </div>
                    </div>
                </div>

                <div class="grid gap-1.5 border-t border-border/70 pt-4">
                    <div class="flex items-center gap-2 text-xs text-muted-foreground"><Label for="project-description" class="font-normal">Deskripsi atau tujuan <span>(opsional)</span></Label></div>
                    <textarea id="project-description" name="description" rows={3} maxlength={10000} placeholder="Apa yang ingin diselesaikan project ini?" class="min-h-20 w-full resize-y border-0 bg-transparent px-0 py-1 text-sm leading-6 outline-none placeholder:text-muted-foreground/60 focus-visible:ring-0"></textarea>
                    <InputError message={errors.description} />
                </div>

                <div class="flex flex-col-reverse gap-2 border-t border-border/70 pt-4 sm:flex-row sm:justify-end">
                    <Button type="button" variant="ghost" onclick={() => { open = false; }}>Batal</Button>
                    <Button type="submit" disabled={processing}>{processing ? 'Membuat...' : 'Buat Project'}</Button>
                </div>
            {/snippet}
        </Form>
    </DialogContent>
</Dialog>
