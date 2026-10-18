---
tags: [architecture, sync]
updated: 2026-10-18
---
# Sync Rules (WP ⇄ Twenty via n8n)

Applies to two-way entities: **customers** (contact fields + opt-in) and **pets**.

## Identity
- WP user meta `twenty_person_id`; Twenty Person field `wpUserId`.
- WP pet meta `twenty_id`; Twenty Pet field `wpPetId`.
- First sync matches customers by phone (E.164), then email. No match → create.

## Triggers
- WP → n8n: `rpw-core` outbound webhook on save (signed with HMAC secret `RPW_WEBHOOK_SECRET`).
- Twenty → n8n: Twenty webhook on Person/Pet create/update (verify signature/secret).

## Echo suppression (prevents infinite loops)
- n8n computes `sync_hash` = SHA-256 of the canonical JSON of synced fields only.
- Before writing to the other side, n8n compares with the stored `sync_hash` there; equal → skip.
- After writing, n8n stores the new hash on both sides.

## Conflict rule
- Last write wins, compared by each side's `updated_at` (UTC).
- If both sides changed within 60 seconds of each other → do not overwrite; create a Twenty task "Sync conflict: <record>" for staff.

## Deletes
- Soft delete only: WP pet → status `archived`; Twenty Pet → `status = Archived`. No hard deletes through sync.

## Failure handling
- n8n retries 3× with backoff, then writes to n8n error workflow → Twenty task "Sync failed".
- Nightly reconciliation workflow compares counts and hashes; reports drift.

## Fields in scope
- Customer: first_name, last_name, phone, email, address (line1, city, pincode), whatsapp_opt_in.
- Pet: name, species, breed, date_of_birth, sex, weight_kg, vaccinations, next_grooming_due, notes, status.
- Photo: WP is master; Twenty gets the URL only.
