---
tags: [config]
updated: 2026-10-18
---
# Environments

| Item | Staging | Production |
|---|---|---|
| URL | https://royalpetworld-staging.switch2growth.com | https://royalpetworld.switch2growth.com |
| Coolify project / env | Royal Pet World / staging | Royal Pet World / production |
| Coolify app UUID | _fill in Phase 0_ | _Phase 6_ |
| Container name | _fill in Phase 0_ | _Phase 6_ |
| Database host / name | _fill in Phase 0_ | _Phase 6_ |
| Git branch deployed | `develop` | `main` |
| Indexing | blocked (noindex + basic auth) | allowed |
| `DISALLOW_FILE_MODS` | false | true |

## External services
| Service | URL | Notes |
|---|---|---|
| n8n | _fill in_ | existing Coolify instance |
| Twenty | https://twenty.switch2growth.com | Kotesh's shared workspace, not dedicated — see [[../02-decisions/ADR-010-shared-twenty-workspace]]; RoyalPetWorld Venture id `f788ebfc-5061-456b-a29f-8a2a92fd41f5` |
| GitHub repo | https://github.com/jkotesh007/royalpetworld | **public, temporary** (Kotesh's choice for now) — private was the original plan |

## Database host (Phase 0)
| Item | Value |
|---|---|
| Shared MariaDB resource | `mariadb-shared` (Coolify uuid `z44bhrpwy7chctgzokbyr39y`, project `infra`) — same engine as Bondlee/Akaunting/uptime-kuma |
| `rpw_staging` DB + user | _not created yet — pending Coolify project/app creation_ |
