<?php
/**
 * Settings model — checkout field configuration CRUD.
 *
 * @package CecomsmaradAddress
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Cecomsmarad_Settings
 *
 * Manages per-field checkout configuration (type, label, description,
 * placeholder, class, required, clear, label_class, options, priority)
 * stored in wp_options under the key `cecomsmarad_field_settings`.
 */
class Cecomsmarad_Settings {

	/**
	 * Option key for field configuration stored in wp_options.
	 *
	 * @var string
	 */
	private const OPTION_KEY = 'cecomsmarad_field_settings';

	/**
	 * Allowed field keys that this model manages.
	 *
	 * @var string[]
	 */
	private const MANAGED_FIELDS = array(
		'billing_first_name',
		'billing_last_name',
		'billing_company',
		'billing_country',
		'billing_address_1',
		'billing_address_2',
		'billing_city',
		'billing_postcode',
		'billing_state',
		'billing_email',
		'billing_phone',
		'shipping_first_name',
		'shipping_last_name',
		'shipping_company',
		'shipping_country',
		'shipping_address_1',
		'shipping_address_2',
		'shipping_city',
		'shipping_postcode',
		'shipping_state',
	);

	/**
	 * Allowed field type values.
	 *
	 * @var string[]
	 */
	private const ALLOWED_TYPES = array(
		'text',
		'select',
		'email',
		'tel',
		'password',
		'textarea',
		'country',
		'state',
		'radio',
		'checkbox',
		'file',
		'date',
		'datetime-local',
	);

	/**
	 * Allowed visibility values.
	 *
	 * @var string[]
	 */
	private const ALLOWED_VISIBILITY = array( 'visible', 'hidden', 'unset' );

	/**
	 * Fields that cannot be unset (removed) from checkout.
	 *
	 * These are essential WooCommerce fields: country drives locale/tax/shipping,
	 * billing_email is required for order communication.
	 *
	 * @var string[]
	 */
	public const NO_UNSET_FIELDS = array(
		'billing_country',
		'shipping_country',
		'billing_email',
	);

	/**
	 * Fields that must always remain required.
	 *
	 * Country fields drive locale, tax, and shipping calculations; making
	 * them optional would break WooCommerce core behaviour.
	 *
	 * @var string[]
	 */
	public const ALWAYS_REQUIRED_FIELDS = array(
		'billing_country',
		'shipping_country',
	);

	/**
	 * Get all field configurations.
	 *
	 * Returns saved settings merged with defaults so every field always
	 * has a complete set of properties. Includes migration from legacy
	 * `css_class` / `visible` properties.
	 *
	 * @return array<string, array<string, mixed>> Field key => properties.
	 */
	public static function get_fields(): array {
		$defaults = self::get_default_fields();
		$saved    = get_option( self::OPTION_KEY, array() );

		if ( ! is_array( $saved ) ) {
			return $defaults;
		}

		$fields = array();
		foreach ( $defaults as $key => $default_props ) {
			if ( isset( $saved[ $key ] ) && is_array( $saved[ $key ] ) ) {
				$record = $saved[ $key ];

				// Migration: css_class → class.
				if ( isset( $record['css_class'] ) && ! isset( $record['class'] ) ) {
					$record['class'] = $record['css_class'];
				}
				unset( $record['css_class'] );

				// Migration: drop legacy `visible` property.
				unset( $record['visible'] );

				$fields[ $key ] = array_merge( $default_props, $record );
			} else {
				$fields[ $key ] = $default_props;
			}
		}

		return $fields;
	}

