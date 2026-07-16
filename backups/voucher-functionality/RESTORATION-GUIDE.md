# Voucher Functionality Restoration Guide

## Overview

Voucher functionality was removed from plugin but backed up to allow future re-implementation. This document explains how vouchers work and restoration steps.

## How Vouchers Work

**Concept:**
BoxNow API creates delivery "parcels" - each parcel gets a unique ID and optionally a PDF voucher for pickup.

**Two Modes:**
1. **Automatic** - Single parcel created when order completes (`handle_order_completed` hook)
2. **Manual** - Admin creates multiple parcels via AJAX from order edit screen

**Voucher Delivery Methods:**
- **Button mode** (default) - Admin prints PDF voucher manually
- **Email mode** - BoxNow sends voucher PDF to specified email when parcel ready

**Return Support:**
- Optional "Allow Returns" setting enables customer returns via BoxNow lockers

## Configuration Settings

**Location:** WooCommerce → Settings → BoxNow → Voucher Settings

**Settings:**
- `boxnow_voucher_option` - "button" or "email" delivery method
- `boxnow_voucher_email` - Email for notifications and origin contact
- `boxnow_mobile_number` - Phone number for origin warehouse contact
- `boxnow_allow_returns` - Enable/disable return functionality
- `boxnow_thankyou_page` - Show voucher info on thank you page

## Order Meta Keys

**Tracking Flags:**
- `_voucher_created` - Single parcel auto-created flag ("yes" or empty)
- `_boxnow_vouchers_created` - Manual parcels created flag (1 or empty)

**Parcel IDs:**
- `_boxnow_parcel_id` - Single auto-created parcel ID
- `_boxnow_parcel_ids` - Array of manually created parcel IDs

## Files Modified

### 1. `includes/constants/class-meta-keys.php`
**Purpose:** Define order meta key constants

**Restore:**
Add two constants:
```php
const VOUCHER_CREATED    = '_voucher_created';
const VOUCHERS_CREATED   = '_boxnow_vouchers_created';
```

---

### 2. `includes/integrations/woocommerce/class-wc-settings-box-now.php`
**Purpose:** WooCommerce settings page - add Voucher Settings tab

**Restore:**

**Step A:** Add section to `get_own_sections()`:
```php
protected function get_own_sections() {
    return array(
        ''       => __( 'API Settings', 'codesoup-woo-boxnow' ),
        'widget' => __( 'Widget Settings', 'codesoup-woo-boxnow' ),
        'voucher' => __( 'Voucher Settings', 'codesoup-woo-boxnow' ),  // ADD THIS
    );
}
```

**Step B:** Add method to route section:
```php
protected function get_settings_for_voucher_section() {
    return $this->get_voucher_settings();
}
```

**Step C:** Add private method with settings fields:
```php
private function get_voucher_settings() {
    return array(
        array(
            'title' => __( 'Voucher Configuration', 'codesoup-woo-boxnow' ),
            'type'  => 'title',
            'id'    => 'boxnow_voucher_settings',
        ),
        array(
            'title'   => __( 'Voucher Option', 'codesoup-woo-boxnow' ),
            'id'      => 'boxnow_voucher_option',
            'type'    => 'select',
            'options' => array(
                'button' => __( 'Button', 'codesoup-woo-boxnow' ),
                'email'  => __( 'Email', 'codesoup-woo-boxnow' ),
            ),
            'default' => 'button',
        ),
        array(
            'title' => __( 'Voucher Email', 'codesoup-woo-boxnow' ),
            'id'    => 'boxnow_voucher_email',
            'type'  => 'email',
        ),
        array(
            'title' => __( 'Mobile Number', 'codesoup-woo-boxnow' ),
            'id'    => 'boxnow_mobile_number',
            'type'  => 'text',
        ),
        array(
            'title'       => __( 'Allow Returns', 'codesoup-woo-boxnow' ),
            'id'          => 'boxnow_allow_returns',
            'type'        => 'checkbox',
            'default'     => 'yes',
            'description' => __( 'Allow customers to return items via Box Now lockers', 'codesoup-woo-boxnow' ),
        ),
        array(
            'title'   => __( 'Show on Thank You Page', 'codesoup-woo-boxnow' ),
            'id'      => 'boxnow_thankyou_page',
            'type'    => 'checkbox',
            'default' => 'yes',
        ),
        array(
            'type' => 'sectionend',
            'id'   => 'boxnow_voucher_settings',
        ),
    );
}
```

---

### 3. `includes/services/api/class-delivery-request-service.php`
**Purpose:** Build API request data with voucher settings

**Restore:**

**Step A:** Update method signature from `$num_parcels` to `$num_vouchers`:
```php
public function prepare_delivery_data( $order, $num_vouchers = 1, $compartment_size = null ) {
```

**Step B:** Add voucher email check:
```php
$send_voucher_via_email = 'email' === get_option( 'boxnow_voucher_option', 'button' );
```

**Step C:** Update loop variable:
```php
for ( $i = 0; $i < $num_vouchers; $i++ ) {
```

