export interface User {
  id: number;
  username: string;
  created_at: string;
}

export interface Task {
  id: number;
  project_id: number;
  content: string;
  created_at: string;
}

export interface Project {
  id: number;
  user_id: number;
  title: string;
  description: string | null;
  created_at: string;
  tasks?: Task[];
}

export interface ApiEnvelope<T> {
  status: "success" | "fail" | "error";
  data?: T;
  message?: string;
}

export type FieldErrors = Record<string, string>;