	/**
	 * Update a single field's configuration.
	 *
	 * Only known field keys and allowed property names are persisted.
	 *
	 * @param string               $key   Field key (e.g. 'billing_state').
	 * @param array<string, mixed> $props Properties to update.
	 * @return bool True on success, false on invalid key or save failure.
	 */
	public static function update_field( string $key, array $props ): bool {
		if ( ! in_array( $key, self::MANAGED_FIELDS, true ) ) {
			return false;
		}

		$sanitized = self::sanitize_props( $props );

		// Prevent unsetting protected fields.
		if ( isset( $sanitized['visibility'] ) && 'unset' === $sanitized['visibility']
			&& in_array( $key, self::NO_UNSET_FIELDS, true ) ) {
			$sanitized['visibility'] = 'visible';
		}

		// Country fields must always be required.
		if ( in_array( $key, self::ALWAYS_REQUIRED_FIELDS, true ) ) {
			$sanitized['required'] = true;
		}

		if ( empty( $sanitized ) ) {
			return false;
		}

		$fields         = self::get_fields();
		$fields[ $key ] = array_merge( $fields[ $key ], $sanitized );

		return update_option( self::OPTION_KEY, $fields, false );
	}

	/**
	 * Bulk-update multiple fields in a single option write.
	 *
	 * Each entry in the input array is sanitized and merged. Returns the
	 * number of fields that were accepted (valid key + non-empty sanitized
	 * props), regardless of whether the option value actually changed.
	 *
	 * @param array<string, array<string, mixed>> $updates Field key => raw properties.
	 * @return int Number of fields successfully processed.
	 */
	public static function update_fields_bulk( array $updates ): int {
		$fields = self::get_fields();
		$count  = 0;

		foreach ( $updates as $key => $props ) {
			if ( ! in_array( $key, self::MANAGED_FIELDS, true ) ) {
				continue;
			}

			if ( ! is_array( $props ) ) {
				continue;
			}

			$sanitized = self::sanitize_props( $props );

			// Prevent unsetting protected fields.
			if ( isset( $sanitized['visibility'] ) && 'unset' === $sanitized['visibility']
				&& in_array( $key, self::NO_UNSET_FIELDS, true ) ) {
				$sanitized['visibility'] = 'visible';
			}

			// Country fields must always be required.
			if ( in_array( $key, self::ALWAYS_REQUIRED_FIELDS, true ) ) {
				$sanitized['required'] = true;
			}

			if ( empty( $sanitized ) ) {
				continue;
			}

			$fields[ $key ] = array_merge( $fields[ $key ], $sanitized );
			++$count;
		}

		if ( $count > 0 ) {
			update_option( self::OPTION_KEY, $fields, false );
		}

		return $count;
	}

	/**
	 * Reset all field configurations to factory defaults.
	 *
	 * @return void
	 */
	public static function reset_defaults(): void {
		delete_option( self::OPTION_KEY );
	}

	/**
	 * Parse a pipe-separated options string into an associative array.
	 *
	 * Format: `key:Value|key2:Value2` → `['key' => 'Value', 'key2' => 'Value2']`.
	 *
	 * @param string $raw Raw options string.
	 * @return array<string, string> Parsed key-value pairs.
	 */
	public static function parse_options_string( string $raw ): array {
		$raw = trim( $raw );
		if ( '' === $raw ) {
			return array();
		}

		$options = array();
		$pairs   = explode( '|', $raw );

		foreach ( $pairs as $pair ) {
			$pair = trim( $pair );
			if ( '' === $pair ) {
				continue;
			}
			$parts = explode( ':', $pair, 2 );
			if ( 2 === count( $parts ) ) {
				$k = trim( $parts[0] );
				$v = trim( $parts[1] );
				if ( '' !== $k ) {
					$options[ $k ] = $v;
				}
			}
		}

		return $options;
	}

