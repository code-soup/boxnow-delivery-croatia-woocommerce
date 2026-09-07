<?php
/**
 * Parcel Link Item Template
 *
 * @package CodeSoup\BoxNow
 * @var string $parcel_id Parcel ID
 * @var int    $order_id  Order ID
 */

defined( 'ABSPATH' ) || exit;
?>

<tr class="codesoup-boxnow-parcel-item">
	<th scope="row">
		<a href="#" data-parcel-id="<?php echo esc_attr( $parcel_id ); ?>" class="codesoup-boxnow-parcel-link">
			<span class="dashicons dashicons-printer"></span>
			<?php echo esc_html( $parcel_id ); ?>
		</a>
	</th>
	<td>
		<button
			class="codesoup-boxnow-cancel-voucher button button-secondary"
			data-order-id="<?php echo esc_attr( $order_id ); ?>"
			data-parcel-id="<?php echo esc_attr( $parcel_id ); ?>"
		>
			&#9664; <?php esc_html_e( 'Cancel Voucher', 'codesoup-woo-boxnow' ); ?>
		</button>
	</td>
</tr>
