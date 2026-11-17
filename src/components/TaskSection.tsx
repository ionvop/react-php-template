import { useState } from "react";
import { formatDate } from "../lib/format";
import type { Task } from "../lib/types";

interface TaskSectionProps {
  tasks: Task[];
  pending: boolean;
  onAdd: (content: string) => Promise<void>;
  onUpdate: (task: Task, content: string) => Promise<void>;
  onDelete: (task: Task) => void;
}

export function TaskSection({ tasks, pending, onAdd, onUpdate, onDelete }: TaskSectionProps) {
  const [draft, setDraft] = useState("");
  const [adding, setAdding] = useState(false);
  const [editingId, setEditingId] = useState<number | null>(null);
  const [editDraft, setEditDraft] = useState("");
  const [savingId, setSavingId] = useState<number | null>(null);

  async function handleAdd(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();

    const content = draft.trim();

    if (content === "") {
      return;
    }

    setAdding(true);

    try {
      await onAdd(content);
      setDraft("");
    } finally {
      setAdding(false);
    }
  }

  function startEditing(task: Task) {
    setEditingId(task.id);
    setEditDraft(task.content);
  }

  function cancelEditing() {
    setEditingId(null);
    setEditDraft("");
  }

  async function saveEdit(task: Task) {
    const content = editDraft.trim();

    if (content === "" || content === task.content) {
      cancelEditing();
      return;
    }

    setSavingId(task.id);

    try {
      await onUpdate(task, content);
      cancelEditing();
    } finally {
      setSavingId(null);
    }
  }

  return (
    <section className="space-y-4">
      <div className="flex items-center justify-between gap-2">
        <h2 className="text-lg font-semibold">Tasks</h2>
        <span className="badge badge-ghost badge-sm">{tasks.length}</span>
      </div>

      <form onSubmit={handleAdd} className="join w-full">
        <input
          type="text"
          className="input join-item w-full"
          placeholder="Add a task"
          value={draft}
          onChange={(event) => setDraft(event.target.value)}
        />
        <button
          type="submit"
          className="btn join-item"
          disabled={adding || pending || draft.trim() === ""}
        >
          {adding && <span className="loading loading-spinner loading-sm"></span>}
          Add
        </button>
      </form>

      {tasks.length === 0 ? (
        <p className="rounded-box border border-dashed border-base-300 px-4 py-10 text-center text-sm text-base-content/70">
          No tasks yet. Add one above to try <code>POST /api/tasks/</code>.
        </p>
      ) : (
        <ul className="list">
          {tasks.map((task) => (
            <li key={task.id} className="list-row items-center">
              {editingId === task.id ? (
                <>
                  <input
                    type="text"
                    className="input input-sm w-full"
                    value={editDraft}
                    onChange={(event) => setEditDraft(event.target.value)}
                    autoFocus
                  />
                  <div className="flex gap-1">
                    <button
                      type="button"
                      className="btn btn-sm"
                      onClick={() => saveEdit(task)}
                      disabled={savingId === task.id}
                    >
                      {savingId === task.id && (
                        <span className="loading loading-spinner loading-xs"></span>
                      )}
                      Save
                    </button>
                    <button type="button" className="btn btn-ghost btn-sm" onClick={cancelEditing}>
                      Cancel
                    </button>
                  </div>
                </>
              ) : (
                <>
                  <div className="list-col-grow">
                    <p className="text-sm break-words">{task.content}</p>
                    <p className="text-xs text-base-content/50">
                      Created {formatDate(task.created_at)}
                    </p>
                  </div>
                  <div className="flex gap-1">
                    <button
                      type="button"
                      className="btn btn-ghost btn-sm"
                      onClick={() => startEditing(task)}
                    >
                      Edit
                    </button>
                    <button
                      type="button"
                      className="btn btn-ghost btn-sm text-error"
                      onClick={() => onDelete(task)}
                    >
                      Delete
                    </button>
                  </div>
                </>
              )}
            </li>
          ))}
        </ul>
      )}
    </section>
  );
}