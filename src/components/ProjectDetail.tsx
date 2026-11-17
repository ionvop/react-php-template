import { formatDate } from "../lib/format";
import type { Project, Task } from "../lib/types";
import { TaskSection } from "./TaskSection";

interface ProjectDetailProps {
  project: Project | null;
  loading: boolean;
  error: string | null;
  pending: boolean;
  onBack: () => void;
  onEdit: () => void;
  onDelete: () => void;
  onAddTask: (content: string) => Promise<void>;
  onUpdateTask: (task: Task, content: string) => Promise<void>;
  onDeleteTask: (task: Task) => void;
}

export function ProjectDetail({
  project,
  loading,
  error,
  pending,
  onBack,
  onEdit,
  onDelete,
  onAddTask,
  onUpdateTask,
  onDeleteTask,
}: ProjectDetailProps) {
  return (
    <div className="space-y-6">
      <div className="breadcrumbs text-sm">
        <ul>
          <li>
            <button type="button" className="link link-hover" onClick={onBack}>
              Projects
            </button>
          </li>
          <li>{project?.title ?? "…"}</li>
        </ul>
      </div>

      {error !== null ? (
        <div role="alert" className="alert alert-error alert-soft">
          <span className="text-sm">{error}</span>
          <button type="button" className="btn btn-ghost btn-sm" onClick={onBack}>
            Back to projects
          </button>
        </div>
      ) : loading || project === null ? (
        <div className="space-y-3">
          <div className="skeleton h-8 w-1/2"></div>
          <div className="skeleton h-4 w-full"></div>
          <div className="skeleton h-4 w-2/3"></div>
        </div>
      ) : (
        <>
          <header className="flex flex-wrap items-start justify-between gap-3">
            <div className="space-y-1">
              <h1 className="text-2xl font-semibold">{project.title}</h1>
              <p className="text-sm text-base-content/70">
                {project.description || "No description."}
              </p>
              <p className="text-xs text-base-content/50">
                Created {formatDate(project.created_at)}
              </p>
            </div>
            <div className="flex gap-1">
              <button type="button" className="btn btn-ghost btn-sm" onClick={onEdit}>
                Edit
              </button>
              <button
                type="button"
                className="btn btn-ghost btn-sm text-error"
                onClick={onDelete}
              >
                Delete
              </button>
            </div>
          </header>

          <div className="divider my-0"></div>

          <TaskSection
            tasks={project.tasks ?? []}
            pending={pending}
            onAdd={onAddTask}
            onUpdate={onUpdateTask}
            onDelete={onDeleteTask}
          />
        </>
      )}
    </div>
  );
}