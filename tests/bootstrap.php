<?php
/**
 * PHPUnit bootstrap — stubs WordPress & WooCommerce globals/functions
 * so that CecomsmaradAddress unit tests run without a full WP installation.
 *
 * @package CecomsmaradAddress
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Test bootstrap stubs must match WP/WC function, class, and constant names exactly.
// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Test-only exception; no browser output.
// phpcs:disable WordPress.WP.AlternativeFunctions.strip_tags_strip_tags -- Minimal stub for testing; wp_strip_all_tags() not yet available.

// Direct HTTP access guard — MUST be the first executable code.
// Exits on browser requests. CLI/PHPUnit runs define a placeholder ABSPATH so tests work
// without a real WordPress installation.
// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited, PluginCheck.CodeAnalysis.GlobalVariables -- PHPUnit bootstrap only. ABSPATH is undefined in CLI test runs; this placeholder prevents plugin files from exiting on their ABSPATH guard. Protected by ! defined() + PHP_SAPI === 'cli' checks — never fires at runtime.
if ( ! defined( 'ABSPATH' ) ) {
	if ( PHP_SAPI === 'cli' ) {
		define( 'ABSPATH', dirname( dirname( __DIR__ ) ) . '/' );
	} else {
		exit;
	}
}

// ── Path stubs (needed before vendor autoload) ──────────────────────────
if ( ! function_exists( 'trailingslashit' ) ) {
	function trailingslashit( $value ) {
		return rtrim( $value, '/\\' ) . '/';
	}
}
if ( ! function_exists( 'plugin_dir_path' ) ) {
	function plugin_dir_path( $file ) {
		return trailingslashit( dirname( $file ) );
	}
}

// Yoast PHPUnit polyfills for PHPUnit 10+ compatibility.
// Vendor is at the container level (parent of the edition directory).
$cecomsmarad_plugin_dir = plugin_dir_path( __DIR__ );
$cecomsmarad_vendor_dir = plugin_dir_path( $cecomsmarad_plugin_dir ) . 'vendor';
if ( ! file_exists( $cecomsmarad_vendor_dir . '/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php' ) ) {
	// Fall back to edition-level vendor if it exists.
	$cecomsmarad_vendor_dir = $cecomsmarad_plugin_dir . 'vendor';
}
require_once $cecomsmarad_vendor_dir . '/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php';

/*
|--------------------------------------------------------------------------
| Constants
|--------------------------------------------------------------------------
*/
define( 'CECOMSMARAD_VERSION', '1.3.0-test' );
define( 'CECOMSMARAD_PLUGIN_DIR', $cecomsmarad_plugin_dir );
define( 'CECOMSMARAD_PLUGIN_FILE', $cecomsmarad_plugin_dir . 'cecomsmarad-address.php' );
define( 'CECOMSMARAD_PLUGIN_URL', 'https://example.com/wp-content/plugins/cecomsmarad-address/' );
define( 'CECOMSMARAD_PLUGIN_BASENAME', 'cecomsmarad-address/cecomsmarad-address.php' );

/*
|--------------------------------------------------------------------------
| WordPress Function Stubs
|--------------------------------------------------------------------------
*/

