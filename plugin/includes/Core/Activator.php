<?php

namespace Tucuma\MeuDelivery\Core;

use Tucuma\MeuDelivery\Database\Migrator;
use Tucuma\MeuDelivery\Security\Capabilities;

if (!defined('ABSPATH')) {
	exit;
}

final class Activator {
	public static function activate() {
		Capabilities::ensure();

		if (!Migrator::migrate()) {
			// Keep the plugin active; the next WordPress request retries safely.
			error_log('Meu Delivery 2.0: initial database migration is pending.');
		}
	}
}
