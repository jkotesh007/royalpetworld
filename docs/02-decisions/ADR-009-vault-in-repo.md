---
id: ADR-009
title: Obsidian vault lives inside the repo
status: accepted
date: 2026-10-18
tags: [adr]
---
# ADR-009 — Obsidian vault inside the repo

## Decision
A new Obsidian vault at `docs/` in the project repo. Claude Code (on the VPS) edits it directly; Kotesh opens it in Obsidian on Windows and syncs with the Obsidian Git plugin. CLAUDE.md stays short and links into the vault.

## Consequences
One source of truth, versioned with the code. Secrets never go in the vault. Pull before editing in Obsidian to avoid merge conflicts.
