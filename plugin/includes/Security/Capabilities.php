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
		if ((string) get_option('md2_capabilities_version', '') === '1' && self::verify()) {
			return true;
		}

		$capabilities = self::all();
		$role = get_role(self::ROLE);

		if (!$role) {
			$role = add_role(
				self::ROLE,
				__('Gerente Meu Delivery', 'meu-delivery-2'),
				array('read' => true)
			);
			$role = $role ? get_role(self::ROLE) : false;
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

		if (!self::verify()) {
			delete_option('md2_capabilities_version');
			return false;
		}

		update_option('md2_capabilities_version', '1', false);
		return true;
	}

	private static function verify() {
		$role = get_role(self::ROLE);
		$administrator = get_role('administrator');
		if (!$role || !$administrator) {
			return false;
		}
		foreach (self::all() as $capability) {
			if (!$role->has_cap($capability) || !$administrator->has_cap($capability)) {
				return false;
			}
		}
		return true;
	}
}
