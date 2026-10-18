---
tags: [integration, twenty]
updated: 2026-10-18
---
# Twenty Schema (Royal Pet World workspace)

Status: planned — create in Phase 4. Record final API names here after creation.

## Person (standard object) — added fields
| Field | Type | Notes |
|---|---|---|
| wpUserId | Number | WP user ID |
| whatsappOptIn | Boolean | |
| optInAt | Date-time | |
| optInSource | Select | checkout, registration, staff, inbound |
| segments | Multi-select | e.g. dog-owner, cat-owner, member-gold, lapsed-90d, test-internal |
| membershipTier | Select | |
| membershipStatus | Select | active, expired, cancelled, none |
| membershipExpiry | Date | |
| orderCount, lastOrderAt, lifetimeValue | Number / Date / Currency | WP → Twenty only |
| nextBookingAt | Date-time | WP → Twenty only |
| syncHash | Text | sync internal |

## Pet (custom object) → relation to Person (many pets per person)
name, species (select), breed, dateOfBirth, sex (select), weightKg, photoUrl, vaccinations (JSON/rich text), nextVaccinationDue (date), nextGroomingDue (date), notes, status (Active/Archived), wpPetId, syncHash.

## WhatsApp Campaign (custom object)
name, templateName, language, variables (JSON), targetSegment, status (Draft/Ready/Sending/Done/Failed), sentCount, failedCount, createdBy.

## WhatsApp Message (custom object) → relations to Person, Campaign
direction (outbound/inbound), templateName, body/variables, status (simulated/queued/sent/delivered/read/failed), providerMessageId, error, sentAt.

## Workflows inside Twenty
- "Send WhatsApp campaign" — Manual trigger on People (Bulk) → HTTP Request POST to n8n `campaign-send` webhook with record IDs + campaign ID.

## Client role
Can read/edit People, Pets, Campaigns, Messages; can run the campaign workflow; cannot change data model, API keys or workspace settings.
