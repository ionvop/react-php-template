import { useCallback, useEffect, useState } from "react";
import * as api from "./lib/api";
import { ApiError } from "./lib/api";
import type { Project, Task } from "./lib/types";
import { useAuth, readLastUsername } from "./state/useAuth";
import { useToasts } from "./state/useToasts";
import { AuthPage } from "./components/AuthPage";
import { AppNavbar } from "./components/AppNavbar";
import { ProjectGrid } from "./components/ProjectGrid";
import { ProjectDetail } from "./components/ProjectDetail";
import { ProjectFormModal } from "./components/ProjectFormModal";
import { ConfirmDialog } from "./components/ConfirmDialog";
import { ToastStack } from "./components/ToastStack";

type View = { name: "list" } | { name: "detail"; id: number };

type ConfirmTarget =
  | { kind: "project"; project: Project }
  | { kind: "task"; task: Task };

interface DetailError {
  key: string;
  message: string;
}

export default function App() {
  const { user, status, signIn, signUp, signOut } = useAuth();
  const { toasts, push, dismiss } = useToasts();

  const [view, setView] = useState<View>({ name: "list" });
  const [lastUsername] = useState(readLastUsername);

  const [projects, setProjects] = useState<Project[]>([]);
  const [listVersion, setListVersion] = useState(0);
  const [loadedListVersion, setLoadedListVersion] = useState(-1);

  const [detail, setDetail] = useState<Project | null>(null);
  const [detailVersion, setDetailVersion] = useState(0);
  const [loadedDetailKey, setLoadedDetailKey] = useState<string | null>(null);
  const [detailError, setDetailError] = useState<DetailError | null>(null);

  const [formOpen, setFormOpen] = useState(false);
  const [formTarget, setFormTarget] = useState<Project | null>(null);
  const [formPending, setFormPending] = useState(false);

  const [confirmTarget, setConfirmTarget] = useState<ConfirmTarget | null>(null);
  const [confirmPending, setConfirmPending] = useState(false);

  const [taskPending, setTaskPending] = useState(false);

  // Derived instead of stored in state: a request is in flight while the
  // requested version differs from the version that finished loading.
  const projectsLoading = loadedListVersion !== listVersion;

  const detailKey = view.name === "detail" ? `${view.id}:${detailVersion}` : null;
  const detailLoading = detailKey !== null && loadedDetailKey !== detailKey;
  const visibleDetailError =
    detailError !== null && detailError.key === detailKey ? detailError.message : null;

  const reportError = useCallback(
    (error: unknown, fallback: string) => {
      if (error instanceof ApiError) {
        if (error.status === 401) {
          void signOut();
          return;
        }

        push("error", error.message);
        return;
      }

      push("error", fallback);
    },
    [push, signOut],
  );

  const reloadProjects = useCallback(() => setListVersion((value) => value + 1), []);
  const reloadDetail = useCallback(() => setDetailVersion((value) => value + 1), []);

  useEffect(() => {
    if (status !== "signedIn") {
      return;
    }

    let cancelled = false;

    api
      .listProjects()
      .then((result) => {
        if (!cancelled) {
          setProjects(result);
        }
      })
      .catch((error: unknown) => {
        if (!cancelled) {
          reportError(error, "Could not load projects.");
        }
      })
      .finally(() => {
        if (!cancelled) {
          setLoadedListVersion(listVersion);
        }
      });

    return () => {
      cancelled = true;
    };
  }, [status, listVersion, reportError]);

  useEffect(() => {
    if (status !== "signedIn" || view.name !== "detail") {
      return;
    }

    const key = `${view.id}:${detailVersion}`;
    let cancelled = false;

    api
      .getProject(view.id)
      .then((result) => {
        if (!cancelled) {
          setDetail(result);
        }
      })
      .catch((error: unknown) => {
        if (!cancelled) {
          setDetail(null);
          setDetailError({
            key,
            message:
              error instanceof ApiError ? error.message : "Could not load this project.",
          });
        }
      })
      .finally(() => {
        if (!cancelled) {
          setLoadedDetailKey(key);
        }
      });

    return () => {
      cancelled = true;
    };
  }, [status, view, detailVersion]);

  function goToList() {
    setView({ name: "list" });
    setDetail(null);
    setDetailError(null);
  }

  function openProject(id: number) {
    setView({ name: "detail", id });
  }

  function openCreateForm() {
    setFormTarget(null);
    setFormOpen(true);
  }

  function openEditForm(project: Project) {
    setFormTarget(project);
    setFormOpen(true);
  }

  async function submitForm(title: string, description: string) {
    setFormPending(true);

    try {
      if (formTarget === null) {
        const created = await api.createProject(title, description);
        push("success", `Project "${created.title}" created.`);
        reloadProjects();
      } else {
        const updated = await api.updateProject(formTarget.id, title, description);
        push("success", `Project "${updated.title}" updated.`);
        setFormTarget(null);
        reloadProjects();

        if (view.name === "detail" && view.id === formTarget.id) {
          reloadDetail();
        }
      }

      setFormOpen(false);
    } finally {
      setFormPending(false);
    }
  }

  async function confirmDelete() {
    if (confirmTarget === null) {
      return;
    }

    setConfirmPending(true);

    try {
      if (confirmTarget.kind === "project") {
        await api.deleteProject(confirmTarget.project.id);
        push("success", "Project deleted.");

        if (view.name === "detail" && view.id === confirmTarget.project.id) {
          goToList();
        }

        reloadProjects();
      } else {
        await api.deleteTask(confirmTarget.task.id);
        push("success", "Task deleted.");
        reloadDetail();
      }

      setConfirmTarget(null);
    } catch (error) {
      reportError(error, "Delete failed.");
    } finally {
      setConfirmPending(false);
    }
  }

  async function addTask(content: string) {
    if (view.name !== "detail") {
      return;
    }

    setTaskPending(true);

    try {
      await api.createTask(view.id, content);
      reloadDetail();
    } catch (error) {
      reportError(error, "Could not add the task.");
    } finally {
      setTaskPending(false);
    }
  }

  async function updateTask(task: Task, content: string) {
    setTaskPending(true);

    try {
      await api.updateTask(task.id, content);
      reloadDetail();
    } catch (error) {
      reportError(error, "Could not update the task.");
    } finally {
      setTaskPending(false);
    }
  }

  if (status === "loading") {
    return (
      <div className="flex min-h-screen items-center justify-center bg-base-200">
        <span className="loading loading-spinner loading-lg"></span>
      </div>
    );
  }

  if (status === "signedOut" || user === null) {
    return (
      <>
        <AuthPage
          onSignIn={signIn}
          onSignUp={signUp}
          onRegistered={(message) => push("info", message)}
          lastUsername={lastUsername}
        />
        <ToastStack toasts={toasts} onDismiss={dismiss} />
      </>
    );
  }

  return (
    <div className="min-h-screen bg-base-200">
      <AppNavbar
        user={user}
        onSignOut={() => {
          goToList();
          void signOut();
        }}
      />

      <main className="mx-auto w-full max-w-5xl space-y-6 px-4 py-8">
        {view.name === "list" ? (
          <>
            <div className="flex flex-wrap items-center justify-between gap-2">
              <div>
                <h1 className="text-2xl font-semibold">Projects</h1>
                <p className="text-sm text-base-content/70">
                  Backed by <code className="badge badge-ghost badge-sm">/api/projects/</code>
                </p>
              </div>
              <button type="button" className="btn btn-primary btn-sm" onClick={openCreateForm}>
                New project
              </button>
            </div>

            <ProjectGrid
              projects={projects}
              loading={projectsLoading}
              onOpen={openProject}
              onEdit={openEditForm}
              onDelete={(project) => setConfirmTarget({ kind: "project", project })}
              onCreate={openCreateForm}
            />
          </>
        ) : (
          <ProjectDetail
            project={detail}
            loading={detailLoading}
            error={visibleDetailError}
            pending={taskPending}
            onBack={goToList}
            onEdit={() => {
              if (detail !== null) {
                openEditForm(detail);
              }
            }}
            onDelete={() => {
              if (detail !== null) {
                setConfirmTarget({ kind: "project", project: detail });
              }
            }}
            onAddTask={addTask}
            onUpdateTask={updateTask}
            onDeleteTask={(task) => setConfirmTarget({ kind: "task", task })}
          />
        )}
      </main>

      <ProjectFormModal
        key={formOpen ? (formTarget?.id ?? "new") : "closed"}
        open={formOpen}
        project={formTarget}
        pending={formPending}
        onClose={() => {
          setFormOpen(false);
          setFormTarget(null);
        }}
        onSubmit={submitForm}
      />

      <ConfirmDialog
        open={confirmTarget !== null}
        title={confirmTarget?.kind === "task" ? "Delete task" : "Delete project"}
        message={
          confirmTarget?.kind === "task"
            ? "This task will be removed permanently."
            : "This project and its tasks will be removed permanently."
        }
        pending={confirmPending}
        onConfirm={() => {
          void confirmDelete();
        }}
        onClose={() => setConfirmTarget(null)}
      />

      <ToastStack toasts={toasts} onDismiss={dismiss} />
    </div>
  );
}