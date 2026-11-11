---
id: ADR-012
title: LatePoint (free) for services booking, pay at store
status: accepted
date: 2026-11-11
tags: [adr]
---
# ADR-012 — LatePoint (free) for services booking, pay at store

## Context
Services in scope (Kotesh, 2026-11-11): grooming & bathing, vet consultation, boarding/daycare,
training. Kotesh first chose "pay online at booking", but a 2026-11-11 check of the
[[ADR-005-free-booking-plugin]] candidates found no well-maintained free plugin that pays
through WooCommerce/Razorpay: Amelia Lite (Square), BookingPress Lite (PayPal only, listing
status unclear), Bookly (Woo add-on is paid). Correction found after install: LatePoint free
also ships **Razorpay Connect** (its own checkout, not WooCommerce; LatePoint shows a platform
transaction fee in live mode) — so online payment stays possible later without a code change.

## Decision
Use **LatePoint free** (wordpress.org `latepoint`, pinned 5.7.8 — 100k+ installs, tested to WP
7.1.4) with **payment at the store**. Kotesh chose this over building booking into `rpw-core`.

## Alternatives considered
- Custom booking module in `rpw-core` (services as Woo products, Razorpay checkout, date-range
  boarding) — keeps online payment, but more build + maintenance; not chosen.
- Amelia Lite / BookingPress Lite / Bookly free — same online-payment gap, smaller free feature set.

## Consequences
- No online payment for services for now; "pay online" (open question #4) is reversed to pay at
  store (`enable_payments_local` = on). Switching on Razorpay Connect later costs a per-transaction
  fee on top of Razorpay's — needs Kotesh's/client's OK.
- Boarding/daycare is time-slot based in LatePoint free (no native multi-day stays) — modelled as
  a drop-off appointment; real multi-day capacity needs a paid add-on or `rpw-core` later.
- The "Which pet?" selector (F3) waits for the pet CPT (Phase 2); until then customers write pet
  name/breed/age in the booking form's comments box (stored on the order and customer notes).
- One LatePoint agent per department (Grooming, Vet, Boarding, Training) so departments don't
  block each other's slots; shortcodes use `selected_agent="any"` to skip the staff step.
- Supersedes the plugin-choice part of ADR-005.
