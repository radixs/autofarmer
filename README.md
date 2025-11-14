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

### Reverb & Redis

`config/reverb.php` ships with Redis-based coordination settings, but `REVERB_SCALING_ENABLED` defaults to `false`, so Redis is not required for this deployment. A Redis server is only needed if you enable horizontal scaling; otherwise the standalone Reverb process (or the `reverb` docker service) runs without external dependencies.