if ( ! function_exists( '__' ) ) {
	/**
	 * Translation stub — returns the string as-is.
	 */
	function __( string $text, string $domain = 'default' ): string {
		return $text;
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( string $text ): string {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	function esc_html__( string $text, string $domain = 'default' ): string {
		return esc_html( $text );
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( string $text ): string {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( string $str ): string {
		return trim( strip_tags( $str ) );
	}
}

if ( ! function_exists( 'absint' ) ) {
	function absint( $value ): int {
		return abs( (int) $value );
	}
}

if ( ! function_exists( 'wp_unslash' ) ) {
	function wp_unslash( $value ) {
		return is_string( $value ) ? stripslashes( $value ) : $value;
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $data, int $options = 0, int $depth = 512 ) {
		return json_encode( $data, $options, $depth );
	}
}

if ( ! function_exists( 'esc_attr__' ) ) {
	function esc_attr__( string $text, string $domain = 'default' ): string {
		return esc_attr( $text );
	}
}

if ( ! function_exists( 'esc_url_raw' ) ) {
	function esc_url_raw( string $url, $protocols = null ): string {
		return filter_var( $url, FILTER_SANITIZE_URL ) ?: '';
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	/**
	 * Apply filters stub — returns the value unchanged.
	 */
	function apply_filters( string $tag, $value, ...$args ) {
		return $value;
	}
}

/*
|--------------------------------------------------------------------------
| wp_cache mock store
|--------------------------------------------------------------------------
*/

global $cecomsmarad_test_cache;
$cecomsmarad_test_cache = array();

if ( ! function_exists( 'wp_cache_get' ) ) {
	function wp_cache_get( string $key, string $group = 'default' ) {
		global $cecomsmarad_test_cache;
		$full_key = $group . ':' . $key;
		return $cecomsmarad_test_cache[ $full_key ] ?? false;
	}
}

if ( ! function_exists( 'wp_cache_set' ) ) {
	function wp_cache_set( string $key, $data, string $group = 'default', int $expire = 0 ): bool {
		global $cecomsmarad_test_cache;
		$full_key                          = $group . ':' . $key;
		$cecomsmarad_test_cache[ $full_key ] = $data;
		return true;
	}
}

if ( ! function_exists( 'wp_cache_flush' ) ) {
	function wp_cache_flush(): bool {
		global $cecomsmarad_test_cache;
		$cecomsmarad_test_cache = array();
		return true;
	}
}

if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( $key ): string {
		return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) );
	}
}

/*
|--------------------------------------------------------------------------
| wp_options mock store
|--------------------------------------------------------------------------
*/

global $cecomsmarad_test_options;
$cecomsmarad_test_options = array();

if ( ! function_exists( 'get_option' ) ) {
	function get_option( string $option, $default = false ) {
		global $cecomsmarad_test_options;
		return $cecomsmarad_test_options[ $option ] ?? $default;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	function update_option( string $option, $value, $autoload = null ): bool {
		global $cecomsmarad_test_options;
		$cecomsmarad_test_options[ $option ] = $value;
		return true;
	}
}

if ( ! function_exists( 'delete_option' ) ) {
	function delete_option( string $option ): bool {
		global $cecomsmarad_test_options;
		unset( $cecomsmarad_test_options[ $option ] );
		return true;
	}
}

/*
|--------------------------------------------------------------------------
| wp_send_json stubs — capture output instead of calling exit().
|--------------------------------------------------------------------------
*/

/**
 * Exception thrown by wp_send_json_* stubs to halt execution in tests.
 */
class Cecomsmarad_Test_Json_Exception extends \RuntimeException {
	public array $response;
	public function __construct( array $response ) {
		$this->response = $response;
		parent::__construct( 'JSON response sent' );
	}
}

if ( ! function_exists( 'wp_send_json_success' ) ) {
	function wp_send_json_success( $data = null, int $status_code = 200 ): void {
		throw new Cecomsmarad_Test_Json_Exception( array(
			'success' => true,
			'data'    => $data,
		) );
	}
}

if ( ! function_exists( 'wp_send_json_error' ) ) {
	function wp_send_json_error( $data = null, int $status_code = 200 ): void {
		throw new Cecomsmarad_Test_Json_Exception( array(
			'success' => false,
			'data'    => $data,
		) );
	}
}

/*
|--------------------------------------------------------------------------
| Nonce stubs
|--------------------------------------------------------------------------
*/

if ( ! function_exists( 'wp_verify_nonce' ) ) {
	/**
	 * Nonce verification stub.
	 *
	 * Returns 1 (valid) when the value equals the action, 0 otherwise.
	 */
	function wp_verify_nonce( string $nonce, string $action = '' ) {
		return $nonce === $action ? 1 : false;
	}
}

/*
|--------------------------------------------------------------------------
| Hook stubs — collect registered hooks for inspection.
|--------------------------------------------------------------------------
*/

global $cecomsmarad_test_hooks;
$cecomsmarad_test_hooks = array();

if ( ! function_exists( 'add_action' ) ) {
	function add_action( string $tag, $callback, int $priority = 10, int $accepted_args = 1 ): void {
		global $cecomsmarad_test_hooks;
		$cecomsmarad_test_hooks[] = array(
			'type'     => 'action',
			'tag'      => $tag,
			'callback' => $callback,
			'priority' => $priority,
		);
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( string $tag, $callback, int $priority = 10, int $accepted_args = 1 ): void {
		global $cecomsmarad_test_hooks;
		$cecomsmarad_test_hooks[] = array(
			'type'     => 'filter',
			'tag'      => $tag,
			'callback' => $callback,
			'priority' => $priority,
		);
	}
}

/*
|--------------------------------------------------------------------------
| Mock wpdb
|--------------------------------------------------------------------------
*/

/**
 * Minimal wpdb mock that records queries and allows programmable results.
 */
class Cecomsmarad_Test_Wpdb {
	public string $prefix  = 'wp_';
	public string $charset = 'utf8mb4';
	public string $options = 'wp_options';
	public array  $queries = array();

	/** @var mixed Next value to return from get_results(). */
	public $next_results = array();

	/** @var mixed Next value to return from get_row(). */
	public $next_row = null;

	/** @var mixed Next value to return from get_var(). */
	public $next_var = null;

	public function get_charset_collate(): string {
		return 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
	}

	public function prepare( string $query, ...$args ): string {
		// Flatten array arguments (WordPress $wpdb->prepare() accepts both varargs and array).
		$flat_args = array();
		foreach ( $args as $arg ) {
			if ( is_array( $arg ) ) {
				foreach ( $arg as $v ) {
					$flat_args[] = $v;
				}
			} else {
				$flat_args[] = $arg;
			}
		}

		// Simple vsprintf-style replacement for test assertions.
		$replacements = array();
		foreach ( $flat_args as $arg ) {
			if ( is_int( $arg ) ) {
				$replacements[] = (string) $arg;
			} elseif ( null === $arg ) {
				$replacements[] = 'NULL';
			} else {
				$replacements[] = "'" . addslashes( (string) $arg ) . "'";
			}
		}

		$result = $query;
		foreach ( $replacements as $replacement ) {
			$pos = strpos( $result, '%s' );
			if ( false === $pos ) {
				$pos = strpos( $result, '%d' );
			}
			if ( false !== $pos ) {
				$result = substr_replace( $result, $replacement, $pos, 2 );
			}
		}

		return $result;
	}

	public function get_results( string $query ): array {
		$this->queries[] = $query;
		$results         = $this->next_results;
		$this->next_results = array();
		return $results;
	}

	public function get_row( string $query ) {
		$this->queries[] = $query;
		$row             = $this->next_row;
		$this->next_row  = null;
		return $row;
	}

	public function get_var( string $query ) {
		$this->queries[] = $query;
		$var             = $this->next_var;
		$this->next_var  = null;
		return $var;
	}

	public function query( string $query ) {
		$this->queries[] = $query;
		return true;
	}

	public function suppress_errors( bool $suppress = true ): bool {
		return true;
	}

	public function esc_like( string $text ): string {
		return addcslashes( $text, '_%\\' );
	}

	/**
	 * Reset the mock state.
	 */
	public function reset(): void {
		$this->queries      = array();
		$this->next_results = array();
		$this->next_row     = null;
		$this->next_var     = null;
	}
}

// Set up global $wpdb.
global $wpdb;
$wpdb = new Cecomsmarad_Test_Wpdb();

/*
|--------------------------------------------------------------------------
| WooCommerce stubs
|--------------------------------------------------------------------------
*/

if ( ! class_exists( 'Cecomsmarad_WP_Error' ) ) {
	class Cecomsmarad_WP_Error {
		private array $errors = array();

		public function add( string $code, string $message, $data = '' ): void {
			$this->errors[ $code ][] = $message;
		}

		public function get_error_codes(): array {
			return array_keys( $this->errors );
		}

		public function get_error_messages( string $code = '' ): array {
			if ( '' === $code ) {
				$all = array();
				foreach ( $this->errors as $messages ) {
					$all = array_merge( $all, $messages );
				}
				return $all;
			}
			return $this->errors[ $code ] ?? array();
		}

		public function has_errors(): bool {
			return ! empty( $this->errors );
		}
	}
}
if ( ! class_exists( 'WP_Error' ) ) {
	class_alias( 'Cecomsmarad_WP_Error', 'WP_Error' );
}

if ( ! class_exists( 'Cecomsmarad_WC_Order' ) ) {
	/**
	 * Minimal WC_Order stub for unit testing meta operations.
	 */
	class Cecomsmarad_WC_Order {
		private array $meta = array();
		private int $id;
		public bool $save_called = false;

		public function __construct( int $id = 1 ) {
			$this->id = $id;
		}

		public function get_id(): int {
			return $this->id;
		}

		public function get_order_number(): int {
			return $this->id;
		}

		public function update_meta_data( string $key, $value ): void {
			$this->meta[ $key ] = $value;
		}

		public function get_meta( string $key ) {
			return $this->meta[ $key ] ?? '';
		}

		public function delete_meta_data( string $key ): void {
			unset( $this->meta[ $key ] );
		}

		public function save(): void {
			$this->save_called = true;
		}

		/**
		 * Helper to get all stored meta (for test assertions).
		 */
		public function get_all_meta(): array {
			return $this->meta;
		}
	}
}
if ( ! class_exists( 'WC_Order' ) ) {
	class_alias( 'Cecomsmarad_WC_Order', 'WC_Order' );
}

if ( ! function_exists( 'wc_get_order' ) ) {
	/**
	 * wc_get_order stub — returns from a global test store.
	 */
	function wc_get_order( int $order_id ) {
		global $cecomsmarad_test_orders;
		return $cecomsmarad_test_orders[ $order_id ] ?? false;
	}
}

global $cecomsmarad_test_orders;
$cecomsmarad_test_orders = array();

/*
|--------------------------------------------------------------------------
| Transient mock store
|--------------------------------------------------------------------------
*/

global $cecomsmarad_test_transients;
$cecomsmarad_test_transients = array();

if ( ! function_exists( 'get_transient' ) ) {
	function get_transient( string $key ) {
		global $cecomsmarad_test_transients;
		return $cecomsmarad_test_transients[ $key ] ?? false;
	}
}

if ( ! function_exists( 'set_transient' ) ) {
	function set_transient( string $key, $value, int $expiration = 0 ): bool {
		global $cecomsmarad_test_transients;
		$cecomsmarad_test_transients[ $key ] = $value;
		return true;
	}
}

if ( ! function_exists( 'delete_transient' ) ) {
	function delete_transient( string $key ): bool {
		global $cecomsmarad_test_transients;
		unset( $cecomsmarad_test_transients[ $key ] );
		return true;
	}
}

if ( ! function_exists( 'wp_cache_delete' ) ) {
	function wp_cache_delete( string $key, string $group = 'default' ): bool {
		global $cecomsmarad_test_cache;
		$full_key = $group . ':' . $key;
		unset( $cecomsmarad_test_cache[ $full_key ] );
		return true;
	}
}

/*
|--------------------------------------------------------------------------
| Cryptographic & scheduling stubs
|--------------------------------------------------------------------------
*/

if ( ! function_exists( 'wp_salt' ) ) {
	function wp_salt( string $scheme = 'auth' ): string {
		return 'cecomsmarad-test-salt';
	}
}

if ( ! function_exists( 'wp_next_scheduled' ) ) {
	function wp_next_scheduled( string $hook ): bool {
		return false;
	}
}

if ( ! function_exists( 'wp_schedule_single_event' ) ) {
	function wp_schedule_single_event( int $timestamp, string $hook ): bool {
		return true;
	}
}

if ( ! function_exists( 'spawn_cron' ) ) {
	function spawn_cron(): void {}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ): bool {
		return $thing instanceof WP_Error;
	}
}

/*
|--------------------------------------------------------------------------
| Admin stubs
|--------------------------------------------------------------------------
*/

if ( ! function_exists( 'check_ajax_referer' ) ) {
	function check_ajax_referer( string $action, $query_arg = false ): bool {
		return true;
	}
}

global $cecomsmarad_test_current_user_can;
$cecomsmarad_test_current_user_can = true;

if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can( string $capability ): bool {
		global $cecomsmarad_test_current_user_can;
		return (bool) $cecomsmarad_test_current_user_can;
	}
}

if ( ! function_exists( 'size_format' ) ) {
	function size_format( int $bytes, int $decimals = 0 ): string {
		return $bytes . ' B';
	}
}

if ( ! function_exists( 'get_bloginfo' ) ) {
	function get_bloginfo( string $show = '', string $filter = 'raw' ): string {
		return '6.5';
	}
}

if ( ! function_exists( 'add_settings_error' ) ) {
	function add_settings_error( string $setting, string $code, string $message, string $type = 'error' ): void {}
}

if ( ! function_exists( 'admin_url' ) ) {
	function admin_url( string $path = '' ): string {
		return 'https://example.com/wp-admin/' . ltrim( $path, '/' );
	}
}

if ( ! function_exists( 'wp_create_nonce' ) ) {
	function wp_create_nonce( string $action ): string {
		return $action;
	}
}

global $menu, $submenu;
$menu    = array();
$submenu = array();

if ( ! function_exists( 'add_menu_page' ) ) {
	/**
	 * @param mixed $position
	 */
	function add_menu_page( string $page_title, string $menu_title, string $capability, string $menu_slug, $function = '', string $icon_url = '', $position = null ): string {
		global $menu;
		$menu[] = array( $page_title, $capability, $menu_slug, $menu_title, '', '', $icon_url );
		return $menu_slug;
	}
}

if ( ! function_exists( 'add_submenu_page' ) ) {
	function add_submenu_page( string $parent_slug, string $page_title, string $menu_title, string $capability, string $menu_slug, $function = '' ): string {
		global $submenu;
		$submenu[ $parent_slug ][] = array( $menu_title, $capability, $menu_slug, $page_title );
		return $menu_slug;
	}
}

if ( ! function_exists( 'remove_menu_page' ) ) {
	function remove_menu_page( string $menu_slug ): bool {
		global $menu;
		foreach ( $menu as $i => $item ) {
			if ( isset( $item[2] ) && $menu_slug === $item[2] ) {
				unset( $menu[ $i ] );
				return true;
			}
		}
		return false;
	}
}

if ( ! defined( 'DB_NAME' ) ) {
	define( 'DB_NAME', 'cecomsmarad_test_db' );
}

/*
|--------------------------------------------------------------------------
| Additional constants
|--------------------------------------------------------------------------
*/

if ( ! defined( 'DAY_IN_SECONDS' ) ) {
	define( 'DAY_IN_SECONDS', 86400 );
}
if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 3600 );
}
if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
	define( 'MINUTE_IN_SECONDS', 60 );
}
if ( ! defined( 'WPMU_PLUGIN_DIR' ) ) {
	define( 'WPMU_PLUGIN_DIR', sys_get_temp_dir() . '/cecomsmarad-mu-plugins' );
}

