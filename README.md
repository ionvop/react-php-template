# React + PHP Template

A starter template for small full-stack prototypes: a React + TypeScript + Tailwind CSS +
daisyUI frontend built with Vite, and a plain PHP + SQLite3 backend that ships inside the same
build output.

The Tasks app in this repository is a worked example of the template, not the template itself.
Remove the example endpoints and components and you have a blank starting point.

## Why this template exists

Most PHP frameworks assume you can point the web server's document root at a `public/`
directory. Free shared hosting usually does not allow that — the document root is fixed and you
upload files into it.

This template is built around that constraint:

- **The build output is the deployable artifact.** `npm run build` produces `dist/`, which
  contains the compiled frontend *and* a copy of `public/api/`. Upload `dist/` as-is.
- **No document root changes.** Everything is served from one directory, so the frontend and the
  API share an origin and cookies work without CORS.
- **Subdirectory-safe.** `vite.config.ts` sets `base: './'` and the API base is derived from it,
  so the app also works at `https://example.com/my-app/`.

## Requirements

- PHP 8.1+ with the `sqlite3` extension (check with `php -v`)
- Node.js 20+

## Quick start

```powershell
npm install

# config.php is gitignored; create it from the committed template
Copy-Item public/api/config-template.php public/api/config.php

npm run serve
```

Open <http://localhost:8000>, create an account, and sign in.

`npm run serve` chains three commands:

```powershell
php public/api/tools/init.php   # recreate database.db from schema.sql
npm run build                   # type-check, bundle, copy public/api/ into dist/
php -S localhost:8000 -t dist   # serve the build output
```

