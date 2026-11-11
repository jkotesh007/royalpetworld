---
tags: [config, content, pages]
updated: 2026-11-11
---
# Site pages: Home, About Us, Contact Us

Built by `wp/seed/pages-home-about-contact.php` (idempotent, matched by slug; not in the image):
```
docker cp wp/seed/pages-home-about-contact.php <ctr>:/tmp/
docker exec -u www-data <ctr> wp eval-file /tmp/pages-home-about-contact.php <dog_img_id> <cat_img_id>
```
Staging: Home = page 105 (front page), About Us = 106 (`/about-us/`), Contact Us = 107 (`/contact-us/`); photos = attachments 103 (dog) / 104 (cat).
Re-running the script **overwrites** edits made in the WordPress editor — after the client starts editing, change pages in WP, not in the script.
Kadence per-page meta on all three: title hidden, fullwidth, unboxed, no vertical padding. Styles: `kadence-child/style.css` section “Home / About / Contact pages” (classes `rpw-*`).

## Home sections
Hero (Shop for dogs / cats, Book a service) → Shop by pet tiles → Popular for dogs / cats (`[products ids=…]`, hand-picked for distinct placeholder photos — switch to `category=` + `orderby="popularity"` after the real catalog import) → Pet services (4 cards, “From ₹” = lowest LatePoint placeholder price per category) → My Pets promo → About snippet → Contact strip.

## PLACEHOLDERS — need the client (open question #14)
| Item | Now |
|---|---|
| Street address | “Shop address to be added, Manikonda, Hyderabad, Telangana 500089” (PIN is Manikonda's; confirm) |
| Phone / WhatsApp | `+91 XXXXX XXXXX` (not linked) |
| Email | “Email address to be added” — deliberately no invented address |
| Opening hours | Every day 10 am – 8 pm (same as LatePoint placeholder) |
| About copy | Generic story/promise written by us — no facts, years or numbers claimed; client to review |
| “From ₹” prices | from LatePoint placeholders |
| Map | Google Maps embed of “Manikonda, Hyderabad” (area, not the shop pin) |
No contact form yet (no form plugin; staging can't send email).

## Photos (public domain / CC0 — no attribution required)
- Dog: “Labrador Retriever portrait.jpg”, Wikimedia Commons, public domain.
- Cat: “Kitty looking into camera (23446536729).jpg”, Wikimedia Commons, CC0.

## Other changes
- Footer credit: Kadence `footer_html_content` = `{copyright} {year} {site-title} · Manikonda, Hyderabad` (removed “WordPress Theme by Kadence WP”).
- Primary menu: + About Us, Contact Us at the end.
- **Bug fix:** product cards were hidden by CSS on every page but only revealed by `shop-animations.js` on shop/category pages — related products on single-product pages were likely invisible. Now hidden only under `html.rpw-anim`, which the script adds; script also loads on the front page. Theme 0.2.1.
