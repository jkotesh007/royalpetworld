---
tags: [runbook]
updated: 2026-10-18
---
# Deploy Runbook

_To be completed in Phase 0 (staging) and Phase 6 (production)._

1. Change on branch `develop` → push → Coolify redeploys staging.
2. Verify on staging (smoke checklist below).
3. Merge `develop` → `main`; deploy production only with Kotesh's approval.
4. Changelog entry.

Smoke checklist: home loads, product page, add to cart, checkout (test mode), My Account → My Pets, wp-admin login, no PHP errors in logs.

Content/config migration staging → production: _define in Phase 6 (WP-CLI export/import of options, never full DB copy over production)._
