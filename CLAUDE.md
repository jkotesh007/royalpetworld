# Royal Pet World — Claude Code operating guide

Client WordPress + WooCommerce site for Royal Pet World (pet shop, services, memberships, pet profiles), integrated with Twenty CRM and WhatsApp via n8n. Hosted on Coolify on the switch2growth VPS.

## Read order (keep token use low)
1. This file.
2. `docs/00-index.md` — map of the vault. Open ONLY the notes relevant to the current task.
3. Never re-derive something the vault already records. If the vault is wrong, fix the vault.

## Environments
| Env | URL | Purpose |
|---|---|---|
| staging | https://royalpetworld-staging.switch2growth.com | All work happens here first |
| production | https://royalpetworld.switch2growth.com | Changed ONLY via deploy after staging is verified and Kotesh approves |

## Hard rules
- Staging first. Never change production config, data or Coolify resources without explicit approval in the current session.
- No secrets in git, in `docs/`, or in chat output. Secrets live in Coolify env vars, n8n credentials, or `~/.config/royalpetworld/.env` on the VPS (chmod 600). Docs record variable NAMES only.
- The site image is immutable: third-party plugins/themes come from `wp/plugins.manifest`, custom code lives in `wp/wp-content/`. Never install plugins through wp-admin on production (`DISALLOW_FILE_MODS` is on there).
- Each data field has one sync rule — see `docs/01-architecture/sync-rules.md`. Do not add a sync path that is not documented there.
- WhatsApp sending goes ONLY through the n8n sub-workflow `wa-send`. Until Meta is configured it runs in `dry_run` mode.
- Ask before: deleting anything, running destructive DB commands, changing Twenty workspace settings, or spending money.

## After every change (definition of done)
1. Update the relevant note in `docs/03-config/` or `docs/04-integrations/`.
2. Append a line to `docs/06-changelog/<YYYY-MM>.md`.
3. If a choice was made between alternatives, run the `log-decision` skill to write an ADR.
4. Exported n8n workflows go to `n8n/workflows/<name>.json`.
5. Commit with a message that references the ADR or changelog entry.

## Common commands
- WP-CLI on staging: `docker exec -u www-data <staging-container> wp <command>` (container name recorded in `docs/03-config/environments.md`)
- Coolify: use the Coolify API available to you; record resource UUIDs in `docs/03-config/environments.md`.
- n8n API: `$N8N_API_URL/api/v1/...` with header `X-N8N-API-KEY`.
- Twenty API: REST/GraphQL at `$TWENTY_API_URL` with `Authorization: Bearer $TWENTY_API_KEY` (Royal Pet World workspace key only).

## Repo layout
- `wp/` — Dockerfile, plugins.manifest, wp-content (custom plugin `rpw-core`, child theme `astra-child`)
- `n8n/workflows/` — exported workflow JSON
- `docs/` — Obsidian vault (project memory)
- `.claude/skills/` — project skills
