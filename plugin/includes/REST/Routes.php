<?php

namespace Tucuma\MeuDelivery\REST;

use Tucuma\MeuDelivery\Database\Schema;
use Tucuma\MeuDelivery\Core\Plugin;

if (!defined('ABSPATH')) {
	exit;
}

final class Routes {
	public static function register() {
		$route = array(
			'/setup/status',
			array(
				'methods' => \WP_REST_Server::READABLE,
				'callback' => array(__CLASS__, 'setup_status'),
				'permission_callback' => array(__CLASS__, 'can_access_panel'),
			)
		);
		register_rest_route(
			'meu-delivery-2/v1',
			$route[0],
			$route[1]
		);

		$legacy_route = '/meu-delivery/v1/setup/status';
		$registered_routes = rest_get_server()->get_routes();
		if (empty($registered_routes[$legacy_route])) {
			register_rest_route(
				'meu-delivery/v1',
				'/setup/status',
				$route[1]
			);
		}
	}

	public static function can_access_panel() {
		return current_user_can('md2_access_panel');
	}

	public static function setup_status() {
		return rest_ensure_response(
			array(
				'plugin_version' => Plugin::VERSION,
				'database_version' => (string) get_option('md2_db_version', ''),
				'database_ready' => Schema::verify(),
			)
		);
	}
}
