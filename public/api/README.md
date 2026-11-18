# Backend Reference — `public/api/`

A plain PHP + SQLite3 backend that ships inside the same build output as a Vite frontend. There is
no framework, no Composer, no autoloader, and no router: one directory per endpoint, one
`index.php` per directory, and a handful of shared helpers in `common.php`.

This document is the point of reference for building on top of this folder. Copy `public/api/`
into the `public/` directory of an existing Vite React app and start adding endpoints.

## How it ships

Vite copies everything in `public/` verbatim into `dist/`, so `public/api/` becomes `dist/api/`.
The compiled frontend and the PHP source always deploy together, from one document root, on one
origin — which is why cookies work without CORS and why the app also runs from a subdirectory.

```
public/api/  ──(vite build)──▶  dist/api/
```

Requirements: **PHP 8.1+** with the `sqlite3` extension, and a web server that executes PHP.

## Layout

| Path                 | Purpose                                                                     |
| -------------------- | --------------------------------------------------------------------------- |
| `index.php`          | Root endpoint. Returns a health check by default.                           |
| `common.php`         | Shared helpers and the `$db` connection. Every endpoint includes this.      |
| `config.php`         | Constants (`$ADMIN_KEY`). Gitignored — copy `config-template.php` to create it. |
| `config-template.php`| Committed template for `config.php`.                                        |
| `template/`          | Endpoint scaffold. Copy, rename, fill in.                                   |
| `tools/`             | CLI-only scripts. Blocked from the web by `.htaccess`.                      |
| `tools/schema.sql`   | The database definition. Edit this, then run `init.php`.                     |
| `tools/init.php`     | Deletes `database.db` and recreates it from `schema.sql`.                   |
| `tools/export.php`   | The inverse: regenerates `schema.sql` from the current `database.db`.       |
| `.htaccess`          | Denies everything except files named `index.php`.                           |
| `.gitignore`         | Ignores `config.php` and `database.db`.                                     |

## Setup

```powershell
Copy-Item public/api/config-template.php public/api/config.php
php public/api/tools/init.php
```

`init.php` creates `database.db` next to `common.php`. `common.php` opens it on every request:

```php
require_once __DIR__ . "/config.php";
$db = new SQLite3(__DIR__ . "/database.db");
```

Because `common.php` is included at the top of every endpoint, **`$db` and `$ADMIN_KEY` are already
in scope** inside your handler. Do not open a second connection.

## Shared helpers in `common.php`

Three helpers ship with the template. They are documented with docblocks in the file itself.

### `executePreparedQuery()`

```php
function executePreparedQuery(
    SQLite3 $db,
    string $query,
    array $values = [],
    ?callable $onFailure = null
): SQLite3Result | false
```

Prepares a statement, binds each entry of `$values` by name, and executes it. Placeholder keys
include the leading colon (`":id" => $id`). The optional `$onFailure` callback receives the
statement and `$db->lastErrorMsg()` when execution fails.

**All SQL goes through this function.** No exceptions, no `$db->query()` with interpolated values,
no string concatenation of user input.

### `fetch()`

```php
function fetch(string $url, array $options = []): array
```

A cURL wrapper for **outbound** HTTP calls — use it when your endpoint needs to talk to a
third-party API. Options:

| Key       | Type   | Default | Meaning                                              |
| --------- | ------ | ------- | ---------------------------------------------------- |
| `method`  | string | `"GET"` | HTTP method.                                         |
| `headers` | array  | `[]`    | Associative headers, e.g. `["Content-Type" => "application/json"]`. |
| `body`    | mixed  | `null`  | String, or an array that is JSON-encoded when `Content-Type` is JSON. |
| `timeout` | int    | `30`    | Seconds.                                             |

Returns:

| Key       | Type   | Meaning                                                    |
| --------- | ------ | ---------------------------------------------------------- |
| `status`  | int    | HTTP status code.                                          |
| `ok`      | bool   | `true` for 2xx.                                            |
| `headers` | array  | Parsed response headers, keyed by name.                    |
| `body`    | string | Raw response body.                                         |
| `json`    | mixed  | `json_decode($body, true)`, or `null` if not JSON.         |

