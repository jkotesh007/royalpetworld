---
id: ADR-002
title: Environments and domains
status: accepted
date: 2026-10-18
tags: [adr]
---
# ADR-002 — Environments and domains

## Decision
- Staging: royalpetworld-staging.switch2growth.com — all development and testing.
- Production: royalpetworld.switch2growth.com — changed only by deploy after staging verification and Kotesh's approval.
- Separate databases and secrets per environment. Staging is noindex and behind basic auth.

## Alternatives considered
- Single environment — too risky for a live store with payments.

## Consequences
Content created on staging must be migrated deliberately (runbook [[deploy]]). A custom client domain can be added later as an extra domain on production.
