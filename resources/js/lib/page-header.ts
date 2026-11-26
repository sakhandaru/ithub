import type { BreadcrumbItem } from '@/components/PageBreadcrumb.svelte';
import { dashboard as unitDashboard } from '@/routes/units';
import { toUrl } from '@/lib/utils';

export type ProjectHeaderPage = 'activity' | 'dashboard' | 'files' | 'milestones' | 'modules' | 'releases' | 'updates' | 'cycles' | 'settings';

export const globalHeaderLabels = {
    allUnits: 'Semua Unit',
    analytics: 'Analitik',
    dashboard: 'Dasbor',
    home: 'Beranda',
    myTasks: 'Tugas saya',
    projects: 'Projects',
    archivedProjects: 'Projects arsip',
    activity: 'Aktivitas',
    files: 'Berkas',
    task: 'Task',
    unit: 'Unit',
} as const;

export const pageHeaderLabels: Record<ProjectHeaderPage, string> = {
    activity: 'Aktivitas',
    dashboard: 'Dashboard',
    files: 'Berkas',
    milestones: 'Milestone',
    modules: 'Modul',
    releases: 'Rilis',
    updates: 'Update',
    cycles: 'Siklus',
    settings: 'Setelan',
};

export function projectBreadcrumbs(unitName: string, unitId: string | undefined, projectsHref: string, projectName: string, projectHref: string, page: ProjectHeaderPage, count?: number, projectIcon?: string): BreadcrumbItem[] {
    return [
        { label: unitName, href: unitId ? toUrl(unitDashboard(unitId)) : undefined, icon: 'none' },
        { label: globalHeaderLabels.projects, href: projectsHref, icon: 'folder' },
        { label: projectName, href: projectHref, projectIcon },
        { label: pageHeaderLabels[page], count, icon: page === 'dashboard' ? 'dashboard' : page === 'modules' ? 'modules' : page === 'files' ? 'files' : page === 'milestones' ? 'milestones' : page === 'updates' ? 'updates' : page === 'releases' ? 'releases' : page === 'activity' ? 'activity' : undefined },
    ];
}

export function taskBreadcrumbs(unitName: string, unitId: string | undefined, projectsHref: string, projectName: string, projectHref: string, taskHref: string, count?: number, taskLabel = globalHeaderLabels.task, projectIcon?: string): BreadcrumbItem[] {
    return [
        { label: unitName, href: unitId ? toUrl(unitDashboard(unitId)) : undefined, icon: 'none' },
        { label: globalHeaderLabels.projects, href: projectsHref, icon: 'folder' },
        { label: projectName, href: projectHref, projectIcon },
        { label: taskLabel, href: taskHref, icon: 'tasks', count },
    ];
}

export function taskDetailBreadcrumbs(unitName: string, unitId: string | undefined, projectsHref: string, projectName: string, projectHref: string, taskHref: string, taskTitle: string, projectIcon?: string): BreadcrumbItem[] {
    return [
        { label: unitName, href: unitId ? toUrl(unitDashboard(unitId)) : undefined, icon: 'none' },
        { label: globalHeaderLabels.projects, href: projectsHref, icon: 'folder' },
        { label: projectName, href: projectHref, projectIcon },
        { label: globalHeaderLabels.task, href: taskHref, icon: 'tasks' },
        { label: taskTitle },
    ];
}

export function dashboardBreadcrumbs(scopeName: string, isUnitScope: boolean, unitId?: string): BreadcrumbItem[] {
    return [{ label: scopeName, href: isUnitScope && unitId ? toUrl(unitDashboard(unitId)) : undefined, icon: 'none' }, { label: isUnitScope ? globalHeaderLabels.dashboard : globalHeaderLabels.home, icon: isUnitScope ? 'dashboard' : undefined }];
}

export function myTasksBreadcrumbs(scopeName: string, count: number, unitId?: string): BreadcrumbItem[] {
    return [{ label: globalHeaderLabels.myTasks, icon: 'user' }, { label: scopeName, href: unitId ? toUrl(unitDashboard(unitId)) : undefined, icon: 'none', count }];
}

export function unitPageBreadcrumbs(unitName: string, page: 'analytics' | 'activity', count?: number, unitId?: string): BreadcrumbItem[] {
    return [{ label: unitName, href: unitId ? toUrl(unitDashboard(unitId)) : undefined, icon: 'none' }, { label: page === 'analytics' ? globalHeaderLabels.analytics : globalHeaderLabels.activity, count, icon: page === 'analytics' ? 'analytics' : 'activity' }];
}

export function projectsIndexBreadcrumbs(unitName: string, unitId: string | undefined, archived: boolean, count?: number): BreadcrumbItem[] {
    return [{ label: unitName, href: unitId ? toUrl(unitDashboard(unitId)) : undefined, icon: 'none' }, { label: archived ? globalHeaderLabels.archivedProjects : globalHeaderLabels.projects, count, icon: archived ? 'archive' : 'folder' }];
}

export function projectDetailBreadcrumbs(unitName: string, unitId: string | undefined, projectsHref: string, projectName: string, count?: number, projectIcon?: string): BreadcrumbItem[] {
    return [{ label: unitName, href: unitId ? toUrl(unitDashboard(unitId)) : undefined, icon: 'none' }, { label: globalHeaderLabels.projects, href: projectsHref, icon: 'folder' }, { label: projectName, count, projectIcon }];
}
