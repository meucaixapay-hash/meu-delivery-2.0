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
	const VERSION = '2.0.0-alpha.1';
	const DB_VERSION = '1';
	const SHORTCODE = 'meu_delivery_app';
	const SHORTCODE_FALLBACK = 'md2_meu_delivery_app';
	const SHORTCODE_ALTERNATE = 'meu_delivery_2_app';
	const SHORTCODE_TERTIARY = 'md2_meu_delivery_2_app';
	const MIGRATION_HOOK = 'tucuma_meu_delivery_2_retry_migration';

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
		add_action('elementor/frontend/before_enqueue_styles', array(Panel::class, 'enqueue_assets'));
		add_action('elementor/frontend/before_enqueue_scripts', array(Panel::class, 'enqueue_assets'));
		add_action('elementor/frontend/before_render', array(Panel::class, 'before_elementor_widget'));
		add_action('save_post_elementor_library', array(Panel::class, 'clear_elementor_template_cache'));
		add_action(self::MIGRATION_HOOK, array(Migrator::class, 'retry_scheduled'));
		$shortcodes = array(self::SHORTCODE, self::SHORTCODE_FALLBACK, self::SHORTCODE_ALTERNATE, self::SHORTCODE_TERTIARY);
		foreach ($shortcodes as $shortcode) {
			if (!shortcode_exists($shortcode)) {
				add_shortcode($shortcode, array(Panel::class, 'render_shortcode'));
				break;
			}
		}
	}
}
