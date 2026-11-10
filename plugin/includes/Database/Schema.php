<?php

namespace Tucuma\MeuDelivery\Database;

if (!defined('ABSPATH')) {
	exit;
}

final class Schema {
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
		$required_tables = array(
			'orders' => array('id', 'public_number', 'source', 'status', 'total', 'created_at'),
			'order_items' => array('id', 'order_id', 'position', 'product_name', 'quantity', 'line_total'),
			'order_events' => array('id', 'order_id', 'to_status', 'created_at'),
			'salon_tables' => array('id', 'name', 'sort_order', 'active'),
			'coupons' => array('id', 'code', 'discount_type', 'active'),
			'coupon_usage' => array('id', 'coupon_id', 'order_id'),
		);

		foreach ($required_tables as $suffix => $required_columns) {
			$table = self::table($suffix);
			$like = $wpdb->esc_like($table);
			$found = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $like));
			if ($found !== $table) {
				return false;
			}

			foreach ($required_columns as $column) {
				$found_column = $wpdb->get_var(
					$wpdb->prepare("SHOW COLUMNS FROM `{$table}` LIKE %s", $column)
				);
				if (!$found_column) {
					return false;
				}
			}
		}

		return true;
	}
}
