<?php

namespace Tucuma\MeuDelivery\Security;

if (!defined('ABSPATH')) {
	exit;
}

final class Capabilities {
	const ROLE = 'md2_store_manager';

	public static function all() {
		return array(
			'md2_access_panel',
			'md2_manage_menu',
			'md2_manage_orders',
			'md2_manage_salon',
			'md2_manage_coupons',
			'md2_manage_settings',
			'md2_view_reports',
		);
	}

	public static function ensure() {
		if ((string) get_option('md2_capabilities_version', '') === '1') {
			return;
		}

		$capabilities = self::all();
		$role = get_role(self::ROLE);

		if (!$role) {
			$role = add_role(
				self::ROLE,
				__('Gerente Meu Delivery', 'meu-delivery-2'),
				array('read' => true)
			);
		}

		if ($role) {
			foreach ($capabilities as $capability) {
				$role->add_cap($capability);
			}
		}

		$administrator = get_role('administrator');
		if ($administrator) {
			foreach ($capabilities as $capability) {
				$administrator->add_cap($capability);
			}
		}

		update_option('md2_capabilities_version', '1', false);
	}
}
