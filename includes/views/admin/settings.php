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

$enabled = get_option( 'cecomsmarad_enabled', '1' );

?>

<div id="cecomsmaradWrap">

<?php /* ── Non-JS fallback notices ────────────────────── */ ?>
<noscript><?php settings_errors( 'cecomsmarad_messages' ); ?></noscript>

<?php /* ── Header ──────────────────────────────────────── */ ?>
<header class="d-flex flex-wrap align-items-center justify-content-between bg-white shadow-sm rounded-4 border border-light-subtle p-3 p-sm-4 mb-3 gap-2">
	<div class="d-flex align-items-center gap-2">
		<button class="d-lg-none btn btn-outline-secondary border-0 p-1"
				type="button"
				data-bs-toggle="offcanvas"
				data-bs-target="#sidebarTabs"
				aria-controls="sidebarTabs"
				aria-label="<?php esc_attr_e( 'Open navigation', 'smarttr-address' ); ?>">
			<i class="bi bi-list fs-4" aria-hidden="true"></i>
		</button>
		<img
			src="<?php echo esc_url( CECOMSMARAD_PLUGIN_URL . 'assets/img/cecomsmarad-logo.svg' ); ?>"
			height="60"
			alt="<?php esc_attr_e( 'SmartTR Address', 'smarttr-address' ); ?>"
		/>
		<div class="d-flex align-items-center gap-1">
			<h1 class="plugin-title text-primary fw-light fs-5 mb-0">
				<span class="fw-bold"><?php esc_html_e( 'SmartTR', 'smarttr-address' ); ?></span> <?php esc_html_e( 'Address', 'smarttr-address' ); ?>
			</h1>
			<span class="badge bg-primary-subtle border border-primary-subtle text-primary-emphasis rounded-pill">
				<?php echo esc_html( 'v' . CECOMSMARAD_VERSION ); ?>
			</span>
		</div>
	</div>
	<div class="d-flex align-items-center gap-2">
		<a href="https://cecom.in/docs-category/smarttr-address-turkish-address-auto-fill"
			class="btn btn-light bg-white rounded-pill px-3 d-none d-md-inline-flex align-items-center gap-2"
			target="_blank" rel="noopener">
			<i class="bi bi-book fs-5" aria-hidden="true"></i>
			<?php esc_html_e( 'Docs', 'smarttr-address' ); ?>
		</a>
		<a href="https://cecom.in/smarttr-address"
			class="btn btn-warning rounded-pill px-3 d-inline-flex align-items-center gap-2"
			target="_blank" rel="noopener">
			<i class="bi bi-stars fs-5" aria-hidden="true"></i>
			<?php esc_html_e( 'Upgrade to Pro', 'smarttr-address' ); ?>
		</a>
	</div>
</header>

