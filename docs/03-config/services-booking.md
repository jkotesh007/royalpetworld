---
tags: [config, services, latepoint]
updated: 2026-11-11
---
# Services Booking (LatePoint)

Plugin: LatePoint 5.7.8 free — [[../02-decisions/ADR-012-latepoint-pay-at-store]].
Page: **/services/** (page id 96, in Primary menu after Shop All) — `[latepoint_resources items="services" columns="3" selected_agent="any"]` + `[latepoint_book_form selected_agent="any"]`.
Setup script: one-off `wp eval-file` (idempotent by name, not kept); data is in the LatePoint tables.

**Everything below is placeholder** until the client confirms (open question #4).

## Settings
| Setting | Value |
|---|---|
| Currency | ₹ before amount |
| Payment | pay at store (`enable_payments_local` = on); no online processor |
| Time / date | 12-hour, d/m/Y |
| Location | id 1 "Royal Pet World, Manikonda" (single location → step skipped) |
| Opening hours | Mon–Sun 10:00–20:00 (general schedule) |
| Help text | "Call or WhatsApp Royal Pet World, Manikonda." — LatePoint's default shows a US phone number; replace with the client's real number when known |
| Agent info on form | hidden (`steps_hide_agent_info` = on) |

## Agents (one per department, so departments don't block each other)
Grooming Team · Vet Desk · Boarding Desk · Training Team — emails are `*@royalpetworld.invalid` placeholders (no notifications delivered). Replace with real staff/emails.

## Services
| Category | Service | Min | ₹ | Agent |
|---|---|---|---|---|
| Grooming & Bathing | Dog Bath & Blow-dry | 60 | 799 | Grooming |
| | Dog Full Grooming | 120 | 1,499 | Grooming |
| | Cat Bath & Brush | 60 | 899 | Grooming |
| | Cat Full Grooming | 90 | 1,599 | Grooming |
| | Nail Trim & Ear Cleaning | 20 | 299 | Grooming |
| Vet Consultation | Vet Consultation | 30 | 500 | Vet |
| | Vaccination Visit (vaccine extra) | 20 | 300 | Vet |
| Boarding & Daycare | Daycare (full day) — drop-off slot | 30 | 699 | Boarding |
| | Boarding (overnight, per night) — drop-off slot | 30 | 999 | Boarding |
| Training | Puppy Training Session | 45 | 799 | Training |
| | Dog Obedience Session | 60 | 999 | Training |

`charge_amount` = price (shown as the total; nothing is charged online).

## Known limits (free tier)
- Boarding/daycare are booked as a 30-min drop-off slot; LatePoint free has no multi-night stays or kennel capacity.
- No custom fields → pet details go in the comments box until `rpw-core` adds the pet selector (F3).

## Test (2026-11-11)
Booked Dog Bath & Blow-dry via /services/ in a browser: staff step skipped, slots 10:00–19:00, ₹799 total, booking `NWNNTTS` saved approved / not_paid, auto-assigned to Grooming, comment saved. Test customer `rpw-test-booking@example.com` and that booking are still on staging (12 Nov 11:00) — delete before go-live.
