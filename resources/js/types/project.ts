export type ProjectPerson = {
    id: number;
    name: string;
    email?: string;
};

export type ProjectSummary = {
    id: string;
    name: string;
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