<?php /* ── Layout: Sidebar + Content ──────────────────── */ ?>
<div class="d-flex align-items-start gap-3">

	<?php /* ── Sidebar ─────────────────────────────────── */ ?>
	<div class="col-lg-3 offcanvas-lg offcanvas-start flex-shrink-0 bg-white shadow-sm rounded-4 border border-light-subtle"
		 id="sidebarTabs" tabindex="-1">
		<div class="offcanvas-header border-bottom d-lg-none">
			<span class="fw-semibold"><?php esc_html_e( 'Navigation', 'smarttr-address' ); ?></span>
			<button type="button" class="btn-close"
					data-bs-dismiss="offcanvas"
					data-bs-target="#sidebarTabs"
					aria-label="<?php esc_attr_e( 'Close', 'smarttr-address' ); ?>"></button>
		</div>
		<nav class="offcanvas-body p-3 d-flex flex-column gap-1 sticky-top"
			 role="tablist"
			 style="top:0;overflow-y:auto;max-height:100vh;">

			<?php /* General tab */ ?>
			<button class="admin-tab d-flex align-items-center gap-2 w-100 py-3 px-3 fs-6 fw-medium text-secondary border-0 rounded-2 bg-transparent text-start<?php echo ( 'general' === $current_tab ) ? ' active' : ''; ?>"
					type="button"
					role="tab"
					data-panel="panelGeneral"
					aria-selected="<?php echo esc_attr( ( 'general' === $current_tab ) ? 'true' : 'false' ); ?>">
				<i class="bi bi-gear-fill fs-5" aria-hidden="true"></i>
				<?php esc_html_e( 'General', 'smarttr-address' ); ?>
			</button>

			<?php /* Data Management tab */ ?>
			<button class="admin-tab d-flex align-items-center gap-2 w-100 py-3 px-3 fs-6 fw-medium text-secondary border-0 rounded-2 bg-transparent text-start<?php echo ( 'data' === $current_tab ) ? ' active' : ''; ?>"
					type="button"
					role="tab"
					data-panel="panelData"
					aria-selected="<?php echo esc_attr( ( 'data' === $current_tab ) ? 'true' : 'false' ); ?>">
				<i class="bi bi-database-fill-check fs-5" aria-hidden="true"></i>
				<?php esc_html_e( 'Data Management', 'smarttr-address' ); ?>
			</button>

			<?php /* Field Editor tab — locked (premium feature) */ ?>
			<button class="admin-tab disabled d-flex align-items-center gap-2 w-100 py-3 px-3 fs-6 fw-medium text-secondary border-0 rounded-2 bg-light text-start opacity-50"
					type="button"
					role="tab"
					data-panel="panelFields"
					aria-selected="false"
					aria-disabled="true">
				<i class="bi bi-sliders fs-5" aria-hidden="true"></i>
				<?php esc_html_e( 'Field Editor', 'smarttr-address' ); ?>
				<i class="bi bi-lock-fill ms-auto text-warning" style="font-size:.8rem;" aria-hidden="true"></i>
			</button>

			<?php /* Upgrade tab */ ?>
			<button class="admin-tab d-flex align-items-center gap-2 w-100 py-3 px-3 fs-6 fw-medium text-secondary border-0 rounded-2 bg-transparent text-start<?php echo ( 'upgrade' === $current_tab ) ? ' active' : ''; ?>"
					type="button"
					role="tab"
					data-panel="panelUpgrade"
					aria-selected="<?php echo esc_attr( ( 'upgrade' === $current_tab ) ? 'true' : 'false' ); ?>">
				<i class="bi bi-stars fs-5" aria-hidden="true"></i>
				<?php esc_html_e( 'Upgrade', 'smarttr-address' ); ?>
			</button>

		</nav>
	</div><!-- /#sidebarTabs -->

	<?php /* ── Content Area ─────────────────────────────── */ ?>
	<div class="col-7 shadow-sm rounded-4 border border-light-subtle flex-grow-1 p-3 p-sm-4">
		<div class="card-body">

			<?php
			/*
			 * ══════════════════════════════════════════════════
			 * GENERAL TAB
			 * ══════════════════════════════════════════════════
			 */
			?>
			<div class="tab-panel<?php echo ( 'general' !== $current_tab ) ? ' d-none' : ''; ?>"
				 id="panelGeneral"
				 role="tabpanel">

				<form method="post" action="" class="cecomsmarad-ajax-form" data-ajax-action="cecomsmarad_save_general">
					<?php wp_nonce_field( 'cecomsmarad_admin_save', '_cecomsmarad_nonce' ); ?>
					<input type="hidden" name="cecomsmarad_action" value="save_general" />

					<?php /* ── Status grid ── */ ?>
					<div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-3 mb-3">

						<div class="col">
							<div class="card mt-0 h-100 text-center shadow-sm rounded-3 p-3 gap-2 bg-light border border-light-subtle">
								<div class="stat-value text-body-emphasis"><?php echo esc_html( CECOMSMARAD_VERSION ); ?></div>
								<div class="small text-muted text-uppercase"><?php esc_html_e( 'Version', 'smarttr-address' ); ?></div>
							</div>
						</div>

						<div class="col">
							<div class="card mt-0 h-100 text-center shadow-sm rounded-3 p-3 gap-2 bg-light border border-light-subtle">
								<div class="stat-value">
									<?php if ( $env_info['wc_active'] ) : ?>
										<span class="badge bg-success-subtle text-success-emphasis rounded-pill"><?php echo esc_html( 'v' . $env_info['wc'] ); ?></span>
									<?php else : ?>
										<span class="badge bg-danger-subtle text-danger-emphasis rounded-pill"><?php esc_html_e( 'Missing', 'smarttr-address' ); ?></span>
									<?php endif; ?>
								</div>
								<div class="small text-muted text-uppercase"><?php esc_html_e( 'WooCommerce', 'smarttr-address' ); ?></div>
							</div>
						</div>

						<div class="col">
							<div class="card mt-0 h-100 text-center shadow-sm rounded-3 p-3 gap-2 bg-light border border-light-subtle">
								<div class="stat-value">
									<?php if ( $env_info['hpos'] ) : ?>
										<span class="badge bg-success-subtle text-success-emphasis rounded-pill"><?php esc_html_e( 'Active', 'smarttr-address' ); ?></span>
									<?php else : ?>
										<span class="badge bg-warning-subtle text-warning-emphasis rounded-pill"><?php esc_html_e( 'Inactive', 'smarttr-address' ); ?></span>
									<?php endif; ?>
								</div>
								<div class="small text-muted text-uppercase"><?php esc_html_e( 'HPOS', 'smarttr-address' ); ?></div>
							</div>
						</div>

						<div class="col">
							<div class="card mt-0 h-100 text-center shadow-sm rounded-3 p-3 gap-2 bg-light border border-light-subtle">
								<div class="stat-value text-body-emphasis"><?php echo esc_html( $env_info['php'] ); ?></div>
								<div class="small text-muted text-uppercase"><?php esc_html_e( 'PHP', 'smarttr-address' ); ?></div>
							</div>
						</div>

						<div class="col">
							<div class="card mt-0 h-100 text-center shadow-sm rounded-3 p-3 gap-2 bg-light border border-light-subtle">
								<div class="stat-value text-body-emphasis"><?php echo esc_html( $env_info['wp'] ); ?></div>
								<div class="small text-muted text-uppercase"><?php esc_html_e( 'WordPress', 'smarttr-address' ); ?></div>
							</div>
						</div>

						<div class="col">
							<div class="card mt-0 h-100 text-center shadow-sm rounded-3 p-3 gap-2 bg-light border border-light-subtle">
								<div class="stat-value">
									<?php if ( 'block' === $env_info['checkout_type'] ) : ?>
										<span class="badge bg-danger-subtle text-danger-emphasis rounded-pill"><?php esc_html_e( 'Block', 'smarttr-address' ); ?></span>
									<?php else : ?>
										<span class="badge bg-success-subtle text-success-emphasis rounded-pill"><?php esc_html_e( 'Classic', 'smarttr-address' ); ?></span>
									<?php endif; ?>
								</div>
								<div class="small text-muted text-uppercase"><?php esc_html_e( 'Checkout', 'smarttr-address' ); ?></div>
							</div>
						</div>

					</div><!-- /.row -->

					<?php if ( 'block' === $env_info['checkout_type'] ) : ?>
					<div class="alert alert-warning d-flex align-items-start gap-3 rounded-3 mb-3" role="alert">
						<i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1" aria-hidden="true"></i>
						<div>
							<strong><?php esc_html_e( 'Block Checkout is active', 'smarttr-address' ); ?></strong>
							<p class="mb-2 small"><?php esc_html_e( 'SmartTR Address only works with Classic Payment. To use this plugin, copy the shortcode below and paste it into the shortcode field on your payment page.', 'smarttr-address' ); ?></p>
							<button type="button"
									class="btn btn-outline-warning btn-sm rounded-pill shortcode-copy-btn"
									data-shortcode="[woocommerce_checkout]">
								<i class="bi bi-clipboard me-1" aria-hidden="true"></i>
								<code>[woocommerce_checkout]</code>
								&nbsp;<?php esc_html_e( 'Copy', 'smarttr-address' ); ?>
							</button>
						</div>
					</div>
					<?php endif; ?>

					<?php /* ── Enable toggle ── */ ?>
					<div class="d-flex align-items-center justify-content-between gap-3 py-3 border-top">
						<div>
							<div class="fw-medium"><?php esc_html_e( 'Enable Plugin', 'smarttr-address' ); ?></div>
							<div class="small text-muted"><?php esc_html_e( 'Enable Turkish address auto-fill feature', 'smarttr-address' ); ?></div>
						</div>
						<div class="form-check form-switch">
							<input class="form-check-input"
								   type="checkbox"
								   role="switch"
								   id="cecomsmaradEnabled"
								   name="cecomsmarad_enabled"
								   value="1"
								   <?php checked( $enabled, '1' ); ?> />
							<label class="form-check-label visually-hidden" for="cecomsmaradEnabled">
								<?php esc_html_e( 'Enable Plugin', 'smarttr-address' ); ?>
							</label>
						</div>
					</div>

					<?php /* ── Quick links ── */ ?>
					<?php
					$checkout_url = function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : '#';
					$wc_settings  = admin_url( 'admin.php?page=wc-settings' );
					?>
					<div class="d-flex flex-wrap gap-2 py-3 border-top">
						<a href="<?php echo esc_url( $checkout_url ); ?>"
							class="btn btn-light btn-sm bg-white border d-inline-flex align-items-center gap-1 rounded-pill px-3 py-2"
							target="_blank" rel="noopener">
							<i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
							<?php esc_html_e( 'View Checkout Page', 'smarttr-address' ); ?>
						</a>
						<a href="<?php echo esc_url( $wc_settings ); ?>"
							class="btn btn-light btn-sm bg-white border d-inline-flex align-items-center gap-1 rounded-pill px-3 py-2">
							<i class="bi bi-gear-fill" aria-hidden="true"></i>
							<?php esc_html_e( 'WooCommerce Settings', 'smarttr-address' ); ?>
						</a>
					</div>

					<div class="pt-3 border-top">
						<button type="submit" class="btn btn-primary rounded-pill px-4">
							<?php esc_html_e( 'Save Settings', 'smarttr-address' ); ?>
						</button>
					</div>

				</form>

			</div><!-- /#panelGeneral -->

			<?php
			/*
			 * ══════════════════════════════════════════════════
			 * DATA MANAGEMENT TAB
			 * ══════════════════════════════════════════════════
			 */
			?>
			<div class="tab-panel<?php echo ( 'data' !== $current_tab ) ? ' d-none' : ''; ?>"
				 id="panelData"
				 role="tabpanel">

				<form method="post" action="" class="cecomsmarad-ajax-form" data-ajax-action="cecomsmarad_reimport_data">
					<?php wp_nonce_field( 'cecomsmarad_admin_save', '_cecomsmarad_nonce' ); ?>
					<input type="hidden" name="cecomsmarad_action" value="reimport_data" />

					<?php /* ── Data stat cards ── */ ?>
					<div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-3 mb-3">

						<div class="col">
							<div class="card mt-0 text-center shadow-sm rounded-3 p-3 gap-2 border border-light-subtle">
								<div class="stat-value fs-3 text-primary">
									<?php echo esc_html( number_format_i18n( $counts['provinces'] ?? 0 ) ); ?>
								</div>
								<div class="small text-muted text-uppercase"><?php esc_html_e( 'Province', 'smarttr-address' ); ?></div>
							</div>
						</div>

						<div class="col">
							<div class="card mt-0 text-center shadow-sm rounded-3 p-3 gap-2 border border-light-subtle">
								<div class="stat-value fs-3 text-primary">
									<?php echo esc_html( number_format_i18n( $counts['districts'] ?? 0 ) ); ?>
								</div>
								<div class="small text-muted text-uppercase"><?php esc_html_e( 'District', 'smarttr-address' ); ?></div>
							</div>
						</div>

						<?php /* Neighborhood — locked (Design 4: Corner Ribbon + Dark Action Bar) */ ?>
						<div class="col">
							<div class="card mt-0 text-center shadow-sm rounded-3 p-3 gap-2 bg-light border border-light-subtle overflow-hidden position-relative data-stat-locked">
								<div class="position-absolute text-center fw-bold"
									 style="top:18px;right:-26px;width:110px;background:#4f46e5;color:#fff;font-size:.6rem;padding:5px 0;transform:rotate(45deg);letter-spacing:.08em;z-index:2;">PRO</div>
								<div class="p-1 text-center user-select-none" style="filter:blur(2px);pointer-events:none;">
									<div class="stat-value fs-3 text-primary">—</div>
									<div class="small text-muted text-uppercase"><?php esc_html_e( 'Neighborhood', 'smarttr-address' ); ?></div>
								</div>
								<div class="position-absolute top-50 start-50 translate-middle">
									<a href="https://cecom.in/cecomsmarad-address-turkish-address"
										class="btn btn-sm btn-warning rounded-pill d-inline-flex align-items-center gap-1"
										target="_blank" rel="noopener">
										<i class="bi bi-stars" aria-hidden="true"></i>
										<?php esc_html_e( 'Upgrade to Pro', 'smarttr-address-premium' ); ?>
									</a>
								</div>
							</div>
						</div>
					</div><!-- /.row -->

					<?php /* ── Data details table ── */ ?>
					<table class="table table-sm mb-4">
						<tbody>
							<tr>
								<th class="text-muted fw-medium w-50"><?php esc_html_e( 'Data Version', 'smarttr-address' ); ?></th>
								<td><?php echo esc_html( $data_ver ? $data_ver : '—' ); ?></td>
							</tr>
							<tr>
								<th class="text-muted fw-medium"><?php esc_html_e( 'Last Import', 'smarttr-address' ); ?></th>
								<td>
									<?php
									if ( $last_import ) {
										$local_time = get_date_from_gmt( $last_import, get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) );
										echo esc_html( $local_time );
									} else {
										esc_html_e( 'Not yet', 'smarttr-address' );
									}
									?>
								</td>
							</tr>
							<?php if ( ! $sync_available ) : ?>
							<tr>
								<th class="text-muted fw-medium"><?php esc_html_e( 'Next Update Available', 'smarttr-address' ); ?></th>
								<td><?php echo esc_html( $next_sync_date ); ?></td>
							</tr>
							<?php endif; ?>
						</tbody>
					</table>

					<?php /* ── Update button ── */ ?>
					<div class="d-flex flex-column gap-3">
						<div>
							<button type="submit"
									class="btn btn-outline-secondary rounded-pill px-4 d-inline-flex align-items-center gap-2"
									<?php if ( ! $sync_available ) : ?>disabled aria-disabled="true"<?php endif; ?>>
								<i class="bi bi-arrow-clockwise" aria-hidden="true"></i>
								<?php esc_html_e( 'Update Addresses', 'smarttr-address' ); ?>
							</button>
						</div>
						<?php if ( ! $sync_available ) : ?>
						<div class="alert alert-info d-flex align-items-center gap-2 rounded-3 py-2 px-3 mb-0" role="alert">
							<i class="bi bi-info-circle-fill flex-shrink-0" aria-hidden="true"></i>
							<span class="small">
								<?php
								printf(
									/* translators: %s: date when next manual sync is available */
									esc_html__( 'Address data can be updated once per month. Next update available: %s.', 'smarttr-address' ),
									'<strong>' . esc_html( $next_sync_date ) . '</strong>'
								);
								?>
							</span>
						</div>
						<?php endif; ?>
					</div>

					<div id="cecomsmarad-reimport-progress" style="display:none;" class="mt-3"></div>

				</form>

			</div><!-- /#panelData -->

			<?php
			/*
			 * ══════════════════════════════════════════════════
			 * FIELD EDITOR TAB — LOCKED (Design 2: Frosted Glass)
			 * ══════════════════════════════════════════════════
			 */
			?>
			<div class="tab-panel<?php echo ( 'fields' !== $current_tab ) ? ' d-none' : ''; ?>"
				 id="panelFields"
				 role="tabpanel">

				<div class="card rounded-4 border-0 overflow-hidden position-relative">
					<?php /* Blurred preview stub */ ?>
					<div class="p-4 user-select-none" style="filter:blur(4px);pointer-events:none;min-height:260px;" aria-hidden="true">
						<div class="mb-3">
							<div class="h6 fw-semibold mb-3"><?php esc_html_e( 'Field Editor', 'smarttr-address' ); ?></div>
							<div class="d-flex align-items-center gap-2 p-2 border rounded-2 mb-2">
								<i class="bi bi-grip-vertical text-muted"></i>
								<span class="fw-medium small"><?php esc_html_e( 'Billing — Province', 'smarttr-address' ); ?></span>
								<span class="badge bg-primary-subtle text-primary-emphasis ms-auto">select</span>
							</div>
							<div class="d-flex align-items-center gap-2 p-2 border rounded-2 mb-2">
								<i class="bi bi-grip-vertical text-muted"></i>
								<span class="fw-medium small"><?php esc_html_e( 'Billing — District', 'smarttr-address' ); ?></span>
								<span class="badge bg-primary-subtle text-primary-emphasis ms-auto">select</span>
							</div>
							<div class="d-flex align-items-center gap-2 p-2 border rounded-2">
								<i class="bi bi-grip-vertical text-muted"></i>
								<span class="fw-medium small"><?php esc_html_e( 'Billing — Neighborhood', 'smarttr-address' ); ?></span>
								<span class="badge bg-primary-subtle text-primary-emphasis ms-auto">select</span>
							</div>
						</div>
					</div>
					<?php /* Frosted glass overlay */ ?>
					<div class="position-absolute top-0 start-0 w-100 h-100 d-flex flex-column align-items-center justify-content-center text-center px-4 rounded-4"
						 style="background:rgba(255,255,255,.72);backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);">
						<i class="bi bi-shield-lock-fill text-primary mb-2" style="font-size:2.4rem;" aria-hidden="true"></i>
						<p class="fw-bold text-body-emphasis mb-1"><?php esc_html_e( 'Premium Feature', 'smarttr-address' ); ?></p>
						<p class="text-secondary small mb-3"><?php esc_html_e( 'Customize field labels, visibility, and ordering for all WooCommerce address fields.', 'smarttr-address' ); ?></p>
						<a href="https://cecom.in/smarttr-address"
							class="btn btn-primary rounded-pill px-4 btn-sm d-inline-flex align-items-center gap-2"
							target="_blank" rel="noopener">
							<i class="bi bi-stars" aria-hidden="true"></i>
							<?php esc_html_e( 'Upgrade to Premium', 'smarttr-address' ); ?>
						</a>
					</div>
				</div>

			</div><!-- /#panelFields -->

			<?php
			/*
			 * ══════════════════════════════════════════════════
			 * UPGRADE TAB
			 * ══════════════════════════════════════════════════
			 */
			?>
			<div class="tab-panel<?php echo ( 'upgrade' !== $current_tab ) ? ' d-none' : ''; ?>"
				 id="panelUpgrade"
				 role="tabpanel">

				<?php /* ── Hero ── */ ?>
				<div class="text-center py-4 py-md-5">
					<div class="mb-3">
						<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-3 py-2 fs-6">
							<i class="bi bi-stars me-1" aria-hidden="true"></i> <?php esc_html_e( 'Premium', 'smarttr-address' ); ?>
						</span>
					</div>
					<h2 class="display-6 fw-bold text-body-emphasis mb-2"><?php esc_html_e( 'SmartTR Address — Premium', 'smarttr-address' ); ?></h2>
					<p class="lead text-muted col-12 col-sm-10 col-md-8 mx-auto mb-4"><?php esc_html_e( 'Unlock the full Turkish address experience for your WooCommerce store.', 'smarttr-address' ); ?></p>
				</div>

				<?php /* ── Feature grid ── */ ?>
				<div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-3 mb-4">
					<div class="col">
						<div class="d-flex align-items-start gap-3 p-3 rounded-3 bg-body-tertiary h-100">
							<i class="bi bi-pin-map-fill text-primary fs-4 flex-shrink-0" aria-hidden="true"></i>
							<div>
								<h6 class="fw-semibold mb-1"><?php esc_html_e( 'Neighborhood Dropdown', 'smarttr-address' ); ?></h6>
								<p class="small text-muted mb-0"><?php esc_html_e( 'AJAX-powered select with 70,000+ neighborhood entries.', 'smarttr-address' ); ?></p>
							</div>
						</div>
					</div>
					<div class="col">
						<div class="d-flex align-items-start gap-3 p-3 rounded-3 bg-body-tertiary h-100">
							<i class="bi bi-mailbox2 text-primary fs-4 flex-shrink-0" aria-hidden="true"></i>
							<div>
								<h6 class="fw-semibold mb-1"><?php esc_html_e( 'Postal Code Auto-Fill', 'smarttr-address' ); ?></h6>
								<p class="small text-muted mb-0"><?php esc_html_e( 'Postal code populates automatically on neighborhood selection.', 'smarttr-address' ); ?></p>
							</div>
						</div>
					</div>
					<div class="col">
						<div class="d-flex align-items-start gap-3 p-3 rounded-3 bg-body-tertiary h-100">
							<i class="bi bi-sliders text-primary fs-4 flex-shrink-0" aria-hidden="true"></i>
							<div>
								<h6 class="fw-semibold mb-1"><?php esc_html_e( 'Field Editor', 'smarttr-address' ); ?></h6>
								<p class="small text-muted mb-0"><?php esc_html_e( 'Customize labels, visibility, and drag-and-drop ordering.', 'smarttr-address' ); ?></p>
							</div>
						</div>
					</div>
					<div class="col">
						<div class="d-flex align-items-start gap-3 p-3 rounded-3 bg-body-tertiary h-100">
							<i class="bi bi-ui-checks-grid text-primary fs-4 flex-shrink-0" aria-hidden="true"></i>
							<div>
								<h6 class="fw-semibold mb-1"><?php esc_html_e( 'Custom Checkout Fields', 'smarttr-address' ); ?></h6>
								<p class="small text-muted mb-0"><?php esc_html_e( 'Add text, select, file, date, and more field types.', 'smarttr-address' ); ?></p>
							</div>
						</div>
					</div>
					<div class="col">
						<div class="d-flex align-items-start gap-3 p-3 rounded-3 bg-body-tertiary h-100">
							<i class="bi bi-arrow-repeat text-primary fs-4 flex-shrink-0" aria-hidden="true"></i>
							<div>
								<h6 class="fw-semibold mb-1"><?php esc_html_e( 'Automatic Data Updates', 'smarttr-address' ); ?></h6>
								<p class="small text-muted mb-0"><?php esc_html_e( 'Recurring sync — weekly, monthly, or bi-monthly.', 'smarttr-address' ); ?></p>
							</div>
						</div>
					</div>
					<div class="col">
						<div class="d-flex align-items-start gap-3 p-3 rounded-3 bg-body-tertiary h-100">
							<i class="bi bi-cloud-arrow-down-fill text-primary fs-4 flex-shrink-0" aria-hidden="true"></i>
							<div>
								<h6 class="fw-semibold mb-1"><?php esc_html_e( 'Unlimited Manual Syncs', 'smarttr-address' ); ?></h6>
								<p class="small text-muted mb-0"><?php esc_html_e( 'No cooldown — re-import address data whenever you need.', 'smarttr-address' ); ?></p>
							</div>
						</div>
					</div>
					<div class="col">
						<div class="d-flex align-items-start gap-3 p-3 rounded-3 bg-body-tertiary h-100">
							<i class="bi bi-headset text-primary fs-4 flex-shrink-0" aria-hidden="true"></i>
							<div>
								<h6 class="fw-semibold mb-1"><?php esc_html_e( 'Priority Support', 'smarttr-address' ); ?></h6>
								<p class="small text-muted mb-0"><?php esc_html_e( 'Dedicated email support with faster response times.', 'smarttr-address' ); ?></p>
							</div>
						</div>
					</div>
				</div>

				<?php /* ── CTA ── */ ?>
				<div class="text-center py-4 border-top">
					<p class="text-muted small mb-3"><?php esc_html_e( 'One-time purchase. Lifetime updates. 14-day money-back guarantee.', 'smarttr-address' ); ?></p>
					<a href="https://cecom.in/smarttr-address"
						class="btn btn-warning btn-lg rounded-pill px-5 fw-semibold d-inline-flex align-items-center gap-2"
						target="_blank" rel="noopener">
						<i class="bi bi-stars" aria-hidden="true"></i>
						<?php esc_html_e( 'Get Premium', 'smarttr-address' ); ?>
					</a>
				</div>

			</div><!-- /#panelUpgrade -->

		</div><!-- /.card-body -->
	</div><!-- /.card (content area) -->
