---
tags: [config, woocommerce]
updated: 2026-11-11
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
| Customer registration | On at My Account (`woocommerce_enable_myaccount_registration=yes`); customer sets own password (`woocommerce_registration_generate_password=no`, staging has no email); username auto-generated — needed so owners can add pets | 2026-11-12 |

Pincode list: _paste once confirmed with client_.

## Category tree (2026-11-11) — cats & dogs only, pet first
Kotesh decided 2026-11-11: store sells for **cats and dogs only** (bird/fish/small pets
dropped) and the menu is **pet first**.

| Parent | Children (slug `dog-…` / `cat-…`) |
|---|---|
| Dog (`dog`) | Food, Treats, Toys, Grooming, Health & Vet, Accessories, Beds & Bowls |
| Cat (`cat`) | Food, Treats, Toys, Litter, Grooming, Health & Vet, Accessories, Beds & Bowls |

- Products that suit both pets are in both trees (WooCommerce multi-category), not tagged.
- Pet-type tags (Dog/Cat/Bird/Fish/Small Pets/General) and the old flat categories
  (Pet Food / Veterinary Supplies / Toys) were deleted.
- 12 bird/fish/small-pet placeholder products moved to **trash** (IDs 34–41, 72–75),
  restorable from Products → Trash.
- Menu "Primary" (id 42) assigned to Kadence `primary` + `mobile`: Home, Shop All,
  Dog ▸ 7 children, Cat ▸ 8 children. Services entry added in Phase 3.
- Empty so far (no placeholder products): Dog/Cat Accessories, Beds & Bowls, Cat Litter.
- Script used: one-off `wp eval-file` (not kept); data lives in the DB.

## Pilot catalog (2026-10-18, superseded by the tree above)
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
