# Agent Instructions for Autofarmer

## Scope & Safety
- Operate strictly within this repository. Never modify parent directories or host-level config.
- Do not install global packages on the host. All tooling runs via Docker/Makefile.
- Source of truth for specs: `docsAgent/project_description.md`. Always reconcile requests with that file.

## Runtime Environment
- Containers: `php` (php-fpm), `nginx`, `mysql`, `reverb`.
- Preferred commands: use `make` targets (`make up`, `make down`, `make migrate`, `make seed`, `make build`, etc.). `make up` already runs `ensure-storage` to chown/chmod writable dirs.
- When shelling into services, use `docker compose exec php sh` (or specify `-u www-data` when needed). Never run `docker compose` without Makefile dependency checks unless instructed.
- Laravel tasks: `docker compose exec php php artisan ...`.
- Database inspection: `docker compose exec mysql mysql -uautofarmer -pautofarmer autofarmer -e "..."`.
- Frontend: `npm run build` (inside host but uses node inside php container via mounted dirs). For dev server use `npm run dev` (host). Document if deviating.

## Task Management
- `docsAgent/ai_todo.md`: Maintain the ordered task list. Add new entries as work expands, mark completed tasks explicitly.
- `docsAgent/ai_log.md`: Append log entries each session (UTC timestamps). Record summary, files changed, commands, notes, next steps.
- Avoid multi-task mingling; keep each task atomic per session when possible.

## Development Workflow
1. Read `docsAgent/project_description.md` to understand desired functionality and stack decisions.
2. Before coding, confirm instructions align with current repo state and task list.
3. Use `git status` to review before/after changes (but do not commit without explicit instruction).
4. After each change:
   - Run targeted tests (`php artisan test`, `npm run build`, etc.) relevant to the task.
   - Note any manual verification in `ai_log.md`.
5. Keep controllers thin: route → controller → service → repository (no DB calls from controllers/services skipping layers).
6. Keep Vue components modular; heavy state belongs in Vuex modules.
7. Maintain typed DTOs (app/DataTransferObjects) for service boundaries.
8. Document non-trivial flows or caveats inline with short comments (no noise).

## Testing Expectations
- Every functional addition requires corresponding automated tests or documented manual verification when automation is infeasible.
- Do not leave failing tests; if a legacy test is obsolete, disable with reason and link to log entry.
- Always re-run existing suites relevant to touched areas (e.g., backend `php artisan test`, frontend build) to avoid regressions.

## Documentation Duties
- Update `README.md` whenever setup steps, env vars, or workflows change.
- Keep `docsAgent/project_description.md` and `ai_todo.md` in sync with actual implementation when architecture shifts.
- Mention new config/env requirements explicitly.

## Prohibited Actions
- Never run `git commit`, `git merge`, `git rebase`, or `git push` unless the user explicitly tells you to.
- Never delete user data or modify outside-workdir files.
- Do not disable linting/testing as a shortcut.
- Do not change system-wide Docker/Compose settings.

## Escalation
- If specs conflict or are incomplete, pause and ask the user to clarify or update the spec file.
- When encountering permissions/network issues (e.g., DB host resolution), prefer running commands inside containers rather than adjusting host configs.

Follow these rules to keep Autofarmer maintainable and ready for future AI sessions.
