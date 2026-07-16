# WordPress Plugin Boilerplate - Issues & Improvements

## Critical Issue: Double Hook Registration

### Problem Description

The WordPress plugin boilerplate has a critical flaw that causes hooks (actions/filters) to be registered multiple times, leading to duplicate functionality execution.

### Symptoms

- Settings pages appear twice in WooCommerce admin
- AJAX handlers execute multiple times
- Filters apply transformations repeatedly
- Performance degradation

### Root Cause

The boilerplate's architecture instantiates the main plugin class multiple times:

1. `index.php` includes `run.php`
2. `run.php` creates `Plugin` instance
3. Settings class constructor also creates instances
4. Multiple `Hooker::run()` calls register same hooks

### Flow Diagram

```
index.php
  → run.php
    → new Plugin()
      → Container->boot()
        → ServiceProvider->register()
          → Hooker::run() [First registration]
        → new WC_Settings_Page()
          → Hooker::run() [Second registration - DUPLICATE]
```

### Solution Implemented

Added registration guard in `Hooker` class:

```php
class Hooker {
    private static $hooks_registered = false;

    public static function run() {
        if (self::$hooks_registered) {
            return;
        }
        self::$hooks_registered = true;
        
        // Register hooks...
    }
}
```

### Recommended Boilerplate Fix

The boilerplate should implement singleton pattern or dependency injection to ensure single instantiation:

```php
class Plugin {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Initialize
    }
}
```

## Architecture Analysis

### Strengths

1. **Separation of Concerns** - Clear service provider pattern
2. **PSR-4 Autoloading** - Modern PHP standards
3. **Dependency Injection** - PSR-11 container
4. **Trait System** - Reusable logging and requirements

### Weaknesses

1. **Multiple Instantiation** - No singleton enforcement
2. **Obsolete WC Pattern** - Uses old `WC_Settings_API` extends pattern
3. **Hook Management** - No built-in duplicate prevention
4. **Static Dependencies** - Hooker class uses static methods

## WooCommerce Best Practices

### Modern Settings Pattern (WC 3.4+)

**Current (Obsolete):**
```php
class Settings extends WC_Settings_API {
    public function __construct() {
        $this->init_form_fields();
        add_filter('woocommerce_settings_tabs_array', ...);
    }
}
```

**Recommended:**
```php
class Settings extends WC_Settings_Page {
    protected function get_settings_for_section($section) {
        return array(/* settings */);
    }
}
```

### Hook Registration

**Current:**
```php
Hooker::run(); // Called multiple times
```

**Recommended:**
```php
class ServiceProvider {
    private $hooks_registered = false;
    
    public function register() {
        if ($this->hooks_registered) {
            return;
        }
        $this->register_hooks();
        $this->hooks_registered = true;
    }
}
```

## PHP Best Practices

### Instance Properties vs Static

**Issue:**
```php
private static $hooks_registered = false; // Shared across all instances
```

**Better:**
```php
private $hooks_registered = false; // Per-instance state
```

### Type Declarations

All methods should use parameter and return type hints:

```php
public function register_hook(string $hook, callable $callback, int $priority = 10): void
```

### Guard Clauses

Use early returns to reduce nesting:

```php
public function process() {
    if (!$this->is_valid()) {
        return;
    }
    
    // Process...
}
```

## Recommended Improvements

### Priority 1 (Critical)

- [ ] Implement singleton pattern for Plugin class
- [ ] Add hook registration guard by default
- [ ] Update documentation with anti-patterns

### Priority 2 (Important)

- [ ] Modernize WooCommerce settings integration
- [ ] Add instance-based service registration
- [ ] Implement proper dependency injection

### Priority 3 (Nice to Have)

- [ ] Add type hints throughout
- [ ] Implement interface-based contracts
- [ ] Add PHPDoc blocks

## Testing Checklist

After implementing fixes:

- [ ] Verify settings page appears only once
- [ ] Check AJAX handlers execute once per request
- [ ] Confirm filters apply transformations once
- [ ] Test activation/deactivation multiple times
- [ ] Verify no duplicate database entries

## Reference Files

**Core Classes:**
- `includes/core/class-plugin.php` - Main plugin orchestration
- `includes/core/class-hooker.php` - Hook registration (with guard)
- `includes/core/class-container.php` - Dependency injection
- `includes/abstracts/class-abstract-service-provider.php` - Base provider

**Integration:**
- `includes/integrations/woocommerce/class-wc-settings-box-now.php` - Modern pattern
- `includes/providers/` - Service provider implementations

## Questions for Boilerplate Maintainer

1. Should Plugin class implement singleton by default?
2. Is static Hooker class intentional or technical debt?
3. Should service providers have built-in duplicate prevention?
4. Will boilerplate update to modern WC settings pattern?

## License Note

This analysis is provided as-is for boilerplate improvement. No warranty provided.
