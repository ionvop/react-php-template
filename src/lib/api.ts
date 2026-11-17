import type { ApiEnvelope, FieldErrors, Project, Task, User } from "./types";

// Derived from Vite's `base` so the app also works when deployed under a
// subdirectory (e.g. https://example.com/my-app/).
const API_BASE = `${import.meta.env.BASE_URL}api`;

export class ApiError extends Error {
  status: number;
  fields: FieldErrors;

  constructor(message: string, status: number, fields: FieldErrors = {}) {
    super(message);
    this.name = "ApiError";
    this.status = status;
    this.fields = fields;
  }
}

interface RequestOptions {
  method?: "GET" | "POST";
  body?: unknown;
}

function extractFieldErrors(data: unknown): FieldErrors {
  if (typeof data !== "object" || data === null) {
    return {};
  }

  const fields: FieldErrors = {};

  for (const [key, value] of Object.entries(data)) {
    if (typeof value === "string") {
      fields[key] = value;
    }
  }

  return fields;
}

async function request<T>(path: string, options: RequestOptions = {}): Promise<T | null> {
  const { method = "GET", body } = options;

  const response = await fetch(`${API_BASE}${path}`, {
    method,
    credentials: "include",
    headers:
      body === undefined ? undefined : { "Content-Type": "application/json" },
    body: body === undefined ? undefined : JSON.stringify(body),
  });

  if (response.status === 204) {
    return null;
  }

  const text = await response.text();

  if (text === "") {
    return null;
  }

  let envelope: ApiEnvelope<T>;

  try {
    envelope = JSON.parse(text) as ApiEnvelope<T>;
  } catch {
    throw new ApiError("Unexpected response from the server.", response.status);
  }

  if (envelope.status === "success") {
    return envelope.data ?? null;
  }

  const fields = extractFieldErrors(envelope.data);
  const fallback = Object.values(fields)[0] ?? "Request failed.";

  throw new ApiError(envelope.message ?? fallback, response.status, fields);
}

export function register(username: string, password: string): Promise<null> {
  return request<null>("/register/", { method: "POST", body: { username, password } });
}

export function login(username: string, password: string): Promise<null> {
  return request<null>("/login/", { method: "POST", body: { username, password } });
}

export function logout(): Promise<null> {
  return request<null>("/logout/", { method: "POST" });
}

export async function me(): Promise<User> {
  const data = await request<{ user: User }>("/me/");

  if (data === null) {
    throw new ApiError("Unable to resolve the current user.", 500);
  }

  return data.user;
}

export async function listProjects(): Promise<Project[]> {
  const data = await request<{ projects: Project[] }>("/projects/");
  return data?.projects ?? [];
}

export async function getProject(id: number): Promise<Project> {
  const data = await request<{ project: Project }>(`/projects/?id=${id}`);

  if (data === null) {
    throw new ApiError("Project not found.", 404);
  }

  return data.project;
}

export async function createProject(title: string, description: string): Promise<Project> {
  const data = await request<{ project: Project }>("/projects/", {
    method: "POST",
    body: { title, description },
  });

  if (data === null) {
    throw new ApiError("Project could not be created.", 500);
  }

  return data.project;
}

export async function updateProject(
  id: number,
  title: string,
  description: string,
): Promise<Project> {
  const data = await request<{ project: Project }>(`/projects/?id=${id}`, {
    method: "POST",
    body: { _method: "PUT", title, description },
  });

  if (data === null) {
    throw new ApiError("Project could not be updated.", 500);
  }

  return data.project;
}

export function deleteProject(id: number): Promise<null> {
  return request<null>(`/projects/?id=${id}`, {
    method: "POST",
    body: { _method: "DELETE" },
  });
}

export async function createTask(projectId: number, content: string): Promise<Task> {
  const data = await request<{ task: Task }>("/tasks/", {
    method: "POST",
    body: { project_id: projectId, content },
  });

  if (data === null) {
    throw new ApiError("Task could not be created.", 500);
  }

  return data.task;
}

export async function updateTask(id: number, content: string): Promise<Task> {
  const data = await request<{ task: Task }>(`/tasks/?id=${id}`, {
    method: "POST",
    body: { _method: "PUT", content },
  });

  if (data === null) {
    throw new ApiError("Task could not be updated.", 500);
  }

  return data.task;
}

export function deleteTask(id: number): Promise<null> {
  return request<null>(`/tasks/?id=${id}`, {
    method: "POST",
    body: { _method: "DELETE" },
  });
}