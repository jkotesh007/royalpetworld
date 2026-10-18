---
id: ADR-005
title: Free booking plugin (supersedes earlier premium-plugin plan)
status: proposed
date: 2026-10-18
tags: [adr]
---
# ADR-005 — Free booking plugin

## Context
Earlier plan was a premium booking plugin; now a free plugin is preferred.

## Decision
Use a free booking plugin from wordpress.org, selected in Phase 3 against these criteria:
1. Free tier allows the services, staff and time slots the client needs.
2. Supports custom fields or hooks so `rpw-core` can add a required "Which pet?" selector and store the pet ID.
3. Exposes hooks/webhooks for booking created/updated/cancelled.
4. Payment: works with WooCommerce/Razorpay, or "pay at store" is acceptable.
5. Active maintenance and compatibility with current WP/WooCommerce.

Candidates to verify (free-tier limits change, so check current terms): Amelia Lite, BookingPress Lite, Bookly (free). Fallback: a minimal booking module inside `rpw-core`.

## Consequences
Record the chosen plugin, its version and free-tier limits in [[plugins]]; mark this ADR accepted.
