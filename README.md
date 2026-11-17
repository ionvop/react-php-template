# Tasks API Demo

A small React + TypeScript + Vite frontend that demonstrates the plain-PHP/SQLite API in
`public/api/`. Authentication is cookie-based and every screen maps to a real endpoint — nothing
is mocked.

## Requirements

- PHP 8.1+ with the `sqlite3` extension (check with `php -v`)
- Node.js 20+

## First run

```powershell
# 1. Create the SQLite database from the schema
php public/api/tools/init.php

# 2. Install dependencies and build (Vite copies public/api/** into dist/api/**)
npm install
npm run build

# 3. Serve the built app and the API from one origin
npm run api
```

Open <http://localhost:8000>, create an account, and sign in.

`npm run api` is just `php -S localhost:8000 -t dist`. Serving `dist` puts the React bundle and
the PHP API on the same origin, so the `session` cookie behaves exactly as it would in production.

### Hot reload while developing

Run PHP against the source `public/` directory in one terminal and Vite in another. The Vite dev
server proxies `/api` to PHP, so cookies still work.

```powershell
npm run api:dev   # php -S localhost:8000 -t public
npm run dev       # http://localhost:5173 (proxies /api -> :8000)
```

## Scripts

| Script    | Command                           | Purpose                                        |
| --------- | --------------------------------- | ---------------------------------------------- |
| `dev`     | `vite`                            | Dev server with HMR on port 5173               |
| `api`     | `php -S localhost:8000 -t dist`   | Serve the built app + API on port 8000         |
| `api:dev` | `php -S localhost:8000 -t public` | Serve PHP only, for use behind the Vite proxy  |
| `build`   | `tsc -b && vite build`            | Type-check and build into `dist/`              |
| `lint`    | `oxlint`                          | Lint the frontend                              |
| `preview` | `vite preview`                    | Preview the bundle (static only, no PHP)       |

## API reference

Every response uses the envelope `{ "status": "success" | "fail" | "error", "data"?, "message"? }`.
Validation errors are returned in `data`, keyed by field name.

| Method | Endpoint              | Body                                     | Notes                            |
| ------ | --------------------- | ---------------------------------------- | -------------------------------- |
| GET    | `/api/`               | —                                        | Health check                     |
| GET    | `/api/me/`            | —                                        | Current user; 401 when signed out |
| POST   | `/api/register/`      | `{ username, password }`                 | 201; 400/409 on validation       |
| POST   | `/api/login/`         | `{ username, password }`                 | Sets the `session` cookie        |
| POST   | `/api/logout/`        | —                                        | Clears the session               |
| GET    | `/api/projects/`      | —                                        | List the user's projects         |
| GET    | `/api/projects/?id=1` | —                                        | Project plus its `tasks`         |
| POST   | `/api/projects/`      | `{ title, description }`                 | Create                           |
| POST   | `/api/projects/?id=1` | `{ _method: "PUT", title, description }` | Update                           |
| POST   | `/api/projects/?id=1` | `{ _method: "DELETE" }`                  | 204 on success                   |
| POST   | `/api/tasks/`         | `{ project_id, content }`                | Create                           |
| GET    | `/api/tasks/?id=1`    | —                                        | Single task                      |
| POST   | `/api/tasks/?id=1`    | `{ _method: "PUT", content }`            | Update                           |
| POST   | `/api/tasks/?id=1`    | `{ _method: "DELETE" }`                  | 204 on success                   |

Mutations are sent as `POST` with a `_method` override — that is why `src/lib/api.ts` has no
`PUT`/`DELETE` helpers.

## Project layout

```
public/api/          PHP endpoints (one directory per resource)
  common.php         DB connection + query/response helpers
  tools/             CLI utilities: init.php, export.php, schema.sql
src/lib/api.ts       Typed fetch wrapper (ApiError, field errors)
src/lib/types.ts     Shared API types
src/lib/format.ts    Date formatting helper
src/state/           useAuth, useToasts
src/components/      daisyUI-based UI pieces
src/App.tsx          Auth gate + project list/detail views
```

`src/index.css` imports Tailwind CSS and enables daisyUI with the `light` (default) and `dark`
(prefers-color-scheme) themes. There is no `tailwind.config.js`; Tailwind 4 is configured in CSS.
`index.html` applies the saved theme before first paint to avoid a flash.

## Demo caveats

This is a teaching demo, not a hardened application:

- **The database lives inside the served directory.** `php -S -t dist` will serve
  `dist/api/database.db` as a plain download because `.htaccess` only applies to Apache. Anyone
  who can reach the server can download the SQLite file, including password hashes. Do not expose
  this on a public network. Under Apache, the bundled `public/api/.htaccess` blocks those files.
- **`public/api/` is snapshotted into `dist/` on every build.** Data created while serving `dist`
  is discarded on the next `npm run build`; re-run `init.php` for a clean slate.
- `config.php` is gitignored and optional — `common.php` includes it only when present.
- `/api/template/` is an unfinished stub and is not used by the UI.