/*
|--------------------------------------------------------------------------
| Additional WordPress function stubs
|--------------------------------------------------------------------------
*/

if ( ! function_exists( 'current_time' ) ) {
	function current_time( string $type, bool $gmt = false ): string {
		return gmdate( 'Y-m-d H:i:s' );
	}
}

if ( ! function_exists( 'get_date_from_gmt' ) ) {
	function get_date_from_gmt( string $string, string $format = 'Y-m-d H:i:s' ): string {
		return $string;
	}
}

if ( ! function_exists( 'wp_mail' ) ) {
	/**
	 * @global array $cecomsmarad_test_wp_mail_log Captures wp_mail() calls.
	 */
	function wp_mail( string $to, string $subject, string $message, $headers = '', $attachments = array() ): bool {
		global $cecomsmarad_test_wp_mail_log;
		$cecomsmarad_test_wp_mail_log[] = compact( 'to', 'subject', 'message' );
		return true;
	}
}

if ( ! function_exists( 'wp_date' ) ) {
	function wp_date( string $format, ?int $timestamp = null, $timezone = null ): string {
		return gmdate( $format, $timestamp ?? time() );
	}
}

if ( ! function_exists( 'get_site_url' ) ) {
	function get_site_url( ?int $blog_id = null, string $path = '', string $scheme = '' ): string {
		return 'https://example.com' . ( '' !== $path ? '/' . ltrim( $path, '/' ) : '' );
	}
}

