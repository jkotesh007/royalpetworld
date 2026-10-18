---
tags: [config, woocommerce]
updated: 2026-10-18
---
# WooCommerce Settings

| Setting | Value | Status |
|---|---|---|
| Store country / city | India / Hyderabad (Manikonda) | Phase 1 — confirmed by Kotesh 2026-10-18 |
| Currency | INR (₹) | done — was already INR by default on install |
| Timezone | Asia/Kolkata | Phase 0 — done |
| Shipping | Local delivery, Hyderabad pincodes only; store pickup TBD | Phase 1 — not configured yet |
| Payments | Razorpay (test on staging) | Phase 1 — plugin active, not configured |
| Tax / GST display | TBD with client | Phase 1 |
| Guest checkout | TBD | Phase 1 |
| Registration fields | phone (required), WhatsApp opt-in checkbox | Phase 1 |

Pincode list: _paste once confirmed with client_.

## Pilot catalog (2026-10-18)
52 placeholder products created via `wp eval-file` (see
[[../05-runbooks/deploy|deploy runbook]] pattern — script no longer on disk, data is
in the DB). Categories: Pet Food (22), Veterinary Supplies (15), Toys (15). Tags
(pet type): Dog (19), Cat (13), Bird (5), Fish (2), Small Pets (5), General (8).
Images are 12 reused stock photos from Wikimedia Commons (search was noisy —
category-based lookups worked better than free-text search), resized to ≤1000px.
**All placeholder** — names, prices, stock, and photos must be replaced once the
client returns the filled product CSV (see open question #13).

### Gotcha: WooCommerce "Coming Soon" mode
A fresh WooCommerce install sets `woocommerce_coming_soon = yes`, which replaces the
entire shop/storefront with a "Coming Soon" page — no error, just an empty-looking
shop with no visible products or price markup. Fixed with
`wp option update woocommerce_coming_soon no`. Worth checking first if products
"disappear" after any fresh install or reset.
