## AGENTS.md

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