if ( ! function_exists( 'wp_parse_url' ) ) {
	function wp_parse_url( string $url, int $component = -1 ) {
		return parse_url( $url, $component );
	}
}

if ( ! function_exists( 'is_email' ) ) {
	/**
	 * @return string|false
	 */
	function is_email( string $email ) {
		return filter_var( $email, FILTER_VALIDATE_EMAIL ) ? $email : false;
	}
}

if ( ! function_exists( 'sanitize_email' ) ) {
	function sanitize_email( string $email ): string {
		$valid = filter_var( $email, FILTER_VALIDATE_EMAIL );
		return $valid ? strtolower( trim( $email ) ) : '';
	}
}

if ( ! function_exists( 'sanitize_textarea_field' ) ) {
	function sanitize_textarea_field( string $str ): string {
		return trim( strip_tags( $str ) );
	}
}

if ( ! function_exists( 'wp_kses_post' ) ) {
	function wp_kses_post( string $data ): string {
		return $data;
	}
}

if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
	function wp_add_privacy_policy_content( string $plugin_name, string $policy_text ): void {}
}

if ( ! function_exists( 'wp_clear_scheduled_hook' ) ) {
	function wp_clear_scheduled_hook( string $hook, array $args = array() ): int|false {
		return 0;
	}
}