Because `init.php` runs first, **every `npm run serve` starts from an empty database.** That is
intentional — see [Non-goals](#non-goals).

### Frontend-only development

`npm run dev` starts Vite on port 5173 with HMR, but it does **not** serve the PHP API and there
is no proxy configured. To exercise the API, use the built output:

```powershell
npm run serve
```

## Scripts

| Script    | Command                                                                              | Purpose                                    |
| --------- | ------------------------------------------------------------------------------------ | ------------------------------------------ |
| `serve`   | `php public/api/tools/init.php && npm run build && php -S localhost:8000 -t dist`     | Reset the database, build, and serve `dist/` |
| `dev`     | `vite`                                                                               | Vite dev server with HMR (no PHP API)      |
| `build`   | `tsc -b && vite build`                                                               | Type-check and build into `dist/`          |
| `lint`    | `oxlint`                                                                             | Lint the frontend                          |
| `preview` | `vite preview`                                                                       | Preview the bundle (static only, no PHP)   |

## Project structure

A blank copy of this template looks like this:

```
/
├── public/
│   ├── api/
│   │   ├── template/
│   │   │   └── index.php
│   │   ├── tools/
│   │   │   ├── .htaccess
│   │   │   ├── export.php
│   │   │   ├── init.php
│   │   │   └── schema.sql
│   │   ├── .gitignore
│   │   ├── .htaccess
│   │   ├── common.php
│   │   ├── config.php
│   │   ├── database.db
│   │   └── index.php
│   ├── favicon.svg
│   └── icons.svg
├── src/
│   ├── assets/
│   │   ├── hero.png
│   │   ├── react.svg
│   │   └── vite.svg
│   ├── App.css
│   ├── App.tsx
│   ├── index.css
│   └── main.tsx
├── .gitignore
├── .oxlintrc.json
├── index.html
├── package-lock.json
├── package.json
├── README.md
├── tsconfig.app.json
├── tsconfig.json
├── tsconfig.node.json
└── vite.config.ts
```

This repository adds the Tasks example on top of that skeleton: the `login/`, `logout/`, `me/`,
`projects/`, `register/`, `tasks/`, and `users/` endpoint directories, plus the components and
state hooks under `src/`.

### Backend: `public/api/`

Everything in `public/api/` is copied into `dist/api/` by Vite during the build, so the PHP
source and the built frontend always ship together.

| Path              | Purpose                                                                                                                        |
| ----------------- | ------------------------------------------------------------------------------------------------------------------------------ |
| `index.php`       | Root endpoint, used as a health check by default.                                                                              |
| `common.php`      | Shared helpers. `executePreparedQuery()`, `fetch()`, and `jsonResponse()` ship with the template; `getUser()` is an example of a user-defined helper. Add your own at the bottom. |
| `config.php`      | Constants and configuration. Gitignored — copy `config-template.php` to create it.                                             |
| `template/`       | Copy this directory, rename it to your endpoint name, and put your logic in `index.php`.                                       |
| `tools/`          | CLI-only scripts, blocked from the web by `.htaccess`.                                                                         |
| `tools/schema.sql`| The database definition. Edit this, then run `init.php`.                                                                        |
| `tools/init.php`  | Deletes `database.db` and recreates it from `schema.sql`.                                                                       |
| `tools/export.php`| The inverse: regenerates `schema.sql` from the current `database.db`.                                                           |

`login/`, `logout/`, `me/`, `projects/`, `register/`, `tasks/`, and `users/` are example
endpoints. Delete them when starting a new project.

### Frontend: `src/`

| Path                | Purpose                                                                     |
| ------------------- | --------------------------------------------------------------------------- |
| `src/lib/api.ts`    | The only module that calls `fetch`. Typed wrapper plus `ApiError`.           |
| `src/lib/types.ts`  | Shared API types, mirroring the database rows.                              |
| `src/lib/format.ts` | Small display helpers (date formatting).                                    |
| `src/state/`        | Custom hooks that own state and side effects (`useAuth`, `useToasts`).      |
| `src/components/`   | Presentational daisyUI components. Props in, callbacks out.                 |
| `src/App.tsx`       | Owns application state and wires the hooks to the components.               |
| `src/index.css`     | Tailwind and daisyUI configuration.                                         |

## Conventions

### Backend

**One directory per resource.** Each endpoint is a directory containing a single `index.php`.
The URL path is the directory name, so `/api/projects/` maps to `public/api/projects/index.php`.

**Every endpoint has the same skeleton.** Include the shared helpers, decode the JSON body, then
switch on the request method inside a `try`/`catch`:

```php
require_once __DIR__ . "/../common.php";
$data = json_decode(file_get_contents("php://input"), true);

try {
    switch ($_SERVER["REQUEST_METHOD"]) {
        case "GET":
            // ...
        default:
            jsonResponse(["status" => "error", "message" => "Method not allowed."], 405);
    }
} catch (Exception $e) {
    jsonResponse(["status" => "error", "message" => $e->getMessage()], 500);
}
```

**`jsonResponse()` never returns.** It is typed `: never` because it calls `exit`. Handlers are
therefore written as a flat sequence of guards — validate, respond, and fall through — rather than
with nested `if`/`else` blocks.

**One response envelope.** Every response is `{ status, data?, message? }` where `status` is
`"success"`, `"fail"`, or `"error"`:

- `success` — the request worked; the payload is in `data`.
- `fail` — the request was understood but rejected (validation, not found, unauthorized).
  `data` holds field-keyed messages.
- `error` — something unexpected broke. `message` holds a human-readable string.

**Validation errors are keyed by field name**, so the frontend can attach them to inputs:

```php
jsonResponse([
    "status" => "fail",
    "data" => ["username" => "Username is required."]
], 400);
```

**Mutations use `POST` with a `_method` override.** Real `PUT` and `DELETE` requests are not used;
the client sends `POST` with `{ "_method": "PUT" }` or `{ "_method": "DELETE" }` in the body.
This keeps the frontend wrapper to a single method and avoids preflight requests.

**All SQL goes through `executePreparedQuery()`.** It takes named placeholders, binds each value,
and accepts an optional failure callback. Queries are written as heredocs:

```php
executePreparedQuery($db, <<<SQL
    UPDATE `tasks`
    SET `content` = :content
    WHERE `id` = :id
SQL, [
    ":content" => $data["content"],
    ":id" => $task["id"]
], function () {
    jsonResponse(["status" => "error", "message" => "Failed to update task."], 500);
});
```

**Authentication is a cookie plus a database column.** `login/index.php` generates a session
string, stores it on the user row, and sets a `session` cookie. `getUser($db)` reads that cookie
and looks the user up. Protected endpoints repeat the same guard at the top of the `try` block:

```php
$user = getUser($db);

if ($user == false) {
    jsonResponse(["status" => "fail", "data" => ["session" => "Invalid session."]], 401);
}
```

**Ownership is enforced in the query.** Rather than fetching a row and comparing afterwards,
scoped queries include the owner in the `WHERE` clause:

```sql
SELECT * FROM `projects` WHERE `user_id` = :user_id AND `id` = :id
```

**`tools/` scripts are CLI-only.** Both `init.php` and `export.php` start with a
`php_sapi_name() != "cli"` guard and `exit(1)` on failure, so they can never be triggered over
HTTP. `tools/.htaccess` denies web access as a second layer.

### Frontend

**`src/lib/api.ts` is the only place that calls `fetch`.** Components never talk to the network
directly. The wrapper adds `credentials: "include"`, unwraps the envelope, and throws `ApiError`
for anything that is not `success`.

**`ApiError` carries the HTTP status and field errors.** `extractFieldErrors()` keeps only the
string values from `data`, so a form can render `error.fields.username` next to the input.

**Empty responses become `null`.** A `204` or an empty body resolves to `null` instead of
throwing, which is why delete helpers return `Promise<null>`.

**Types mirror the database rows.** `src/lib/types.ts` uses the same snake_case field names as
the SQL schema, so no mapping layer is needed between the API and the UI.

**`state/` holds hooks, `components/` holds views.** Hooks own state and side effects; components
are presentational and receive props plus callbacks. `App.tsx` is the only place that combines
them.

**Loading state is derived, not stored.** `App.tsx` keeps a version counter per resource and
compares it against the version that finished loading:

```ts
const projectsLoading = loadedListVersion !== listVersion;
```

Bumping the version triggers the effect; the comparison tells the UI whether a request is still
in flight. There is no separate `isLoading` boolean to keep in sync.

**Errors are handled in one place.** `reportError()` shows a toast for ordinary failures and
signs the user out on a `401`, so no component has to special-case an expired session.

**Tailwind 4 is configured in CSS.** `src/index.css` contains the whole setup — there is no
`tailwind.config.js`:

```css
@import "tailwindcss";
@plugin "daisyui" {
  themes: light --default, dark --prefersdark;
}
```

**The theme is applied before first paint.** An inline script in `index.html` reads the saved
theme from `localStorage` and sets `data-theme` on `<html>`, avoiding a flash of the wrong theme.
`ThemeToggle` persists the choice under the same key.

**`Dialog.tsx` wraps the native `<dialog>` element.** It calls `showModal()` and `close()` from an
effect so daisyUI's modal styles work with React state instead of imperative DOM calls.

## Tasks example

The example app is a small project/task tracker. It exists to demonstrate the conventions above
end to end; it is not part of the template.

| Method | Endpoint              | Body                                     | Notes                             |
| ------ | --------------------- | ---------------------------------------- | --------------------------------- |
| GET    | `/api/`               | —                                        | Health check                      |
| GET    | `/api/me/`            | —                                        | Current user; 401 when signed out |
| POST   | `/api/register/`      | `{ username, password }`                 | 201; 400/409 on validation        |
| POST   | `/api/login/`         | `{ username, password }`                 | Sets the `session` cookie         |
| POST   | `/api/logout/`        | —                                        | Clears the session                |
| GET    | `/api/projects/`      | —                                        | List the user's projects          |
| GET    | `/api/projects/?id=1` | —                                        | Project plus its `tasks`          |
| POST   | `/api/projects/`      | `{ title, description }`                 | Create                            |
| POST   | `/api/projects/?id=1` | `{ _method: "PUT", title, description }` | Update                            |
| POST   | `/api/projects/?id=1` | `{ _method: "DELETE" }`                  | 204 on success                    |
| POST   | `/api/tasks/`         | `{ project_id, content }`                | Create                            |
| GET    | `/api/tasks/?id=1`    | —                                        | Single task                       |
| POST   | `/api/tasks/?id=1`    | `{ _method: "PUT", content }`            | Update                            |
| POST   | `/api/tasks/?id=1`    | `{ _method: "DELETE" }`                  | 204 on success                    |

`/api/users/` is a separate example that authenticates with an `Authorization: Bearer` header
against `$ADMIN_KEY` from `config.php` instead of the session cookie. It is not used by the UI.

## Deployment

1. Run `npm run build`.
2. Upload the contents of `dist/` to your host's document root.

That is the whole process — `dist/` already contains the compiled frontend and the PHP API.

**Subdirectories work.** `vite.config.ts` sets `base: './'`, and `src/lib/api.ts` derives its base
from `import.meta.env.BASE_URL`, so the same build runs at `https://example.com/` or
`https://example.com/my-app/` without rebuilding.

**`.htaccess` only applies to Apache.** `public/api/.htaccess` denies everything except
`index.php`, and `public/api/tools/.htaccess` denies the CLI scripts. On nginx or PHP's built-in
server these files are ignored — see the warning below.

## Non-goals

This template is deliberately primitive. It is meant for prototypes, not production:

- **Security is not a priority.** Passwords are hashed, but sessions are plain random strings,
  there is no CSRF protection, no rate limiting, and no HTTPS enforcement.
- **The database lives inside the served directory.** Under Apache, `.htaccess` blocks
  `database.db`. Under `php -S` or nginx it does not, so anyone who can reach the server can
  download the SQLite file — including password hashes. Do not expose this on a public network.
- **Data is disposable.** `npm run serve` recreates the database from `schema.sql` every time, and
  `npm run build` overwrites `dist/api/` with the contents of `public/api/`. Anything created
  while serving `dist/` is lost on the next build.
- **Testing is manual.** There is no test suite. Verify changes by running the app.
- **No router.** `App.tsx` switches between views with a state union, so there are no URLs to
  bookmark or share.