TLS peer and host verification are disabled in this helper, so self-signed endpoints work.

### `jsonResponse()`

```php
function jsonResponse(mixed $data, int $status = 200): never
```

Sets `Content-Type: application/json`, sets the status code, echoes the JSON encoding of `$data`,
and calls `exit`.

**It never returns** — the return type is `never`. This is the single most important fact about
writing handlers here: after `jsonResponse()` the request is over, so handlers are written as a
flat sequence of guards rather than nested `if`/`else` blocks.

### `getUser()`

```php
function getUser(SQLite3 $db): array | false
```

Not part of the shipped trio — it is an example of a **user-defined helper**, and the pattern to
follow when you add your own. It reads the `session` cookie, looks the user up by that column, and
returns the row or `false`.

### Adding helpers

Put new shared helpers at the bottom of `common.php`, under the existing marker:

```php
// All helper functions shared across files go here
```

Helpers that only one endpoint needs stay in that endpoint's `index.php`, declared as plain
functions above the `try` block.

## Endpoint anatomy

Every endpoint is a directory containing exactly one file named `index.php`. The URL path is the
directory name:

```
public/api/projects/index.php   →   /api/projects/
```

The filename must be `index.php` — `public/api/.htaccess` denies every other basename.

### The skeleton

`template/index.php` is the scaffold. Copy the directory, rename it, and fill in the branches:

```php
<?php

require_once __DIR__ . "/../common.php";
$data = json_decode(file_get_contents("php://input"), true);

try {
    switch ($_SERVER["REQUEST_METHOD"]) {
        case "GET":
            // ...
        case "POST":
            // ...
        default:
            jsonResponse([
                "status" => "error",
                "message" => "Method not allowed."
            ], 405);
    }
} catch (Exception $e) {
    jsonResponse([
        "status" => "error",
        "message" => $e->getMessage()
    ], 500);
}
```

Rules that follow from this shape:

- **Include `common.php` first**, with a path relative to the endpoint directory
  (`__DIR__ . "/../common.php"`).
- **Decode the body once**, at the top, into `$data`. Omit the line if the endpoint never reads a
  body.
- **Switch on `$_SERVER["REQUEST_METHOD"]`** inside a `try`/`catch`. The `catch` converts any
  uncaught exception into a `500` `error` envelope.
- **`default` returns `405`.** Never fall through to an implicit response.
- **No `else` branches.** Each guard responds and exits; the happy path continues downward.

### Resource identity is a query parameter

There is no path routing. A single resource is addressed with `?id=`:

```
GET  /api/projects/?id=1
POST /api/projects/?id=1
```

Read it with `isset($_GET["id"])` and reject the request when it is missing.

### Mutations are `POST` with a `_method` override

Real `PUT` and `DELETE` requests are never used. The client sends `POST` with a `_method` field in
the JSON body, and the handler switches on it inside the `POST` branch:

```php
case "POST":
    switch ($data["_method"] ?? null) {
        case "PUT":
            // update
        case "DELETE":
            // delete
    }

    // create
```

Use `$data["_method"] ?? null` so a plain create request (no `_method` field) falls through to the
create logic instead of raising a warning.

| Client sends                                     | Handler branch |
| ------------------------------------------------ | -------------- |
| `POST /api/x/`                                   | create         |
| `POST /api/x/?id=1` + `{ "_method": "PUT" }`     | update         |
| `POST /api/x/?id=1` + `{ "_method": "DELETE" }`  | delete         |

### Status codes

| Code | When                                                              |
| ---- | ----------------------------------------------------------------- |
| 200  | Successful read or update.                                        |
| 201  | Resource created.                                                 |
| 204  | Resource deleted.                                                 |
| 400  | Validation failure, or a required parameter is missing.           |
| 401  | No valid session.                                                 |
| 404  | The row does not exist, or does not belong to the caller.         |
| 405  | Unsupported HTTP method.                                          |
| 409  | Conflict, e.g. a unique value already exists.                     |
| 500  | Unexpected failure.                                               |

