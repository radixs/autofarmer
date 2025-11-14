Autofarmer 
==========

For detailed project description go to docsAgent/project_description.md
All files in docsAgent are AI agent maintained.

### The stack exposes:

- `http://localhost` — nginx + PHP-FPM serving the SPA
- `ws://localhost:6001` — Laravel Reverb websocket server (started by the `reverb` service)
- `mysql:3306` — internal MySQL 8.4 instance

### Seeding realistic telemetry

Use the bespoke CLI helper to wipe and seed the measurements and cache tables:

```bash
php artisan measurements:seed --days=45
```

This command truncates the tables, generates believable aquarium metrics (hourly readings for the given range), and refreshes all cache buckets without emitting websocket events.

### Database maintenance helpers

Backup/restore flows now live under dedicated artisan commands so you can recover from corrupt data without touching Docker manually:

```bash
php artisan measurements:reset            # truncate measurements + caches without reseeding
php artisan measurements:backup           # dump SQL to storage/db_backups/db_YYYY-mm-dd_-HH-mm-ss.sql
php artisan measurements:restore db.sql   # merge (default) or --merge=false to truncate first
```

`measurements:restore` accepts either absolute paths or file names that exist under `storage/db_backups`. When merging (default) each `REPLACE INTO` statement overwrites rows that share their primary/unique keys, letting you patch corrupted records without dropping everything.
Prefer the Makefile shorthands when working locally—they wrap the same artisan calls inside the php container:

```bash
make resetdb
make backupdb
make restoredb FILE=storage/db_backups/db.sql MERGE=false
make sensor on   # enable ingestion
make sensor off  # disable ingestion
```

### Running backend tests

Execute the PHPUnit suite inside the php container via the Makefile helper:

```bash
make test
```

Optional variables let you narrow the run without typing the full artisan command:

```bash
make test ARGS=tests/Feature/Auth
```

Both options can be combined if desired. This wraps `php artisan test` ensuring the sqlite driver inside the container is used. If you prefer to run the suite on the host directly, make sure your PHP install has the `pdo_sqlite` extension enabled (Composer now enforces this via `ext-pdo_sqlite`).

### Frontend unit tests

Vitest + Vue Test Utils cover the dashboard components and store logic:

```bash
npm run test:unit
npm run test:unit:watch
```

These commands run on the host (Node 18+) and rely on deterministic fixtures, so no backend containers are required.
Run `npm install` after pulling the latest code so Vitest and Vue Test Utils are available locally.

### Websockets

The UI subscribes to `private-measurements.{subscriptionId}` channels through Laravel Reverb. If you prefer to run processes manually instead of the dockerized `reverb` service:

```bash
php artisan reverb:start --host=0.0.0.0 --port=6001
```

Ensure `.env` values for `REVERB_*` and `VITE_REVERB_*` stay in sync.

### Posting sensor readings

Automated sources (Arduino, etc.) should call the public API directly:

- **Method**: `POST`
- **URL**: `http://localhost/api/measurements`
- **Headers**: `Content-Type: application/json`
- **Body**:

```json
{
    "name": "ph",
    "value": 7.12,
    "source": "sensor",
    "created_at": "2025-11-13 15:00:00"
}
```

`name` must match one of the configured measurement slugs (see `resources/data/measurement_ranges.json`). `created_at` is optional—omit it to let the server store the current UTC timestamp.

Toggle automated ingestion without touching Arduino firmware by using the persisted sensor-mode command:

```bash
php artisan sensor:set off   # silently drop sensor payloads while still accepting manual entries
php artisan sensor:set on    # resume normal operations
```

When the receiver is OFF the API still responds with HTTP 201 but includes a `notice` field so you can confirm that the payload was intentionally ignored. The setting lives under `storage/app/sensor_mode.json`, so it survives container rebuilds.
Sensor ingestion ships DISABLED by default—explicitly run `php artisan sensor:set on` (or use the dashboard toggle in the top-right corner) after provisioning whenever you want automated feeds to resume. A REST endpoint (`GET/PUT /api/sensor-mode`) powers the UI toggle so you can script remote changes if needed.

### Reverb & Redis

`config/reverb.php` ships with Redis-based coordination settings, but `REVERB_SCALING_ENABLED` defaults to `false`, so Redis is not required for this deployment. A Redis server is only needed if you enable horizontal scaling; otherwise the standalone Reverb process (or the `reverb` docker service) runs without external dependencies.
