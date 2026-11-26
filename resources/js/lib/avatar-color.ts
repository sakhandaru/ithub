const avatarColors = [
    'bg-rose-500 text-white dark:bg-rose-400 dark:text-rose-950',
    'bg-amber-600 text-white dark:bg-amber-400 dark:text-amber-950',
    'bg-emerald-600 text-white dark:bg-emerald-400 dark:text-emerald-950',
    'bg-sky-600 text-white dark:bg-sky-400 dark:text-sky-950',
    'bg-violet-600 text-white dark:bg-violet-400 dark:text-violet-950',
    'bg-fuchsia-600 text-white dark:bg-fuchsia-400 dark:text-fuchsia-950',
] as const;

export function avatarColor(name: string): string {
    const hash = [...name].reduce((total, character) => total + character.charCodeAt(0), 0);

    return avatarColors[hash % avatarColors.length];
}
