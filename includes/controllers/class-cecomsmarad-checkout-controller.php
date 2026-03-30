<?php
/**
 * Classic checkout controller — field injection, script enqueue, state registration.
 *
 * @package CecomsmaradAddress
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Cecomsmarad_Checkout_Controller
 *
 * Hooks into WooCommerce classic checkout to replace default address
 * fields with cascading Province → District dropdowns
 * when Turkey is the selected country.
 */
class Cecomsmarad_Checkout_Controller {

	/**
	 * Register all hooks.
	 */
	public function __construct() {
		if ( '0' === get_option( 'cecomsmarad_enabled', '1' ) ) {
			return;
		}

		add_filter( 'woocommerce_states', array( $this, 'register_provinces_as_states' ), 20 );
		add_filter( 'woocommerce_checkout_fields', array( $this, 'modify_checkout_fields' ), 20 );
		add_filter( 'woocommerce_get_country_locale', array( $this, 'set_field_locale' ), 20 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_filter( 'body_class', array( $this, 'add_noscript_body_class' ) );
		add_action( 'woocommerce_after_checkout_billing_form', array( $this, 'render_noscript_billing_fields' ) );
		add_action( 'woocommerce_after_checkout_shipping_form', array( $this, 'render_noscript_shipping_fields' ) );

		// Custom renderers: fix required mark on radio/checkbox option labels,
		// and add multi-select checkbox support.
		add_filter( 'woocommerce_form_field_file',     array( $this, 'render_file_form_field' ),     10, 4 );
		add_filter( 'woocommerce_form_field_radio',    array( $this, 'render_radio_form_field' ),    10, 4 );
		add_filter( 'woocommerce_form_field_checkbox', array( $this, 'render_checkbox_form_field' ), 10, 4 );
	}

	/**
	 * Register Turkish provinces as WooCommerce "states".
	 *
	 * @param array<string, array<string, string>> $states Existing states keyed by country code.
	 * @return array<string, array<string, string>> Modified states array.
	 */
	public function register_provinces_as_states( array $states ): array {
		$provinces = Cecomsmarad_Province::get_all();
		$tr_states = array();

		foreach ( $provinces as $province ) {
			$tr_states[ $province->code ] = $province->name;
		}

		$states['TR'] = $tr_states;

		return $states;
	}

	/**
	 * Modify checkout fields.
	 *
	 * Adds Turkish cascade dropdowns (Province → District) for the billing
	 * and shipping address when the customer selects Turkey. All other fields
	 * remain at WooCommerce defaults.
	 *
	 * @param array<string, array<string, array<string, mixed>>> $fields Checkout fields.
	 * @return array<string, array<string, array<string, mixed>>> Modified fields.
	 */
	public function modify_checkout_fields( array $fields ): array {
		foreach ( array( 'billing', 'shipping' ) as $type ) {
			if ( $this->is_customer_country_turkey( $type ) ) {
				$cascade_settings = $this->build_default_cascade_settings( $fields, $type );
				$fields           = $this->apply_turkish_cascade_overlay( $fields, $type, $cascade_settings );
			}
		}

		return $fields;
	}

	/**
	 * Build minimal cascade settings from WooCommerce's current field values.
	 *
	 * Provides the cascade overlay with WC's own labels, required flags,
	 * and priorities. Cascade fields are always full-width because
	 * they occupy individual rows, unlike WC's paired address_2 / city layout.
	 *
	 * @param array<string, array<string, array<string, mixed>>> $fields WC fields.
	 * @param string                                             $type   'billing' or 'shipping'.
	 * @return array<string, array<string, mixed>> Minimal settings keyed by field key.
	 */
	private function build_default_cascade_settings( array $fields, string $type ): array {
		$defaults = array();

		// Cascade-appropriate priorities: appear in correct step order after
		// the province/state field which WC positions at priority 80 for Turkey.
		$cascade_priority = array(
			'city'      => 81,
			'address_2' => 82,
			'postcode'  => 83,
		);

		foreach ( array( 'city', 'address_2', 'postcode' ) as $field ) {
			$key      = $type . '_' . $field;
			$wc_field = $fields[ $type ][ $key ] ?? array();

			// Always full-width: WC defaults address_2 to form-row-first and
			// city to form-row-last (a side-by-side pair), but as cascade steps
			// each field occupies its own row.
			$defaults[ $key ] = array(
				'label'       => $wc_field['label'] ?? '',
				'placeholder' => $wc_field['placeholder'] ?? '',
				'priority'    => $cascade_priority[ $field ] ?? 81,
				'required'    => $wc_field['required'] ?? false,
				'class'       => 'form-row-wide',
				'visibility'  => 'visible',
				'description' => '',
			);
		}

		return $defaults;
	}

	/**
	 * Apply Turkish cascade overlay for city, address_2, and postcode.
	 *
	 * Only runs when the customer's country is Turkey. Overrides city and
	 * address_2 to type=select with cascade classes, and adds postcode
	 * readonly class. The JS handles dynamic conversion on country switch.
	 *
	 * @param array<string, array<string, array<string, mixed>>> $fields   Checkout fields.
	 * @param string                                             $type     'billing' or 'shipping'.
	 * @param array<string, array<string, mixed>>                $settings Field settings.
	 * @return array<string, array<string, array<string, mixed>>> Modified fields.
	 */
	private function apply_turkish_cascade_overlay( array $fields, string $type, array $settings ): array {
		$city_key     = $type . '_city';
		$address2_key = $type . '_address_2';
		$postcode_key = $type . '_postcode';

		// District — override the existing city field.
		if ( isset( $settings[ $city_key ] ) && 'unset' !== ( $settings[ $city_key ]['visibility'] ?? 'visible' ) ) {
			$s          = $settings[ $city_key ];
			$is_hidden  = 'hidden' === ( $s['visibility'] ?? 'visible' );
			$classes    = array( $this->strip_wc_behaviour_classes( $s['class'] ?? 'form-row-wide' ), 'cecomsmarad-field', 'cecomsmarad-district' );
			if ( $is_hidden ) {
				$classes[] = 'cecomsmarad-field-hidden';
			}
			$overlay = array(
				'type'        => 'select',
				'label'       => $s['label'],
				'placeholder' => $s['placeholder'],
				'priority'    => $s['priority'],
				'required'    => $is_hidden ? false : $s['required'],
				'class'       => $classes,
				'input_class' => array( 'cecomsmarad-select' ),
				'options'     => array( '' => $s['placeholder'] ),
			);
			if ( ! empty( $s['description'] ) ) {
				$overlay['description'] = $s['description'];
			}
			if ( ! empty( $s['label_class'] ) ) {
				$overlay['label_class'] = array_filter( explode( ' ', $s['label_class'] ) );
			}
			$fields[ $type ][ $city_key ] = $overlay;
		}

		// Postal code — keep as text, add cascade class.
		if ( isset( $settings[ $postcode_key ] ) && 'unset' !== ( $settings[ $postcode_key ]['visibility'] ?? 'visible' ) ) {
			$s          = $settings[ $postcode_key ];
			$is_hidden  = 'hidden' === ( $s['visibility'] ?? 'visible' );
			$classes    = array( $this->strip_wc_behaviour_classes( $s['class'] ?? 'form-row-wide' ), 'cecomsmarad-field', 'cecomsmarad-postcode' );
			if ( $is_hidden ) {
				$classes[] = 'cecomsmarad-field-hidden';
			}
			$postcode_merge = array(
				'label'       => $s['label'],
				'placeholder' => $s['placeholder'],
				'priority'    => $s['priority'],
				'required'    => $is_hidden ? false : $s['required'],
				'class'       => $classes,
			);
			if ( ! empty( $s['label_class'] ) ) {
				$postcode_merge['label_class'] = array_filter( explode( ' ', $s['label_class'] ) );
			}
			$fields[ $type ][ $postcode_key ] = array_merge(
				$fields[ $type ][ $postcode_key ] ?? array(),
				$postcode_merge
			);
		}

		return $fields;
	}

	/**
	 * Strip WooCommerce behavioural CSS classes from a class string.
	 *
	 * `address-field` causes WC's country-select.js to monitor the field
	 * for changes and fire `update_checkout`, which re-initialises the
	 * cascade and wipes the user's selection.  `update_totals_on_change`
	 * triggers order total recalculation on every change.  Neither class
	 * belongs on cascade-managed fields because our JS handles them.
	 *
	 * @param string $css_classes Space-separated CSS class string.
	 * @return string Cleaned class string.
	 */
	private function strip_wc_behaviour_classes( string $css_classes ): string {
		$cleaned = preg_replace( '/\b(address-field|update_totals_on_change)\b/', '', $css_classes );
		return trim( preg_replace( '/\s+/', ' ', $cleaned ) );
	}

	/**
	 * Check if the customer's current country is Turkey for a given address type.
	 *
	 * @param string $type 'billing' or 'shipping'.
	 * @return bool True when the customer's country is TR.
	 */
	private function is_customer_country_turkey( string $type ): bool {
		if ( ! function_exists( 'WC' ) || ! WC()->customer ) {
			return false;
		}

		$country = 'shipping' === $type
			? WC()->customer->get_shipping_country()
			: WC()->customer->get_billing_country();

		return 'TR' === $country;
	}

	/**
	 * Inject admin field settings into WooCommerce country locale.
	 *
	 * WC's country-select.js applies locale overrides client-side AFTER our
	 * PHP checkout-fields filter has run. If the locale doesn't include our
	 * admin values, WC's defaults overwrite them on every country change.
	 *
	 * We merge admin-configured label, placeholder, priority, and required
	 * into the default locale (applies to all countries), then add
	 * TR-specific overrides (address_2 visible).
	 *
	 * Note: email and phone are not locale-managed fields in WC — they are
	 * handled by the PHP filter only.
	 *
	 * @param array<string, array<string, array<string, mixed>>> $locale Country locale configs.
	 * @return array<string, array<string, array<string, mixed>>> Modified locale.
	 */
	public function set_field_locale( array $locale ): array {
		// Always append cascade classes to TR locale so WC's locale JS
		// preserves them on every country switch.
		// When no class has been set, default to form-row-wide so
		// WC's country-select.js does not strip the full-width class.
		if ( ! isset( $locale['TR'] ) ) {
			$locale['TR'] = array();
		}
		$cascade_extra = array(
			'city'     => array( 'cecomsmarad-field', 'cecomsmarad-district' ),
			'postcode' => array( 'cecomsmarad-field', 'cecomsmarad-postcode' ),
		);
		foreach ( $cascade_extra as $field => $extra ) {
			$base                            = ! empty( $locale['TR'][ $field ]['class'] )
				? (array) $locale['TR'][ $field ]['class']
				: array( 'form-row-wide' );
			$locale['TR'][ $field ]['class'] = array_merge( $base, $extra );
		}

		return $locale;
	}

	/**
	 * Enqueue checkout scripts and styles.
	 *
	 * Loads only on the WooCommerce checkout page. Inlines all provinces
	 * and districts (~12 KB) via wp_localize_script so province → district
	 * filtering happens client-side without AJAX.
	 *
	 * @return void
	 */
	public function enqueue_scripts(): void {
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
			return;
		}

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		$css_file = CECOMSMARAD_PLUGIN_DIR . 'assets/css/cecomsmarad-checkout' . $suffix . '.css';
		$js_file  = CECOMSMARAD_PLUGIN_DIR . 'assets/js/cecomsmarad-checkout' . $suffix . '.js';

		wp_enqueue_style(
			'cecomsmarad-checkout-styles',
			CECOMSMARAD_PLUGIN_URL . 'assets/css/cecomsmarad-checkout' . $suffix . '.css',
			array(),
			(string) filemtime( $css_file )
		);

		wp_enqueue_script(
			'cecomsmarad-checkout',
			CECOMSMARAD_PLUGIN_URL . 'assets/js/cecomsmarad-checkout' . $suffix . '.js',
			array( 'jquery', 'selectWoo', 'wc-checkout' ),
			(string) filemtime( $js_file ),
			true
		);

		wp_localize_script( 'cecomsmarad-checkout', 'cecomsmaradData', $this->get_localized_data() );

		// Noscript fallback: when JS is disabled, the PHP body_class filter keeps
		// 'cecomsmarad-no-js' on <body> (JS removes it on load). Scope the hide rules
		// to that class so they never fire when JS is active.
		wp_add_inline_style(
			'cecomsmarad-checkout-styles',
			'.cecomsmarad-no-js .cecomsmarad-district select,' .
			'.cecomsmarad-no-js .cecomsmarad-district .select2-container{display:none !important;}'
		);
	}

