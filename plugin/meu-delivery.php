<?php
/**
 * Plugin Name: Meu Delivery 2.0
 * Plugin URI:  https://tucumafood.com.br/
 * Description: Base modular do Meu Delivery para restaurantes.
 * Version:     2.0.0-alpha.1
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author:      TUCUMÃ food
 * Text Domain: meu-delivery-2
 */

if (!defined('ABSPATH')) {
	exit;
}

require_once __DIR__ . '/includes/Core/Autoloader.php';
\Tucuma\MeuDelivery\Core\Autoloader::register();

register_activation_hook(
	__FILE__,
	array(\Tucuma\MeuDelivery\Core\Activator::class, 'activate')
);

add_action(
	'plugins_loaded',
	array(\Tucuma\MeuDelivery\Core\Plugin::class, 'boot')
);
