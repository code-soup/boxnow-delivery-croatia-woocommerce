---
name: checkout-locker
description: Locker selection on checkout — AJAX session, hidden fields, blocks vs classic, widget placement. Use when changing checkout locker behavior.
---

# Checkout locker flow

## PHP

- `Checkout_Handler` — hooks, hidden checkout fields, AJAX save/clear
- `Locker_Data_Manager` — session storage and sanitization
- Nonce: `codesoup_boxnow_nonce`

## AJAX actions

| Action | Auth |
|--------|------|
| `codesoup_bndp_save_boxnow_locker` | logged-in + nopriv |
| `codesoup_bndp_clear_boxnow_locker` | logged-in + nopriv |

## Persisting locker on order

1. Prefer hidden fields on checkout POST (`boxnow_locker_id`, etc.)
2. Fallback: session via `Locker_Data_Manager::save_to_order()`
3. Session cleared after order meta save

## JavaScript bundles

| Bundle | Entry |
|--------|--------|
| Classic | `src/scripts/checkout/index.js` |
| Blocks | `src/scripts/checkout/index-blocks.js` |

Orchestrators: `checkout-classic.js`, `checkout-blocks.js` — widget popup/embedded/custom modes from options.

## Validation

- Client: `validation-service.js`, locker required before place order (widget settings)
- Server: ensure locker meta exists on BoxNow shipping orders before fulfillment

## Rules

- Keep blocks and classic flows in sync when changing locker state shape
- Warehouse default from `boxnow_warehouse_id` option when not posted
- Button position `inline` / `custom` affects `render_locker_button()` and shortcode `[codesoup_boxnow_pick_locker]`
