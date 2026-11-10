<?php

namespace Tucuma\MeuDelivery\Database;

if (!defined('ABSPATH')) {
	exit;
}

final class Migrator {
	public static function maybe_migrate() {
		if ((string) get_option('md2_db_version', '') === MD2_DB_VERSION) {
			return;
		}

		self::migrate();
	}

	public static function migrate() {
		global $wpdb;
		$lock_name = 'md2_schema_' . substr(md5(DB_NAME . '|' . $wpdb->prefix), 0, 32);
		$lock = $wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s,0)', $lock_name));

		if ((int) $lock !== 1) {
			return false;
		}

		$success = false;

		try {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';

			foreach (Schema::statements() as $statement) {
				dbDelta($statement);
			}

			$success = Schema::verify();
			if (!$success) {
				error_log('Meu Delivery 2.0: database schema verification failed; migration version was not advanced.');
				return false;
			}

			update_option('md2_db_version', MD2_DB_VERSION, false);
			return (string) get_option('md2_db_version', '') === MD2_DB_VERSION;
		} catch (\Throwable $error) {
			error_log('Meu Delivery 2.0: database migration failed (' . get_class($error) . ').');
			return false;
		} finally {
			$wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)', $lock_name));
		}
	}
}