	/**
	 * Build the localized data object for the checkout script.
	 *
	 * @return array{provinces: array, districts: array, ajaxUrl: string, i18n: array}
	 */
	private function get_localized_data(): array {
		$provinces     = Cecomsmarad_Province::get_all();
		$provinces_out = array();
		$districts_out = array();

		foreach ( $provinces as $province ) {
			$provinces_out[] = array(
				'id'   => (int) $province->id,
				'code' => $province->code,
				'name' => $province->name,
			);

			$districts     = Cecomsmarad_District::get_by_province( $province->code );
			$district_list = array();

			foreach ( $districts as $district ) {
				$district_list[] = array(
					'id'   => (int) $district->id,
					'name' => $district->name,
				);
			}

			$districts_out[ $province->code ] = $district_list;
		}

		$hidden_fields      = array();
		$clear_fields       = array();
		$file_fields        = array();
		$turkey_only_fields = array( 'billing' => array(), 'shipping' => array() );

		// Pre-saved cascade field values so JS can restore selections on page load
		// (e.g. after a validation error or browser back-navigation).
		$saved_values  = array();
		$checkout      = WC()->checkout();
		foreach ( array( 'billing', 'shipping' ) as $addr_type ) {
			foreach ( array( 'city', 'address_2', 'postcode' ) as $field ) {
				$fk  = $addr_type . '_' . $field;
				$val = $checkout->get_value( $fk );
				if ( $val ) {
					$saved_values[ $fk ] = $val;
				}
			}
		}

		/**
		 * Filter the data object localized to the checkout script.
		 *
		 * @param array $data Localized data passed to cecomsmaradData JS global.
		 */
		return apply_filters(
			'cecomsmarad_localized_data',
			array(
				'provinces'        => $provinces_out,
				'districts'        => $districts_out,
				'savedValues'      => $saved_values,
				'turkeyOnlyFields' => $turkey_only_fields,
				'hiddenFields'     => $hidden_fields,
				'clearFields'      => $clear_fields,
				'fileFields'       => $file_fields,
				'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
				'i18n'             => array(
					'selectProvince'       => __( 'Select province', 'smarttr-address' ),
					'selectDistrict'       => __( 'Select district', 'smarttr-address' ),
					'noResults'            => __( 'No results found', 'smarttr-address' ),
				),
			)
		);
	}

