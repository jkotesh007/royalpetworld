---
id: ADR-003
title: Immutable WordPress image with plugin manifest
status: accepted
date: 2026-10-18
tags: [adr]
---
# ADR-003 — Immutable WordPress image with plugin manifest

## Context
Plugins installed through wp-admin drift from git and cannot be reproduced or rolled back.

## Decision
Build a Docker image from `wp/Dockerfile`: official WordPress image + WP-CLI; third-party plugins and themes downloaded at build time from `wp/plugins.manifest` (slug + pinned version, wordpress.org sources only); custom code copied from `wp/wp-content/`. Only `wp-content/uploads` is a persistent volume. Production sets `DISALLOW_FILE_MODS=true`.

## Alternatives considered
- Coolify one-click WordPress service with a persistent wp-content volume — faster start, but no reproducibility.

## Consequences
Plugin updates = edit manifest → redeploy staging → test → deploy production.

**Validated on Coolify 2026-10-18** (Phase 0): deployed as a `dockercompose`-buildpack
application from the GitHub repo, staging live at
royalpetworld-staging.switch2growth.com. One real issue found and fixed: a fresh
named volume for `wp-content/uploads` mounts root-owned while Apache's children run
as `www-data` — fixed with a `chown` wrapped around the base image's entrypoint (see
`docker-compose.yml`). Accepting this ADR as-is.
