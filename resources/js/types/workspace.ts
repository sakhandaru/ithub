export type WorkspaceSummary = {
    id: string;
    name: string;
    timezone: string;
    role: string;
    can_manage: boolean;
};

export type WorkspaceContext = {
    active: WorkspaceSummary | null;
    available: Array<Pick<WorkspaceSummary, 'id' | 'name' | 'role'>>;
};

export type WorkspaceMember = {
    id: string;
    user: {
        id: number;
        name: string;
        email: string;
    };
    role: string;
    role_label: string;
    joined_at: string;
};

export type UnitMember = {
    id: string;
    user: WorkspaceMember['user'];
};

export type WorkspaceUnit = {
    id: string;
    name: string;
    memberships: UnitMember[];
};

export type WorkspaceRoleOption = {
    value: string;
    label: string;
};
