<?php
/**
 * Address Book — load dropdown rendered above the billing/shipping form.
 *
 * Available: $addresses (object[]), $type (string 'billing'|'shipping').
 *
 * @package CecomsmaradAddress
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="cecomsmarad-address-book-load" data-type="<?php echo esc_attr( $type ); ?>">
	<p class="form-row form-row-wide cecomsmarad-address-book-load-row">
		<label for="cecomsmarad_load_<?php echo esc_attr( $type ); ?>">
			<?php esc_html_e( 'My saved addresses', 'smarttr-address' ); ?>
		</label>
		<select id="cecomsmarad_load_<?php echo esc_attr( $type ); ?>"
		        class="cecomsmarad-address-book-select"
		        data-type="<?php echo esc_attr( $type ); ?>">
			<option value=""><?php esc_html_e( '— Select address —', 'smarttr-address' ); ?></option>
			<?php foreach ( $addresses as $cecomsmarad_address ) : ?>
				<option value="<?php echo esc_attr( (string) $cecomsmarad_address->id ); ?>">
					<?php
					echo esc_html(
						$cecomsmarad_address->nickname . ' — ' . $cecomsmarad_address->province_name . ' / ' . $cecomsmarad_address->district_name
						. ( $cecomsmarad_address->neighborhood ? ' / ' . $cecomsmarad_address->neighborhood : '' )
					);
					?>
				</option>
			<?php endforeach; ?>
		</select>
	</p>
</div>
