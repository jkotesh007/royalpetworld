---
tags: [architecture, brief]
updated: 2026-10-18
---
# Project Brief — Royal Pet World

Client: Royal Pet World (pet shop + pet services, Hyderabad). Built and operated by Kotesh (Switch2Growth) with Claude Code.

## Goals
1. Online pet shop (~2,000 products) with offline local delivery inside Hyderabad.
2. Pet services booking (grooming, etc.) tied to a specific pet.
3. Memberships for pet owners, renewed manually from the WP admin panel.
4. Customers keep private profiles for each of their pets.
5. Client staff manage customers, pets and WhatsApp messaging from Twenty CRM.
6. WhatsApp: automatic reminders + custom template messages to selected customer groups, via n8n.

## Systems and their single job
| System | Job | Source of truth for |
|---|---|---|
| WordPress + WooCommerce (Astra child theme) | Storefront, checkout (Razorpay), bookings, memberships, My Pets | Money: orders, payments, bookings, membership periods |
| Twenty CRM (dedicated Royal Pet World workspace) | Staff/client view, segments, campaigns, message log | Segments, campaigns, messaging log |
| n8n (existing Coolify instance) | Glue only — no business data stored | Nothing |
| WhatsApp Cloud API | Delivery | Nothing (placeholder until Meta account is ready) |
| Obsidian vault (`docs/`) | Project memory for humans and Claude Code | Decisions, config, runbooks |

Customer contact data and pets are two-way synced — see [[sync-rules]].

## Features

### F1 Shop
- WooCommerce, INR, timezone Asia/Kolkata, Razorpay (test mode on staging).
- Local delivery restricted to Hyderabad pincodes (shipping zones); store pickup optional.
- Product import in batches via CSV + WP-CLI; categories by pet type (dog, cat, bird, fish, small pets) × product type.

### F2 Pet profiles (custom plugin `rpw-core`)
- Private custom post type `rpw_pet`, author = owning customer; never publicly queryable.
- Fields: name, species, breed, date_of_birth, sex, weight_kg, photo, vaccinations (repeater: vaccine, date_given, next_due), next_grooming_due, notes, twenty_id, sync_hash, updated_at.
- My Account tab "My Pets": list, add, edit, delete (soft delete).
- REST namespace `rpw/v1` (authenticated with an application password for n8n): pets CRUD, customers lookup by phone/email.
- Fires outbound webhook to n8n on pet create/update/delete and on customer profile update.

### F3 Services booking
- Free booking plugin (chosen in Phase 3 — see [[ADR-005-free-booking-plugin]]).
- `rpw-core` adds a required "Which pet?" selector populated from the customer's pets; pet ID stored on the booking.
- Booking events (created, rescheduled, cancelled, reminder-due) → n8n.

### F4 Memberships
- Membership = record on the user: tier, start_date, expiry_date, status (active/expired/cancelled), notes.
- Admin screen in wp-admin (`rpw-core`): assign tier, renew (extend expiry by the tier's period), cancel. Optional Woo product for first purchase online.
- Member benefits (tiers, discount %, inclusions) — TBD, see [[07-open-questions]]. Implementation: role or user-meta driven pricing rule.
- Expiry reminders sent by n8n (T-15, T-3, T+1 days — adjustable).

### F5 Twenty CRM
- See [[twenty-schema]]. Client gets a member account with a limited role; Kotesh is admin.
- Staff run WhatsApp campaigns from Twenty: filter People → select → manual workflow (Bulk) → HTTP request to n8n webhook.

### F6 WhatsApp via n8n
- All sends go through the n8n sub-workflow `wa-send` (provider = `dry_run` | `meta_cloud`). See [[ADR-008-whatsapp-dry-run-until-meta]].
- Business-initiated messages = approved templates only. Reminders → utility templates; promotions → marketing templates.
- Opt-in captured at registration/checkout; STOP reply → opt-out in Twenty and WP.
- Flows: (1) transactional, (2) scheduled reminders, (3) campaigns, (4) inbound + opt-out. Details in [[n8n-workflows]].

## Phases and acceptance criteria
Every phase ends with: vault notes updated, changelog entry, ADRs for choices, commit pushed, short report to Kotesh.

**Phase 0 — Foundation**
- Private GitHub repo with this seed pushed.
- Access verified (no secrets printed): Coolify API, n8n API, Twenty API.
- Coolify project "Royal Pet World" with staging app deployed from the repo; HTTPS works on the staging domain.
- WordPress installed with WooCommerce, Astra + astra-child, Razorpay plugin (unconfigured); search engines discouraged; staging behind basic auth or noindex.
- WP-CLI usable from Claude Code; container name and resource UUIDs recorded in [[environments]].
- Twenty Royal Pet World workspace exists (or the blocker is documented) and its API key stored as `TWENTY_API_KEY`.

**Phase 1 — Shop**
- Store settings, tax display, pincode delivery zones, Razorpay test checkout succeeds end to end.
- Category tree + a 50-product pilot import; import runbook written; then full import.

**Phase 2 — Pets + memberships**
- `rpw-core` v0.1: pet CPT, My Pets tab, REST endpoints, webhooks, membership admin screen; PHPCS clean; tested on staging.

**Phase 3 — Services**
- Booking plugin evaluated (shortlist + criteria in ADR-005), installed via manifest, services configured, pet selector working.

**Phase 4 — Twenty sync**
- Twenty custom objects/fields created per [[twenty-schema]].
- n8n flows: WP→Twenty and Twenty→WP for customers and pets, with echo suppression and conflict rule from [[sync-rules]]; backfill script; 20-record round-trip test passes.

**Phase 5 — WhatsApp**
- `wa-send` sub-workflow in dry_run; flows 1–4 built and logging to Twenty; template drafts in [[whatsapp-templates]].
- When Meta is ready: switch provider, submit templates, test with the internal test segment.

**Phase 6 — Hardening and go-live**
- Backups scheduled + restore tested, security hardening, performance check, production app created and deployed, DNS cutover, smoke tests.
