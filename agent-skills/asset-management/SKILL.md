---
name: asset-management
description: Enqueue webpack-built scripts and styles using the Assets service for URLs and manifest lookup. Use when adding JavaScript or CSS bundles from dist/.
---

# Asset Management

## Assets service role

`CodeSoup\BoxNow\Core\Assets` (`plugin()->get( 'assets' )`) provides:

- `get_asset_url( $manifest_key )` — hashed production path from `dist/manifest.json`
- `asset_exists( $manifest_key )` — whether a bundle key exists

It does **not** wrap `wp_enqueue_script()` or `localize_script()`. Enqueue happens in `Admin\Init` and `Frontend\Init`.

## Manifest keys (current bundles)

From `src/config/config.user.js`:

| Key | Output |
|-----|--------|
| `runtime.js` | Webpack runtime |
| `admin-order.js` / `admin-order.css` | Order admin / voucher UI |
| `checkout.js` / `checkout.css` | Classic checkout |
| `checkout-blocks.js` | Blocks checkout |

## Enqueue pattern (admin)

See `includes/admin/class-init.php`:

```php
$assets_handler = plugin()->get( 'assets' );
$plugin_version = plugin()->get_config( 'PLUGIN_VERSION' );

if ( $assets_handler->asset_exists( 'admin-order.js' ) ) {
	wp_enqueue_script(
		'csbxwoo-admin-order',
		$assets_handler->get_asset_url( 'admin-order.js' ),
		$dependencies,
		$plugin_version,
		true
	);

	wp_localize_script(
		'csbxwoo-admin-order',
		'myAjax',
		array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'codesoup_boxnow_nonce' ),
		)
	);
}
```

## Enqueue pattern (frontend checkout)

See `includes/frontend/class-init.php` — only on `is_checkout()`, uses `checkout.js` or `checkout-blocks.js` depending on blocks vs classic, localizes BoxNow widget settings from `Settings_Service`.

## Adding a new bundle

1. Add entry in `src/config/config.user.js`.
2. Run `npm run build` (commit `dist/` if your release process includes built assets).
3. Enqueue using `asset_exists()` / `get_asset_url()` with the manifest key (for example `my-bundle.js`).

## Rules

- Always check `asset_exists()` before enqueueing optional chunks (runtime, vendor).
- Use `PLUGIN_VERSION` for the enqueue version argument.
- Localize nonces and `ajaxurl` with `wp_localize_script()`; admin order scripts expect global `myAjax`.
- Scope enqueue to the screen or template that needs the bundle (order edit, checkout).
