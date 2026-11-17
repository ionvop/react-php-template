import { useState } from "react";
import { ApiError } from "../lib/api";
import type { FieldErrors } from "../lib/types";

type Mode = "login" | "register";

interface AuthPageProps {
  onSignIn: (username: string, password: string) => Promise<void>;
  onSignUp: (username: string, password: string) => Promise<void>;
  onRegistered: (message: string) => void;
  lastUsername: string | null;
}

export function AuthPage({ onSignIn, onSignUp, onRegistered, lastUsername }: AuthPageProps) {
  const [mode, setMode] = useState<Mode>("login");
  const [username, setUsername] = useState(lastUsername ?? "");
  const [password, setPassword] = useState("");
  const [pending, setPending] = useState(false);
  const [fields, setFields] = useState<FieldErrors>({});
  const [message, setMessage] = useState<string | null>(null);

  function switchMode(next: Mode) {
    setMode(next);
    setFields({});
    setMessage(null);
  }

  async function handleSubmit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setPending(true);
    setFields({});
    setMessage(null);

    try {
      if (mode === "login") {
        await onSignIn(username, password);
      } else {
        await onSignUp(username, password);
        onRegistered("Account created. Sign in to continue.");
        setMode("login");
        setPassword("");
      }
    } catch (error) {
      if (error instanceof ApiError) {
        setFields(error.fields);
        setMessage(Object.keys(error.fields).length === 0 ? error.message : null);
      } else {
        setMessage("Could not reach the API.");
      }
    } finally {
      setPending(false);
    }
  }

  return (
    <main className="flex min-h-screen items-center justify-center bg-base-200 p-4">
      <div className="card w-full max-w-sm bg-base-100 card-border">
        <div className="card-body gap-4">
          <div className="space-y-1">
            <h1 className="card-title text-2xl">Tasks API Demo</h1>
            <p className="text-sm text-base-content/70">
              Sign in to manage projects and their tasks.
            </p>
          </div>

          <div role="tablist" className="tabs tabs-box">
            <button
              type="button"
              role="tab"
              className={`tab ${mode === "login" ? "tab-active" : ""}`}
              onClick={() => switchMode("login")}
            >
              Login
            </button>
            <button
              type="button"
              role="tab"
              className={`tab ${mode === "register" ? "tab-active" : ""}`}
              onClick={() => switchMode("register")}
            >
              Register
            </button>
          </div>

          {message !== null && (
            <div role="alert" className="alert alert-info alert-soft">
              <span className="text-sm">{message}</span>
            </div>
          )}

          <form onSubmit={handleSubmit} className="space-y-4">
            <fieldset className="fieldset">
              <legend className="fieldset-legend">Username</legend>
              <input
                type="text"
                name="username"
                className={`input w-full ${fields.username ? "input-error" : ""}`}
                placeholder="Your username"
                autoComplete="username"
                value={username}
                onChange={(event) => setUsername(event.target.value)}
                required
              />
              {fields.username && <p className="label text-error">{fields.username}</p>}
            </fieldset>

            <fieldset className="fieldset">
              <legend className="fieldset-legend">Password</legend>
              <input
                type="password"
                name="password"
                className={`input w-full ${fields.password ? "input-error" : ""}`}
                placeholder="At least 6 characters"
                autoComplete={mode === "login" ? "current-password" : "new-password"}
                value={password}
                onChange={(event) => setPassword(event.target.value)}
                required
              />
              {fields.password && <p className="label text-error">{fields.password}</p>}
            </fieldset>

            <button type="submit" className="btn btn-primary btn-block" disabled={pending}>
              {pending && <span className="loading loading-spinner loading-sm"></span>}
              {mode === "login" ? "Sign in" : "Create account"}
            </button>
          </form>
        </div>
      </div>
    </main>
  );
}