	/**
	 * Add 'cecomsmarad-no-js' body class on checkout page.
	 *
	 * JS will replace it with 'cecomsmarad-js-ready' on load.
	 * When JS is disabled the class persists, triggering noscript CSS.
	 *
	 * @param string[] $classes Body CSS classes.
	 * @return string[] Modified classes.
	 */
	public function add_noscript_body_class( array $classes ): array {
		if ( function_exists( 'is_checkout' ) && is_checkout() ) {
			$classes[] = 'cecomsmarad-no-js';
		}
		return $classes;
	}

	/**
	 * Render noscript fallback text inputs for billing fields.
	 *
	 * Inside `<noscript>`, these text inputs replace the empty
	 * selects so users can type district manually.
	 *
	 * @return void
	 */
	public function render_noscript_billing_fields(): void {
		$this->render_noscript_fields( 'billing' );
	}

	/**
	 * Render noscript fallback text inputs for shipping fields.
	 *
	 * @return void
	 */
	public function render_noscript_shipping_fields(): void {
		$this->render_noscript_fields( 'shipping' );
	}

	/**
	 * Render noscript fallback text inputs for a given address type.
	 *
	 * @param string $type 'billing' or 'shipping'.
	 * @return void
	 */
	private function render_noscript_fields( string $type ): void {
		$settings = Cecomsmarad_Settings::get_fields();
		$city_key = $type . '_city';
		$hood_key = $type . '_address_2';

		$city_label = $settings[ $city_key ]['label'] ?? __( 'District', 'smarttr-address' );
		$hood_label = $settings[ $hood_key ]['label'] ?? __( 'Neighborhood', 'smarttr-address' );
		?>
		<noscript>
			<p class="form-row form-row-wide cecomsmarad-noscript-field">
				<label for="cecomsmarad_noscript_<?php echo esc_attr( $city_key ); ?>">
					<?php echo esc_html( $city_label ); ?>
				</label>
				<input type="text"
					id="cecomsmarad_noscript_<?php echo esc_attr( $city_key ); ?>"
					name="<?php echo esc_attr( $city_key ); ?>"
					class="input-text"
					placeholder="<?php echo esc_attr( $city_label ); ?>" />
			</p>
			<p class="form-row form-row-wide cecomsmarad-noscript-field">
				<label for="cecomsmarad_noscript_<?php echo esc_attr( $hood_key ); ?>">
					<?php echo esc_html( $hood_label ); ?>
				</label>
				<input type="text"
					id="cecomsmarad_noscript_<?php echo esc_attr( $hood_key ); ?>"
					name="<?php echo esc_attr( $hood_key ); ?>"
					class="input-text"
					placeholder="<?php echo esc_attr( $hood_label ); ?>" />
			</p>
		</noscript>
		<?php
	}

