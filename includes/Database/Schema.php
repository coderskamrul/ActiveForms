<?php
/**
 * Database schema installer.
 *
 * @package ActiveForms
 */

namespace ActiveForms\Database;

use ActiveForms\Core\Config;

defined( 'ABSPATH' ) || exit;

/**
 * Creates and upgrades ActiveForms custom tables using dbDelta().
 */
class Schema {

	/**
	 * Install or upgrade all tables.
	 *
	 * @return void
	 */
	public static function install() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$tables          = Config::tables();
		$prefix          = $wpdb->prefix;

		$statements = array();

		$forms = $prefix . $tables['forms'];
		$statements[] = "CREATE TABLE {$forms} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			title VARCHAR(255) NOT NULL,
			slug VARCHAR(191) NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'published',
			type VARCHAR(40) NOT NULL DEFAULT 'classic',
			fields LONGTEXT NULL,
			settings LONGTEXT NULL,
			has_payment TINYINT(1) NOT NULL DEFAULT 0,
			created_by BIGINT UNSIGNED NULL,
			created_at DATETIME NULL,
			updated_at DATETIME NULL,
			PRIMARY KEY  (id),
			KEY status (status),
			KEY slug (slug)
		) {$charset_collate};";

		$form_meta = $prefix . $tables['form_meta'];
		$statements[] = "CREATE TABLE {$form_meta} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			form_id BIGINT UNSIGNED NOT NULL,
			meta_key VARCHAR(191) NOT NULL,
			meta_value LONGTEXT NULL,
			PRIMARY KEY  (id),
			KEY form_id_meta_key (form_id, meta_key)
		) {$charset_collate};";

		$entries = $prefix . $tables['entries'];
		$statements[] = "CREATE TABLE {$entries} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			form_id BIGINT UNSIGNED NOT NULL,
			serial BIGINT UNSIGNED NULL,
			response LONGTEXT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'unread',
			is_favorite TINYINT(1) NOT NULL DEFAULT 0,
			user_id BIGINT UNSIGNED NULL,
			source_url VARCHAR(255) NULL,
			ip VARCHAR(45) NULL,
			country VARCHAR(60) NULL,
			browser VARCHAR(60) NULL,
			device VARCHAR(60) NULL,
			payment_status VARCHAR(20) NULL,
			payment_total BIGINT NULL,
			currency VARCHAR(8) NULL,
			created_at DATETIME NULL,
			updated_at DATETIME NULL,
			PRIMARY KEY  (id),
			KEY form_id_status (form_id, status),
			KEY form_id_created (form_id, created_at),
			KEY user_id (user_id)
		) {$charset_collate};";

		$entry_meta = $prefix . $tables['entry_meta'];
		$statements[] = "CREATE TABLE {$entry_meta} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			entry_id BIGINT UNSIGNED NOT NULL,
			form_id BIGINT UNSIGNED NULL,
			meta_key VARCHAR(191) NOT NULL,
			meta_value LONGTEXT NULL,
			created_at DATETIME NULL,
			PRIMARY KEY  (id),
			KEY entry_id_meta_key (entry_id, meta_key)
		) {$charset_collate};";

		$entry_detail = $prefix . $tables['entry_detail'];
		$statements[] = "CREATE TABLE {$entry_detail} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			form_id BIGINT UNSIGNED NOT NULL,
			entry_id BIGINT UNSIGNED NOT NULL,
			field_key VARCHAR(191) NOT NULL,
			sub_field VARCHAR(191) NULL,
			field_value LONGTEXT NULL,
			PRIMARY KEY  (id),
			KEY form_field (form_id, field_key(100)),
			KEY entry_id (entry_id)
		) {$charset_collate};";

		$logs = $prefix . $tables['logs'];
		$statements[] = "CREATE TABLE {$logs} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			form_id BIGINT UNSIGNED NULL,
			entry_id BIGINT UNSIGNED NULL,
			component VARCHAR(60) NULL,
			status VARCHAR(20) NULL,
			title VARCHAR(255) NULL,
			message LONGTEXT NULL,
			created_at DATETIME NULL,
			PRIMARY KEY  (id),
			KEY form_id (form_id)
		) {$charset_collate};";

		$scheduled = $prefix . $tables['scheduled'];
		$statements[] = "CREATE TABLE {$scheduled} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			action VARCHAR(100) NOT NULL,
			payload LONGTEXT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'pending',
			attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
			scheduled_at DATETIME NULL,
			created_at DATETIME NULL,
			PRIMARY KEY  (id),
			KEY status_scheduled (status, scheduled_at)
		) {$charset_collate};";

		foreach ( $statements as $sql ) {
			dbDelta( $sql );
		}
	}

	/**
	 * Drop all tables (used by uninstall when data removal is enabled).
	 *
	 * @return void
	 */
	public static function uninstall() {
		global $wpdb;

		foreach ( Config::tables() as $table ) {
			$name = $wpdb->prefix . $table;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- schema teardown.
			$wpdb->query( "DROP TABLE IF EXISTS {$name}" );
		}
	}
}
