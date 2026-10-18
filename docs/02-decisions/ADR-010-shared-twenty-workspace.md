---
id: ADR-010
title: Use Kotesh's existing shared Twenty workspace, not a dedicated one
status: accepted
date: 2026-10-18
tags: [adr]
---
# ADR-010 — Use the existing shared Twenty workspace

## Context
ADR-007 called for a new, dedicated Twenty workspace for Royal Pet World so client
data stays isolated. In this session Kotesh instead added "RoyalPetWorld" as a
Venture record in his existing shared Twenty workspace (twenty.switch2growth.com —
the same instance used for Switch2Growth, Bondlee, AdExcel, etc.) and supplied an API
key scoped to that shared workspace for Claude Code to use.

## Decision
Royal Pet World's Twenty integration (Phase 4: Person/Pet/Campaign/Message custom
objects, client staff access) lives in the existing shared workspace, tracked via its
Venture record, not a separate workspace. `TWENTY_API_KEY` in
`~/.config/royalpetworld/.env` is this shared workspace's key.

## Alternatives considered
- Dedicated workspace per ADR-007 — stronger client-data isolation, but the instance's
  multi-workspace mode was never confirmed (open question #8) and Kotesh chose the
  simpler path instead of resolving it.

## Consequences
Client-facing custom objects (Pet, WhatsApp Campaign/Message) and the client's limited
role will sit inside Kotesh's shared CRM alongside his other ventures' data, not in an
isolated workspace. If stronger isolation is needed later, revisit as a new ADR.
