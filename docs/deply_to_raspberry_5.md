# Deploying Autofarmer to Raspberry Pi 5 (Debian 12 / Bookworm)

This guide documents how to put the latest `https://github.com/radixs/autofarmer` branch onto a Raspberry Pi 5 that lives on the example ip **192.168.18.0/24** (Pi IP `192.168.18.21`). The Pi hosts the LAN-only dashboard (HTTP 80) and websocket server (6001) so operators + Arduino sensors can talk to it without exposing anything to the internet.

## Prerequisites
- Raspberry Pi 5 with Debian GNU/Linux 12 (bookworm) logged in as a sudo-capable user.
- `docker`, `docker compose`, `git`, `curl`, `ufw`, and `make` installed. (Docker/Compose already installed per initial state.)
- Local router hands out the static lease `192.168.18.21` to the Pi (configure the reservation on the router so the address never changes).
- Arduino traffic originates from the same `192.168.18.0/24` segment.

If any dependency is missing:

```bash
sudo apt update
sudo apt install -y make git ufw
```

Docker installation instructions are skipped here because the machine already has it, but [Docker's official docs](https://docs.docker.com/engine/install/raspberry-pi-os/) cover the process if you need to re-install.

## Makefile commands used on the Pi

| Command | Purpose |
| --- | --- |
| `make key-generate` | Generates/refreshes the `APP_KEY` using a one-off php container. |
| `make up` | Installs Composer + NPM deps, builds the SPA, ensures writable storage, runs migrations, and boots every container. |
| `make down` | Stops all containers. |
| `make pi-deploy [BRANCH=main]` | Pulls the requested branch from GitHub and then re-runs `make up` (one command update loop). |
| `make sensor on/off` | Toggles whether Arduino payloads are ingested. |
| `make logs SERVICE=nginx` | Tails container logs when troubleshooting. |

## 1. Lock down networking (LAN-only access)
1. Confirm the Pi is on LAN and reporting the correct address:
   ```bash
   hostname -I    # should include 192.168.18.21
   ```
2. Configure a firewall that only allows LAN machines to reach SSH (optional), HTTP (port 80), and Reverb websockets (port 6001). This blocks WAN/Wi-Fi clients outside of the 192.168.18.0/24 subnet:
   ```bash
   sudo ufw default deny incoming
   sudo ufw default allow outgoing
   sudo ufw allow from 192.168.18.0/24 to any port 22 proto tcp comment 'LAN SSH'
   sudo ufw allow from 192.168.18.0/24 to any port 80 proto tcp comment 'LAN dashboard'
   sudo ufw allow from 192.168.18.0/24 to any port 6001 proto tcp comment 'LAN websocket'
   sudo ufw enable
   sudo ufw status verbose
   ```
   *If SSH is not needed, omit the port 22 rule. The rules ensure clients on cellular/WAN cannot hit the dashboard, but anyone plugged into/signed onto the LAN still can.*

3. (Optional) Disable routing/NAT to the WAN interface if the Pi has Ethernet uplinks. Keeping only Wi-Fi enabled is enough for most setups.

## 2. Fetch the application source
1. Choose an install directory (e.g., `/home/pi`):
   ```bash
   cd /home/pi
   git clone https://github.com/radixs/autofarmer.git
   cd autofarmer
   ```
2. For subsequent updates, simply pull via the new helper target:
   ```bash
   make pi-deploy BRANCH=main
   ```
   (This command performs `git fetch`, checks out the specified branch, fast-forwards it, and reruns `make up`.)

## 3. Configure the `.env` for LAN hosting
1. Copy the example file the first time you deploy:
   ```bash
   cp .env.example .env
   ```
2. Edit `.env` so it reflects the Raspberry Pi host and production settings:
   ```
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=http://192.168.18.21
   REVERB_ALLOWED_ORIGINS=http://192.168.18.21

   VITE_APP_URL=http://192.168.18.21
   VITE_REVERB_HOST=192.168.18.21
   VITE_REVERB_PORT=6001
   ```
   Leave `REVERB_HOST=reverb` so the backend hits the dockerized service, and keep the default MySQL credentials unless you purposefully changed them elsewhere. This configuration ensures browsers within the LAN load assets and connect to websockets via the Pi's IP.

3. Generate an application key (safe to re-run):
   ```bash
   make key-generate
   ```

## 4. Build the images and start the containers
1. From the repository root on the Pi run:
   ```bash
   make up
   ```
   This will:
   - Install PHP deps (Composer) and JS deps (npm) if they changed.
   - Run `npm run build` to produce the production SPA bundle.
   - Build updated Docker images (php + nginx) targeting the Pi's ARM64 architecture.
   - Ensure `storage/` + `bootstrap/cache` permissions are compatible with the containers.
   - Start `php`, `nginx`, `mysql`, and `reverb` in detached mode with `docker compose up -d --build`.
   - Run all pending database migrations.

2. Verify everything is healthy:
   ```bash
   docker compose ps
   curl http://192.168.18.21/api/measurements
   ```
   (The measurements endpoint returns JSON when nginx/php are running.)

3. Once the UI loads, re-enable sensor ingestion so Arduino payloads are stored:
   ```bash
   make sensor on
   ```

4. Visit `http://192.168.18.21` from any LAN machine. The Vue dashboard should load fully (charts, tables, websocket status indicator). The websocket URL will be `ws://192.168.18.21:6001`.

## 5. Day-to-day update loop
1. From the Pi, pause Arduino transmissions (or run `make sensor off`) so you can redeploy safely.
2. Pull + rebuild + migrate via one command:
   ```bash
   make pi-deploy BRANCH=main
   ```
   Override `BRANCH` if you want a feature branch instead of `main`.
3. Confirm the containers restarted cleanly using `docker compose ps`.
4. Browse to the dashboard from a laptop/phone to confirm visuals + websocket updates look good.
5. Power/enable the Arduino again (`make sensor on`) so it resumes POSTing to `http://192.168.18.21/api/measurements`.

## 6. Maintenance + troubleshooting
- Stop the stack cleanly when you need to power the Pi down: `make down`.
- Tail logs if nginx/php/mysql ever misbehave:
  ```bash
  make logs SERVICE=nginx
  make logs SERVICE=php
  ```
- Reset/seed/demo data:
  ```bash
  make resetdb        # truncate measurement + cache tables
  make seed DAYS=45   # backfill sample data
  ```
- Database backups stay under `storage/db_backups`. Use `make backupdb` and `make restoredb FILE=...`.
- If the dashboard is unreachable, double-check `ufw status` to ensure only LAN ranges are allowed, and confirm the Pi still owns `192.168.18.21` via `hostname -I`.

## 7. Arduino POST checklist
- Arduino must target `http://192.168.18.21/api/measurements` (port 80) with `Content-Type: application/json`.
- The JSON payload needs `name`, `value`, and `source` (`sensor` for automated posts).
- Keep Arduino on the same Wi-Fi network so the firewall rules allow it through. If its IP lives in `192.168.18.0/24`, it's automatically permitted.
- When testing new payloads manually:
  ```bash
  curl -X POST http://192.168.18.21/api/measurements \
    -H "Content-Type: application/json" \
    -d '{"name":"ph","value":7.1,"source":"sensor"}'
  ```
- Watch the dashboard's "Current Measurements" panel and websocket indicator to ensure events flow immediately after redeployments.

Following this sequence keeps the Pi deployment reproducible: fetch → configure `.env` → `make up` → verify/resume Arduino. All recurring updates collapse down to a single `make pi-deploy BRANCH=...` command while the firewall rules preserve the desired LAN-only exposure.
