AI Task List for Autofarmer

Format:
- ID: TNN
- Fields: Objective, Scope, Deliverables, Tests, Acceptance, Status

T01 – Autofarmer MVP Delivered
- Objective: Capture the current state (Laravel backend + Vue SPA + realtime measurements) as a single milestone.
- Scope: API ingestion, caching/events, websocket streaming, Vue dashboard, Docker/Makefile, docs.
- Deliverables: Working stack proven during this session.
- Tests: `npm run build`, `php artisan test`, manual curl/websocket verification.
- Acceptance: Dashboard loads, manual/sensor submissions persist + broadcast, caches recalc, CSV export works.
- Status: Completed

T02 – Comprehensive Backend Test Suite
- Objective: Implement an automated backend testing suite that validates all measurement ingestion, caching, broadcasting, and repository flows end-to-end.
- Scope: Build unit tests for DTOs/services, repository tests for query logic, and feature/API tests that cover manual + sensor submissions, cache recalculation, and websocket event dispatch contracts.
- Deliverables: New PHPUnit test cases (unit + feature) with descriptive comments, supporting helpers/factories, and any required test configuration updates.
- Tests: `php artisan test` (full suite) executing the new coverage locally and in CI.
- Acceptance: Tests document intent, pass consistently, and fail when core flows regress; no reliance on manual verification.
- Status: Completed (tests added)
