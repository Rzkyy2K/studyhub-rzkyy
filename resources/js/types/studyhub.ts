export type SchoolSessionType = 'teori' | 'bengkel';

export interface SchoolSessionInfo {
    active_session: SchoolSessionType;
    official_session: SchoolSessionType;
    view_session: SchoolSessionType;
    is_viewing_official: boolean;
    label: string;
    type_name: string;
    subtitle: string;
    official_label: string;
    period_start: string;
    period_end: string;
    period_range: string;
    days_remaining: number;
    started_at_raw: string;
    ends_at_raw: string;
}

export interface Subject {
    id: number;
    user_id: number;
    name: string;
    teacher?: string | null;
    room?: string | null;
    session?: SchoolSessionType;
    tasks_count?: number;
    schedules_count?: number;
    created_at?: string;
    updated_at?: string;
}

export type Priority = 'low' | 'medium' | 'high';
export type TaskStatus = 'pending' | 'in_progress' | 'completed';
export type TodoStatus = 'pending' | 'completed';
export type UrgencyType = 'normal' | 'warning' | 'danger' | 'overdue' | 'completed';

export interface Task {
    id: number;
    user_id: number;
    subject_id?: number | null;
    subject?: Subject | null;
    title: string;
    description?: string | null;
    deadline: string;
    priority: Priority;
    status: TaskStatus;
    session?: SchoolSessionType;
    completed_at?: string | null;
    formatted_completed_at?: string;
    days_until_reset?: string | null;
    urgency?: UrgencyType;
    urgency_label?: string;
    formatted_deadline?: string;
    formatted_time?: string;
    deadline_relative?: string;
    created_at?: string;
    updated_at?: string;
}

export type DayOfWeek = 'monday' | 'tuesday' | 'wednesday' | 'thursday' | 'friday';

export interface Schedule {
    id: number;
    user_id: number;
    subject_id?: number | null;
    subject?: Subject | null;
    day: DayOfWeek;
    start_time: string;
    end_time: string;
    room?: string | null;
    session?: SchoolSessionType;
    day_label?: string;
    formatted_time_range?: string;
    is_active_now?: boolean;
    created_at?: string;
    updated_at?: string;
}

export interface Todo {
    id: number;
    user_id: number;
    title: string;
    priority: Priority;
    due_date?: string | null;
    status: TodoStatus;
    session?: SchoolSessionType | 'all';
    is_completed?: boolean;
    formatted_due_date?: string | null;
    created_at?: string;
    updated_at?: string;
}

export interface DashboardStats {
    uncompleted_tasks: number;
    due_today_tasks: number;
    completed_tasks: number;
    pending_todos: number;
}

export interface UserNotification {
    id: number;
    user_id: number;
    key?: string | null;
    title: string;
    message: string;
    type: 'danger' | 'warning' | 'info' | 'success';
    action_url?: string | null;
    read_at?: string | null;
    is_read: boolean;
    time_ago: string;
    created_at?: string;
    updated_at?: string;
}