</div><!-- /.d-flex (layout) -->

<?php /* ── Bootstrap Confirmation Modal ──────────────── */ ?>
<div class="modal fade"
	 id="cecomsmaradModal"
	 tabindex="-1"
	 aria-labelledby="cecomsmaradModalTitle"
	 aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content rounded-4 shadow">
			<div class="modal-header border-bottom-0">
				<h5 class="modal-title" id="cecomsmaradModalTitle"></h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal"
						aria-label="<?php esc_attr_e( 'Close', 'smarttr-address' ); ?>"></button>
			</div>
			<div class="modal-body py-0">
				<p id="cecomsmaradModalMessage" class="text-muted"></p>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-danger rounded-pill" id="cecomsmaradModalConfirm">
					<?php esc_html_e( 'Confirm', 'smarttr-address' ); ?>
				</button>
				<button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">
					<?php esc_html_e( 'Cancel', 'smarttr-address' ); ?>
				</button>
			</div>
		</div>
	</div>
</div>

<?php /* ── Bootstrap Toast Container ─────────────────── */ ?>
<div class="toast-container position-fixed top-0 end-0 p-3" id="cecomsmaradToastContainer" style="z-index:99999;">
	<div class="toast"
		 id="cecomsmaradToast"
		 role="alert"
		 aria-live="assertive"
		 aria-atomic="true">
		<div class="toast-header">
			<i class="bi me-2" id="cecomsmaradToastIcon" aria-hidden="true"></i>
			<strong class="me-auto" id="cecomsmaradToastTitle"><?php esc_html_e( 'Notice', 'smarttr-address' ); ?></strong>
			<button type="button" class="btn-close" data-bs-dismiss="toast"
					aria-label="<?php esc_attr_e( 'Close', 'smarttr-address' ); ?>"></button>
		</div>
		<div class="toast-body" id="cecomsmaradToastBody"></div>
	</div>
</div>

</div><!-- /#cecomsmaradWrap -->
