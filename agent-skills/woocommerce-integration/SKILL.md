---
name: woocommerce-integration
description: WooCommerce settings, shipping methods, payment gateways, HPOS order screens, and order meta for the BoxNow plugin. Use when changing checkout, admin orders, or WooCommerce settings.
---

# WooCommerce integration (BoxNow)

## Settings

- WooCommerce → Settings → **BoxNow** tab
- Classes: `includes/integrations/woocommerce/class-wc-settings-box-now.php`, `class-settings-page.php`
- Option keys: `CodeSoup\BoxNow\Constants\Option_Keys` (always use constants, never raw strings)

Example: voucher mode is `Option_Keys::VOUCHER_OPTION` (`codesoup_boxnow_voucher_option`), values `button` | `email`.

## Shipping

- Method ID: `Shipping_Method_Ids::CURRENT` — `includes/services/shipping/class-shipping-method.php`
- Registered via `ShippingServiceProvider`

## Payment gateways

- Pay at locker: `class-pay-at-locker-gateway.php`
- COD vs prepaid mapping for BoxNow API: `Delivery_Request_Service::prepare_delivery_data()`

## Orders and meta

- Locker fields: `Meta_Keys` (`_codesoup_boxnow_locker_*`, parcel IDs, voucher flags)
- Prefer `Order_Service::get_parcel_ids()` over reading a single meta key
- BoxNow order check: `Order_Service::is_box_now_order()`

## HPOS (custom order tables)

- Admin scripts: screen IDs `shop_order` and `woocommerce_page_wc-orders` (`includes/admin/class-init.php`)
- Metabox: use `wc_get_page_screen_id( 'shop-order' )` when registering meta boxes
- Capabilities: `Order_Service::user_can_edit_order( $order_id )` for AJAX

## Custom order status

- `wc-boxnow-canceled` registered in `Order_Handler`; cancels parcels via API on status change

## Hooks to know

| Hook | Handler |
|------|---------|
| `woocommerce_checkout_update_order_meta` | `Checkout_Handler::save_locker_data` |
| `woocommerce_order_status_completed` | `Order_Handler::handle_order_completed` (email voucher mode only) |
| `woocommerce_email_*` | `Email_Handler` locker details |

## Rules

- Never hardcode `boxnow_*` option names without verifying `Option_Keys`
- Test both classic and blocks checkout when changing locker JS
- Voucher button UI only when voucher option is `button`
