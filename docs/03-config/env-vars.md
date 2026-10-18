---
tags: [config, secrets]
updated: 2026-10-18
---
# Environment Variables (names only — never values)

| Name | Used by | Stored in |
|---|---|---|
| `COOLIFY_API_TOKEN` | Claude Code | already configured for Claude Code |
| `N8N_API_URL`, `N8N_API_KEY` | Claude Code | `~/.config/royalpetworld/.env` (VPS, chmod 600) |
| `TWENTY_API_URL`, `TWENTY_API_KEY` | Claude Code, n8n credential | VPS .env + n8n credentials |
| `WORDPRESS_DB_HOST/NAME/USER/PASSWORD` | WP container | Coolify env (per environment) |
| `WP_AUTH_KEYS` (salts) | WP container | Coolify env |
| `RPW_WEBHOOK_SECRET` | rpw-core, n8n | Coolify env + n8n credentials |
| `RPW_N8N_WEBHOOK_URL` | rpw-core | Coolify env |
| `WP_APP_PASSWORD` (n8n user) | n8n → WP REST | n8n credentials |
| `RAZORPAY_KEY_ID/SECRET` | WooCommerce Razorpay | WP settings (test keys on staging) |
| `WA_PROVIDER` | n8n `wa-send` | n8n variable (`dry_run` now) |
| `WA_PHONE_NUMBER_ID`, `WA_ACCESS_TOKEN`, `WA_APP_SECRET` | n8n | n8n credentials (later) |
| `STAGING_BASIC_AUTH` | Coolify/Traefik | Coolify |