	/**
	 * Get factory default field configurations.
	 *
	 * @return array<string, array<string, mixed>> Field key => default properties.
	 */
	public static function get_default_fields(): array {
		return array(
			'billing_first_name'  => array(
				'type'        => 'text',
				'label'       => __( 'First Name', 'smarttr-address' ),
				'description' => '',
				'placeholder' => '',
				'class'       => 'form-row-first',
				'required'    => true,
				'clear'       => false,
				'label_class' => '',
				'options'     => '',
				'priority'    => 10,
				'visibility'  => 'visible',
			),
			'billing_last_name'   => array(
				'type'        => 'text',
				'label'       => __( 'Last Name', 'smarttr-address' ),
				'description' => '',
				'placeholder' => '',
				'class'       => 'form-row-last',
				'required'    => true,
				'clear'       => true,
				'label_class' => '',
				'options'     => '',
				'priority'    => 20,
				'visibility'  => 'visible',
			),
			'billing_company'     => array(
				'type'        => 'text',
				'label'       => __( 'Company Name', 'smarttr-address' ),
				'description' => '',
				'placeholder' => '',
				'class'       => 'form-row-wide',
				'required'    => false,
				'clear'       => false,
				'label_class' => '',
				'options'     => '',
				'priority'    => 30,
				'visibility'  => 'visible',
			),
			'billing_country'     => array(
				'type'        => 'country',
				'label'       => __( 'Country / Region', 'smarttr-address' ),
				'description' => '',
				'placeholder' => '',
				'class'       => 'form-row-wide address-field update_totals_on_change',
				'required'    => true,
				'clear'       => false,
				'label_class' => '',
				'options'     => '',
				'priority'    => 40,
				'visibility'  => 'visible',
			),
			'billing_address_1'   => array(
				'type'        => 'text',
				'label'       => __( 'Street Address', 'smarttr-address' ),
				'description' => '',
				'placeholder' => __( 'House number and street name', 'smarttr-address' ),
				'class'       => 'form-row-wide address-field',
				'required'    => true,
				'clear'       => false,
				'label_class' => '',
				'options'     => '',
				'priority'    => 50,
				'visibility'  => 'visible',
			),
			'billing_address_2'   => array(
				'type'        => 'text',
				'label'       => __( 'Neighborhood', 'smarttr-address' ),
				'description' => '',
				'placeholder' => __( 'Select neighborhood', 'smarttr-address' ),
				'class'       => 'form-row-wide address-field',
				'required'    => true,
				'clear'       => false,
				'label_class' => '',
				'options'     => '',
				'priority'    => 60,
				'visibility'  => 'visible',
			),
			'billing_city'        => array(
				'type'        => 'text',
				'label'       => __( 'District', 'smarttr-address' ),
				'description' => '',
				'placeholder' => __( 'Select district', 'smarttr-address' ),
				'class'       => 'form-row-wide address-field',
				'required'    => true,
				'clear'       => false,
				'label_class' => '',
				'options'     => '',
				'priority'    => 70,
				'visibility'  => 'visible',
			),
			'billing_state'       => array(
				'type'        => 'state',
				'label'       => __( 'Province', 'smarttr-address' ),
				'description' => '',
				'placeholder' => __( 'Select province', 'smarttr-address' ),
				'class'       => 'form-row-wide address-field',
				'required'    => true,
				'clear'       => false,
				'label_class' => '',
				'options'     => '',
				'priority'    => 80,
				'visibility'  => 'visible',
			),
			'billing_postcode'    => array(
				'type'        => 'text',
				'label'       => __( 'Postal Code', 'smarttr-address' ),
				'description' => '',
				'placeholder' => '',
				'class'       => 'form-row-wide address-field',
				'required'    => false,
				'clear'       => false,
				'label_class' => '',
				'options'     => '',
				'priority'    => 90,
				'visibility'  => 'visible',
			),
			'billing_email'       => array(
				'type'        => 'email',
				'label'       => __( 'Email Address', 'smarttr-address' ),
				'description' => '',
				'placeholder' => '',
				'class'       => 'form-row-first',
				'required'    => true,
				'clear'       => false,
				'label_class' => '',
				'options'     => '',
				'priority'    => 100,
				'visibility'  => 'visible',
			),
			'billing_phone'       => array(
				'type'        => 'tel',
				'label'       => __( 'Phone', 'smarttr-address' ),
				'description' => '',
				'placeholder' => '',
				'class'       => 'form-row-last',
				'required'    => true,
				'clear'       => true,
				'label_class' => '',
				'options'     => '',
				'priority'    => 110,
				'visibility'  => 'visible',
			),
			'shipping_first_name' => array(
				'type'        => 'text',
				'label'       => __( 'First Name', 'smarttr-address' ),
				'description' => '',
				'placeholder' => '',
				'class'       => 'form-row-first',
				'required'    => true,
				'clear'       => false,
				'label_class' => '',
				'options'     => '',
				'priority'    => 10,
				'visibility'  => 'visible',
			),
			'shipping_last_name'  => array(
				'type'        => 'text',
				'label'       => __( 'Last Name', 'smarttr-address' ),
				'description' => '',
				'placeholder' => '',
				'class'       => 'form-row-last',
				'required'    => true,
				'clear'       => true,
				'label_class' => '',
				'options'     => '',
				'priority'    => 20,
				'visibility'  => 'visible',
			),
			'shipping_company'    => array(
				'type'        => 'text',
				'label'       => __( 'Company Name', 'smarttr-address' ),
				'description' => '',
				'placeholder' => '',
				'class'       => 'form-row-wide',
				'required'    => false,
				'clear'       => false,
				'label_class' => '',
				'options'     => '',
				'priority'    => 30,
				'visibility'  => 'visible',
			),
			'shipping_country'    => array(
				'type'        => 'country',
				'label'       => __( 'Country / Region', 'smarttr-address' ),
				'description' => '',
				'placeholder' => '',
				'class'       => 'form-row-wide address-field update_totals_on_change',
				'required'    => true,
				'clear'       => false,
				'label_class' => '',
				'options'     => '',
				'priority'    => 40,
				'visibility'  => 'visible',
			),
			'shipping_address_1'  => array(
				'type'        => 'text',
				'label'       => __( 'Street Address', 'smarttr-address' ),
				'description' => '',
				'placeholder' => __( 'House number and street name', 'smarttr-address' ),
				'class'       => 'form-row-wide address-field',
				'required'    => true,
				'clear'       => false,
				'label_class' => '',
				'options'     => '',
				'priority'    => 50,
				'visibility'  => 'visible',
			),
			'shipping_address_2'  => array(
				'type'        => 'text',
				'label'       => __( 'Neighborhood', 'smarttr-address' ),
				'description' => '',
				'placeholder' => __( 'Select neighborhood', 'smarttr-address' ),
				'class'       => 'form-row-wide address-field',
				'required'    => true,
				'clear'       => false,
				'label_class' => '',
				'options'     => '',
				'priority'    => 60,
				'visibility'  => 'visible',
			),
			'shipping_city'       => array(
				'type'        => 'text',
				'label'       => __( 'District', 'smarttr-address' ),
				'description' => '',
				'placeholder' => __( 'Select district', 'smarttr-address' ),
				'class'       => 'form-row-wide address-field',
				'required'    => true,
				'clear'       => false,
				'label_class' => '',
				'options'     => '',
				'priority'    => 70,
				'visibility'  => 'visible',
			),
			'shipping_postcode'   => array(
				'type'        => 'text',
				'label'       => __( 'Postal Code', 'smarttr-address' ),
				'description' => '',
				'placeholder' => '',
				'class'       => 'form-row-wide address-field',
				'required'    => false,
				'clear'       => false,
				'label_class' => '',
				'options'     => '',
				'priority'    => 90,
				'visibility'  => 'visible',
			),
			'shipping_state'      => array(
				'type'        => 'state',
				'label'       => __( 'Province', 'smarttr-address' ),
				'description' => '',
				'placeholder' => __( 'Select province', 'smarttr-address' ),
				'class'       => 'form-row-wide address-field',
				'required'    => true,
				'clear'       => false,
				'label_class' => '',
				'options'     => '',
				'priority'    => 80,
				'visibility'  => 'visible',
			),
		);
	}