if ( ! function_exists( 'flush_rewrite_rules' ) ) {
	function flush_rewrite_rules( bool $hard = true ): void {}
}

if ( ! function_exists( 'deactivate_plugins' ) ) {
	function deactivate_plugins( $plugins, bool $silent = false, ?bool $network_wide = null ): void {}
}

if ( ! function_exists( 'plugin_basename' ) ) {
	function plugin_basename( string $file ): string {
		return basename( dirname( $file ) ) . '/' . basename( $file );
	}
}

if ( ! function_exists( 'wp_mkdir_p' ) ) {
	function wp_mkdir_p( string $target ): bool {
		return true;
	}
}

if ( ! function_exists( 'wp_is_writable' ) ) {
	function wp_is_writable( string $path ): bool {
		return true;
	}
}

/*
|--------------------------------------------------------------------------
| wc_get_orders stub — returns from a global test store.
|--------------------------------------------------------------------------
*/

global $cecomsmarad_test_wc_orders_list, $cecomsmarad_test_wp_mail_log;
$cecomsmarad_test_wc_orders_list = array();
$cecomsmarad_test_wp_mail_log    = array();

if ( ! function_exists( 'wc_get_orders' ) ) {
	function wc_get_orders( array $args = array() ): array {
		global $cecomsmarad_test_wc_orders_list;
		return $cecomsmarad_test_wc_orders_list;
	}
}

