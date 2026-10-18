---
id: ADR-001
title: Stack and hosting
status: accepted (theme choice superseded by ADR-011)
date: 2026-10-18
tags: [adr]
---
# ADR-001 — Stack and hosting

## Context
Client needs a shop, services and memberships, operable by Claude Code, on infrastructure Kotesh already runs.

## Decision
WordPress + WooCommerce, Razorpay payments, hosted on Coolify on the switch2growth VPS. Code in a private GitHub repo. Claude Code operates via WP-CLI and WordPress MCP (read + create access).

**Theme: see [[ADR-011-theme-astra-to-kadence]]** — originally Astra, replaced with
Kadence 2026-10-18. Everything else below (hosting/stack/payments) is unaffected.

## Alternatives considered
- Managed WP hosting — extra cost, less control, harder for Claude Code to operate.
- Headless/custom stack — slower to build; client needs familiar WP admin.

## Consequences
Kotesh owns uptime, backups and updates. Deploys are reproducible from git.
