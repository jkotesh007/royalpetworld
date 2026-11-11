---
tags: [architecture, data]
updated: 2026-11-11
---
# Data Ownership

| Data | Created in | Editable in | Direction | Notes |
|---|---|---|---|---|
| Customer name, phone, email, address | WP (registration/checkout) | WP and Twenty | Two-way | Phone (E.164) is the matching key; email secondary |
| WhatsApp opt-in | WP (checkbox) | WP; opt-out also from inbound STOP | Two-way | Store opt_in_at timestamp and source |
| Pets | WP (My Pets) | WP and Twenty | Two-way | See [[sync-rules]] |
| Pet history (visits, vaccinations, health notes, purchases) | WP (staff in wp-admin; auto from LatePoint + Woo) | WP admin only (staff) | WP → Twenty, summary only (Phase 4): last visit, next vaccination due | Customers read-only in My Pets, internal entries hidden — [[ADR-013-pet-history]] |
| Orders | WP | WP only | WP → Twenty | Summary only: order count, last order date, lifetime value |
| Bookings | WP | WP only | WP → Twenty | Summary + next booking date |
| Membership | WP admin | WP admin only | WP → Twenty | Tier, status, expiry |
| Segments / tags | Twenty | Twenty | Twenty only | Never written back to WP |
| Campaigns, message log | Twenty / n8n | Twenty | Twenty only | |
| Products, prices, stock | WP | WP | None | Not synced to Twenty |
