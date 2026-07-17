<?php
/**
 * Plugin main file.
 *
 * @package CodeSoup\BoxNow
 */

namespace CodeSoup\BoxNow;

// If this file is called directly, abort.
defined( 'ABSPATH' ) || die;

/**
 * Plugin Name:       BoxNow Delivery for WooCommerce by CodeSoup
 * Plugin URI:        https://github.com/code-soup/woo-box-now-delivery-croatia
 * Description:       Enable BoxNow locker delivery service for WooCommerce. Unofficial plugin - not affiliated with BoxNow.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.1
 * Author:            Code Soup
 * Author URI:        https://www.codesoup.co
 * License:           GPL-3.0+
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.en.html
 * Update URI:        https://github.com/code-soup/woo-box-now-delivery-croatia
 * Text Domain:       codesoup-woo-boxnow
 * Domain Path:       /languages
 */

// Load autoloaders BEFORE registering hooks.
require_once __DIR__ . '/vendor/autoload.php';
\CodeSoup\BoxNow\Autoloader::register( __DIR__ );

// NOTE: Activation hooks need to be inside index.php file or it might not work properly.
// It can fail without error, WordPress is silently failing in case of error.

// The code that runs during plugin activation.
register_activation_hook(
	__FILE__,
	array( \CodeSoup\BoxNow\Core\Activator::class, 'activate' )
);

// The code that runs during plugin deactivation.
register_deactivation_hook(
	__FILE__,
	array( \CodeSoup\BoxNow\Core\Deactivator::class, 'deactivate' )
);

// Run plugin, run.
require_once __DIR__ . '/run.php';
