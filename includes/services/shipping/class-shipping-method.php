<?php
/**
 * Box Now Shipping Method
 *
 * @package CodeSoup\BoxNow
 */

namespace CodeSoup\BoxNow\Services\Shipping;

use CodeSoup\BoxNow\Constants\Shipping_Method_Ids;
use WC_Shipping_Method;
use WC_Tax;

defined( 'ABSPATH' ) || exit;

/**
 * Box Now Delivery shipping method.
 */
class Shipping_Method extends WC_Shipping_Method {

	/**
	 * Shipping cost.
	 *
	 * @var string
	 */
	public $cost;

	/**
	 * Free delivery threshold.
	 *
	 * @var string
	 */
	public $free_delivery_threshold;

	/**
	 * Whether shipping is taxable.
	 *
	 * @var string
	 */
	public $taxable;

	/**
	 * Constructor.
	 *
	 * @param int $instance_id Instance ID.
	 */
	public function __construct( $instance_id = 0 ) {
		$this->id                 = Shipping_Method_Ids::CURRENT;
		$this->instance_id        = absint( $instance_id );
		$this->method_title       = __( 'BOX NOW Delivery Croatia by CodeSoup', 'codesoup-woo-boxnow' );
		$this->method_description = __( 'BOX NOW Delivery Croatia by CodeSoup', 'codesoup-woo-boxnow' );

		$this->supports = array(
			'shipping-zones',
			'instance-settings',
			'instance-settings-modal',
		);

		$this->init_form_fields();
		$this->init_settings();

		$this->title                    = $this->get_option( 'title' );
		$this->cost                     = $this->get_option( 'cost' );
		$this->free_delivery_threshold  = $this->get_option( 'free_delivery_threshold' );
		$this->taxable                  = $this->get_option( 'taxable' );

		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
	 * Initialize form fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'                      => array(
				'title'   => __( 'Enable/Disable', 'codesoup-woo-boxnow' ),
				'type'    => 'checkbox',
				'default' => 'yes',
			),
			'title'                        => array(
				'title'       => __( 'Method Title', 'codesoup-woo-boxnow' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'codesoup-woo-boxnow' ),
				'default'     => __( 'Box Now Delivery', 'codesoup-woo-boxnow' ),
				'desc_tip'    => true,
			),
			'cost'                         => array(
				'title'       => __( 'Cost', 'codesoup-woo-boxnow' ),
				'type'        => 'text',
				'description' => __( 'Enter the cost for this shipping method', 'codesoup-woo-boxnow' ),
				'default'     => 0,
				'desc_tip'    => true,
			),
			'taxable'                      => array(
				'title'   => __( 'Taxable', 'codesoup-woo-boxnow' ),
				'type'    => 'checkbox',
				'default' => 'no',
			),
			'free_delivery_threshold'      => array(
				'title'       => __( 'Free Delivery Threshold', 'codesoup-woo-boxnow' ),
				'type'        => 'text',
				'description' => __( 'Order total threshold for free delivery', 'codesoup-woo-boxnow' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'custom_weight'                => array(
				'title'             => __( 'Max Weight', 'codesoup-woo-boxnow' ),
				'type'              => 'number',
				'description'       => __( 'Maximum weight allowed (kg or g)', 'codesoup-woo-boxnow' ),
				'default'           => 20,
				'desc_tip'          => true,
				'custom_attributes' => array(
					'step' => '0.1',
					'min'  => '0',
				),
			),
			'dimensions'                   => array(
				'title'       => __( 'Max Package Dimensions', 'codesoup-woo-boxnow' ),
				'type'        => 'title',
				'description' => __( 'Maximum package size allowed', 'codesoup-woo-boxnow' ),
			),
			'max_length'                   => array(
				'title'   => __( 'Max Length (cm)', 'codesoup-woo-boxnow' ),
				'type'    => 'number',
				'default' => 60,
			),
			'max_width'                    => array(
				'title'   => __( 'Max Width (cm)', 'codesoup-woo-boxnow' ),
				'type'    => 'number',
				'default' => 40,
			),
			'max_height'                   => array(
				'title'   => __( 'Max Height (cm)', 'codesoup-woo-boxnow' ),
				'type'    => 'number',
				'default' => 36,
			),
			'cod_description'              => array(
				'title'       => __( 'Cash on delivery custom description', 'codesoup-woo-boxnow' ),
				'type'        => 'title',
				'description' => __( 'Enable custom Cash on delivery description', 'codesoup-woo-boxnow' ),
			),
			'enable_custom_cod_description' => array(
				'title'   => __( 'Enable Custom COD Description', 'codesoup-woo-boxnow' ),
				'type'    => 'checkbox',
				'default' => 'no',
			),
			'custom_cod_description'       => array(
				'title'   => __( 'Custom COD Description', 'codesoup-woo-boxnow' ),
				'type'    => 'textarea',
				'default' => '',
			),
		);
	}

	/**
	 * Calculate shipping cost.
	 *
	 * @param array $package Shipping package.
	 */
	public function calculate_shipping( $package = array() ) {
		if ( $this->has_oversized_products() ) {
			return;
		}

		$taxable      = 'yes' === $this->taxable;
		$order_total  = WC()->cart->get_cart_contents_total();

		// Apply free delivery threshold
		if ( ! empty( $this->free_delivery_threshold ) && $order_total >= $this->free_delivery_threshold ) {
			$this->cost = 0;
		}

		$rate = array(
			'id'       => $this->id,
			'label'    => $this->title,
			'cost'     => $this->cost,
			'taxes'    => $taxable ? WC_Tax::calc_shipping_tax( $this->cost, WC_Tax::get_shipping_tax_rates() ) : '',
			'calc_tax' => 'per_item',
		);

		$this->add_rate( $rate );
	}

	/**
	 * Check if cart has oversized products.
	 *
	 * @return bool
	 */
	private function has_oversized_products() {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return false;
		}

		$custom_weight_limit = (float) $this->get_option( 'custom_weight', 20 );
		$max_length          = (float) $this->get_option( 'max_length', 60 );
		$max_width           = (float) $this->get_option( 'max_width', 40 );
		$max_height          = (float) $this->get_option( 'max_height', 36 );

		foreach ( WC()->cart->get_cart_contents() as $cart_item ) {
			$product = $cart_item['data'];
			$length  = (float) $product->get_length();
			$width   = (float) $product->get_width();
			$height  = (float) $product->get_height();
			$weight  = (float) $product->get_weight();

			if ( $length > $max_length || $width > $max_width || $height > $max_height || $weight > $custom_weight_limit ) {
				return true;
			}
		}

		return false;
	}
}
