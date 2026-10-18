---
id: ADR-007
title: Dedicated Twenty workspace for Royal Pet World
status: superseded by ADR-010
date: 2026-10-18
tags: [adr]
---
# ADR-007 — Dedicated Twenty workspace for Royal Pet World

## Decision
Create a new Twenty workspace (organization) for Royal Pet World on Kotesh's existing Twenty. Client gets a member account with a limited role to manage customers, pets and WhatsApp campaigns. Kotesh is admin.

## Things to verify in Phase 0
- Twenty API keys are scoped to a workspace: a NEW key must be generated inside the Royal Pet World workspace. Do not reuse the key from another workspace.
- If Twenty is self-hosted in single-workspace mode, multiple workspaces need a config change (multi-workspace setting + subdomain/DNS). Claude Code must report the current mode and ask before changing Twenty's environment.
- Confirm the role/permission options available to restrict the client.

## Alternatives considered
- Separate Twenty instance per client — strongest isolation, more resources to run.

## Consequences
Client data stays isolated from Kotesh's other CRM data within the shared instance.

**Superseded 2026-10-18 by [[ADR-010-shared-twenty-workspace]]** — Kotesh added
Royal Pet World as a Venture in the existing shared workspace instead.
