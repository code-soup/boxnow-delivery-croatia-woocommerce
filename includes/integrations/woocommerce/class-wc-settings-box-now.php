<?php
/**
 * WooCommerce Settings Integration
 *
 * @package CodeSoup\BoxNow
 */

namespace CodeSoup\BoxNow\Integrations\WooCommerce;

defined( 'ABSPATH' ) || exit;

/**
 * BoxNow WooCommerce Settings Tab
 */
class WC_Settings_BoxNow extends \WC_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'codesoup_boxnow';
		$this->label = __( 'BoxNow by CodeSoup', 'codesoup-woo-boxnow' );

		parent::__construct();

		add_action( 'woocommerce_admin_field_checkbox_group', array( $this, 'output_checkbox_group' ) );
		add_action( 'woocommerce_update_options_' . $this->id, array( $this, 'save_checkbox_group_fields' ) );
	}

	/**
	 * Get own sections.
	 *
	 * Modern WooCommerce pattern - override protected method instead of public get_sections().
	 * The base class handles caching and filters.
	 *
	 * @return array
	 */
	protected function get_own_sections() {
		return array(
			''       => __( 'API Settings', 'codesoup-woo-boxnow' ),
			'widget' => __( 'Widget Settings', 'codesoup-woo-boxnow' ),
		);
	}

	/**
	 * Get settings for the default section (API Settings).
	 *
	 * Modern WooCommerce pattern - individual methods per section.
	 * The base class get_settings_for_section() method calls these automatically.
	 *
	 * @return array
	 */
	protected function get_settings_for_default_section() {
		return $this->get_api_settings();
	}

	/**
	 * Get settings for the widget section.
	 *
	 * @return array
	 */
	protected function get_settings_for_widget_section() {
		return $this->get_widget_settings();
	}



	/**
	 * Get API settings.
	 *
	 * @return array
	 */
	private function get_api_settings() {
		return array(
			array(
				'title' => __( 'API Configuration', 'codesoup-woo-boxnow' ),
				'type'  => 'title',
				'id'    => 'boxnow_api_settings',
			),
			array(
				'title'       => __( 'API URL', 'codesoup-woo-boxnow' ),
				'id'          => 'boxnow_api_url',
				'type'        => 'text',
				'default'     => 'api.boxnow.hr',
				'desc_tip'    => __( 'BoxNow API endpoint URL. Use api.boxnow.hr for Croatia.', 'codesoup-woo-boxnow' ),
				'description' => __( 'Leave default value unless instructed otherwise by BoxNow.', 'codesoup-woo-boxnow' ),
			),
			array(
				'title'       => __( 'Client ID', 'codesoup-woo-boxnow' ),
				'id'          => 'boxnow_client_id',
				'type'        => 'text',
				'desc_tip'    => __( 'Your BoxNow API client identifier.', 'codesoup-woo-boxnow' ),
				'description' => __( 'Obtain this credential from BoxNow support.', 'codesoup-woo-boxnow' ),
			),
			array(
				'title'       => __( 'Client Secret', 'codesoup-woo-boxnow' ),
				'id'          => 'boxnow_client_secret',
				'type'        => 'password',
				'desc_tip'    => __( 'Your BoxNow API client secret key.', 'codesoup-woo-boxnow' ),
				'description' => __( 'Keep this confidential. Obtain from BoxNow support.', 'codesoup-woo-boxnow' ),
			),
			array(
				'title'       => __( 'Partner ID', 'codesoup-woo-boxnow' ),
				'id'          => 'boxnow_partner_id',
				'type'        => 'text',
				'desc_tip'    => __( 'Your BoxNow partner identifier.', 'codesoup-woo-boxnow' ),
				'description' => __( 'Obtain this from BoxNow support.', 'codesoup-woo-boxnow' ),
			),
			array(
				'title'       => __( 'Warehouse ID', 'codesoup-woo-boxnow' ),
				'id'          => 'boxnow_warehouse_id',
				'type'        => 'text',
				'desc_tip'    => __( 'Comma-separated warehouse location IDs for parcel origin.', 'codesoup-woo-boxnow' ),
				'description' => __( 'Example: WAREHOUSE_1,WAREHOUSE_2', 'codesoup-woo-boxnow' ),
			),
			array(
				'type' => 'sectionend',
				'id'   => 'boxnow_api_settings',
			),
		);
	}

	/**
	 * Output checkbox group field.
	 *
	 * @param array $value Field data.
	 */
	public function output_checkbox_group( $value ) {
		$option_value = get_option( $value['id'], $value['default'] );

		if ( ! is_array( $option_value ) ) {
			$option_value = array();
		}

		$tooltip_html = '';
		if ( ! empty( $value['desc_tip'] ) ) {
			$tooltip_html = wc_help_tip( $value['desc_tip'] );
		}

		?>
		<tr valign="top" class="">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo $tooltip_html; ?></label>
			</th>
			<td class="forminp forminp-checkbox">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo esc_html( $value['title'] ); ?></span></legend>
					<?php if ( ! empty( $value['desc'] ) ) : ?>
						<p class="description" style="margin-bottom: 10px;"><?php echo wp_kses_post( $value['desc'] ); ?></p>
					<?php endif; ?>
					<?php foreach ( $value['options'] as $key => $label ) : ?>
						<label style="display: block; margin: 8px 0;">
							<input
								type="checkbox"
								name="<?php echo esc_attr( $value['id'] ); ?>[]"
								value="<?php echo esc_attr( $key ); ?>"
								<?php checked( in_array( $key, $option_value, true ) ); ?>
							/>
							<?php echo esc_html( $label ); ?>
						</label>
					<?php endforeach; ?>
				</fieldset>
			</td>
		</tr>
		<?php
	}

	/**
	 * Save checkbox group fields.
	 */
	public function save_checkbox_group_fields() {
		$settings = $this->get_settings_for_default_section();

		if ( 'widget' === $this->get_current_section() ) {
			$settings = $this->get_settings_for_widget_section();
		}

		foreach ( $settings as $setting ) {
			if ( isset( $setting['type'] ) && 'checkbox_group' === $setting['type'] && isset( $setting['id'] ) ) {
				$option_key = $setting['id'];
				$value      = isset( $_POST[ $option_key ] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST[ $option_key ] ) ) : array();
				update_option( $option_key, $value );
			}
		}
	}

	/**
	 * Get available payment gateways.
	 *
	 * @return array
	 */
	private function get_available_payment_gateways() {
		$gateways = array();

		if ( ! function_exists( 'WC' ) ) {
			return $gateways;
		}

		$available_gateways = WC()->payment_gateways->payment_gateways();

		foreach ( $available_gateways as $gateway ) {
			if ( isset( $gateway->enabled ) && 'yes' === $gateway->enabled ) {
				$gateways[ $gateway->id ] = $gateway->get_title();
			}
		}

		return $gateways;
	}

	/**
	 * Get widget settings.
	 *
	 * @return array
	 */
	private function get_widget_settings() {
		$settings = array(
			array(
				'title' => __( 'Widget Configuration', 'codesoup-woo-boxnow' ),
				'type'  => 'title',
				'id'    => 'boxnow_widget_settings',
			),
			array(
				'title'       => __( 'Button Color', 'codesoup-woo-boxnow' ),
				'id'          => 'boxnow_button_color',
				'type'        => 'color',
				'default'     => '#6CD04E',
				'css'         => 'width: 6em;',
				'desc_tip'    => __( 'Color of the locker selection button displayed on checkout.', 'codesoup-woo-boxnow' ),
				'description' => __( 'Default: #6CD04E (BoxNow green)', 'codesoup-woo-boxnow' ),
			),
			array(
				'title'       => __( 'Button Text', 'codesoup-woo-boxnow' ),
				'id'          => 'boxnow_button_text',
				'type'        => 'text',
				'default'     => 'Pick a Locker',
				'desc_tip'    => __( 'Text displayed on the locker selection button.', 'codesoup-woo-boxnow' ),
				'description' => __( 'Customize the button label shown to customers.', 'codesoup-woo-boxnow' ),
			),
			array(
				'title'       => __( 'Button Description', 'codesoup-woo-boxnow' ),
				'id'          => 'boxnow_button_description',
				'type'        => 'textarea',
				'default'     => '',
				'desc_tip'    => __( 'Optional description text displayed below the locker selection button.', 'codesoup-woo-boxnow' ),
				'description' => __( 'Leave empty to hide. Supports HTML.', 'codesoup-woo-boxnow' ),
				'css'         => 'min-height: 80px; max-width: 400px;',
			),
			array(
				'title'       => __( 'Button Position', 'codesoup-woo-boxnow' ),
				'id'          => 'boxnow_button_position',
				'type'        => 'select',
				'options'     => array(
					'inline'   => __( 'Inline with shipping method', 'codesoup-woo-boxnow' ),
					'checkbox' => __( 'Below ship to different address checkbox', 'codesoup-woo-boxnow' ),
					'both'     => __( 'Both positions', 'codesoup-woo-boxnow' ),
					'custom'   => __( 'Custom (use shortcode)', 'codesoup-woo-boxnow' ),
				),
				'default'     => 'inline',
				'desc_tip'    => __( 'Choose where to display the locker selection button.', 'codesoup-woo-boxnow' ),
				'description' => __( 'Use shortcode [codesoup_boxnow_pick_locker] for custom placement.', 'codesoup-woo-boxnow' ),
			),
			array(
				'title'       => __( 'Locker Not Selected Text', 'codesoup-woo-boxnow' ),
				'id'          => 'boxnow_locker_not_selected_message',
				'type'        => 'text',
				'default'     => 'Please select a locker first!',
				'desc_tip'    => __( 'Error message shown when customer attempts checkout without selecting a locker.', 'codesoup-woo-boxnow' ),
				'description' => __( 'Validation message displayed to customer.', 'codesoup-woo-boxnow' ),
			),
			array(
				'title'   => __( 'Geolocation', 'codesoup-woo-boxnow' ),
				'desc'    => __( 'Use browser geolocation to auto-center map on customer location', 'codesoup-woo-boxnow' ),
				'id'      => 'boxnow_enable_geolocation',
				'type'    => 'checkbox',
				'default' => 'yes',
			),
			array(
				'title'       => __( 'Locker Select Map', 'codesoup-woo-boxnow' ),
				'id'          => 'boxnow_display_mode',
				'type'        => 'select',
				'options'     => array(
					'popup'    => __( 'Popup', 'codesoup-woo-boxnow' ),
					'embedded' => __( 'Embedded', 'codesoup-woo-boxnow' ),
					'custom'   => __( 'Custom (Shortcode)', 'codesoup-woo-boxnow' ),
				),
				'default'     => 'popup',
				'desc_tip'    => __( 'How the locker selection map appears.', 'codesoup-woo-boxnow' ),
				'description' => __( 'Popup: Modal overlay. Embedded: Inline on page. Custom: Manual placement via shortcode [codesoup_boxnow_pick_locker].', 'codesoup-woo-boxnow' ),
			),
			array(
				'title'   => __( 'Enable Pay with Card at Locker', 'codesoup-woo-boxnow' ),
				'desc'    => __( 'Allow paying at the locker', 'codesoup-woo-boxnow' ),
				'id'      => 'boxnow_enable_pay_at_locker',
				'type'    => 'checkbox',
				'default' => 'no',
			),
			array(
				'title'       => __( 'Payment Method Title', 'codesoup-woo-boxnow' ),
				'desc'        => __( 'The title shown to customers for card payment at locker', 'codesoup-woo-boxnow' ),
				'id'          => 'boxnow_pay_at_locker_title',
				'type'        => 'text',
				'default'     => __( 'Pay with Card at BoxNow', 'codesoup-woo-boxnow' ),
				'desc_tip'    => true,
			),
			array(
				'title'    => __( 'Allowed Payment Methods', 'codesoup-woo-boxnow' ),
				'desc_tip' => __( 'Select which payment methods are available when BoxNow shipping is selected.', 'codesoup-woo-boxnow' ),
				'id'       => 'boxnow_allowed_payment_methods',
				'type'     => 'checkbox_group',
				'options'  => $this->get_available_payment_gateways(),
				'default'  => array_keys( $this->get_available_payment_gateways() ),
			),
			array(
				'type' => 'sectionend',
				'id'   => 'boxnow_widget_settings',
			),
		);

		$settings[] = array(
			'type' => 'sectionend',
			'id'   => 'boxnow_widget_settings',
		);

		return $settings;
	}


}
