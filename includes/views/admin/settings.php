<?php
/**
 * Admin settings page template.
 *
 * Variables from controller: $current_tab, $fields.
 *
 * @package CecomsmaradAddress
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template file included from a class method; variables are in function scope, not true global scope.

$page_url = admin_url( 'admin.php?page=cecomsmarad-settings' );

$cecomsmarad_tabs = array(
	'general' => array(
		'label' => __( 'General', 'smarttr-address' ),
		'icon'  => '<i class="bi bi-gear-fill" aria-hidden="true"></i>',
	),
	'data'    => array(
		'label' => __( 'Data Management', 'smarttr-address' ),
		'icon'  => '<i class="bi bi-database-fill-check" aria-hidden="true"></i>',
	),
	'fields'  => array(
		'label'  => __( 'Field Editor', 'smarttr-address' ),
		'icon'   => '<i class="bi bi-sliders" aria-hidden="true"></i>',
		'locked' => true,
	),
	'upgrade' => array(
		'label'  => __( 'Upgrade', 'smarttr-address' ),
		'icon'   => '<i class="bi bi-stars" aria-hidden="true"></i>',
		'locked' => true,
	),
);

$field_labels = array(
	'billing_first_name'  => __( 'Billing — First Name', 'smarttr-address' ),
	'billing_last_name'   => __( 'Billing — Last Name', 'smarttr-address' ),
	'billing_company'     => __( 'Billing — Company', 'smarttr-address' ),
	'billing_country'     => __( 'Billing — Country', 'smarttr-address' ),
	'billing_address_1'   => __( 'Billing — Address 1', 'smarttr-address' ),
	'billing_address_2'   => __( 'Billing — Neighborhood', 'smarttr-address' ),
	'billing_city'        => __( 'Billing — District', 'smarttr-address' ),
	'billing_state'       => __( 'Billing — Province', 'smarttr-address' ),
	'billing_postcode'    => __( 'Billing — Postal Code', 'smarttr-address' ),
	'billing_email'       => __( 'Billing — Email', 'smarttr-address' ),
	'billing_phone'       => __( 'Billing — Phone', 'smarttr-address' ),
	'shipping_first_name' => __( 'Shipping — First Name', 'smarttr-address' ),
	'shipping_last_name'  => __( 'Shipping — Last Name', 'smarttr-address' ),
	'shipping_company'    => __( 'Shipping — Company', 'smarttr-address' ),
	'shipping_country'    => __( 'Shipping — Country', 'smarttr-address' ),
	'shipping_address_1'  => __( 'Shipping — Address 1', 'smarttr-address' ),
	'shipping_address_2'  => __( 'Shipping — Neighborhood', 'smarttr-address' ),
	'shipping_city'       => __( 'Shipping — District', 'smarttr-address' ),
	'shipping_state'      => __( 'Shipping — Province', 'smarttr-address' ),
	'shipping_postcode'   => __( 'Shipping — Postal Code', 'smarttr-address' ),
);

$allowed_types = array( 'text', 'select', 'email', 'tel', 'password', 'textarea', 'country', 'state', 'radio', 'checkbox', 'file', 'date', 'datetime-local' );
$layout_chips  = array( 'form-row-wide', 'form-row-first', 'form-row-last' );

// Environment data.
$env_info = Cecomsmarad_Admin_Controller::get_environment_info();

// Data management options.
$counts_raw  = get_option( 'cecomsmarad_record_counts', '{}' );
$counts      = json_decode( $counts_raw, true );
$last_import = get_option( 'cecomsmarad_last_import', '' );
$data_ver    = get_option( 'cecomsmarad_data_version', '' );

if ( ! is_array( $counts ) ) {
	$counts = array();
}

// Monthly manual-sync cooldown state.
$last_manual_sync = get_option( 'cecomsmarad_last_manual_sync', '' );
$sync_available   = true;
$next_sync_date   = '';
if ( '' !== $last_manual_sync ) {
	$last_manual_ts = (int) strtotime( $last_manual_sync );
	if ( ( time() - $last_manual_ts ) < 30 * DAY_IN_SECONDS ) {
		$sync_available = false;
		$next_sync_date = get_date_from_gmt(
			gmdate( 'Y-m-d H:i:s', $last_manual_ts + 30 * DAY_IN_SECONDS ),
			get_option( 'date_format' )
		);
	}
}

?>

<div class="wrap cecomsmarad-admin-wrap">

	<?php /* ── Upgrade Header ─────────────────────────────── */ ?>
	<header class="cecomsmarad-header">
		<div class="cecomsmarad-header-left">
			<img
				src="<?php echo esc_url( CECOMSMARAD_PLUGIN_URL . 'assets/img/cecomsmarad-logo.png' ); ?>"
				class="cecomsmarad-header-logo"
				alt="<?php esc_attr_e( 'SmartTR Address', 'smarttr-address' ); ?>"
			/>
		</div>
		<div class="cecomsmarad-header-right">
			<a href="https://cecom.in/docs-category/smarttr-address-turkish-address-auto-fill" class="cecomsmarad-header-link" target="_blank" rel="noopener">
				<i class="bi bi-file-text-fill" aria-hidden="true"></i>
				<?php esc_html_e( 'Documentation', 'smarttr-address' ); ?>
			</a>
			<a href="https://cecom.in/smarttr-address" class="cecomsmarad-header-link cecomsmarad-header-upgrade" target="_blank" rel="noopener">
				<i class="bi bi-stars" aria-hidden="true"></i>
				<?php esc_html_e( 'Upgrade to Premium', 'smarttr-address' ); ?>
			</a>
		</div>
	</header>

	<?php /* ── Non-JS fallback notices ────────────────────── */ ?>
	<noscript><?php settings_errors( 'cecomsmarad_messages' ); ?></noscript>

	<?php /* ── Layout: sidebar nav + content ─────────────── */ ?>
	<div class="cecomsmarad-layout">

	<?php /* ── Tab Navigation (vertical sidebar) ────────── */ ?>
	<nav class="cecomsmarad-tabs" role="tablist">
		<?php foreach ( $cecomsmarad_tabs as $slug => $tab_item ) :
			$is_locked   = ! empty( $tab_item['locked'] );
			$extra_class = $is_locked ? ' cecomsmarad-tab--locked' : '';
		?>
			<button type="button" role="tab"
				class="cecomsmarad-tab<?php echo esc_attr( ( $current_tab === $slug ? ' active' : '' ) . $extra_class ); ?>"
				aria-selected="<?php echo esc_attr( $current_tab === $slug ? 'true' : 'false' ); ?>"
				aria-controls="cecomsmarad-panel-<?php echo esc_attr( $slug ); ?>"
				data-tab="<?php echo esc_attr( $slug ); ?>">
				<?php echo wp_kses( $tab_item['icon'], array( 'i' => array( 'class' => true, 'aria-hidden' => true ) ) ); ?>
				<span><?php echo esc_html( $tab_item['label'] ); ?></span>
				<?php if ( $is_locked ) : ?>
					<i class="bi bi-lock-fill cecomsmarad-tab-lock-icon" aria-hidden="true"></i>
				<?php endif; ?>
			</button>
		<?php endforeach; ?>
		<?php /* Non-JS fallback links (hidden when JS is active) */ ?>
		<?php foreach ( $cecomsmarad_tabs as $slug => $tab_item ) :
			$is_locked   = ! empty( $tab_item['locked'] );
			$extra_class = $is_locked ? ' cecomsmarad-tab--locked' : '';
		?>
			<a href="<?php echo esc_url( add_query_arg( 'tab', $slug, $page_url ) ); ?>"
				class="cecomsmarad-tab cecomsmarad-tab-noscript <?php echo esc_attr( ( $current_tab === $slug ? 'active' : '' ) . $extra_class ); ?>">
				<?php echo wp_kses( $tab_item['icon'], array( 'i' => array( 'class' => true, 'aria-hidden' => true ) ) ); ?>
				<?php echo esc_html( $tab_item['label'] ); ?>
				<?php if ( $is_locked ) : ?>
					<i class="bi bi-lock-fill cecomsmarad-tab-lock-icon" aria-hidden="true"></i>
				<?php endif; ?>
			</a>
		<?php endforeach; ?>
	</nav>

	<div class="cecomsmarad-content">

	<?php
	/*
	 * ══════════════════════════════════════════════════
	 * GENERAL TAB
	 * ══════════════════════════════════════════════════
	 */
	?>

	<div id="cecomsmarad-panel-general" role="tabpanel"
		class="cecomsmarad-panel <?php echo esc_attr( 'general' !== $current_tab ? 'cecomsmarad-panel-hidden' : '' ); ?>">

		<?php $enabled = get_option( 'cecomsmarad_enabled', '1' ); ?>
		<form method="post" action="" class="cecomsmarad-ajax-form" data-ajax-action="cecomsmarad_save_general">
			<?php wp_nonce_field( 'cecomsmarad_admin_save', '_cecomsmarad_nonce' ); ?>
			<input type="hidden" name="cecomsmarad_action" value="save_general" />

			<div class="cecomsmarad-card">
				<?php /* ── 6-card status grid ── */ ?>
				<div class="cecomsmarad-status-grid cecomsmarad-status-grid-6">
					<div class="cecomsmarad-stat-card">
						<span class="cecomsmarad-stat-value"><span><?php echo esc_html( CECOMSMARAD_VERSION ); ?></span></span>
						<span class="cecomsmarad-stat-label"><?php esc_html_e( 'Version', 'smarttr-address' ); ?></span>
					</div>
					<div class="cecomsmarad-stat-card">
						<span class="cecomsmarad-stat-value">
							<?php if ( $env_info['wc_active'] ) : ?>
								<span class="cecomsmarad-status-dot cecomsmarad-dot-ok"></span>
								<?php echo esc_html( 'v' . $env_info['wc'] ); ?>
							<?php else : ?>
								<span class="cecomsmarad-status-dot cecomsmarad-dot-err"></span>
								<?php esc_html_e( 'Not installed', 'smarttr-address' ); ?>
							<?php endif; ?>
						</span>
						<span class="cecomsmarad-stat-label"><?php esc_html_e( 'WooCommerce', 'smarttr-address' ); ?></span>
					</div>
					<div class="cecomsmarad-stat-card">
						<span class="cecomsmarad-stat-value">
							<?php if ( $env_info['hpos'] ) : ?>
								<span class="cecomsmarad-status-dot cecomsmarad-dot-ok"></span>
								<?php esc_html_e( 'Active', 'smarttr-address' ); ?>
							<?php else : ?>
								<span class="cecomsmarad-status-dot cecomsmarad-dot-warn"></span>
								<?php esc_html_e( 'Inactive', 'smarttr-address' ); ?>
							<?php endif; ?>
						</span>
						<span class="cecomsmarad-stat-label"><?php esc_html_e( 'HPOS', 'smarttr-address' ); ?></span>
					</div>
					<div class="cecomsmarad-stat-card">
						<span class="cecomsmarad-stat-value"><span><?php echo esc_html( $env_info['php'] ); ?></span></span>
						<span class="cecomsmarad-stat-label"><?php esc_html_e( 'PHP', 'smarttr-address' ); ?></span>
					</div>
					<div class="cecomsmarad-stat-card">
						<span class="cecomsmarad-stat-value"><span><?php echo esc_html( $env_info['wp'] ); ?></span></span>
						<span class="cecomsmarad-stat-label"><?php esc_html_e( 'WordPress', 'smarttr-address' ); ?></span>
					</div>
					<div class="cecomsmarad-stat-card">
						<span class="cecomsmarad-stat-value">
							<?php if ( 'block' === $env_info['checkout_type'] ) : ?>
								<span class="cecomsmarad-status-dot cecomsmarad-dot-err"></span>
								<?php esc_html_e( 'Block', 'smarttr-address' ); ?>
							<?php else : ?>
								<span class="cecomsmarad-status-dot cecomsmarad-dot-ok"></span>
								<?php esc_html_e( 'Classic', 'smarttr-address' ); ?>
							<?php endif; ?>
						</span>
						<span class="cecomsmarad-stat-label"><?php esc_html_e( 'Checkout Type', 'smarttr-address' ); ?></span>
					</div>
				</div>

				<?php if ( 'block' === $env_info['checkout_type'] ) : ?>
				<div class="cecomsmarad-block-notice">
					<i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
					<div class="cecomsmarad-block-notice-body">
						<strong><?php esc_html_e( 'Block Checkout is active', 'smarttr-address' ); ?></strong>
						<p><?php esc_html_e( 'SmartTR Address only works with Classic Payment. To use this plugin, copy the shortcode below and paste it into the shortcode field on your payment page.', 'smarttr-address' ); ?></p>
						<button type="button" class="cecomsmarad-shortcode-copy" data-shortcode="[woocommerce_checkout]">
							<code>[woocommerce_checkout]</code>
							<i class="bi bi-clipboard" aria-hidden="true"></i>
							<span class="cecomsmarad-copy-label"><?php esc_html_e( 'Copy', 'smarttr-address' ); ?></span>
						</button>
					</div>
				</div>
				<?php endif; ?>

				<div class="cecomsmarad-toggle-row">
					<div>
						<div class="cecomsmarad-toggle-label-text"><?php esc_html_e( 'Enable Plugin', 'smarttr-address' ); ?></div>
						<div class="cecomsmarad-toggle-desc"><?php esc_html_e( 'Enable Turkish address auto-fill feature', 'smarttr-address' ); ?></div>
					</div>
					<label class="cecomsmarad-toggle">
						<input type="checkbox" name="cecomsmarad_enabled" value="1" <?php checked( $enabled, '1' ); ?> />
						<span class="cecomsmarad-toggle-track"><span class="cecomsmarad-toggle-thumb"></span></span>
					</label>
				</div>

				<?php /* ── Quick links ── */ ?>
				<div class="cecomsmarad-quick-links">
					<?php
					$checkout_url = function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : '#';
					$wc_settings  = admin_url( 'admin.php?page=wc-settings' );
					?>
					<a href="<?php echo esc_url( $checkout_url ); ?>" class="cecomsmarad-quick-link" target="_blank" rel="noopener">
						<i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
						<?php esc_html_e( 'View Checkout Page', 'smarttr-address' ); ?>
					</a>
					<a href="<?php echo esc_url( $wc_settings ); ?>" class="cecomsmarad-quick-link">
						<i class="bi bi-gear-fill" aria-hidden="true"></i>
						<?php esc_html_e( 'WooCommerce Settings', 'smarttr-address' ); ?>
					</a>
				</div>

				<div class="cecomsmarad-btn-group">
					<button type="submit" class="cecomsmarad-btn cecomsmarad-btn-primary"><?php esc_html_e( 'Save', 'smarttr-address' ); ?></button>
				</div>
			</div>
		</form>
	</div>

	<?php
	/*
	 * ══════════════════════════════════════════════════
	 * DATA MANAGEMENT TAB
	 * ══════════════════════════════════════════════════
	 */
	?>

	<div id="cecomsmarad-panel-data" role="tabpanel"
		class="cecomsmarad-panel <?php echo esc_attr( 'data' !== $current_tab ? 'cecomsmarad-panel-hidden' : '' ); ?>">

		<form method="post" action="" class="cecomsmarad-ajax-form" data-ajax-action="cecomsmarad_reimport_data">
			<?php wp_nonce_field( 'cecomsmarad_admin_save', '_cecomsmarad_nonce' ); ?>
			<input type="hidden" name="cecomsmarad_action" value="reimport_data" />

			<div class="cecomsmarad-card">
				<div class="cecomsmarad-data-grid">
					<div class="cecomsmarad-data-stat">
						<span class="cecomsmarad-stat-value"><?php echo esc_html( number_format_i18n( $counts['provinces'] ?? 0 ) ); ?></span>
						<span class="cecomsmarad-stat-label"><?php esc_html_e( 'Province', 'smarttr-address' ); ?></span>
					</div>
					<div class="cecomsmarad-data-stat">
						<span class="cecomsmarad-stat-value"><?php echo esc_html( number_format_i18n( $counts['districts'] ?? 0 ) ); ?></span>
						<span class="cecomsmarad-stat-label"><?php esc_html_e( 'District', 'smarttr-address' ); ?></span>
					</div>
					<div class="cecomsmarad-data-stat cecomsmarad-data-stat--locked">
					<span class="cecomsmarad-stat-value">
						<i class="bi bi-lock-fill" aria-hidden="true"></i>
					</span>
					<span class="cecomsmarad-stat-label"><?php esc_html_e( 'Neighborhood', 'smarttr-address' ); ?></span>
					<a href="https://cecom.in/smarttr-address" class="cecomsmarad-stat-upgrade-link" target="_blank" rel="noopener">
						<i class="bi bi-stars" aria-hidden="true"></i>
						<?php esc_html_e( 'Premium', 'smarttr-address' ); ?>
					</a>
				</div>
				</div>

				<?php /* ── Data details ── */ ?>
				<div class="cecomsmarad-data-details">
					<dl class="cecomsmarad-detail-list">
						<div class="cecomsmarad-detail-item">
							<dt><?php esc_html_e( 'Data Version', 'smarttr-address' ); ?></dt>
							<dd><?php echo esc_html( $data_ver ? $data_ver : '—' ); ?></dd>
						</div>
						<div class="cecomsmarad-detail-item">
							<dt><?php esc_html_e( 'Last Import', 'smarttr-address' ); ?></dt>
							<dd>
								<?php
								if ( $last_import ) {
									$local_time = get_date_from_gmt( $last_import, get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) );
									echo esc_html( $local_time );
								} else {
									esc_html_e( 'Not yet', 'smarttr-address' );
								}
								?>
							</dd>
						</div>
						<?php if ( ! $sync_available ) : ?>
							<div class="cecomsmarad-detail-item">
								<dt><?php esc_html_e( 'Next Update Available', 'smarttr-address' ); ?></dt>
								<dd><?php echo esc_html( $next_sync_date ); ?></dd>
							</div>
						<?php endif; ?>
					</dl>
				</div>


				<div class="cecomsmarad-btn-group">
					<button type="submit" class="cecomsmarad-btn cecomsmarad-btn-secondary cecomsmarad-reimport-btn"
						<?php if ( ! $sync_available ) { echo 'disabled aria-disabled="true"'; } ?>>
						<i class="bi bi-arrow-clockwise" aria-hidden="true" style="margin-right:4px;vertical-align:-2px"></i>
						<?php esc_html_e( 'Update Addresses', 'smarttr-address' ); ?>
					</button>
					<?php if ( ! $sync_available ) : ?>
						<p class="cecomsmarad-sync-cooldown">
							<i class="bi bi-info-circle" aria-hidden="true"></i>
							<?php
							printf(
								/* translators: %s: date when next manual sync is available */
								esc_html__( 'Address data can be updated once per month. Next update available: %s.', 'smarttr-address' ),
								'<strong>' . esc_html( $next_sync_date ) . '</strong>'
							);
							?>
						</p>
					<?php endif; ?>
				</div>
				<div id="cecomsmarad-reimport-progress" class="cecomsmarad-reimport-progress" style="display:none;"></div>
			</div>
		</form>

	</div>

	<?php
	/*
	 * ══════════════════════════════════════════════════
	 * FIELD EDITOR TAB (LOCKED)
	 * ══════════════════════════════════════════════════
	 */
	?>

	<div id="cecomsmarad-panel-fields" role="tabpanel"
		class="cecomsmarad-panel <?php echo esc_attr( 'fields' !== $current_tab ? 'cecomsmarad-panel-hidden' : '' ); ?>">

		<div class="cecomsmarad-card cecomsmarad-locked-card">
			<i class="bi bi-lock-fill cecomsmarad-locked-card__icon" aria-hidden="true"></i>
			<strong class="cecomsmarad-locked-card__label">
				<?php esc_html_e( 'Premium Feature', 'smarttr-address' ); ?>
			</strong>
			<p class="cecomsmarad-locked-card__desc">
				<?php esc_html_e( 'Customize field labels, visibility, and ordering for all WooCommerce address fields.', 'smarttr-address' ); ?>
			</p>
			<a href="https://cecom.in/smarttr-address" class="cecomsmarad-btn cecomsmarad-btn-upgrade" target="_blank" rel="noopener">
				<i class="bi bi-stars" aria-hidden="true"></i>
				<?php esc_html_e( 'Upgrade to Premium', 'smarttr-address' ); ?>
			</a>
		</div>

	</div>

	<?php
	/*
	 * ══════════════════════════════════════════════════
	 * UPGRADE TAB (LOCKED)
	 * ══════════════════════════════════════════════════
	 */
	?>

	<div id="cecomsmarad-panel-upgrade" role="tabpanel"
		class="cecomsmarad-panel <?php echo esc_attr( 'upgrade' !== $current_tab ? 'cecomsmarad-panel-hidden' : '' ); ?>">

		<div class="cecomsmarad-card">
			<div class="cecomsmarad-upgrade-panel">
				<div class="cecomsmarad-upgrade-header">
					<i class="bi bi-stars cecomsmarad-upgrade-icon" aria-hidden="true"></i>
					<h2 class="cecomsmarad-upgrade-title">
						<?php esc_html_e( 'SmartTR Address — Premium', 'smarttr-address' ); ?>
					</h2>
					<p class="cecomsmarad-upgrade-subtitle">
						<?php esc_html_e( 'Unlock the full Turkish address experience for your WooCommerce store.', 'smarttr-address' ); ?>
					</p>
				</div>
				<ul class="cecomsmarad-upgrade-features">
					<li><i class="bi bi-check-circle-fill" aria-hidden="true"></i> <?php esc_html_e( 'Neighborhood AJAX dropdown — 70,000+ entries', 'smarttr-address' ); ?></li>
					<li><i class="bi bi-check-circle-fill" aria-hidden="true"></i> <?php esc_html_e( 'Postal code auto-fill on neighborhood selection', 'smarttr-address' ); ?></li>
					<li><i class="bi bi-check-circle-fill" aria-hidden="true"></i> <?php esc_html_e( 'Field Editor — labels, visibility, drag-and-drop ordering', 'smarttr-address' ); ?></li>
					<li><i class="bi bi-check-circle-fill" aria-hidden="true"></i> <?php esc_html_e( 'Custom checkout fields (text, select, file, date…)', 'smarttr-address' ); ?></li>
					<li><i class="bi bi-check-circle-fill" aria-hidden="true"></i> <?php esc_html_e( 'Automatic recurring data updates (weekly / monthly / bi-monthly)', 'smarttr-address' ); ?></li>
					<li><i class="bi bi-check-circle-fill" aria-hidden="true"></i> <?php esc_html_e( 'Unlimited manual data syncs', 'smarttr-address' ); ?></li>
					<li><i class="bi bi-check-circle-fill" aria-hidden="true"></i> <?php esc_html_e( 'Priority email support', 'smarttr-address' ); ?></li>
				</ul>
				<div class="cecomsmarad-upgrade-actions">
					<a href="https://cecom.in/smarttr-address" class="cecomsmarad-btn cecomsmarad-btn-upgrade" target="_blank" rel="noopener">
						<i class="bi bi-stars" aria-hidden="true"></i>
						<?php esc_html_e( 'Get Premium', 'smarttr-address' ); ?>
					</a>
				</div>
			</div>
		</div>

	</div>


	</div><?php /* .cecomsmarad-content */ ?>
	</div><?php /* .cecomsmarad-layout */ ?>

	<?php /* ── Modal Overlay ──────────────────────────────── */ ?>
	<div id="cecomsmarad-modal-overlay" class="cecomsmarad-modal-overlay" style="display:none;" aria-hidden="true">
		<div class="cecomsmarad-modal" role="dialog" aria-modal="true" aria-labelledby="cecomsmarad-modal-title">
			<h3 id="cecomsmarad-modal-title" class="cecomsmarad-modal-title"></h3>
			<p id="cecomsmarad-modal-message" class="cecomsmarad-modal-message"></p>
			<div class="cecomsmarad-modal-actions">
				<button type="button" class="cecomsmarad-btn cecomsmarad-btn-secondary" id="cecomsmarad-modal-cancel"><?php esc_html_e( 'Cancel', 'smarttr-address' ); ?></button>
				<button type="button" class="cecomsmarad-btn cecomsmarad-btn-danger" id="cecomsmarad-modal-confirm"><?php esc_html_e( 'Confirm', 'smarttr-address' ); ?></button>
			</div>
		</div>
	</div>


	<?php /* ── Toast Container ────────────────────────────── */ ?>
	<div id="cecomsmarad-toast-container" class="cecomsmarad-toast-container" aria-live="polite"></div>

</div>

