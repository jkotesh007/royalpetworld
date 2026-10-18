---
tags: [runbook]
updated: 2026-10-18
---
# Deploy Runbook

_Phase 6 will add the production half of this._

1. Change on branch `develop` → push → redeploy staging:
   `POST /api/v1/deploy?uuid=jexn2vgxlnh5cm6luasjh2zr&force=true` (Coolify REST API,
   `localhost:8000`, Bearer token — see `~/.claude.json` project mcpServers.coolify
   config). `force=true` matters: a plain deploy without it can skip re-parsing
   `docker-compose.yml`/env vars.
2. Poll `GET /api/v1/deployments/{deployment_uuid}` until `status` leaves
   `queued`/`in_progress`.
3. Verify on staging (smoke checklist below).
4. Merge `develop` → `main`; deploy production only with Kotesh's approval.
5. Changelog entry.

## WP-CLI on staging
```
docker exec -u www-data wordpress-jexn2vgxlnh5cm6luasjh2zr-<current-suffix> wp <command>
```
Find the current container name with
`docker ps --format '{{.Names}}' | grep jexn2vgxlnh5cm6luasjh2zr` — the suffix changes
every redeploy.

Smoke checklist: home loads, product page, add to cart, checkout (test mode), My Account → My Pets, wp-admin login, no PHP errors in logs.

Content/config migration staging → production: _define in Phase 6 (WP-CLI export/import of options, never full DB copy over production)._
