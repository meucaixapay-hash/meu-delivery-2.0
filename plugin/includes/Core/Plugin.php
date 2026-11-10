<?php

namespace Tucuma\MeuDelivery\Core;

use Tucuma\MeuDelivery\Admin\Admin;
use Tucuma\MeuDelivery\Database\Migrator;
use Tucuma\MeuDelivery\Owner\Panel;
use Tucuma\MeuDelivery\REST\Routes;
use Tucuma\MeuDelivery\Security\Capabilities;

if (!defined('ABSPATH')) {
	exit;
}

final class Plugin {
	private static $booted = false;

	public static function boot() {
		if (self::$booted) {
			return;
		}

		self::$booted = true;

		add_action('init', array(Migrator::class, 'maybe_migrate'), 1);
		add_action('init', array(Capabilities::class, 'ensure'), 2);
		add_action('rest_api_init', array(Routes::class, 'register'));
		add_action('admin_menu', array(Admin::class, 'register_menu'));
		add_action('admin_notices', array(Admin::class, 'migration_notice'));
		add_action('wp_enqueue_scripts', array(Panel::class, 'enqueue_assets'));
		add_shortcode('meu_delivery_app', array(Panel::class, 'render_shortcode'));
	}
}
