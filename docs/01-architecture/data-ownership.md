---
tags: [architecture, data]
updated: 2026-10-18
---
# Data Ownership

| Data | Created in | Editable in | Direction | Notes |
|---|---|---|---|---|
| Customer name, phone, email, address | WP (registration/checkout) | WP and Twenty | Two-way | Phone (E.164) is the matching key; email secondary |
| WhatsApp opt-in | WP (checkbox) | WP; opt-out also from inbound STOP | Two-way | Store opt_in_at timestamp and source |
| Pets | WP (My Pets) | WP and Twenty | Two-way | See [[sync-rules]] |
| Orders | WP | WP only | WP → Twenty | Summary only: order count, last order date, lifetime value |
| Bookings | WP | WP only | WP → Twenty | Summary + next booking date |
| Membership | WP admin | WP admin only | WP → Twenty | Tier, status, expiry |
| Segments / tags | Twenty | Twenty | Twenty only | Never written back to WP |
| Campaigns, message log | Twenty / n8n | Twenty | Twenty only | |
| Products, prices, stock | WP | WP | None | Not synced to Twenty |
