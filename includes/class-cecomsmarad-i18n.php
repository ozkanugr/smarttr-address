<?php
/**
 * Internationalization handler.
 *
 * Since WordPress 4.6, translations for plugins hosted on WordPress.org
 * are loaded automatically. Manual load_plugin_textdomain() is no longer
 * required and triggers a PluginCheck warning.
 *
 * This class is retained as a no-op to avoid breaking any code that
 * instantiates it, but the textdomain call has been removed.
 *
 * @package CecomsmaradAddress
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Cecomsmarad_I18n
 *
 * Placeholder for the i18n bootstrap.
 * WordPress auto-loads translations for wordpress.org-hosted plugins since 4.6.
 */
class Cecomsmarad_I18n {

	/**
	 * No-op: WordPress auto-loads translations for plugins hosted on WordPress.org.
	 *
	 * @return void
	 */
	public function load_plugin_textdomain(): void {
		// Intentionally empty — WordPress handles this automatically since 4.6.
	}
}
