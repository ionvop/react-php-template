import type { User } from "../lib/types";
import { ThemeToggle } from "./ThemeToggle";

interface AppNavbarProps {
  user: User;
  onSignOut: () => void;
}

export function AppNavbar({ user, onSignOut }: AppNavbarProps) {
  return (
    <header className="navbar bg-base-200 px-4">
      <div className="navbar-start gap-2">
        <span className="text-base font-semibold">Tasks API Demo</span>
        <span className="badge badge-ghost badge-sm">{user.username}</span>
      </div>
      <div className="navbar-end gap-1">
        <ThemeToggle />
        <button type="button" className="btn btn-ghost btn-sm" onClick={onSignOut}>
          Sign out
        </button>
      </div>
    </header>
  );
}