A `204` still carries a JSON body because `jsonResponse()` always echoes. Clients treat `204` as
"no content" and ignore it.

## Response envelope

Every response — success or failure — is a single JSON object:

```json
{ "status": "success", "data": { } }
```

| Field     | Presence            | Meaning                                              |
| --------- | ------------------- | ---------------------------------------------------- |
| `status`  | always              | `"success"`, `"fail"`, or `"error"`.                 |
| `data`    | `success` and `fail`| Payload, or field-keyed error messages.              |
| `message` | `error`             | Human-readable description.                          |

- **`success`** — the request worked. The payload is in `data`, or `null` when there is nothing to
  return.
- **`fail`** — the request was understood and rejected: validation, not found, unauthorized.
  `data` is an object keyed by field name.
- **`error`** — something unexpected broke. `message` explains it.

### Validation errors are keyed by field name

The key matches the input the user should fix, so the frontend can attach the message to that
field:

```php
if (trim($data["username"]) == "") {
    jsonResponse([
        "status" => "fail",
        "data" => [
            "username" => "Username is required."
        ]
    ], 400);
}
```

Use a stable key for non-field failures too — `"session"` for auth, `"id"` for a missing
identifier, `"project"` / `"task"` for a missing row.

### Success payloads are named

Wrap collections and single rows in a named key rather than returning a bare array:

```php
jsonResponse([
    "status" => "success",
    "data" => [
        "projects" => $projects
    ]
]);
```

## SQL conventions

**Heredocs, backticked identifiers, named placeholders.** Every query is written the same way:

```php
executePreparedQuery($db, <<<SQL
    UPDATE `tasks`
    SET `content` = :content
    WHERE `id` = :id
SQL, [
    ":content" => $data["content"],
    ":id" => $task["id"]
], function () {
    jsonResponse([
        "status" => "error",
        "message" => "Failed to update task."
    ], 500);
});
```

**Pass a failure callback on writes.** The callback responds with a `500` `error` envelope, so a
failed `INSERT`/`UPDATE`/`DELETE` never silently continues. Reads usually omit it.

**Collect rows with a `while` loop.** There is no fetch-all helper:

```php
$result = executePreparedQuery($db, <<<SQL
    SELECT * FROM `projects` WHERE `user_id` = :user_id
SQL, [
    ":user_id" => $user["id"]
]);

$projects = [];

while ($project = $result->fetchArray(SQLITE3_ASSOC)) {
    $projects[] = $project;
}
```

**Re-read after writing.** To return the canonical row, fetch it again — by
`$db->lastInsertRowID()` after an insert, or by the known id after an update.

**Cast ids in responses.** SQLite returns integers as-is but be explicit where the frontend expects
a number: `"id" => (int) $user["id"]`.

**Ownership is enforced in the `WHERE` clause.** Do not fetch a row and compare owners afterwards.
Scope the query to the caller:

```sql
SELECT * FROM `projects` WHERE `user_id` = :user_id AND `id` = :id
```

A row that belongs to someone else is indistinguishable from a row that does not exist — both are
`404`.

## Authentication

Authentication is a cookie plus a database column. There is no token format, no signing, and no
middleware.

**Sign in** — verify the password, generate a session string, store it on the user row, and set the
cookie:

```php
$session = uniqid("session-");

executePreparedQuery($db, <<<SQL
    UPDATE `users`
    SET `session` = :session
    WHERE `id` = :id
SQL, [
    ":session" => $session,
    ":id" => $user["id"]
], function () {
    jsonResponse([
        "status" => "error",
        "message" => "Failed to create session."
    ], 500);
});

setcookie("session", $session, time() + 86400, "/");
```

**Sign out** — null the column and expire the cookie:

```php
setcookie("session", "", time() - 3600, "/");
```

**Protected endpoints** start their `try` block with the same guard, before anything else:

