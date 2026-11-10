<?php
/** Lightweight PSR-4-style autoloader for this plugin. */

namespace Tucuma\MeuDelivery\Core;

if (!defined('ABSPATH')) {
	exit;
}

final class Autoloader {
	public static function register() {
		spl_autoload_register(array(__CLASS__, 'load'));
	}

	public static function load($class_name) {
		$prefix = 'Tucuma\\MeuDelivery\\';
		$prefix_length = strlen($prefix);

		if (strncmp($prefix, $class_name, $prefix_length) !== 0) {
			return;
		}

		$relative_class = substr($class_name, $prefix_length);
		$file = MD2_PLUGIN_DIR . 'includes/' . str_replace('\\', '/', $relative_class) . '.php';

		if (is_readable($file)) {
			require_once $file;
		}
	}
}
