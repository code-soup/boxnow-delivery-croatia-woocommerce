# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-07-16

### Added
- BoxNow shipping method integration for WooCommerce
- Interactive locker selection widget with map interface
- API integration with BoxNow service (production and sandbox)
- Automatic parcel creation on order completion
- Order meta storage for locker and parcel information
- Admin settings page in WooCommerce settings
- Widget configuration options (position, display mode)
- API request logging for debugging
- Shipping cost configuration
- Tax class support for shipping method
- Min/max order amount restrictions
- Parcel cancellation via API
- Parcel label printing (PDF)
- AJAX endpoints for order management
- Filter hooks for customization
- Action hooks for extensibility
- WooCommerce order integration
- Shortcode for custom locker widget placement
- Warehouse selection support

### Technical
- PHP 8.1+ requirement with strict typing
- PSR-4 autoloading
- PSR-11 dependency injection container
- Service provider architecture
- Hook registration via Hooker service
- Trait-based logging system
- Modern WooCommerce Settings API pattern (WC 3.4+)
- Order meta helper utilities
- API client with error handling
- AJAX security with nonce verification
- Capability checks for admin actions

### Fixed
- Hook registration guard to prevent double execution
- WooCommerce settings duplication issue
- Multiple provider boot protection

[1.0.0]: https://github.com/code-soup/woo-box-now-delivery-croatia/releases/tag/1.0.0
