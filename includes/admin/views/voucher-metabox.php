<?php
/**
 * Voucher Metabox Template
 *
 * @package CodeSoup\BoxNow
 * @var \WC_Order $order              Order object
 * @var int       $max_vouchers       Maximum number of vouchers
 * @var array     $parcel_ids         Existing parcel IDs
 * @var bool      $is_disabled        Whether buttons are disabled
 * @var string    $parcel_items_html  Pre-rendered HTML for parcel items
 */

use CodeSoup\BoxNow\Constants\Form_Fields;
use function CodeSoup\BoxNow\plugin;

defined( 'ABSPATH' ) || exit;

$compartment_sizes = array(
	1 => array(
		'label' => __( 'Small (S)', 'codesoup-woo-boxnow' ),
		'info'  => __( 'HxWxL 8x45x60 cm (< 2kg)', 'codesoup-woo-boxnow' ),
	),
	2 => array(
		'label' => __( 'Medium (M)', 'codesoup-woo-boxnow' ),
		'info'  => __( 'HxWxL 17x45x60 cm (< 5kg)', 'codesoup-woo-boxnow' ),
	),
	3 => array(
		'label' => __( 'Large (L)', 'codesoup-woo-boxnow' ),
		'info'  => __( 'HxWxL 36x45x60 cm (< 20kg)', 'codesoup-woo-boxnow' ),
	),
);
?>

<div class="<?php echo esc_attr( Form_Fields::VOUCHER_CONTAINER_CLASS ); ?> codesoup-boxnow-voucher-grid">
	<div class="codesoup-boxnow-voucher-left">
		<?php
		// Include locker details
		$template_path = plugin()->get_config( 'PLUGIN_BASE_PATH' ) . 'includes/admin/views/locker-details.php';
		include $template_path;
		?>

		<h3>
			<?php esc_html_e( 'Create Voucher', 'codesoup-woo-boxnow' ); ?>
		</h3>

		<small class="codesoup-boxnow-voucher-limit">
			<?php
			echo esc_html(
				sprintf(
					/* translators: %d: maximum number of vouchers */
					__( 'Maximum %d based on order items', 'codesoup-woo-boxnow' ),
					$max_vouchers
				)
			);
			?>
		</small>

	<?php
	printf(
		'<input type="hidden" id="%s" value="%s" />',
		esc_attr( Form_Fields::VOUCHER_ORDER_ID ),
		esc_attr( $order->get_id() )
	);

	printf(
		'<input type="hidden" id="%s" value="%s" />',
		esc_attr( Form_Fields::VOUCHER_PARCEL_IDS ),
		esc_attr( wp_json_encode( $parcel_ids ) )
	);

	printf(
		'<input type="hidden" id="%s" value="true" />',
		esc_attr( Form_Fields::VOUCHER_CREATE_ENABLED )
	);

	printf(
		'<input type="hidden" id="%s" value="%s" />',
		esc_attr( Form_Fields::VOUCHER_MAX_VOUCHERS ),
		esc_attr( $max_vouchers )
	);

	printf(
		'<input type="hidden" id="%s" value="%s" />',
		esc_attr( Form_Fields::VOUCHER_CURRENT_COUNT ),
		esc_attr( count( $parcel_ids ) )
	);
	?>

	<table class="form-table">
		<tbody>
			<tr>
				<th scope="row">
					<label for="<?php echo esc_attr( Form_Fields::VOUCHER_QUANTITY_INPUT ); ?>">
						<?php esc_html_e( 'Number of Vouchers', 'codesoup-woo-boxnow' ); ?>
					</label>
				</th>
				<td>
					<?php
					printf(
						'<input type="number" id="%s" name="%s" min="1" max="%s" value="1" class="%s" />',
						esc_attr( Form_Fields::VOUCHER_QUANTITY_INPUT ),
						esc_attr( Form_Fields::VOUCHER_QUANTITY_INPUT ),
						esc_attr( $max_vouchers ),
						esc_attr( Form_Fields::VOUCHER_QUANTITY_CLASS )
					);
					?>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Compartment Size', 'codesoup-woo-boxnow' ); ?></th>
				<td>
					<div class="<?php echo esc_attr( Form_Fields::VOUCHER_COMPARTMENT_CHECKBOXES ); ?>">
						<?php foreach ( $compartment_sizes as $size => $data ) : ?>
							<label>
								<span class="label">
								<input
									type="radio"
									name="<?php echo esc_attr( Form_Fields::VOUCHER_COMPARTMENT_SIZE ); ?>"
									value="<?php echo esc_attr( $size ); ?>"
									<?php checked( $size, 1 ); ?>
									<?php disabled( $is_disabled, true ); ?>
								>
								<?php echo esc_html( $data['label'] ); ?>
								</span>
								<small class="codesoup-boxnow-size-info">
									<?php echo esc_html( $data['info'] ); ?>
								</small>
							</label>
						<?php endforeach; ?>
					</div>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="<?php echo esc_attr( Form_Fields::VOUCHER_SHOW_RECIPIENT_INFO ); ?>">
						<?php esc_html_e( 'Label info', 'codesoup-woo-boxnow' ); ?>
					</label>
				</th>
				<td>
					<label>
						<input
							type="checkbox"
							id="<?php echo esc_attr( Form_Fields::VOUCHER_SHOW_RECIPIENT_INFO ); ?>"
							name="<?php echo esc_attr( Form_Fields::VOUCHER_SHOW_RECIPIENT_INFO ); ?>"
							value="1"
							<?php checked( true ); ?>
							<?php disabled( $is_disabled, true ); ?>
						>
						<?php esc_html_e( 'Print recipient phone and email on the shipping label.' ); ?>
					</label>
				</td>
			</tr>

			<tr>
				<th scope="row">&nbsp;</th>
				<td colspan="2">
					<?php
					printf(
						'<button type="button" id="%s" class="button button-primary" %s>%s</button>',
						esc_attr( Form_Fields::VOUCHER_CREATE_BUTTON ),
						disabled( $is_disabled, true, false ),
						esc_html__( 'Create Voucher(s)', 'codesoup-woo-boxnow' )
					);
					?>
				</td>
			</tr>
		</tbody>
	</table>
	</div>

	<div id="codesoup-boxnow-vouchers-column" class="codesoup-boxnow-voucher-right">
		<div class="codesoup-boxnow-voucher-right-header">
			<h3><?php esc_html_e( 'Created Vouchers', 'codesoup-woo-boxnow' ); ?></h3>
			<?php
			printf(
				'<button type="button" id="%s" class="button %s" data-order-id="%s"%s>%s</button>',
				esc_attr( Form_Fields::VOUCHER_CANCEL_ALL_BUTTON ),
				esc_attr( Form_Fields::VOUCHER_CANCEL_ALL_CLASS ),
				esc_attr( $order->get_id() ),
				empty( $parcel_ids ) ? ' style="display:none;"' : '',
				esc_html__( 'Cancel All Vouchers', 'codesoup-woo-boxnow' )
			);
			?>
		</div>
		<?php if ( empty( $parcel_items_html ) ) : ?>
			<p class="codesoup-boxnow-no-vouchers"><?php esc_html_e( 'No vouchers created yet.', 'codesoup-woo-boxnow' ); ?></p>
		<?php else : ?>
			<table class="form-table">
				<tbody id="<?php echo esc_attr( Form_Fields::VOUCHER_LINK_CONTAINER ); ?>" class="<?php echo esc_attr( Form_Fields::VOUCHER_LINK_CLASS ); ?>">
					<?php echo $parcel_items_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>
</div>
