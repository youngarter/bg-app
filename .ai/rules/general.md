---
paths:
  - '**'
---

# General

## Use the herd-lite PHP 8.5 binary, not /usr/bin/php
The project's PHP is /home/young/.config/herd-lite/bin/php (8.5.0). The system /usr/bin/php is 8.3.6 and cannot boot the app — composer's platform check fails (deps require >= 8.4.1) and it lacks pdo_pgsql, redis, mbstring and curl.

Non-interactive shells do not source ~/.bashrc, so prefix commands:
export PATH="/home/young/.config/herd-lite/bin:$PATH"
export PHP_INI_SCAN_DIR="/home/young/.config/herd-lite/bin"

Infra (PostgreSQL 17, Redis 7, MinIO) runs via `docker compose up -d` at the repo root; the app and Vite run on the host. Tests target the pgsql `bayan_testing` database, not SQLite — the V1 accounting invariants must be verified on the production engine.