```php
try {
    $user = getUser($db);

    if ($user == false) {
        jsonResponse([
            "status" => "fail",
            "data" => [
                "session" => "Invalid session."
            ]
        ], 401);
    }

    switch ($_SERVER["REQUEST_METHOD"]) {
        // ...
    }
}
```

`$user` is the full row, so `$user["id"]` is available for scoping queries.

**Header-based auth** is the alternative for machine callers. Normalize the header names, then
compare against `$ADMIN_KEY` from `config.php`:

```php
$headers = array_change_key_case(getallheaders(), CASE_LOWER);

if ($headers == false || isset($headers["authorization"]) == false) {
    jsonResponse([
        "status" => "fail",
        "data" => [
            "authorization" => "Authorization header is required."
        ]
    ], 400);
}

if ($headers["authorization"] != "Bearer " . $ADMIN_KEY) {
    jsonResponse([
        "status" => "fail",
        "data" => [
            "authorization" => "Invalid authorization key."
        ]
    ], 400);
}
```

## Endpoint-local helpers

When a check is used more than once inside one endpoint, declare a plain function above the `try`
block. It can call `jsonResponse()` directly, which means it never returns on failure:

```php
function getProjectById(SQLite3 $db, int $id, array $user): array {
    $project = executePreparedQuery($db, <<<SQL
        SELECT * FROM `projects` WHERE `user_id` = :user_id AND `id` = :id
    SQL, [
        ":user_id" => $user["id"],
        ":id" => $id
    ])->fetchArray(SQLITE3_ASSOC);

    if ($project == false) {
        jsonResponse([
            "status" => "fail",
            "data" => [
                "project" => "Project not found."
            ]
        ], 404);
    }

    return $project;
}
```

Callers can then treat the return value as guaranteed to exist.

## Tools

`tools/` holds CLI-only scripts. They are not endpoints and are never reachable over HTTP.

### `tools/schema.sql`

The database definition — the single source of truth for the schema. Plain `CREATE TABLE`
statements, one per line:

```sql
CREATE TABLE `projects` (`id` INTEGER PRIMARY KEY AUTOINCREMENT, `user_id` INTEGER REFERENCES `users`(`id`), `title` TEXT, `description` TEXT, `created_at` TEXT DEFAULT (datetime()));
```

Conventions: backticked table and column names, `INTEGER PRIMARY KEY AUTOINCREMENT` for ids,
`REFERENCES` for foreign keys, `TEXT DEFAULT (datetime())` for timestamps.

There is no migration story. Edit `schema.sql`, run `init.php`, and the database is rebuilt from
scratch.

### `tools/init.php`

Deletes `database.db` if present, then executes `schema.sql` against a fresh database.

```powershell
php public/api/tools/init.php
```

Prints `Database initialized.` and exits `0`, or prints a failure message and exits `1`.

### `tools/export.php`

The inverse: reads the `CREATE TABLE` statements out of `sqlite_master` and rewrites
`schema.sql` from the live database.

```powershell
php public/api/tools/export.php
```

Use it when you changed the database by hand and want `schema.sql` to catch up. Note that it
regenerates the file wholesale — comments and formatting in `schema.sql` are lost.

### CLI guards

Both scripts open with the same guard, so they can never be triggered by a web request:

```php
if (php_sapi_name() != "cli") {
    echo "Please run this script from the command line.";
    exit(1);
}

chdir(__DIR__);
```

`chdir(__DIR__)` makes the relative paths (`../database.db`, `schema.sql`) resolve regardless of
where the command was invoked from. `tools/.htaccess` denies web access as a second layer.

### `.htaccess` files

`public/api/.htaccess` denies every file except ones named `index.php`:

```apache
<FilesMatch ".+">
    Require all denied
</FilesMatch>

<FilesMatch "^(index\.php)?$">
    Require all granted
</FilesMatch>
```

This is what keeps `common.php`, `config.php`, and `database.db` from being served. It also means
**every endpoint file must be named `index.php`**.

