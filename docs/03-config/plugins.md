---
tags: [config, plugins]
updated: 2026-10-18
---
# Plugins and Themes

Source of truth for installation is `wp/plugins.manifest`. This note records WHY each exists.

| Slug | Type | Version | Why | Added |
|---|---|---|---|---|
| woocommerce | plugin | 11.2.1 | Shop | Phase 0 |
| woo-razorpay | plugin | 4.8.8 | Payments | Phase 0 |
| kadence | theme | 1.5.2 | Parent theme — replaced astra 2026-10-18, see [[../02-decisions/ADR-011-theme-astra-to-kadence]] | Phase 0 |
| kadence-child | theme (custom) | 0.1.0 | Brown palette + shop-page animations, in repo | Phase 0 |
| rpw-core | plugin (custom) | 0.1.0 | Pets, memberships, webhooks, REST | Phase 2 |
| _booking plugin_ | plugin | | Services — see [[ADR-005-free-booking-plugin]] | Phase 3 |

Base image: `wordpress:7.1.3-php8.3-apache` + WP-CLI `2.12.0`. Versions pinned 2026-10-18
from the live wordpress.org/GitHub APIs — re-check before every manifest bump.

Rule: before adding a plugin, check whether `rpw-core` or an existing plugin already covers it. Record removals here too.
