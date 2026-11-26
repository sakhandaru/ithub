export type ProjectPerson = {
    id: number;
    name: string;
    email?: string;
    avatar?: string | null;
};

export type ProjectSummary = {
    id: string;
    name: string;
    icon: string;
    identifier: string;
    unit: { id: string; name: string } | null;
    project_lead: ProjectPerson | null;
    visibility: string;
    visibility_label: string;
    status: string;
    status_label: string;
    start_date: string | null;
    target_date: string | null;
    archived: boolean;
    tasks_count: number;
    completed_tasks_count: number;
    blocked_tasks_count: number;
    overdue_tasks_count: number;
};

export type ProjectMember = {
    id: string;
    user: ProjectPerson;
    role: string;
    role_label: string;
    joined_at: string;
};

export type ProjectOption = {
    value: string;
    label: string;
};
