const dateFormatter = new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
});

const dateTimeFormatter = new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
});

export function formatDate(value: string | null | undefined, timeZone?: string): string {
    if (!value) return '—';

    return timeZone ? new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short', year: 'numeric', timeZone }).format(new Date(value)) : dateFormatter.format(new Date(value));
}

export function formatDateTime(value: string | null | undefined, timeZone?: string): string {
    if (!value) return '—';

    return timeZone ? new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', timeZone }).format(new Date(value)) : dateTimeFormatter.format(new Date(value));
}
