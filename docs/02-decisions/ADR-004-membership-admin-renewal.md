---
id: ADR-004
title: Memberships renewed manually from the admin panel
status: accepted
date: 2026-10-18
tags: [adr]
---
# ADR-004 — Memberships renewed manually from the admin panel

## Decision
No auto-debit. Membership is a record on the WP user (tier, start, expiry, status). Admin renews from a wp-admin screen in `rpw-core`, which extends expiry by the tier period. n8n sends WhatsApp renewal reminders.

## Alternatives considered
- WooCommerce Subscriptions + Razorpay Subscriptions plugin — automatic recurring payments, but paid extension and more moving parts.

## Consequences
Revisit if food subscriptions (earlier scope) are confirmed — that feature may require WooCommerce Subscriptions anyway.