`tools/.htaccess` is a blanket `Require all denied`.

Both files only apply to Apache. On nginx or PHP's built-in server they are ignored.

## Adding an endpoint

1. Copy `public/api/template/` to `public/api/<name>/`.
2. Keep the filename `index.php`.
3. Add the `getUser($db)` guard if the endpoint is protected.
4. Fill in the `GET` / `POST` branches, using `$data["_method"] ?? null` for update and delete.
5. Route every query through `executePreparedQuery()` with named placeholders.
6. Return the envelope — `success` with a named payload, `fail` with field-keyed messages.
7. Add the matching typed function to the frontend's `src/lib/api.ts`.

Checklist before you call it done:

- [ ] `php -l public/api/<name>/index.php` passes.
- [ ] Every branch ends in `jsonResponse()`.
- [ ] `default` returns `405`.
- [ ] Writes pass a failure callback.
- [ ] Scoped queries include the owner in `WHERE`.
- [ ] No `else` chains — guards only.

## Frontend contract

The frontend wrapper in `src/lib/api.ts` is the only module that calls `fetch`. It relies on the
following, so keep them stable:

- **Base URL** is `${import.meta.env.BASE_URL}api`, so the same build works at `/` or `/my-app/`.
- **`credentials: "include"`** on every request — the session cookie is the auth mechanism.
- **Only `GET` and `POST`** are ever sent; mutations carry `_method` in the body.
- **`status: "success"`** unwraps to `data`, or `null` when `data` is absent.
- **`204` and empty bodies** resolve to `null` rather than throwing.
- **Anything else** throws `ApiError` carrying the HTTP status and the string values from `data`
  as field errors, so a form can render `error.fields.username` next to its input.

Types in `src/lib/types.ts` mirror the database rows directly — snake_case, no mapping layer. Name
columns and payload keys accordingly.

## Agents

For AI coding agents working in this folder.

**Permissions — do not spend effort on these.** They are deliberate product decisions.

- **Security is out of scope.** Do not add or propose CSRF tokens, rate limiting, HTTPS
  enforcement, session expiry or rotation, password policy, security headers, cookie flags, or
  extra input sanitizing. Do not audit for vulnerabilities or report findings. Passwords are hashed
  and SQL goes through `executePreparedQuery()`; that is the intended level of rigor. Keep using
  those helpers because they are the house style, not because of a threat model.
- **Data is disposable.** Never ask for confirmation before destroying data. Deleting
  `database.db`, dropping tables, or resetting state is always fine. `init.php` recreates the
  database from `schema.sql` on every run. Do not write backup, restore, migration, or
  "are you sure?" logic.
- **Testing is manual, by humans.** Do not create a test suite, fixtures, or CI config. Do not
  start the app, drive a browser, or curl the API to verify your work.

**Verification ceiling.** `php -l <file>` for a PHP file you edited. `npm run build` type-checks and
bundles. `npm run lint` runs oxlint on the frontend. When you finish, state plainly what the human
should click through to validate the change — do not claim a change "works".

**Conventions to follow.**

- One directory per endpoint, one `index.php` per directory.
- Envelope is `{ status, data?, message? }`; validation errors are `fail` with `data` keyed by
  field name.
- `jsonResponse()` never returns — write flat guards, not nested `if`/`else`.
- Mutations are `POST` with `_method` in the body; never real `PUT`/`DELETE`.
- All SQL through `executePreparedQuery()` with named placeholders and heredoc queries.
- Ownership is enforced in the `WHERE` clause.
- Protected endpoints open their `try` block with the `getUser($db)` guard.
- New endpoints start from a copy of `template/`.
- Shared helpers go at the bottom of `common.php`; endpoint-local helpers go above the `try`.

**Style of change.** Keep diffs small and idiomatic to the surrounding file. Match existing naming,
comment density, and formatting. Prefer editing existing files over adding abstractions,
dependencies, or config files. Do not add dependencies without being asked. Do not add comments
explaining that something is insecure or disposable — that is already documented here.
