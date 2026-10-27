---
name: constants-and-meta
description: Use Option_Keys, Meta_Keys, Session_Keys, and Form_Fields constants instead of magic strings. Use when reading/writing options or order meta.
---

# Constants and meta keys

## Option keys (`includes/constants/class-option-keys.php`)

WooCommerce stores settings under these IDs. **Always** use `Option_Keys::*` with `get_option()`.

Common keys:

- `VOUCHER_OPTION` → `codesoup_boxnow_voucher_option`
- `VOUCHER_EMAIL`, `MOBILE_NUMBER`, `ALLOW_RETURNS`
- Widget: button color, position, map mode, etc.

## Order meta (`includes/constants/class-meta-keys.php`)

Prefix `_codesoup_boxnow_` — use `Meta_Keys::*` constants.

Parcel storage:

- `PARCEL_ID` — single (email/auto mode)
- `PARCEL_IDS` — array (manual/button mode)
- `VOUCHER_CREATED` / `VOUCHERS_CREATED` — flags (different flows)

## Admin form field IDs (`includes/constants/class-form-fields.php`)

Shared between PHP templates and `src/scripts/admin/constants/form-fields.js` — keep in sync when renaming.

## Session (`includes/constants/class-session-keys.php`)

Locker data in WooCommerce session during checkout.

## Anti-patterns

```php
// Wrong — option does not exist in settings API
get_option( 'boxnow_voucher_option' );

// Correct
get_option( Option_Keys::VOUCHER_OPTION, 'button' );
```

## Rules

- Add new keys to the constants class first, then use everywhere
- Update README meta documentation when adding public meta keys
- Mirror PHP constants in JS when the admin UI references DOM ids
