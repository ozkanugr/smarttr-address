<?php
/**
 * Address Book — save checkbox rendered below the billing/shipping form.
 *
 * Available: $type (string), $can_save (bool), $upgrade_url (string).
 *
 * @package CecomsmaradAddress
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="cecomsmarad-address-book-save" data-type="<?php echo esc_attr( $type ); ?>">
	<?php if ( $can_save ) : ?>
		<p class="form-row form-row-wide cecomsmarad-address-book-save-row">
			<label class="cecomsmarad-address-book-save-label woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
				<input type="checkbox"
				       name="cecomsmarad_save_<?php echo esc_attr( $type ); ?>_address"
				       value="1"
				       class="cecomsmarad-address-book-save-checkbox woocommerce-form__input woocommerce-form__input-checkbox input-checkbox"
				       data-type="<?php echo esc_attr( $type ); ?>" />
				<span><?php esc_html_e( 'Save this address', 'smarttr-address' ); ?></span>
			</label>
			<span class="cecomsmarad-address-book-nickname-wrap" style="display:none;">
				<input type="text"
				       name="cecomsmarad_<?php echo esc_attr( $type ); ?>_nickname"
				       class="input-text cecomsmarad-address-book-nickname-input"
				       maxlength="50"
				       placeholder="<?php echo 'billing' === $type ? esc_attr__( 'Home', 'smarttr-address' ) : esc_attr__( 'Work', 'smarttr-address' ); ?>" />
			</span>
		</p>
	<?php else : ?>
		<p class="cecomsmarad-address-book-cap-notice">
			⚠ <?php esc_html_e( 'You have reached the address limit.', 'smarttr-address' ); ?>
			<a href="<?php echo esc_url( $upgrade_url ); ?>" target="_blank" rel="noopener noreferrer">
				<?php esc_html_e( 'Upgrade to Premium →', 'smarttr-address' ); ?>
			</a>
		</p>
	<?php endif; ?>
</div>
