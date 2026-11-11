---
tags: [config, rpw-core, pets]
updated: 2026-11-11
---
# Pets and Pet History (`rpw-core` 0.1.0)

Code: `wp/wp-content/plugins/rpw-core/` — decision: [[../02-decisions/ADR-013-pet-history]].

## Pet profile — CPT `rpw_pet`
Private, never public/queryable, not in REST. `post_author` = owning customer; title = pet name; featured image = photo.
Meta: `species` (dog|cat), `breed`, `date_of_birth`, `sex`, `weight_kg`, `notes`, `status` (active|archived — soft delete), `updated_at`, `history_updated_at`.
Staff access = `product` capabilities (shop managers + admins). wp-admin → **Pets**: list (owner, species·breed·age, next vaccination with *overdue* flag, last visit, status) and edit screen (profile box + history box with add/delete).

## Pet history — table `wp_rpw_pet_history`
| Column | Notes |
|---|---|
| type | `visit`, `vaccination`, `health`, `purchase` |
| event_date, title, details | |
| next_due_date | vaccinations; next due = earliest due among the latest entry per vaccine name |
| weight_kg | a health entry with weight also updates the pet's weight |
| is_internal | 1 = staff only, hidden in My Pets |
| source / source_ref | `manual`; `latepoint` / `booking:<id>`; `woocommerce` / `<order_id>:<item_id>` — unique per pet |

DB version option `rpw_core_db_version` (=1); table is created/upgraded on load when it differs.

## Customer side — My Account → My Pets (`/my-account/my-pets/`)
Cards (photo, summary, vaccination due, last visit) → pet page (facts, next due, timeline, upcoming visits flagged) → add/edit (name, dog/cat, breed, DOB, sex, weight, notes, photo jpg/png/webp ≤ 5 MB) → “Remove this pet” = archive.
Customers can only see/edit their own pets; history is read-only for them.

## Automatic entries
- **LatePoint**: contact step shows “Which pet?” (logged-in customers; filtered dog/cat when the service name starts with Dog/Cat or contains Puppy; one match is preselected). Guests see a log-in note. On `latepoint_booking_created` the pet is stored as booking meta `rpw_pet_id` and a visit is added (service, time, team, booking code). Reschedule updates it; cancel removes it.
- **WooCommerce**: on processing/completed, each line item → purchase entry for the owner's active dogs (Dog tree) / cats (Cat tree). Cancelled/refunded/failed → removed. Guest orders skipped.

## Not yet
REST `rpw/v1`, n8n webhooks, memberships (rest of Phase 2); Twenty summary (Phase 4); vaccination WhatsApp reminders (Phase 5). PHPCS not run — not installed on the VPS (php -l clean).

## Test data on staging (delete before go-live)
Customer `rpw-test-owner` (rpw-test-owner@example.com): pets Bruno (dog), Misty (cat), Rex (dog, archived); vaccinations/health notes on Bruno; order #101 (processing); LatePoint booking `NWNNTTS` linked to Bruno.

## Test results (2026-11-11)
Next-due logic, weight sync, internal hidden, owner isolation (“Pet not found”), purchase mapping (dog/cat/both), no duplicates on status change, cancel removes, booking link + reschedule/cancel/re-approve, form-param capture incl. rejecting a pet ID the user doesn't own, admin list/edit render, no PHP errors.
**Not tested in a real browser while logged in** (logged-in pages were checked with a server-generated session cookie).
