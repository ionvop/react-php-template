import { formatDate } from "../lib/format";
import type { Project } from "../lib/types";

interface ProjectGridProps {
  projects: Project[];
  loading: boolean;
  onOpen: (id: number) => void;
  onEdit: (project: Project) => void;
  onDelete: (project: Project) => void;
  onCreate: () => void;
}

export function ProjectGrid({
  projects,
  loading,
  onOpen,
  onEdit,
  onDelete,
  onCreate,
}: ProjectGridProps) {
  if (loading) {
    return (
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {[1, 2, 3].map((key) => (
          <div key={key} className="card bg-base-100 card-border">
            <div className="card-body gap-3">
              <div className="skeleton h-6 w-2/3"></div>
              <div className="skeleton h-4 w-full"></div>
              <div className="skeleton h-4 w-4/5"></div>
            </div>
          </div>
        ))}
      </div>
    );
  }

  if (projects.length === 0) {
    return (
      <div className="flex flex-col items-center gap-3 rounded-box border border-dashed border-base-300 px-6 py-16 text-center">
        <h2 className="text-lg font-semibold">No projects yet</h2>
        <p className="max-w-sm text-sm text-base-content/70">
          Projects group your tasks. Create the first one to see the API in action.
        </p>
        <button type="button" className="btn btn-primary btn-sm" onClick={onCreate}>
          New project
        </button>
      </div>
    );
  }

  return (
    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      {projects.map((project) => (
        <div key={project.id} className="card bg-base-100 card-border">
          <div className="card-body gap-3">
            <h2 className="card-title text-base">
              <button
                type="button"
                className="link link-hover text-left"
                onClick={() => onOpen(project.id)}
              >
                {project.title}
              </button>
            </h2>
            <p className="line-clamp-3 min-h-[3.75rem] text-sm text-base-content/70">
              {project.description || "No description."}
            </p>
            <div className="text-xs text-base-content/50">
              Created {formatDate(project.created_at)}
            </div>
            <div className="card-actions justify-end">
              <button
                type="button"
                className="btn btn-ghost btn-sm"
                onClick={() => onEdit(project)}
              >
                Edit
              </button>
              <button
                type="button"
                className="btn btn-ghost btn-sm text-error"
                onClick={() => onDelete(project)}
              >
                Delete
              </button>
              <button
                type="button"
                className="btn btn-sm"
                onClick={() => onOpen(project.id)}
              >
                Open
              </button>
            </div>
          </div>
        </div>
      ))}
    </div>
  );
}