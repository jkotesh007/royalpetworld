---
id: ADR-013
title: Pet history in rpw-core (custom table, staff-edited, auto from bookings and orders)
status: accepted
date: 2026-11-11
tags: [adr]
---
# ADR-013 — Pet history

## Context
Kotesh (2026-11-11) asked to maintain each pet's history: service visits, vaccinations,
health/vet notes and purchases. Staff only edit; customers read in My Account except entries
marked internal; Twenty gets a summary later (Phase 4). Pet profiles (F2) didn't exist yet.

## Decision
- `rpw-core` 0.1.0 ships pet profiles (private CPT `rpw_pet`) **and** history in a custom table
  `wp_rpw_pet_history` (type, event_date, title, details, next_due_date, weight_kg, is_internal,
  source, source_ref) — one row per event, unique on (pet, source, source_ref) so automatic
  sources are idempotent.
- Visits come from LatePoint bookings via a "Which pet?" field (contact step); cancel → removed.
- Purchases come from WooCommerce orders (processing/completed): product in Dog tree → owner's
  active dogs, Cat tree → cats, both → all; cancelled/refunded/failed → removed. Guest orders skipped.

## Alternatives considered
- History as post meta / repeater on the pet — hard to query (due dates, reminders), grows unbounded.
- History as a second CPT — heavier, and WP post tables are a poor fit for many small rows.
- Asking "which pet?" at Woo checkout — extra checkout friction; category mapping is good enough.

## Consequences
- Vaccination `next_due_date` is the source for Phase 5 reminders (latest entry per vaccine wins).
- Multi-pet households get a purchase on every pet of that species (accepted trade-off).
- Bookings made by staff in LatePoint admin, or by guests, aren't linked — staff add visits manually.
- Twenty receives summary only (last visit, next vaccination due) — WP stays the owner.
