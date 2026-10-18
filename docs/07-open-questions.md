# Open Questions

| # | Question | Blocks | Owner | Status |
|---|---|---|---|---|
| 1 | Membership tiers, prices, period, benefits (discount %, free services, priority) | Phase 2 pricing rules | Client | open |
| 2 | Is first membership purchase online (Woo product) or admin-only? | Phase 2 | Client | open |
| 3 | Food subscriptions still in scope? (may need WooCommerce Subscriptions — see ADR-004) | Phase 2 | Client | open |
| 4 | Service list, durations, staff, working hours, pay online vs at store | Phase 3 | Client | open |
| 5 | Delivery pincodes, delivery fee rules, store pickup | Phase 1 | Client | open |
| 6 | GST display and invoice requirements | Phase 1 | Client | open |
| 7 | Meta Business account + dedicated WhatsApp number | Phase 5 live mode | Client/Kotesh | open |
| 8 | Twenty self-hosted mode: multi-workspace enabled? | Phase 0 | Kotesh | resolved → ADR-010 (using shared workspace, question moot) |
| 9 | Product data source for ~2,000 products (format, images) | Phase 1 import | Client | open |
| 10 | Client's own domain later? | Phase 6 | Client | open |
| 11 | Staging basic auth: Coolify's `is_http_basic_auth_enabled` doesn't reach Traefik for a `dockercompose`-buildpack app routed via `docker_compose_domains` (confirmed Phase 0 2026-10-18 — flag saves but no middleware appears; a custom-router label workaround broke the whole site because `${VAR}` tokens never resolve inside a compose `labels:` block, reverted). Staging is reachable with no password gate right now, noindex only. Options: patch Coolify, hardcode per-environment labels (couples the shared compose file to one environment), or accept noindex-only for staging. | Phase 0 (carries forward) | Kotesh | open |
| 12 | n8n API access check — **resolved, PASS 2026-10-18**: MCP bearer key works against n8n.switch2growth.com (39 tools, `search_workflows` returned the existing content-automation workflows). Key is an MCP-server JWT, not a classic n8n REST API key — `X-N8N-API-KEY` against `/api/v1/workflows` returned 401. If a REST-style key is needed later (e.g. for `rpw-core` webhooks), generate one separately in n8n settings. | — | — | resolved |
| 13 | Product CSV template sent to Kotesh 2026-10-18 to pass to the client. Staging currently shows 52 placeholder products (names/prices/photos all made up) across Pet Food / Veterinary Supplies / Toys so the client has something concrete to react to. Need the filled CSV back before any of this is real. | Phase 1 | Client | open |
