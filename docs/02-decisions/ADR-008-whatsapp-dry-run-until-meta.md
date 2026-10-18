---
id: ADR-008
title: WhatsApp built behind a sender abstraction, dry-run until Meta is ready
status: accepted
date: 2026-10-18
tags: [adr]
---
# ADR-008 — WhatsApp dry-run until Meta is ready

## Context
Meta Business account and WhatsApp number are not ready yet.

## Decision
Every send goes through one n8n sub-workflow, `wa-send`, with input (phone, template_name, language, variables, campaign_id) and env/setting `WA_PROVIDER = dry_run | meta_cloud`.
- `dry_run`: no network call; writes a Message Log record in Twenty with status `simulated`.
- `meta_cloud`: calls WhatsApp Cloud API directly (no BSP), logs status, handles delivery webhooks.
`wa-send` always checks opt-in and a per-recipient daily cap before sending.

## Consequences
All flows can be built and tested now; switching to live is a config change plus template approval.
