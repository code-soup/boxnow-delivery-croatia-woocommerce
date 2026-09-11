<?php
/**
 * Locker Details Template
 *
 * @package CodeSoup\BoxNow
 * @var string $locker_id       Locker ID
 * @var string $locker_name     Locker name
 * @var string $locker_address  Locker address
 * @var string $locker_city     Locker city
 * @var string $locker_postcode Locker postcode
 * @var string $locker_country  Locker country
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="boxnow-locker-details">
	<table class="form-table">
		<thead>
			<tr>
				<th colspan="2"><?php esc_html_e( 'Locker Information', 'codesoup-woo-boxnow' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<th scope="row"><?php esc_html_e( 'Locker ID', 'codesoup-woo-boxnow' ); ?></th>
				<td><?php echo esc_html( $locker_id ); ?></td>
			</tr>

			<?php if ( $locker_name ) : ?>
			<tr>
				<th scope="row"><?php esc_html_e( 'Locker Name', 'codesoup-woo-boxnow' ); ?></th>
				<td><?php echo esc_html( $locker_name ); ?></td>
			</tr>
			<?php endif; ?>

			<?php if ( $locker_address ) : ?>
			<tr>
				<th scope="row"><?php esc_html_e( 'Address', 'codesoup-woo-boxnow' ); ?></th>
				<td><?php echo esc_html( $locker_address ); ?></td>
			</tr>
			<?php endif; ?>

			<?php if ( $locker_city || $locker_postcode ) : ?>
			<tr>
				<th scope="row"><?php esc_html_e( 'City', 'codesoup-woo-boxnow' ); ?></th>
				<td><?php echo esc_html( trim( sprintf( '%s %s', $locker_postcode, $locker_city ) ) ); ?></td>
			</tr>
			<?php endif; ?>

			<?php if ( $locker_country ) : ?>
			<tr>
				<th scope="row"><?php esc_html_e( 'Country', 'codesoup-woo-boxnow' ); ?></th>
				<td><?php echo esc_html( $locker_country ); ?></td>
			</tr>
			<?php endif; ?>
		</tbody>
	</table>
</div>
