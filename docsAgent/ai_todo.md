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

T03 – Measurement Backups & Sensor Toggle
- Objective: Provide artisan tooling to reset, backup, restore, and gate incoming sensor data so on-site operators can recover quickly from corrupted readings.
- Scope: New console commands for truncate-only reset, deterministic SQL backups, merge-aware restores, and a persisted sensor mode switch that integrates with the ingestion service.
- Deliverables: Artisan commands, persistent mode storage helper, API handling updates, documentation, and regression tests validating backup/restore flows plus the sensor toggle behavior.
- Tests: `php artisan test` covering new console commands + API sensor gating.
- Acceptance: Operators can run the new commands end-to-end, sensor mode survives restarts, and test coverage guards the flows.
- Status: Completed

T04 – Raspberry Pi Deployment Runbook
- Objective: Document and automate the steps required to deploy Autofarmer onto the Raspberry Pi 5 on the 192.168.18.0/24 LAN.
- Scope: Update `docs/deply_to_raspberry_5.md` with prerequisites, firewall/LAN configuration, image build + container boot steps, Arduino POST guidance, and describe new Makefile helpers.
- Deliverables: Expanded deployment guide plus new Make targets (`pi-deploy`, `key-generate`) wired into the repo.
- Tests: Manual verification via `make up` and `make pi-deploy` walkthrough instructions; curl check for `/api/measurements`.
- Acceptance: Operators can follow the document to fetch/build/run the stack on the Pi with LAN-only exposure and minimal commands.
- Status: Completed