	/**
	 * Render a file-type checkout field.
	 *
	 * WooCommerce calls apply_filters( 'woocommerce_form_field_{type}', '', $key, $args, $value )
	 * for field types it does not handle natively. This method returns the
	 * HTML string for <input type="file"> so it appears on the checkout form.
	 *
	 * A companion hidden input (`{key}_url`) is rendered alongside the file
	 * input. The pre-upload JS fills this hidden field with the uploaded
	 * file's URL before WooCommerce submits the checkout AJAX request.
	 *
	 * @param string $field Empty string passed by WooCommerce (unused).
	 * @param string $key   Field key (HTML name/id attribute).
	 * @param array  $args  Field arguments from woocommerce_checkout_fields.
	 * @param mixed  $value Current field value (unused for file inputs).
	 * @return string Rendered HTML.
	 */
	public function render_file_form_field( string $field, string $key, array $args, $value ): string {
		$label              = $args['label'] ?? '';
		$required           = ! empty( $args['required'] );
		$description        = $args['description'] ?? '';
		$allowed_extensions = $args['allowed_extensions'] ?? '';
		$class_arr   = is_array( $args['class'] ?? null ) ? $args['class'] : array_filter( explode( ' ', $args['class'] ?? '' ) );
		$label_class = is_array( $args['label_class'] ?? null ) ? $args['label_class'] : array_filter( explode( ' ', $args['label_class'] ?? '' ) );
		$priority    = $args['priority'] ?? '';

		$wrapper_classes   = array_merge( array( 'form-row' ), $class_arr );
		$wrapper_classes[] = 'cecomsmarad-file-field';
		if ( $required ) {
			$wrapper_classes[] = 'validate-required';
		}

		$required_mark = $required
			? ' <abbr class="required" title="' . esc_attr__( 'required', 'smarttr-address' ) . '">*</abbr>'
			: '';

		$html  = '<p class="' . esc_attr( implode( ' ', $wrapper_classes ) ) . '"';
		$html .= ' id="' . esc_attr( $key ) . '_field"';
		if ( $priority ) {
			$html .= ' data-priority="' . esc_attr( (string) $priority ) . '"';
		}
		$html .= '>';

		if ( $label ) {
			$html .= '<label for="' . esc_attr( $key ) . '"';
			if ( $label_class ) {
				$html .= ' class="' . esc_attr( implode( ' ', $label_class ) ) . '"';
			}
			$html .= '>' . esc_html( $label ) . $required_mark . '</label>';
		}

		// Visible file input — the pre-upload JS handles the actual upload.
		$html .= '<input type="file"';
		$html .= ' id="' . esc_attr( $key ) . '"';
		$html .= ' name="' . esc_attr( $key ) . '"';
		$html .= ' class="input-text"';
		if ( $required ) {
			$html .= ' aria-required="true"';
		}
		if ( $allowed_extensions ) {
			$ext_arr = array_filter( array_map( 'trim', explode( ',', $allowed_extensions ) ) );
			$accept  = implode( ',', array_map( static function ( $e ) { return '.' . $e; }, $ext_arr ) );
			$html   .= ' accept="' . esc_attr( $accept ) . '"';
		}
		$html .= ' />';

		// Hidden companion input that stores the uploaded file URL.
		$html .= '<input type="hidden"';
		$html .= ' id="' . esc_attr( $key ) . '_url"';
		$html .= ' name="' . esc_attr( $key ) . '_url"';
		$html .= ' value=""';
		$html .= ' />';

		// Upload status span — filled by JS.
		$html .= '<span class="cecomsmarad-file-status" id="cecomsmarad-file-status-' . esc_attr( $key ) . '" aria-live="polite"></span>';

		if ( $description ) {
			$html .= '<span class="description">' . esc_html( $description ) . '</span>';
		}

		if ( $allowed_extensions ) {
			$ext_list = implode( ', ', array_filter( array_map( 'trim', explode( ',', $allowed_extensions ) ) ) );
			$html    .= '<span class="cecomsmarad-file-hint">'
				/* translators: %s: comma-separated list of allowed file extensions */
				. esc_html( sprintf( __( 'Allowed file types: %s', 'smarttr-address' ), $ext_list ) )
				. '</span>';
		}

		$html .= '</p>';

		/**
		 * Filter the rendered HTML for a file-type checkout field.
		 *
		 * @param string $html The generated field HTML.
		 * @param string $key  The field key.
		 * @param array  $args The field arguments.
		 */
		return apply_filters( 'cecomsmarad_file_form_field_html', $html, $key, $args );
	}

