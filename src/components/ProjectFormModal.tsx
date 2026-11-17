import { useState } from "react";
import { ApiError } from "../lib/api";
import type { FieldErrors, Project } from "../lib/types";
import { Dialog } from "./Dialog";

interface ProjectFormModalProps {
  open: boolean;
  project: Project | null;
  pending: boolean;
  onClose: () => void;
  onSubmit: (title: string, description: string) => Promise<void>;
}

/**
 * Mount this component with a `key` that changes when the edited project
 * changes (see App.tsx), so the form state is seeded once per opening.
 */
export function ProjectFormModal({
  open,
  project,
  pending,
  onClose,
  onSubmit,
}: ProjectFormModalProps) {
  const [title, setTitle] = useState(project?.title ?? "");
  const [description, setDescription] = useState(project?.description ?? "");
  const [fields, setFields] = useState<FieldErrors>({});
  const [message, setMessage] = useState<string | null>(null);

  async function handleSubmit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setFields({});
    setMessage(null);

    try {
      await onSubmit(title, description);
    } catch (error) {
      if (error instanceof ApiError) {
        setFields(error.fields);
        setMessage(Object.keys(error.fields).length === 0 ? error.message : null);
      } else {
        setMessage("Could not reach the API.");
      }
    }
  }

  return (
    <Dialog open={open} onClose={onClose}>
      <div className="modal-box">
        <h3 className="text-lg font-semibold">
          {project === null ? "New project" : "Edit project"}
        </h3>

        {message !== null && (
          <div role="alert" className="alert alert-error alert-soft mt-4">
            <span className="text-sm">{message}</span>
          </div>
        )}

        <form onSubmit={handleSubmit} className="mt-2 space-y-4">
          <fieldset className="fieldset">
            <legend className="fieldset-legend">Title</legend>
            <input
              type="text"
              className={`input w-full ${fields.title || fields.project ? "input-error" : ""}`}
              placeholder="Project title"
              value={title}
              onChange={(event) => setTitle(event.target.value)}
              required
            />
            {(fields.title || fields.project) && (
              <p className="label text-error">{fields.title ?? fields.project}</p>
            )}
          </fieldset>

          <fieldset className="fieldset">
            <legend className="fieldset-legend">Description</legend>
            <textarea
              className="textarea w-full"
              placeholder="What is this project about?"
              rows={3}
              value={description}
              onChange={(event) => setDescription(event.target.value)}
            ></textarea>
          </fieldset>

          <div className="modal-action">
            <button type="button" className="btn btn-ghost" onClick={onClose} disabled={pending}>
              Cancel
            </button>
            <button type="submit" className="btn btn-primary" disabled={pending}>
              {pending && <span className="loading loading-spinner loading-sm"></span>}
              {project === null ? "Create" : "Save"}
            </button>
          </div>
        </form>
      </div>
      <form method="dialog" className="modal-backdrop">
        <button>close</button>
      </form>
    </Dialog>
  );
}