<?php

namespace Tucuma\MeuDelivery\Database;

use Tucuma\MeuDelivery\Core\Plugin;

if (!defined('ABSPATH')) {
	exit;
}

final class Migrator {
	public static function maybe_migrate() {
		$version_current = (string) get_option('md2_db_version', '') === Plugin::DB_VERSION;
		$verified_at = (int) get_option('md2_schema_verified_at', 0);
		$should_verify = !$verified_at || (function_exists('is_admin') && is_admin()) || (time() - $verified_at > DAY_IN_SECONDS);
		if ($version_current && !$should_verify) {
			return true;
		}
		if ($version_current && Schema::verify()) {
			update_option('md2_schema_verified_at', time(), false);
			return true;
		}

		$retry = get_option('md2_migration_retry', array());
		$retry_at = isset($retry['retry_at']) ? (int) $retry['retry_at'] : 0;
		$is_cli = defined('WP_CLI') && WP_CLI;
		if (!$is_cli && $retry_at > time()) {
			return false;
		}

		return self::migrate();
	}

	public static function retry_scheduled() {
		if ((string) get_option('md2_db_version', '') === Plugin::DB_VERSION && Schema::verify()) {
			update_option('md2_schema_verified_at', time(), false);
			delete_option('md2_migration_retry');
			return true;
		}
		return self::migrate();
	}

	public static function migrate() {
		global $wpdb;
		$lock_name = 'md2_schema_' . substr(md5(DB_NAME . '|' . $wpdb->prefix), 0, 32);
		$lock = $wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s,0)', $lock_name));

		if ((int) $lock !== 1) {
			self::record_failure();
			return false;
		}

		$success = false;

		try {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';

			foreach (Schema::statements() as $statement) {
				dbDelta($statement);
			}

			Schema::clear_verification_cache();
			$success = Schema::verify();
			if (!$success) {
				error_log('Meu Delivery 2.0: database schema verification failed; migration version was not advanced.');
				self::record_failure();
				return false;
			}

			update_option('md2_db_version', Plugin::DB_VERSION, false);
			update_option('md2_schema_verified_at', time(), false);
			$success = (string) get_option('md2_db_version', '') === Plugin::DB_VERSION;
			if ($success) {
				delete_option('md2_migration_retry');
				wp_clear_scheduled_hook(Plugin::MIGRATION_HOOK);
				return true;
			}
			self::record_failure();
			return false;
		} catch (\Throwable $error) {
			error_log('Meu Delivery 2.0: database migration failed (' . get_class($error) . ').');
			self::record_failure();
			return false;
		} finally {
			$wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)', $lock_name));
		}
	}

	private static function record_failure() {
		$retry = get_option('md2_migration_retry', array());
		$attempts = isset($retry['attempts']) ? (int) $retry['attempts'] + 1 : 1;
		$delay = min(21600, 30 * (2 ** min(10, $attempts - 1)));
		$retry_at = time() + $delay;
		update_option('md2_migration_retry', array('attempts' => $attempts, 'retry_at' => $retry_at), false);
		if (!wp_next_scheduled(Plugin::MIGRATION_HOOK)) {
			wp_schedule_single_event($retry_at, Plugin::MIGRATION_HOOK);
		}
	}
}
