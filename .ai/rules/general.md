---
paths:
  - '**'
---

# General

## Non-interactive shells need PATH set for both PHP and Node

The user's interactive shell loads herd-lite and nvm from `~/.bashrc`, but agent shells are non-interactive and do not. Both binaries on the bare PATH are too old to run this project, and both fail with confusing errors rather than a clear version message.

Prefix agent commands with:

```bash
export PATH="/home/young/.nvm/versions/node/v24.16.0/bin:/home/young/.config/herd-lite/bin:$PATH"
export PHP_INI_SCAN_DIR="/home/young/.config/herd-lite/bin"
```

- **PHP** — `/usr/bin/php` is 8.3.6 and fails composer's platform check (deps need >= 8.4.1); it also lacks `pdo_pgsql`, `redis`, `mbstring` and `curl`. Use herd-lite's PHP 8.5.
- **Node** — `/usr/bin/node` is 18.19.1; Vite 8 needs >= 20.19 and `npm run build` dies with `does not provide an export named 'styleText'` from rolldown. Use the nvm v24 binary.

## Infrastructure runs in Docker, the app runs on the host

`docker compose up -d` at the repo root starts PostgreSQL 17, Redis 7 and MinIO. Laravel and Vite run on the host, not in a container.

Tests target the pgsql `bayan_testing` database, never SQLite: the V1 accounting invariants (CHECK constraints, bigint, transactional behaviour, the `EXCLUDE USING gist` overlap constraint) only hold on the production engine.

## The V1 specification is authoritative

`doc/fondation/initial/V1/` defines what to build, in numbered steps. Read `README.md` (index and glossary) and `00-principes.md` before touching domain code, then only the files for the step in hand.

Four locks override any implementation convenience:

1. **One financial source of truth** — `journal_entry_lines`. No stored balance is ever authoritative, and there is no parallel receivables ledger.
2. **The lot account is a projection** of `(342x, auxiliary = lot_account)`, not a table with a balance column.
3. **The debtor is frozen** on `fund_call_lines` at issue time — never re-read from current `lot_ownerships`.
4. **Posted records are immutable.** Correction is a reversal plus a new entry, never an edit.
