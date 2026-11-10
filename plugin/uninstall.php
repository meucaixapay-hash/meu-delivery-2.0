<?php
/**
 * The initial release intentionally preserves store data on uninstall.
 * A future, explicit data-removal flow must require a separate confirmation.
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}