	/**
	 * Render a radio-type checkout field.
	 *
	 * Replaces WooCommerce's native radio renderer so the required/optional
	 * mark appears only on the group label, not on each individual option label.
	 *
	 * @param string $field WC-generated HTML (replaced entirely).
	 * @param string $key   Field key.
	 * @param array  $args  Field arguments.
	 * @param mixed  $value Current field value.
	 * @return string Rendered HTML.
	 */
	public function render_radio_form_field( string $field, string $key, array $args, $value ): string {
		$options = $args['options'] ?? array();

		// Fall back to WC's rendering for fields without custom options.
		if ( empty( $options ) ) {
			return $field;
		}

		return $this->render_option_group_field( 'radio', $field, $key, $args, $value );
	}

	/**
	 * Render a multi-select checkbox field.
	 *
	 * Renders one checkbox per option (name="{key}[]").  The selected values
	 * are stored as a comma-separated string in order meta.
	 *
	 * @param string $field WC-generated HTML (replaced entirely).
	 * @param string $key   Field key.
	 * @param array  $args  Field arguments.
	 * @param mixed  $value Comma-separated string of currently selected keys.
	 * @return string Rendered HTML.
	 */
	public function render_checkbox_form_field( string $field, string $key, array $args, $value ): string {
		$options = $args['options'] ?? array();

		// Fall back to WC's native single-checkbox for fields without options.
		if ( empty( $options ) ) {
			return $field;
		}

		return $this->render_option_group_field( 'checkbox', $field, $key, $args, $value );
	}