**Step D:** Update API data array:
```php
'notifyOnAccepted' => $send_voucher_via_email ? get_option( 'boxnow_voucher_email', '' ) : '',
'allowReturn'      => (bool) get_option( 'boxnow_allow_returns', '1' ),
'origin'           => array(
    'contactNumber' => get_option( 'boxnow_mobile_number', '' ),
    'contactEmail'  => get_option( 'boxnow_voucher_email', '' ),
    'locationId'    => $order->get_meta( '_selected_warehouse', true ),
),
```

---

### 4. `includes/services/orders/class-order-handler.php`
**Purpose:** Auto-create single parcel when order completes

**Restore:**

**Step A:** Change flag check from `_boxnow_parcel_id` to `_voucher_created`:
```php
if ( $order->get_meta( '_voucher_created', true ) ) {
    return;  // Already created, skip
}
```

**Step B:** Save flag after parcel creation:
```php
if ( $response && isset( $response['parcels'][0]['id'] ) ) {
    $order->update_meta_data( '_boxnow_parcel_id', $response['parcels'][0]['id'] );
    $order->update_meta_data( '_voucher_created', 'yes' );  // ADD THIS
    $order->save();
}
```

---

### 5. `includes/services/ajax/class-order-ajax-handler.php`
**Purpose:** Handle manual voucher creation via AJAX

**Restore:**

**Step A:** Update class docblock:
```php
/**
 * Handles AJAX requests for order voucher management.
 */
```

**Step B:** Update AJAX hooks in `init()`:
```php
$this->hooker->add_action( 'wp_ajax_create_box_now_vouchers', $this, 'ajax_create_box_now_vouchers' );
$this->hooker->add_action( 'wp_ajax_cancel_voucher', $this, 'ajax_cancel_voucher' );
$this->hooker->add_action( 'wp_ajax_print_box_now_voucher', $this, 'ajax_print_box_now_voucher' );
```

**Step C:** Rename method `ajax_create_box_now_parcels` → `ajax_create_box_now_vouchers`

**Step D:** Update variable names:
```php
$voucher_quantity = isset( $_POST['voucher_quantity'] ) ? absint( $_POST['voucher_quantity'] ) : 1;
```

**Step E:** Update prepare_delivery_data call:
```php
$data = $this->delivery_service->prepare_delivery_data( $order, $voucher_quantity, $compartment_size );
```

**Step F:** Add flag after storing IDs:
```php
$order->update_meta_data( Meta_Keys::PARCEL_IDS, $parcel_ids );
$order->update_meta_data( Meta_Keys::VOUCHERS_CREATED, 1 );  // ADD THIS
$order->save();
```

**Step G:** Update log messages:
```php
$this->log_info( sprintf( 'Created %d vouchers for order #%d', count( $parcel_ids ), $order_id ) );
```

**Step H:** Update success message:
```php
'message' => sprintf(
    // translators: %d: number of vouchers created.
    __( '%d voucher(s) created successfully.', 'codesoup-woo-boxnow' ),
    count( $parcel_ids )
),
```

**Step I:** Rename methods:
- `ajax_cancel_parcel` → `ajax_cancel_voucher`
- `ajax_print_box_now_parcel` → `ajax_print_box_now_voucher`

**Step J:** Update error log message:
```php
$this->log_error( 'Failed to cancel voucher: ' . $e->getMessage() );
```

---

## Testing After Restoration

1. **Settings:** Go to WooCommerce → Settings → BoxNow → Voucher Settings
2. **Configure:**
   - Set voucher option (button/email)
   - Enter voucher email if using email mode
   - Enter mobile number
   - Enable/disable returns
3. **Test Auto-Creation:**
   - Create BoxNow order
   - Mark order as completed
   - Check order meta for `_voucher_created` = "yes" and `_boxnow_parcel_id`
4. **Test Manual Creation:**
   - Edit BoxNow order
   - Use "Create Vouchers" button (requires admin UI implementation)
   - Check order meta for `_boxnow_vouchers_created` = 1 and `_boxnow_parcel_ids` array
5. **Test Email Mode:**
   - Set `boxnow_voucher_option` to "email"
   - Create order
   - Verify API request includes `notifyOnAccepted` with email address

---

## Additional Notes

**Missing Frontend UI:**
This backup contains backend logic only. You also need:
- Admin UI buttons for manual voucher creation in order edit screen
- JavaScript for AJAX calls
- Thank you page voucher display template

**API Integration:**
- Voucher PDF URL returned in API response `parcels[x]['voucherUrl']`
- Print function fetches PDF via `Parcel_Service::get_parcel_pdf()`

**Security:**
- AJAX endpoints verify nonce via `verify_nonce()` method
- Capability check: `current_user_can( 'edit_shop_orders' )`

**Backward Compatibility:**
If re-enabling, existing orders without voucher flags will auto-create on next completion hook.
'notifyOnAccepted' => $send_voucher_via_email ? get_option( 'boxnow_voucher_email', '' ) : '',
'allowReturn'      => (bool) get_option( 'boxnow_allow_returns', '1' ),
'origin'           => array(
    'contactNumber' => get_option( 'boxnow_mobile_number', '' ),
    'contactEmail'  => get_option( 'boxnow_voucher_email', '' ),
    'locationId'    => $order->get_meta( '_selected_warehouse', true ),
),
```
