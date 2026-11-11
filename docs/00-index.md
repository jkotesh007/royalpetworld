# Royal Pet World — Vault Index

## Start here
- [[project-brief]] — scope, features, phases, acceptance criteria
- [[07-open-questions]] — unresolved items (check before building anything they touch)

## Architecture
- [[project-brief]]
- [[data-ownership]] — which system owns which data
- [[sync-rules]] — WP ⇄ Twenty sync mechanics, conflict and loop rules

## Decisions (ADRs)
- [[ADR-001-stack-and-hosting]]
- [[ADR-002-environments-and-domains]]
- [[ADR-003-immutable-wp-image]]
- [[ADR-004-membership-admin-renewal]]
- [[ADR-005-free-booking-plugin]]
- [[ADR-006-two-way-sync]]
- [[ADR-007-twenty-dedicated-workspace]]
- [[ADR-008-whatsapp-dry-run-until-meta]]
- [[ADR-009-vault-in-repo]]
- [[ADR-010-shared-twenty-workspace]]
- [[ADR-011-theme-astra-to-kadence]]
- [[ADR-012-latepoint-pay-at-store]]
- [[ADR-013-pet-history]]
- Template: [[_adr-template]]

## Configuration
- [[environments]] — URLs, Coolify resource UUIDs, container names, DB names
- [[env-vars]] — variable names and where each is stored (no values)
- [[plugins]] — every plugin/theme, version, why it exists
- [[woocommerce-settings]]
- [[services-booking]] — LatePoint services, hours, agents (placeholders)
- [[pets-and-history]] — rpw-core pet profiles + pet history
- [[site-pages]] — Home / About / Contact build script, placeholders, photo licences

## Integrations
- [[twenty-schema]]
- [[n8n-workflows]]
- [[whatsapp-templates]]

## Runbooks
- [[deploy]]
- [[backup-restore]]

## Changelog
- [[2026-10]]
- [[2026-11]]
