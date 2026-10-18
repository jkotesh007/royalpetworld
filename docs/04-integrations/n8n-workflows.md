---
tags: [integration, n8n]
updated: 2026-10-18
---
# n8n Workflows

Naming: prefix every workflow `RPW –` and tag it `royalpetworld`. Export JSON to `n8n/workflows/`.

| Name | Trigger | Does | Phase | n8n ID |
|---|---|---|---|---|
| RPW – wa-send | Sub-workflow | Opt-in + cap check → provider (dry_run/meta_cloud) → log Message in Twenty | 5 | |
| RPW – sync wp→twenty | Webhook (rpw-core, HMAC) | Upsert Person/Pet in Twenty with echo suppression | 4 | |
| RPW – sync twenty→wp | Webhook (Twenty) | Update WP user/pet via rpw/v1 REST | 4 | |
| RPW – reconcile nightly | Cron 02:30 IST | Compare counts/hashes, create Twenty task on drift | 4 | |
| RPW – transactional | Webhook (rpw-core) | Order/booking/membership events → wa-send utility templates | 5 | |
| RPW – reminders daily | Cron 09:00 IST | Vaccination, grooming, booking, membership expiry → wa-send | 5 | |
| RPW – campaign-send | Webhook (Twenty workflow) | Validate campaign → loop recipients (rate-limited) → wa-send → update campaign counts | 5 | |
| RPW – inbound | Webhook (Meta) | Verify signature, log inbound, STOP → opt-out both sides, notify staff | 5 | |
| RPW – errors | Error trigger | Create Twenty task + notify Kotesh | 4 | |
