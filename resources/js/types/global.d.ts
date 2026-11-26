import type { Auth } from '@/types/auth';
import type { UnitContext, WorkspaceContext } from '@/types/workspace';

// Extend ImportMeta interface for Vite...
declare module 'vite/client' {
    interface ImportMetaEnv {
        readonly VITE_APP_NAME: string;
        [key: string]: string | boolean | undefined;
    }

    interface ImportMeta {
        readonly env: ImportMetaEnv;
        readonly glob: <T>(
            pattern: string,
            options?: { eager?: boolean },
        ) => Record<string, T>;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            workspaceContext: WorkspaceContext;
            unitContext: UnitContext;
            projectContext: {
                active: {
                    id: string;
                    workspace_id: string;
                    name: string;
                    icon: string;
                    identifier: string;
                    archived: boolean;
                } | null;
                available: Array<{
                    id: string;
                    workspace_id: string;
                    name: string;
                    icon: string;
                    identifier: string;
                }>;
            };
            [key: string]: unknown;
        };
    }
}

declare module 'frappe-gantt' {
    export type GanttTask = {
        id: string;
        name: string;
        start: string;
        end: string;
        progress?: number;
        dependencies?: string;
        custom_class?: string;
    };

    export type GanttOptions = {
        view_mode?: string;
        date_format?: string;
        language?: string;
        readonly?: boolean;
        readonly_dates?: boolean;
        readonly_progress?: boolean;
        today_button?: boolean;
        view_mode_select?: boolean;
        lines?: 'none' | 'vertical' | 'horizontal' | 'both';
        padding?: number;
        column_width?: number;
        popup_on?: 'click' | 'hover';
        on_click?: (task: GanttTask) => void;
        popup?: false | ((context: { task: GanttTask; set_title: (value: string) => void; set_subtitle: (value: string) => void; set_details: (value: string) => void }) => void);
    };

    export default class Gantt {
        constructor(wrapper: HTMLElement | string, tasks: GanttTask[], options?: GanttOptions);
        refresh(tasks: GanttTask[]): void;
        change_view_mode(mode?: string, maintainPosition?: boolean): void;
        update_options(options: GanttOptions): void;
    }
}
