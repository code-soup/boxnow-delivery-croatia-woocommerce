<?php
/**
 * Shortcode Handler
 *
 * @package CodeSoup\BoxNow
 */

namespace CodeSoup\BoxNow\Services\Shortcodes;

use CodeSoup\BoxNow\Core\Hooker;

defined( 'ABSPATH' ) || exit;

/**
 * Handles plugin shortcodes.
 */
class Shortcode_Handler {

	/**
	 * Hooker instance.
	 *
	 * @var Hooker
	 */
	private Hooker $hooker;

	/**
	 * Constructor.
	 *
	 * @param Hooker $hooker Hooker instance.
	 */
	public function __construct( Hooker $hooker ) {
		$this->hooker = $hooker;
	}

	/**
	 * Initialize hooks.
	 */
	public function init(): void {
		add_shortcode( 'codesoup_boxnow_pick_locker', array( $this, 'render_locker_button' ) );
		add_shortcode( 'codesoup_boxnow_embedded_map', array( $this, 'render_embedded_map' ) );
	}

	/**
	 * Render locker selection button shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_locker_button( $atts ) {
		$atts = shortcode_atts(
			array(
				'text'              => get_option( 'boxnow_button_text', __( 'Pick a Locker', 'codesoup-woo-boxnow' ) ),
				'show_method_name'  => 'yes',
				'auto_select'       => 'yes',
			),
			$atts,
			'codesoup_boxnow_pick_locker'
		);

		$button_text          = esc_html( $atts['text'] );
		$show_method_name     = 'yes' === $atts['show_method_name'];
		$auto_select          = 'yes' === $atts['auto_select'];
		$shipping_method_name = __( 'BoxNow Delivery by CodeSoup', 'codesoup-woo-boxnow' );
		$button_color         = get_option( 'boxnow_button_color', '#6CD04E' );

		if ( $show_method_name ) {
			$button_text .= ' - ' . $shipping_method_name;
		}

		$css_class = 'button box-now-delivery-button';
		if ( $auto_select ) {
			$css_class .= ' box-now-delivery-button-auto-select';
		}

		ob_start();
		?>
		<span class="codesoup-boxnow-button-wrapper">
			<button
				type="button"
				class="<?php echo esc_attr( $css_class ); ?>"
				style="background-color: <?php echo esc_attr( $button_color ); ?> !important; color: #fff !important;"
			>
				<?php echo esc_html( $button_text ); ?>
			</button>
		</span>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render embedded map shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_embedded_map( $atts ) {
		$atts = shortcode_atts(
			array(
				'height' => '500px',
				'width'  => '100%',
			),
			$atts,
			'codesoup_boxnow_embedded_map'
		);

		$height = esc_attr( $atts['height'] );
		$width  = esc_attr( $atts['width'] );

		ob_start();
		?>
		<div
			class="box-now-delivery-embedded-map-container"
			style="width: <?php echo $width; ?>; height: <?php echo $height; ?>;"
		></div>
		<?php
		return ob_get_clean();
	}
}
