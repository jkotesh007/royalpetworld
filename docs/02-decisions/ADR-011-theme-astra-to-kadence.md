---
id: ADR-011
title: Switch parent theme from Astra to Kadence
status: accepted
date: 2026-10-18
tags: [adr]
---
# ADR-011 — Switch parent theme from Astra to Kadence

## Context
ADR-001 picked Astra. After seeing the staging shop, Kotesh asked for a different
free theme: brown colour palette, better-looking, responsive on mobile/tablet/
desktop, with good animations on the shop page.

## Decision
Replace Astra with **Kadence** (free, wordpress.org, v1.5.2) as the parent theme.
Kadence's free WooCommerce styling (image-hover swap, sale badges, product card
layout) is noticeably more polished out of the box than Astra's, and it's just as
actively maintained. `astra-child` is removed; `kadence-child` (v0.1.0) replaces it,
carrying:
- A brown/earthy colour palette applied via CSS custom properties and targeted
  WooCommerce selector overrides (buttons, prices, footer) — not dependent on
  guessing Kadence's internal Customizer option schema.
- Shop-page animation: product cards fade/slide in on scroll (IntersectionObserver,
  `shop-animations.js`, no extra plugin) and lift/zoom on hover (CSS transitions,
  disabled on touch via `@media (hover: none)`).

## Alternatives considered
- **Blocksy** (free) — also strong, lighter-weight, but Kadence's free WooCommerce
  module is the more polished out of the box for a shop specifically.
- Kadence Blocks plugin for animations — would only animate block-editor content,
  not the classic WooCommerce shop loop template; plain CSS/JS in the child theme
  works everywhere and adds no extra plugin dependency.

## Consequences
Astra and `astra-child` are gone from the manifest and the repo. Any Astra-specific
customization done later must be redone for Kadence — there wasn't any yet beyond
the font-enqueue boilerplate, so nothing is lost. Supersedes the theme portion of
ADR-001 (hosting/stack/WooCommerce/Razorpay parts of ADR-001 are unaffected).