	/**
	 * Shared renderer for radio and multi-checkbox option groups.
	 *
	 * @param string $input_type 'radio' or 'checkbox'.
	 * @param string $field      WC-generated HTML (unused).
	 * @param string $key        Field key.
	 * @param array  $args       Field arguments.
	 * @param mixed  $value      Current value: string for radio, comma-separated for checkbox.
	 * @return string Rendered HTML.
	 */
	private function render_option_group_field( string $input_type, string $field, string $key, array $args, $value ): string {
		$label       = $args['label'] ?? '';
		$required    = ! empty( $args['required'] );
		$description = $args['description'] ?? '';
		$options     = $args['options'] ?? array();
		$class_arr   = is_array( $args['class'] ?? null ) ? $args['class'] : array_filter( explode( ' ', $args['class'] ?? '' ) );
		$label_class = is_array( $args['label_class'] ?? null ) ? $args['label_class'] : array_filter( explode( ' ', $args['label_class'] ?? '' ) );
		$priority    = $args['priority'] ?? '';

		// For multi-checkbox: value is comma-separated list of selected option keys.
		// For radio and single-checkbox: plain string comparison is used per option.
		$is_multiple = 'checkbox' === $input_type && ! empty( $args['multiple'] );
		$selected    = $is_multiple
			? array_filter( explode( ',', (string) $value ) )
			: array();

		$wrapper_classes = array_merge( array( 'form-row' ), $class_arr );
		if ( $required ) {
			$wrapper_classes[] = 'validate-required';
		}

		$required_mark = $required
			? ' <abbr class="required" title="' . esc_attr__( 'required', 'smarttr-address' ) . '">*</abbr>'
			: '';

		$html  = '<p class="' . esc_attr( implode( ' ', $wrapper_classes ) ) . '"';
		$html .= ' id="' . esc_attr( $key ) . '_field"';
		if ( $priority ) {
			$html .= ' data-priority="' . esc_attr( (string) $priority ) . '"';
		}
		$html .= '>';

		// Required mark on the group label ONLY — not repeated on each option.
		if ( $label ) {
			$html .= '<label';
			if ( $label_class ) {
				$html .= ' class="' . esc_attr( implode( ' ', $label_class ) ) . '"';
			}
			$html .= '>' . esc_html( $label ) . $required_mark . '</label>';
		}

		// Input name: array notation only for multiple-checkbox; plain for radio and single-checkbox.
		$is_multiple = 'checkbox' === $input_type && ! empty( $args['multiple'] );
		$input_name  = $is_multiple ? $key . '[]' : $key;

		$html .= '<span class="woocommerce-input-wrapper">';
		foreach ( $options as $option_key => $option_text ) {
			$input_id = $key . '_' . $option_key;

			if ( $is_multiple ) {
				$is_checked = in_array( (string) $option_key, $selected, true );
			} else {
				$is_checked = ( (string) $value === (string) $option_key );
			}

			$html .= '<label class="' . esc_attr( $input_type ) . '" for="' . esc_attr( $input_id ) . '">';
			$html .= '<input type="' . esc_attr( $input_type ) . '"';
			$html .= ' id="' . esc_attr( $input_id ) . '"';
			$html .= ' name="' . esc_attr( $input_name ) . '"';
			$html .= ' value="' . esc_attr( $option_key ) . '"';
			if ( $required ) {
				$html .= ' aria-required="true"';
			}
			if ( $is_checked ) {
				$html .= ' checked="checked"';
			}
			$html .= ' /> ' . esc_html( $option_text );
			$html .= '</label>';
		}
		$html .= '</span>';

		if ( $description ) {
			$html .= '<span class="description">' . esc_html( $description ) . '</span>';
		}

		$html .= '</p>';

		return $html;
	}
}
