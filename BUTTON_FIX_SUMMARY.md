# Button Click Fix Summary

## Problem
The "Pick a locker" button was visible but not clickable on the checkout page.

## Root Cause
**Class name mismatch** between PHP-generated HTML and JavaScript selectors.

### Original Mismatch:
- **PHP rendered:** `class="button box-now-delivery-button box-now-delivery-button-checkbox"`
- **JavaScript searched for:** `.boxnow-delivery-button-inline` and `.boxnow-delivery-button-checkbox`

The JavaScript was looking for `boxnow-delivery-button` (no dash between "box" and "now"), but PHP generated `box-now-delivery-button` (with dash).

## Solution

### 1. Created PHP Constants Class
**File:** `includes/core/class-constants.php`

Created a single source of truth for CSS classes and element IDs in PHP:
```php
class Constants {
    const CSS_CLASSES = array(
        'BUTTON_BASE'        => 'box-now-delivery-button',
        'BUTTON_INLINE'      => 'box-now-delivery-button-inline',
        'BUTTON_CHECKBOX'    => 'box-now-delivery-button-checkbox',
        'BUTTON_AUTO_SELECT' => 'box-now-delivery-button-auto-select',
        // ... more constants
    );
}
```

### 2. Updated JavaScript Constants
**File:** `src/scripts/checkout/core/constants.js`

Changed from:
```javascript
BUTTON_BASE: 'boxnow-delivery-button',  // WRONG
```

To:
```javascript
BUTTON_BASE: 'box-now-delivery-button',  // CORRECT - matches PHP
```

### 3. Updated PHP Files to Use Constants

**Files Updated:**
- `includes/services/checkout/class-checkout-handler.php`
- `includes/services/shortcodes/class-shortcode-handler.php`

**Before:**
```php
class="button box-now-delivery-button box-now-delivery-button-checkbox"
```

**After:**
```php
$button_base_class = \CodeSoup\BoxNow\Core\Constants::get_css_class('BUTTON_BASE');
$button_checkbox_class = \CodeSoup\BoxNow\Core\Constants::get_css_class('BUTTON_CHECKBOX');
class="button <?php echo esc_attr($button_base_class . ' ' . $button_checkbox_class); ?>"
```

### 4. Updated SCSS
**File:** `src/styles/components/_button-manager.scss`

Changed selector from:
```scss
.boxnow-delivery-button {  // WRONG
```

To:
```scss
.box-now-delivery-button {  // CORRECT
```

### 5. Added CSS for `.visible-inline` Class
The JavaScript was adding `visible-inline` class but CSS didn't have a rule for it.

**Added:**
```scss
&.visible,
&.visible-inline {
    display: inline-block;
}
```

## Benefits of This Approach

✅ **Single Source of Truth:** PHP constants define all CSS classes and IDs  
✅ **Type Safety:** PHP constants prevent typos  
✅ **Consistency:** JavaScript and PHP always use the same class names  
✅ **Maintainability:** Change a class name in one place, it updates everywhere  
✅ **Documentation:** Constants serve as documentation of all used classes  

## Testing
After the fix:
1. ✅ Button is visible when BoxNow shipping is selected
2. ✅ Click listener attaches successfully
3. ✅ Button click opens the locker selection widget
4. ✅ All button positions work (inline, checkbox, both, custom)

## Files Modified

### Created:
- `includes/core/class-constants.php`

### Updated:
- `src/scripts/checkout/core/constants.js`
- `src/styles/components/_button-manager.scss`
- `includes/services/checkout/class-checkout-handler.php`
- `includes/services/shortcodes/class-shortcode-handler.php`
- `index.php` (version bump to 1.0.1 for cache busting)

## Prevention
To prevent this issue in the future:
1. Always use `Constants::get_css_class()` in PHP
2. Always use `CSSClasses` constants in JavaScript
3. Never hardcode class names in templates
4. Keep PHP and JS constants in sync