/*
|--------------------------------------------------------------------------
| Include plugin source files (skip bootstrap, load models + controllers).
|--------------------------------------------------------------------------
*/

require_once CECOMSMARAD_PLUGIN_DIR . 'includes/models/class-cecomsmarad-province.php';
require_once CECOMSMARAD_PLUGIN_DIR . 'includes/models/class-cecomsmarad-district.php';
require_once CECOMSMARAD_PLUGIN_DIR . 'includes/models/class-cecomsmarad-settings.php';
require_once CECOMSMARAD_PLUGIN_DIR . 'includes/models/class-cecomsmarad-data-importer.php';
require_once CECOMSMARAD_PLUGIN_DIR . 'includes/models/class-cecomsmarad-remote-sync.php';
require_once CECOMSMARAD_PLUGIN_DIR . 'includes/controllers/class-cecomsmarad-order-controller.php';
require_once CECOMSMARAD_PLUGIN_DIR . 'includes/controllers/class-cecomsmarad-checkout-controller.php';
require_once CECOMSMARAD_PLUGIN_DIR . 'includes/controllers/class-cecomsmarad-admin-controller.php';
require_once CECOMSMARAD_PLUGIN_DIR . 'includes/class-cecomsmarad-deactivator.php';
require_once CECOMSMARAD_PLUGIN_DIR . 'includes/class-cecomsmarad-i18n.php';
require_once CECOMSMARAD_PLUGIN_DIR . 'includes/class-cecomsmarad-mu-installer.php';
require_once CECOMSMARAD_PLUGIN_DIR . 'includes/class-cecomsmarad-privacy.php';
require_once CECOMSMARAD_PLUGIN_DIR . 'includes/class-cecomsmarad-activator.php';
