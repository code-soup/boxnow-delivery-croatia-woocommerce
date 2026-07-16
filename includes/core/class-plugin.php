<?php
/**
 * Main Plugin class.
 *
 * @package CodeSoup\BoxNow
 */

declare( strict_types=1 );

namespace CodeSoup\BoxNow\Core;

use CodeSoup\BoxNow\Providers\AdminServiceProvider;
use CodeSoup\BoxNow\Providers\FrontendServiceProvider;
use CodeSoup\BoxNow\Traits\RequirementChecksTrait;

/**
 * The main plugin class
 */
final class Plugin {

	use RequirementChecksTrait;

	/**
	 * The single instance of the class
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * The plugin's configuration
	 *
	 * @var array
	 */
	private array $config = array();

	/**
	 * Error message for display
	 *
	 * @var string|null
	 */
	private ?string $error_message = null;

	/**
	 * The dependency injection container.
	 *
	 * @var Container
	 */
	public Container $container;

	/**
	 * The main plugin file path.
	 *
	 * @var string
	 */
	private string $plugin_file;

	/**
	 * List of providers.
	 *
	 * @var array
	 */
	protected array $providers = array(
		AdminServiceProvider::class,
		FrontendServiceProvider::class,

		// BoxNow Service Providers
		\CodeSoup\BoxNow\Providers\ShippingServiceProvider::class,
		\CodeSoup\BoxNow\Providers\APIServiceProvider::class,
		\CodeSoup\BoxNow\Providers\CheckoutServiceProvider::class,
		\CodeSoup\BoxNow\Providers\OrdersServiceProvider::class,
		\CodeSoup\BoxNow\Providers\AjaxServiceProvider::class,
		\CodeSoup\BoxNow\Providers\EmailServiceProvider::class,
		\CodeSoup\BoxNow\Providers\ShortcodeServiceProvider::class,
		\CodeSoup\BoxNow\Providers\WooCommerceAdminServiceProvider::class,
	);

	/**
	 * Whether providers have been booted.
	 *
	 * @var bool
	 */
	private bool $providers_booted = false;

	/**
	 * Main Plugin instance
	 *
	 * @param string               $plugin_file Main plugin file path.
	 * @param array<string, mixed> $config The plugin configuration.
	 *
	 * @return Plugin
	 */
	public static function instance( string $plugin_file, array $config ): Plugin {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self( $plugin_file, $config );
		}

		return self::$instance;
	}

	/**
	 * Cloning is forbidden.
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'codesoup-woo-boxnow' ), '1.0.0' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'codesoup-woo-boxnow' ), '1.0.0' );
	}

	/**
	 * Constructor
	 *
	 * @param string               $plugin_file Main plugin file path.
	 * @param array<string, mixed> $config The plugin configuration.
	 */
	private function __construct( string $plugin_file, array $config ) {
		$this->plugin_file = $plugin_file;
		$this->setup_config( $config );
		$this->container = new Container();

		// Bind the plugin instance itself into the container.
		$this->container->instance( self::class, $this );

		// Bind the container instance to prevent auto-resolution.
		$this->container->instance( Container::class, $this->container );

		$this->bind_services();
		// DON'T boot providers here - wait for run()
	}

	/**
	 * Boots providers and fires all registered hooks.
	 * This is the main entry point for the plugin logic.
	 */
	public function run(): void {
		if ( ! $this->is_compatible() ) {
			return;
		}

		// Boot providers only once, on first run
		if ( ! $this->providers_booted ) {
			$this->load_providers();
			$this->providers_booted = true;
		}

		$this->get( 'hooker' )->run();
	}

	/**
	 * Get a service from the container by $alias.
	 *
	 * @param string $alias The service name to retrieve.
	 *
	 * @return mixed
	 */
	public function get( string $alias ) {
		return $this->container->get( $alias );
	}

	/**
	 * Get plugin configuration.
	 *
	 * @param string|null $key     Optional. Config key to retrieve (case-insensitive).
	 * @param mixed       $default Optional. Default value if key not found.
	 * @return mixed Full config array if no key provided, specific value otherwise.
	 */
	public function get_config( ?string $key = null, $default = null ) {
		if ( null === $key ) {
			return $this->config;
		}

		$key_upper = strtoupper( $key );

		foreach ( $this->config as $config_key => $value ) {
			if ( strtoupper( $config_key ) === $key_upper ) {
				return $value;
			}
		}

		return $default;
	}

	/**
	 * Register the essential services for the plugin.
	 */
	private function bind_services(): void {
		$this->container->singleton( 'hooker', Hooker::class );
		$this->container->alias( 'hooker', Hooker::class );

		$this->container->singleton( 'assets', Assets::class );
		$this->container->singleton( 'i18n', I18n::class );
		$this->container->singleton( 'settings', \CodeSoup\BoxNow\Services\Settings_Service::class );
		$this->container->singleton( 'locker_data', \CodeSoup\BoxNow\Services\Locker_Data_Service::class );
		$this->container->singleton( 'order_service', \CodeSoup\BoxNow\Services\Order_Service::class );
		$this->container->singleton( 'error_handler', \CodeSoup\BoxNow\Services\Error_Handler::class );
	}

	/**
	 * Load and boot all service providers.
	 *
	 * @throws \Exception If provider class doesn't exist.
	 */
	private function load_providers(): void {
		foreach ( $this->providers as $provider_class ) {
			if ( ! class_exists( $provider_class ) ) {
				throw new \Exception(
					sprintf(
						'Service Provider class "%s" not found. Check that the class exists and is autoloaded correctly.',
						$provider_class
					)
				);
			}

			$provider = new $provider_class( $this->container );
			$provider->register();
			$provider->boot();
		}
	}

	/**
	 * Check if the plugin is compatible with the current environment.
	 *
	 * @return bool
	 * @throws \Exception If a compatibility check fails.
	 */
	private function is_compatible(): bool {
		try {
			self::run_requirement_checks( $this->config );

			return true;
		} catch ( \Exception $e ) {
			$this->error_message = $e->getMessage();
			$this->get( 'hooker' )->add_action(
				'admin_notices',
				$this,
				'render_error_notice'
			);

			return false;
		}
	}

	/**
	 * Render error notice.
	 */
	public function render_error_notice(): void {
		printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			esc_html( $this->error_message )
		);
	}

	/**
	 * Setup the plugin configuration.
	 *
	 * @param array<string, mixed> $config The plugin configuration.
	 */
	private function setup_config( array $config ): void {
		$this->config = array_merge(
			$config,
			array(
				'PLUGIN_BASE_PATH' => plugin_dir_path( $this->plugin_file ),
				'PLUGIN_URL'       => plugin_dir_url( $this->plugin_file ),
				'PLUGIN_BASENAME'  => plugin_basename( $this->plugin_file ),
			)
		);
	}
}
