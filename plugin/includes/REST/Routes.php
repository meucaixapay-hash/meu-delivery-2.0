<?php

namespace Tucuma\MeuDelivery\REST;

use Tucuma\MeuDelivery\Database\Schema;

if (!defined('ABSPATH')) {
	exit;
}

final class Routes {
	public static function register() {
		register_rest_route(
			'meu-delivery/v1',
			'/setup/status',
			array(
				'methods' => \WP_REST_Server::READABLE,
				'callback' => array(__CLASS__, 'setup_status'),
				'permission_callback' => array(__CLASS__, 'can_access_panel'),
			)
		);
	}

	public static function can_access_panel() {
		return current_user_can('md2_access_panel');
	}

	public static function setup_status() {
		return rest_ensure_response(
			array(
				'plugin_version' => MD2_VERSION,
				'database_version' => (string) get_option('md2_db_version', ''),
				'database_ready' => Schema::verify(),
			)
		);
	}
}
