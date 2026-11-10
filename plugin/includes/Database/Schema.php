<?php

namespace Tucuma\MeuDelivery\Database;

if (!defined('ABSPATH')) {
	exit;
}

final class Schema {
	private static $verification = null;

	public static function clear_verification_cache() {
		self::$verification = null;
	}

	public static function table($suffix) {
		global $wpdb;
		return $wpdb->prefix . 'md2_' . $suffix;
	}

	public static function statements() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$orders = self::table('orders');
		$items = self::table('order_items');
		$events = self::table('order_events');
		$salon_tables = self::table('salon_tables');
		$coupons = self::table('coupons');
		$coupon_usage = self::table('coupon_usage');

		return array(
			"CREATE TABLE {$orders} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				public_number varchar(40) NOT NULL,
				access_token_hash char(64) DEFAULT NULL,
				source varchar(20) NOT NULL DEFAULT 'delivery',
				status varchar(30) NOT NULL DEFAULT 'new',
				customer_name varchar(190) NOT NULL,
				customer_phone varchar(40) NOT NULL DEFAULT '',
				delivery_type varchar(20) NOT NULL DEFAULT 'delivery',
				address text DEFAULT NULL,
				neighborhood varchar(190) DEFAULT NULL,
				address_details text DEFAULT NULL,
				notes text DEFAULT NULL,
				payment_method varchar(40) DEFAULT NULL,
				change_for decimal(12,2) DEFAULT NULL,
				subtotal decimal(12,2) NOT NULL DEFAULT 0.00,
				discount decimal(12,2) NOT NULL DEFAULT 0.00,
				delivery_fee decimal(12,2) NOT NULL DEFAULT 0.00,
				total decimal(12,2) NOT NULL DEFAULT 0.00,
				coupon_id bigint(20) unsigned DEFAULT NULL,
				salon_table_id bigint(20) unsigned DEFAULT NULL,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				completed_at datetime DEFAULT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY public_number (public_number),
				KEY source_status_created (source,status,created_at),
				KEY salon_table_status (salon_table_id,status),
				KEY created_at (created_at),
				KEY access_token_hash (access_token_hash)
			) {$charset_collate};",
			"CREATE TABLE {$items} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				order_id bigint(20) unsigned NOT NULL,
				position smallint(5) unsigned NOT NULL DEFAULT 0,
				product_id bigint(20) unsigned DEFAULT NULL,
				product_name varchar(190) NOT NULL,
				quantity int(10) unsigned NOT NULL DEFAULT 1,
				unit_price decimal(12,2) NOT NULL DEFAULT 0.00,
				line_total decimal(12,2) NOT NULL DEFAULT 0.00,
				addons_snapshot longtext DEFAULT NULL,
				observation text DEFAULT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY order_position (order_id,position),
				KEY product_id (product_id)
			) {$charset_collate};",
			"CREATE TABLE {$events} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				order_id bigint(20) unsigned NOT NULL,
				from_status varchar(30) DEFAULT NULL,
				to_status varchar(30) NOT NULL,
				actor_user_id bigint(20) unsigned DEFAULT NULL,
				note text DEFAULT NULL,
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY order_created (order_id,created_at),
				KEY actor_user_id (actor_user_id)
			) {$charset_collate};",
			"CREATE TABLE {$salon_tables} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				name varchar(190) NOT NULL,
				sort_order int(11) NOT NULL DEFAULT 0,
				active tinyint(1) NOT NULL DEFAULT 1,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY active_sort (active,sort_order),
				KEY name (name)
			) {$charset_collate};",
			"CREATE TABLE {$coupons} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				code varchar(64) NOT NULL,
				discount_type varchar(20) NOT NULL DEFAULT 'percent',
				amount decimal(12,2) NOT NULL DEFAULT 0.00,
				minimum_order decimal(12,2) NOT NULL DEFAULT 0.00,
				starts_at datetime DEFAULT NULL,
				ends_at datetime DEFAULT NULL,
				usage_limit int(10) unsigned NOT NULL DEFAULT 0,
				used_count int(10) unsigned NOT NULL DEFAULT 0,
				active tinyint(1) NOT NULL DEFAULT 1,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY code (code),
				KEY active_dates (active,starts_at,ends_at)
			) {$charset_collate};",
			"CREATE TABLE {$coupon_usage} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				coupon_id bigint(20) unsigned NOT NULL,
				order_id bigint(20) unsigned NOT NULL,
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY coupon_order (coupon_id,order_id),
				KEY order_id (order_id)
			) {$charset_collate};",
		);
	}

	public static function verify() {
		global $wpdb;
		if (self::$verification !== null) {
			return self::$verification;
		}

		$requirements = self::requirements();
		foreach ($requirements as $suffix => $required) {
			$table = self::table($suffix);
			$like = $wpdb->esc_like($table);
			$found = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $like));
			if ($found !== $table) {
				return self::$verification = false;
			}

			$columns = $wpdb->get_results("SHOW FULL COLUMNS FROM `{$table}`", ARRAY_A);
			if (!is_array($columns)) {
				return self::$verification = false;
			}
			$actual_columns = array();
			foreach ($columns as $column) {
				$actual_columns[$column['Field']] = $column;
			}
			foreach ($required['columns'] as $name => $definition) {
				if (!isset($actual_columns[$name])) {
					return self::$verification = false;
				}
				$actual = $actual_columns[$name];
				if (self::normalize_type($actual['Type']) !== self::normalize_type($definition[0])) {
					return self::$verification = false;
				}
				if (strtoupper($actual['Null']) !== $definition[1]) {
					return self::$verification = false;
				}
			}

			$table_status = $wpdb->get_row(
				$wpdb->prepare('SHOW TABLE STATUS WHERE Name = %s', $table),
				ARRAY_A
			);
			if (!$table_status || empty($table_status['Engine']) || strtoupper($table_status['Engine']) !== 'INNODB') {
				return self::$verification = false;
			}

			$indexes = $wpdb->get_results("SHOW INDEX FROM `{$table}`", ARRAY_A);
			if (!self::indexes_match($indexes, $required['indexes'])) {
				return self::$verification = false;
			}
		}

		return self::$verification = true;
	}

	private static function normalize_type($type) {
		$type = strtolower(preg_replace('/\s+/', ' ', trim($type)));
		$type = preg_replace('/\b(tinyint|smallint|mediumint|int|integer|bigint)\(\d+\)/', '$1', $type);
		return $type;
	}

	private static function indexes_match($rows, $expected) {
		if (!is_array($rows)) {
			return false;
		}
		$actual = array();
		foreach ($rows as $row) {
			$name = $row['Key_name'];
			$actual[$name]['unique'] = (int) $row['Non_unique'] === 0;
			$actual[$name]['columns'][(int) $row['Seq_in_index']] = $row['Column_name'];
		}
		foreach ($actual as &$index) {
			ksort($index['columns']);
			$index['columns'] = array_values($index['columns']);
		}
		unset($index);
		foreach ($expected as $name => $definition) {
			if (!isset($actual[$name]) || $actual[$name]['unique'] !== $definition[0] || $actual[$name]['columns'] !== $definition[1]) {
				return false;
			}
		}
		return true;
	}

	private static function requirements() {
		return array(
			'orders' => array(
				'columns' => array(
					'id' => array('bigint(20) unsigned', 'NO'), 'public_number' => array('varchar(40)', 'NO'), 'access_token_hash' => array('char(64)', 'YES'),
					'source' => array('varchar(20)', 'NO'), 'status' => array('varchar(30)', 'NO'), 'customer_name' => array('varchar(190)', 'NO'),
					'customer_phone' => array('varchar(40)', 'NO'), 'delivery_type' => array('varchar(20)', 'NO'), 'address' => array('text', 'YES'),
					'neighborhood' => array('varchar(190)', 'YES'), 'address_details' => array('text', 'YES'), 'notes' => array('text', 'YES'),
					'payment_method' => array('varchar(40)', 'YES'), 'change_for' => array('decimal(12,2)', 'YES'), 'subtotal' => array('decimal(12,2)', 'NO'),
					'discount' => array('decimal(12,2)', 'NO'), 'delivery_fee' => array('decimal(12,2)', 'NO'), 'total' => array('decimal(12,2)', 'NO'),
					'coupon_id' => array('bigint(20) unsigned', 'YES'), 'salon_table_id' => array('bigint(20) unsigned', 'YES'), 'created_at' => array('datetime', 'NO'),
					'updated_at' => array('datetime', 'NO'), 'completed_at' => array('datetime', 'YES'),
				),
				'indexes' => array('PRIMARY' => array(true, array('id')), 'public_number' => array(true, array('public_number')), 'source_status_created' => array(false, array('source', 'status', 'created_at')), 'salon_table_status' => array(false, array('salon_table_id', 'status')), 'created_at' => array(false, array('created_at')), 'access_token_hash' => array(false, array('access_token_hash'))),
			),
			'order_items' => array(
				'columns' => array('id' => array('bigint(20) unsigned', 'NO'), 'order_id' => array('bigint(20) unsigned', 'NO'), 'position' => array('smallint(5) unsigned', 'NO'), 'product_id' => array('bigint(20) unsigned', 'YES'), 'product_name' => array('varchar(190)', 'NO'), 'quantity' => array('int(10) unsigned', 'NO'), 'unit_price' => array('decimal(12,2)', 'NO'), 'line_total' => array('decimal(12,2)', 'NO'), 'addons_snapshot' => array('longtext', 'YES'), 'observation' => array('text', 'YES')),
				'indexes' => array('PRIMARY' => array(true, array('id')), 'order_position' => array(true, array('order_id', 'position')), 'product_id' => array(false, array('product_id'))),
			),
			'order_events' => array(
				'columns' => array('id' => array('bigint(20) unsigned', 'NO'), 'order_id' => array('bigint(20) unsigned', 'NO'), 'from_status' => array('varchar(30)', 'YES'), 'to_status' => array('varchar(30)', 'NO'), 'actor_user_id' => array('bigint(20) unsigned', 'YES'), 'note' => array('text', 'YES'), 'created_at' => array('datetime', 'NO')),
				'indexes' => array('PRIMARY' => array(true, array('id')), 'order_created' => array(false, array('order_id', 'created_at')), 'actor_user_id' => array(false, array('actor_user_id'))),
			),
			'salon_tables' => array(
				'columns' => array('id' => array('bigint(20) unsigned', 'NO'), 'name' => array('varchar(190)', 'NO'), 'sort_order' => array('int(11)', 'NO'), 'active' => array('tinyint(1)', 'NO'), 'created_at' => array('datetime', 'NO'), 'updated_at' => array('datetime', 'NO')),
				'indexes' => array('PRIMARY' => array(true, array('id')), 'active_sort' => array(false, array('active', 'sort_order')), 'name' => array(false, array('name'))),
			),
			'coupons' => array(
				'columns' => array('id' => array('bigint(20) unsigned', 'NO'), 'code' => array('varchar(64)', 'NO'), 'discount_type' => array('varchar(20)', 'NO'), 'amount' => array('decimal(12,2)', 'NO'), 'minimum_order' => array('decimal(12,2)', 'NO'), 'starts_at' => array('datetime', 'YES'), 'ends_at' => array('datetime', 'YES'), 'usage_limit' => array('int(10) unsigned', 'NO'), 'used_count' => array('int(10) unsigned', 'NO'), 'active' => array('tinyint(1)', 'NO'), 'created_at' => array('datetime', 'NO'), 'updated_at' => array('datetime', 'NO')),
				'indexes' => array('PRIMARY' => array(true, array('id')), 'code' => array(true, array('code')), 'active_dates' => array(false, array('active', 'starts_at', 'ends_at'))),
			),
			'coupon_usage' => array(
				'columns' => array('id' => array('bigint(20) unsigned', 'NO'), 'coupon_id' => array('bigint(20) unsigned', 'NO'), 'order_id' => array('bigint(20) unsigned', 'NO'), 'created_at' => array('datetime', 'NO')),
				'indexes' => array('PRIMARY' => array(true, array('id')), 'coupon_order' => array(true, array('coupon_id', 'order_id')), 'order_id' => array(false, array('order_id'))),
			),
		);
	}
}
