---
id: ADR-006
title: Two-way sync for customers and pets
status: accepted
date: 2026-10-18
tags: [adr]
---
# ADR-006 — Two-way sync for customers and pets

## Decision
Customer contact fields, WhatsApp opt-in and pets are editable in both WP and Twenty and synced both ways through n8n. Orders, bookings and memberships stay one-way (WP → Twenty). Mechanics in [[sync-rules]].

## Alternatives considered
- One-way WP → Twenty — simpler, but client staff could not correct pet details in Twenty.

## Consequences
Requires stable IDs on both sides, hash-based echo suppression, a conflict rule and nightly reconciliation.
