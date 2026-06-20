<?php
/**
 * My Account — Address Book tab.
 *
 * Available: $addresses (object[]), $can_save (bool), $count (int), $upgrade_url (string).
 *
 * @package CecomsmaradAddress
 */

defined( 'ABSPATH' ) || exit;
?>
<h2><?php esc_html_e( 'Address Book', 'smarttr-address' ); ?></h2>

<?php if ( empty( $addresses ) ) : ?>
	<div class="woocommerce-info">
		<?php esc_html_e( 'You have no saved addresses yet. Fill in an address on the checkout page and use the "Save this address" option.', 'smarttr-address' ); ?>
	</div>
<?php else : ?>
	<div class="woocommerce-addresses">
		<?php foreach ( $addresses as $cecomsmarad_address ) : ?>
			<div class="woocommerce-Address cecomsmarad-address-book-card"
			     data-id="<?php echo esc_attr( (string) $cecomsmarad_address->id ); ?>"
			     data-address-type="<?php echo esc_attr( $cecomsmarad_address->address_type ); ?>">

				<div class="woocommerce-Address-title title">
					<h3 class="woocommerce-column__title cecomsmarad-address-book-nickname">
						<?php echo esc_html( $cecomsmarad_address->nickname ); ?>
					</h3>
					<p>
						<button type="button" class="button cecomsmarad-address-book-edit-btn"
						        data-edit-label="<?php esc_attr_e( 'Edit', 'smarttr-address' ); ?>"
						        data-cancel-label="<?php esc_attr_e( 'Cancel', 'smarttr-address' ); ?>">
							<?php esc_html_e( 'Edit', 'smarttr-address' ); ?>
						</button>
						<button type="button" class="button cecomsmarad-address-book-delete-btn">
							<?php esc_html_e( 'Delete', 'smarttr-address' ); ?>
						</button>
					</p>
				</div>

				<address class="cecomsmarad-address-book-card-body">
					<?php if ( $cecomsmarad_address->first_name || $cecomsmarad_address->last_name ) : ?>
						<?php echo esc_html( trim( $cecomsmarad_address->first_name . ' ' . $cecomsmarad_address->last_name ) ); ?><br>
					<?php endif; ?>
					<?php if ( $cecomsmarad_address->company ) : ?>
						<?php echo esc_html( $cecomsmarad_address->company ); ?><br>
					<?php endif; ?>
					<?php echo esc_html( $cecomsmarad_address->province_name ? $cecomsmarad_address->province_name . ' › ' . $cecomsmarad_address->district_name : $cecomsmarad_address->district_name ); ?>
					<?php if ( $cecomsmarad_address->neighborhood ) : ?>
						<?php echo esc_html( ' › ' . $cecomsmarad_address->neighborhood ); ?>
					<?php endif; ?>
					<br>
					<?php if ( $cecomsmarad_address->address_1 ) : ?>
						<?php echo esc_html( $cecomsmarad_address->address_1 ); ?><?php echo $cecomsmarad_address->postcode ? esc_html( ' — ' . $cecomsmarad_address->postcode ) : ''; ?><br>
					<?php endif; ?>
					<?php if ( ! empty( $cecomsmarad_address->country ) ) : ?>
						<?php echo esc_html( WC()->countries->countries[ $cecomsmarad_address->country ] ?? $cecomsmarad_address->country ); ?><br>
					<?php endif; ?>
				</address>

				<!-- Edit Form -->
				<div class="cecomsmarad-address-book-edit-form woocommerce-address-fields" style="display:none;"
				     data-country="<?php echo esc_attr( $cecomsmarad_address->country ?? '' ); ?>">
					<p class="form-row form-row-wide" data-priority="5">
						<label><?php esc_html_e( 'Address name', 'smarttr-address' ); ?> <abbr class="required" title="<?php esc_attr_e( 'required', 'smarttr-address' ); ?>">*</abbr></label>
						<input type="text" class="input-text cecomsmarad-edit-nickname"
						       value="<?php echo esc_attr( $cecomsmarad_address->nickname ); ?>"
						       maxlength="50" />
					</p>
					<p class="form-row form-row-first" data-priority="10">
						<label><?php esc_html_e( 'First name', 'smarttr-address' ); ?></label>
						<input type="text" class="input-text cecomsmarad-edit-first-name"
						       value="<?php echo esc_attr( $cecomsmarad_address->first_name ); ?>" />
					</p>
					<p class="form-row form-row-last" data-priority="20">
						<label><?php esc_html_e( 'Last name', 'smarttr-address' ); ?></label>
						<input type="text" class="input-text cecomsmarad-edit-last-name"
						       value="<?php echo esc_attr( $cecomsmarad_address->last_name ); ?>" />
					</p>
					<p class="form-row form-row-wide" data-priority="30">
						<label><?php esc_html_e( 'Company', 'smarttr-address' ); ?></label>
						<input type="text" class="input-text cecomsmarad-edit-company"
						       value="<?php echo esc_attr( $cecomsmarad_address->company ); ?>" />
					</p>
					<p class="form-row form-row-wide" data-priority="40">
						<label><?php esc_html_e( 'Country', 'smarttr-address' ); ?></label>
						<select class="cecomsmarad-edit-country">
							<option value=""><?php esc_html_e( '— Select country —', 'smarttr-address' ); ?></option>
							<?php foreach ( WC()->countries->get_countries() as $cecomsmarad_code => $cecomsmarad_name ) : ?>
							<option value="<?php echo esc_attr( $cecomsmarad_code ); ?>"<?php selected( $cecomsmarad_address->country ?? '', $cecomsmarad_code ); ?>>
								<?php echo esc_html( $cecomsmarad_name ); ?>
							</option>
							<?php endforeach; ?>
						</select>
					</p>
					<p class="form-row form-row-wide cecomsmarad-locale-field" data-field="address_1" data-priority="50">
						<label class="cecomsmarad-field-label"><span class="cecomsmarad-label-text"><?php esc_html_e( 'Address', 'smarttr-address' ); ?></span></label>
						<input type="text" class="input-text cecomsmarad-edit-address1"
						       value="<?php echo esc_attr( $cecomsmarad_address->address_1 ); ?>" />
					</p>
					<?php
					$cecomsmarad_country    = $cecomsmarad_address->country ?? '';
					$cecomsmarad_is_foreign = ( '' !== $cecomsmarad_country && 'TR' !== $cecomsmarad_country );
					?>
					<p class="form-row form-row-wide cecomsmarad-generic-only cecomsmarad-locale-field" data-field="address_2" data-priority="60" style="display:none;">
						<label class="cecomsmarad-field-label"><span class="cecomsmarad-label-text"><?php esc_html_e( 'Address line 2', 'smarttr-address' ); ?></span></label>
						<input type="text" class="input-text cecomsmarad-edit-address2"
						       value="<?php echo esc_attr( $cecomsmarad_is_foreign ? $cecomsmarad_address->neighborhood : '' ); ?>" />
					</p>
					<p class="form-row form-row-wide cecomsmarad-generic-only cecomsmarad-locale-field" data-field="city" data-priority="70" style="display:none;">
						<label class="cecomsmarad-field-label"><span class="cecomsmarad-label-text"><?php esc_html_e( 'Town / City', 'smarttr-address' ); ?></span> <abbr class="required" title="<?php esc_attr_e( 'required', 'smarttr-address' ); ?>">*</abbr></label>
						<input type="text" class="input-text cecomsmarad-edit-city"
						       value="<?php echo esc_attr( $cecomsmarad_is_foreign ? $cecomsmarad_address->district_name : '' ); ?>" />
					</p>
					<p class="form-row form-row-wide cecomsmarad-generic-only cecomsmarad-locale-field cecomsmarad-edit-state-row" data-field="state" data-priority="80"
					   data-current-state="<?php echo esc_attr( $cecomsmarad_is_foreign ? $cecomsmarad_address->province_code : '' ); ?>" style="display:none;">
						<label class="cecomsmarad-field-label"><span class="cecomsmarad-label-text"><?php esc_html_e( 'State / County', 'smarttr-address' ); ?></span></label>
						<span class="cecomsmarad-edit-state-control"></span>
					</p>
					<p class="form-row form-row-wide cecomsmarad-tr-only" data-priority="80">
						<label><?php esc_html_e( 'Province', 'smarttr-address' ); ?> <abbr class="required" title="<?php esc_attr_e( 'required', 'smarttr-address' ); ?>">*</abbr></label>
						<select class="cecomsmarad-edit-province"
						        data-current-province="<?php echo esc_attr( $cecomsmarad_address->province_code ); ?>">
							<option value=""><?php esc_html_e( '— Select province —', 'smarttr-address' ); ?></option>
						</select>
					</p>
					<p class="form-row form-row-wide cecomsmarad-tr-only" data-priority="81">
						<label><?php esc_html_e( 'District', 'smarttr-address' ); ?> <abbr class="required" title="<?php esc_attr_e( 'required', 'smarttr-address' ); ?>">*</abbr></label>
						<select class="cecomsmarad-edit-district"
						        data-current-district="<?php echo esc_attr( $cecomsmarad_address->district_name ); ?>">
							<option value=""><?php esc_html_e( '— Select district —', 'smarttr-address' ); ?></option>
						</select>
					</p>
					<p class="form-row form-row-wide cecomsmarad-tr-only" data-priority="82">
						<label><?php esc_html_e( 'Neighborhood', 'smarttr-address' ); ?></label>
						<input type="text" class="input-text cecomsmarad-edit-neighborhood"
						       value="<?php echo esc_attr( $cecomsmarad_address->neighborhood ); ?>" />
					</p>
					<p class="form-row form-row-wide cecomsmarad-locale-field" data-field="postcode" data-priority="90">
						<label class="cecomsmarad-field-label"><span class="cecomsmarad-label-text"><?php esc_html_e( 'Postcode', 'smarttr-address' ); ?></span></label>
						<input type="text" class="input-text cecomsmarad-edit-postcode"
						       value="<?php echo esc_attr( $cecomsmarad_address->postcode ); ?>" />
					</p>
					<?php if ( 'billing' === $cecomsmarad_address->address_type ) : ?>
					<p class="form-row form-row-first" data-priority="100">
						<label><?php esc_html_e( 'Phone', 'smarttr-address' ); ?></label>
						<input type="text" class="input-text cecomsmarad-edit-phone"
						       value="<?php echo esc_attr( $cecomsmarad_address->phone ); ?>" />
					</p>
					<p class="form-row form-row-last" data-priority="110">
						<label><?php esc_html_e( 'Email', 'smarttr-address' ); ?></label>
						<input type="email" class="input-text cecomsmarad-edit-email"
						       value="<?php echo esc_attr( $cecomsmarad_address->email ); ?>" />
					</p>
					<?php endif; ?>
					<p class="form-row form-row-wide" data-priority="200">
						<button type="button" class="button cecomsmarad-address-book-edit-confirm">
							<?php esc_html_e( 'Save changes', 'smarttr-address' ); ?>
						</button>
					</p>
				</div>

				<!-- Delete Confirm -->
				<div class="cecomsmarad-address-book-delete-confirm" style="display:none;">
					<p>
						<?php esc_html_e( 'Are you sure?', 'smarttr-address' ); ?>
						<button type="button" class="button cecomsmarad-address-book-delete-confirm-yes">
							<?php esc_html_e( 'Yes', 'smarttr-address' ); ?>
						</button>
						<button type="button" class="cecomsmarad-address-book-delete-confirm-no">
							<?php esc_html_e( 'No', 'smarttr-address' ); ?>
						</button>
					</p>
				</div>

			</div>
		<?php endforeach; ?>
	</div>
<?php endif; ?>

<p class="woocommerce-info cecomsmarad-address-book-hint">
	<?php esc_html_e( 'You can use the checkout page to add new addresses.', 'smarttr-address' ); ?>
</p>

<?php if ( ! $can_save ) : ?>
	<p class="woocommerce-info cecomsmarad-address-book-cap-notice">
		<?php
		printf(
			/* translators: 1: count of saved addresses, 2: cap number */
			esc_html__( '%1$d/%2$d addresses used.', 'smarttr-address' ),
			(int) $count,
			(int) $cap
		);
		?>
		<a href="<?php echo esc_url( $upgrade_url ); ?>" target="_blank" rel="noopener noreferrer">
			<?php esc_html_e( 'Upgrade to Premium →', 'smarttr-address' ); ?>
		</a>
	</p>
<?php endif; ?>
