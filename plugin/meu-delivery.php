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

define('MD2_VERSION', '2.0.0-alpha.1');
define('MD2_DB_VERSION', '1');
define('MD2_PLUGIN_FILE', __FILE__);
define('MD2_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MD2_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once MD2_PLUGIN_DIR . 'includes/Core/Autoloader.php';
\Tucuma\MeuDelivery\Core\Autoloader::register();

register_activation_hook(
	__FILE__,
	array(\Tucuma\MeuDelivery\Core\Activator::class, 'activate')
);

add_action(
	'plugins_loaded',
	array(\Tucuma\MeuDelivery\Core\Plugin::class, 'boot')
);
