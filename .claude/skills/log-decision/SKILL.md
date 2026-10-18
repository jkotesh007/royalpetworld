---
name: log-decision
description: Record a project decision for Royal Pet World as an ADR in docs/02-decisions, update the vault index and changelog. Use whenever a choice is made between alternatives (plugin, architecture, config, integration approach) or an earlier decision changes.
---

# log-decision

1. List `docs/02-decisions/` to find the next ADR number (ADR-NNN, zero-padded to 3 digits).
2. Copy the structure of `docs/02-decisions/_adr-template.md`. Fill Context, Decision, Alternatives considered, Consequences — max ~25 lines total. Facts only; no secrets.
3. File name: `ADR-NNN-short-kebab-title.md`. Status `accepted` if Kotesh agreed in this session, otherwise `proposed`.
4. If it replaces an earlier ADR: set the old one's status to `superseded by ADR-NNN` and link both ways.
5. Add `- [[ADR-NNN-short-kebab-title]]` under "Decisions" in `docs/00-index.md`.
6. Append to `docs/06-changelog/<YYYY-MM>.md`: `- YYYY-MM-DD — <decision in one line> — [[ADR-NNN-...]]`.
7. If the decision changes config, update the matching note in `docs/03-config/` or `docs/04-integrations/`.
8. If it resolves an item in `docs/07-open-questions.md`, mark it `resolved → ADR-NNN`.
