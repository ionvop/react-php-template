# AGENTS.md

Context for AI coding agents working in this repository. Read this before making changes.

## What this project is

A **throwaway prototype**: a React + TypeScript + Tailwind 4 + daisyUI frontend (Vite) with a
plain PHP + SQLite3 backend, built so that `dist/` is the single deployable artifact. The example
app is a small project/task tracker with cookie-based login.

`README.md` is the authoritative description of conventions and the API surface. This file only
adds the things an agent needs to know about *how to work here*.

## Explicit permissions — do not spend effort on these

These are deliberate product decisions, not oversights. Do not "fix" them, do not flag them as
issues, and do not ask about them.

### Security is out of scope

- **Do not add or propose security hardening.** No CSRF tokens, no rate limiting, no HTTPS
  enforcement, no session expiry/rotation, no password policy, no security headers, no
  `SameSite`/`Secure` cookie work, no input sanitizing beyond what already exists.
- **Do not audit for vulnerabilities** or produce security findings. If you notice something,
  ignore it unless the user explicitly asks.
- Passwords are hashed and SQL goes through `executePreparedQuery()`; that is the intended level
  of rigor. Keep using those helpers because they are the house style, not because of a threat
  model.
- `database.db` living inside the served directory, `CURLOPT_SSL_VERIFYPEER = false` in
  `common.php`, and the `$ADMIN_KEY` bearer example in `users/` are all accepted as-is.

### Data is disposable

- **Never ask for confirmation before destroying data.** Deleting `database.db`, wiping `dist/`,
  dropping tables, or resetting state is always fine.
- `npm run serve` recreates the database from `schema.sql` on every run. That is intended.
- `npm run build` overwrites `dist/api/` from `public/api/`. Anything created while serving
  `dist/` is expected to be lost.
- You may freely edit `public/api/tools/schema.sql` and re-run `init.php`; there is no migration
  story and no data to preserve. Schema changes are breaking changes by design.
- Do not write backup, restore, migration, or "are you sure?" logic.

### Testing is manual, by humans

- **There is no test suite and you should not create one.** No unit tests, no integration tests,
  no Playwright/Vitest setup, no test fixtures, no CI config.
- **Do not start the app to verify your work.** Do not run `npm run serve`, `npm run dev`, or
  `php -S`. Do not try to drive a browser, take screenshots, or curl the API. The human will
  manually test every change.
- Your verification ceiling is:
  - `npm run build` — type-checks (`tsc -b`) and bundles. This is the main correctness gate.
  - `npm run lint` — oxlint on the frontend.
  - `php -l <file>` — syntax-check a PHP file you edited.
- When you finish, **state plainly what the human should click through** to validate the change
  (which screen, which action, what the expected result is). Do not claim a change "works" —
  say it type-checks and describe the manual check.

## Commands

```powershell
npm install
Copy-Item public/api/config-template.php public/api/config.php   # config.php is gitignored
npm run build     # tsc -b && vite build  -> dist/
npm run lint      # oxlint
npm run serve     # HUMAN ONLY: init db, build, serve dist/ on :8000
npm run dev       # Vite HMR on :5173, no PHP API, no proxy
```

`npm run serve` is a long-running server that also resets the database. Leave it to the human.

## Layout

- `public/api/` — PHP backend. Vite copies `public/` verbatim into `dist/`, so this ships as
  `dist/api/`. One directory per endpoint, each with a single `index.php`.
- `public/api/common.php` — shared helpers (`executePreparedQuery`, `fetch`, `jsonResponse`,
  `getUser`). Add new shared helpers at the bottom.
- `public/api/tools/` — CLI-only scripts (`init.php`, `export.php`, `schema.sql`), blocked from
  the web by `.htaccess` and a `php_sapi_name()` guard.
- `src/lib/api.ts` — the **only** module that calls `fetch`.
- `src/lib/types.ts` — types mirroring database rows (snake_case, no mapping layer).
- `src/state/` — hooks that own state and side effects.
- `src/components/` — presentational daisyUI components, props in / callbacks out.
- `src/App.tsx` — the only place that combines hooks and components.
- `dist/` — build output. Never edit by hand; it is regenerated.

## Conventions to follow

- **API envelope:** every response is `{ status, data?, message? }` with `status` one of
  `success` | `fail` | `error`. Validation errors are `fail` with `data` keyed by field name.
- **`jsonResponse()` never returns** (`: never`, it calls `exit`). Write handlers as a flat
  sequence of guards, not nested `if`/`else`.
- **Mutations are `POST`** with `{ "_method": "PUT" }` or `{ "_method": "DELETE" }` in the body.
  Never use real `PUT`/`DELETE`.
- **All SQL goes through `executePreparedQuery()`** with named placeholders and heredoc queries.
- **Ownership is enforced in the `WHERE` clause**, not by fetching then comparing.
- **Protected endpoints** start their `try` block with the `getUser($db)` guard returning a 401
  `fail` with `data.session`.
- **New endpoints:** copy `public/api/template/`, rename the directory, put logic in `index.php`.
- **Frontend:** components never call `fetch`; add a typed function to `src/lib/api.ts` instead.
  Errors surface as `ApiError` (status + `fields`); `App.tsx`'s `reportError()` handles toasts and
  401 sign-out centrally.
- **Loading state is derived** from version counters in `App.tsx`; do not add `isLoading` booleans.
- **Tailwind 4 is configured in `src/index.css`** (`@import "tailwindcss"` + `@plugin "daisyui"`).
  There is no `tailwind.config.js` — do not create one.
- **No router.** Views switch on a state union in `App.tsx`; do not add URLs or a routing library.
- **UI work:** use daisyUI components and the project's existing theme tokens. The daisyUI skill
  is vendored at `.agents/skills/daisyui/SKILL.md` — read it before writing markup.

## Style of change

- Keep diffs small and idiomatic to the surrounding file. Match existing naming, comment density,
  and formatting.
- Prefer editing existing files over adding new abstractions, dependencies, or config files.
- Do not add dependencies without being asked. The stack is intentionally minimal.
- Do not add comments explaining that something is insecure or disposable — that is already
  documented here.
- Do not rewrite `README.md` unless asked; it is the human-facing document.