	/**
	 * Sanitize field properties.
	 *
	 * Only allows known property keys with correct types.
	 *
	 * @param array<string, mixed> $props Raw properties.
	 * @return array<string, mixed> Sanitized properties (may be empty if all invalid).
	 */
	private static function sanitize_props( array $props ): array {
		$sanitized = array();

		if ( isset( $props['type'] ) ) {
			$type = sanitize_text_field( $props['type'] );
			if ( in_array( $type, self::ALLOWED_TYPES, true ) ) {
				$sanitized['type'] = $type;
			}
		}

		if ( isset( $props['label'] ) ) {
			$label = sanitize_text_field( $props['label'] );
			if ( '' !== $label && strlen( $label ) <= 100 ) {
				$sanitized['label'] = $label;
			}
		}

		if ( array_key_exists( 'description', $props ) ) {
			$description = sanitize_text_field( $props['description'] );
			if ( strlen( $description ) <= 200 ) {
				$sanitized['description'] = $description;
			}
		}

		if ( array_key_exists( 'placeholder', $props ) ) {
			$placeholder = sanitize_text_field( $props['placeholder'] );
			if ( strlen( $placeholder ) <= 100 ) {
				$sanitized['placeholder'] = $placeholder;
			}
		}

		if ( isset( $props['priority'] ) ) {
			$priority = (int) $props['priority'];
			if ( $priority >= 1 && $priority <= 999 ) {
				$sanitized['priority'] = $priority;
			}
		}

		if ( isset( $props['required'] ) ) {
			$sanitized['required'] = (bool) $props['required'];
		}

		if ( isset( $props['clear'] ) ) {
			$sanitized['clear'] = (bool) $props['clear'];
		}

		if ( array_key_exists( 'class', $props ) ) {
			$class = sanitize_text_field( $props['class'] );
			if ( preg_match( '/^[a-zA-Z0-9\s\-_]*$/', $class ) ) {
				$sanitized['class'] = $class;
			}
		}

		if ( array_key_exists( 'label_class', $props ) ) {
			$label_class = sanitize_text_field( $props['label_class'] );
			if ( preg_match( '/^[a-zA-Z0-9\s\-_]*$/', $label_class ) ) {
				$sanitized['label_class'] = $label_class;
			}
		}

		if ( array_key_exists( 'options', $props ) ) {
			$options = sanitize_text_field( $props['options'] );
			if ( strlen( $options ) <= 1000 ) {
				$sanitized['options'] = $options;
			}
		}

		if ( isset( $props['visibility'] ) ) {
			$visibility = sanitize_text_field( $props['visibility'] );
			if ( in_array( $visibility, self::ALLOWED_VISIBILITY, true ) ) {
				$sanitized['visibility'] = $visibility;
			}
		}

		if ( array_key_exists( 'allowed_extensions', $props ) ) {
			$raw_ext = sanitize_text_field( $props['allowed_extensions'] );
			// Only allow alphanumeric chars, commas, and spaces — no dots or special chars.
			if ( preg_match( '/^[a-zA-Z0-9,\s]*$/', $raw_ext ) && strlen( $raw_ext ) <= 200 ) {
				$exts                         = array_filter( array_map( 'trim', explode( ',', strtolower( $raw_ext ) ) ) );
				$sanitized['allowed_extensions'] = implode( ',', $exts );
			}
		}

		return $sanitized;
	}

	/**
	 * Get the list of fields that cannot be unset.
	 *
	 * @return string[]
	 */
	public static function get_no_unset_fields(): array {
		return self::NO_UNSET_FIELDS;
	}

	/**
	 * Get the list of fields that must always be required.
	 *
	 * @return string[]
	 */
	public static function get_always_required_fields(): array {
		return self::ALWAYS_REQUIRED_FIELDS;
	}
}
