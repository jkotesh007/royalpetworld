---
tags: [config]
updated: 2026-10-18
---
# Environments

| Item | Staging | Production |
|---|---|---|
| URL | https://royalpetworld-staging.switch2growth.com | https://royalpetworld.switch2growth.com |
| Coolify project / env | Royal Pet World (`pljuizbkmnhwdcu3tdn89qsl`) / staging (`heak3nr2jlaytn0dj7avxzix`) | Royal Pet World / production — _Phase 6_ |
| Coolify app UUID | `jexn2vgxlnh5cm6luasjh2zr` (royalpetworld-staging, build_pack `dockercompose`) | _Phase 6_ |
| Container name | `wordpress-jexn2vgxlnh5cm6luasjh2zr-<redeploy-timestamp>` (suffix changes every deploy) | _Phase 6_ |
| Database host / name | `z44bhrpwy7chctgzokbyr39y` (mariadb-shared, same network) / `rpw_staging`, user `rpw_staging` | _Phase 6_ |
| Git branch deployed | `develop` | `main` |
| Indexing | noindex (set via `wp option update blog_public 0` — Coolify's `noindex_domains` app field did not persist, retried once, not chased further) | allowed |
| Basic auth | **not enforced** — see open question #11 | n/a |
| `DISALLOW_FILE_MODS` | false | true |
| Deploy private key | Coolify security key `xkk5wxozaevpqkifhholwufq`; local half `~/.ssh/royalpetworld_deploy` + `~/.ssh/config` host alias `github-royalpetworld` |  |

## External services
| Service | URL | Notes |
|---|---|---|
| n8n | https://n8n.switch2growth.com | existing Coolify instance; access is via its MCP server (`/mcp-server/http`) with a bearer JWT, not the classic `X-N8N-API-KEY` REST key — see open question #12 |
| Twenty | https://twenty.switch2growth.com | Kotesh's shared workspace, not dedicated — see [[../02-decisions/ADR-010-shared-twenty-workspace]]; RoyalPetWorld Venture id `f788ebfc-5061-456b-a29f-8a2a92fd41f5` |
| GitHub repo | https://github.com/jkotesh007/royalpetworld | **public, temporary** (Kotesh's choice for now) — private was the original plan |

## Database host (Phase 0)
| Item | Value |
|---|---|
| Shared MariaDB resource | `mariadb-shared` (Coolify uuid `z44bhrpwy7chctgzokbyr39y`, project `infra`) — same engine as Bondlee/Akaunting/uptime-kuma |
| `rpw_staging` DB + user | _not created yet — pending Coolify project/app creation_ |
