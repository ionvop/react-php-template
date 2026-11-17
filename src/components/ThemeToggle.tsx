import { useEffect, useState } from "react";

type Theme = "light" | "dark";

const THEME_KEY = "tasks-demo:theme";

function readStoredTheme(): Theme {
  try {
    const stored = localStorage.getItem(THEME_KEY);

    if (stored === "dark" || stored === "light") {
      return stored;
    }
  } catch {
    // Fall through to the system preference.
  }

  return window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
}

export function ThemeToggle() {
  const [theme, setTheme] = useState<Theme>(readStoredTheme);

  useEffect(() => {
    document.documentElement.setAttribute("data-theme", theme);

    try {
      localStorage.setItem(THEME_KEY, theme);
    } catch {
      // Persisting the theme is optional.
    }
  }, [theme]);

  return (
    <label className="swap swap-rotate btn btn-ghost btn-circle" aria-label="Toggle theme">
      <input
        type="checkbox"
        className="theme-controller"
        value="dark"
        checked={theme === "dark"}
        onChange={(event) => setTheme(event.target.checked ? "dark" : "light")}
      />
      <span className="swap-off text-lg">☀</span>
      <span className="swap-on text-lg">☾</span>
    </label>
  );
}