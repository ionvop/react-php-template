import { useCallback, useEffect, useState } from "react";
import * as api from "../lib/api";
import type { User } from "../lib/types";

const LAST_USERNAME_KEY = "tasks-demo:last-username";

export type AuthStatus = "loading" | "signedIn" | "signedOut";

export function readLastUsername(): string | null {
  try {
    return localStorage.getItem(LAST_USERNAME_KEY);
  } catch {
    return null;
  }
}

function writeLastUsername(username: string | null) {
  try {
    if (username === null) {
      localStorage.removeItem(LAST_USERNAME_KEY);
    } else {
      localStorage.setItem(LAST_USERNAME_KEY, username);
    }
  } catch {
    // Storage can be unavailable (private mode); display name is optional.
  }
}

export function useAuth() {
  const [user, setUser] = useState<User | null>(null);
  const [status, setStatus] = useState<AuthStatus>("loading");

  useEffect(() => {
    let cancelled = false;

    api
      .me()
      .then((current) => {
        if (cancelled) {
          return;
        }

        setUser(current);
        setStatus("signedIn");
        writeLastUsername(current.username);
      })
      .catch(() => {
        if (cancelled) {
          return;
        }

        setUser(null);
        setStatus("signedOut");
      });

    return () => {
      cancelled = true;
    };
  }, []);

  const signIn = useCallback(async (username: string, password: string) => {
    await api.login(username, password);

    const current = await api.me();

    writeLastUsername(current.username);
    setUser(current);
    setStatus("signedIn");
  }, []);

  const signUp = useCallback(async (username: string, password: string) => {
    await api.register(username, password);
  }, []);

  const signOut = useCallback(async () => {
    try {
      await api.logout();
    } finally {
      writeLastUsername(null);
      setUser(null);
      setStatus("signedOut");
    }
  }, []);

  return { user, status, signIn, signUp, signOut };
}