<?php

namespace Tucuma\MeuDelivery\Admin;

use Tucuma\MeuDelivery\Database\Schema;
use Tucuma\MeuDelivery\Core\Plugin;

if (!defined('ABSPATH')) {
	exit;
}

final class Admin {
	public static function register_menu() {
		add_menu_page(
			__('Meu Delivery 2.0', 'meu-delivery-2'),
			__('Meu Delivery 2.0', 'meu-delivery-2'),
			'md2_access_panel',
			'meu-delivery-2',
			array(__CLASS__, 'render_page'),
			'dashicons-store',
			26
		);
	}

	public static function render_page() {
		if (!current_user_can('md2_access_panel')) {
			wp_die(esc_html__('Você não tem permissão para acessar esta página.', 'meu-delivery-2'));
		}

		$ready = Schema::verify();
		?>
		<div class="wrap">
			<h1><?php echo esc_html__('Meu Delivery 2.0', 'meu-delivery-2'); ?></h1>
			<p><?php echo esc_html__('Fundação inicial do sistema. Os módulos de operação serão implementados nas próximas fases.', 'meu-delivery-2'); ?></p>
			<p>
				<strong><?php echo esc_html__('Banco de dados:', 'meu-delivery-2'); ?></strong>
				<?php echo esc_html($ready ? __('Estrutura inicial criada', 'meu-delivery-2') : __('Aguardando migração', 'meu-delivery-2')); ?>
			</p>
			<p><code>[meu_delivery_app]</code> — <?php echo esc_html__('esqueleto do painel SaaS, acessível a usuários com a permissão md2_access_panel. Se outro plugin já registrar esse nome, use um dos shortcodes alternativos listados no readme do plugin.', 'meu-delivery-2'); ?></p>
		</div>
		<?php
	}

	public static function migration_notice() {
		if (!current_user_can('manage_options') || ((string) get_option('md2_db_version', '') === Plugin::DB_VERSION && Schema::verify())) {
			return;
		}

		$message = __('A estrutura do banco do Meu Delivery 2.0 ainda não foi confirmada. A migração será tentada novamente em uma próxima solicitação; consulte o log PHP se o aviso persistir.', 'meu-delivery-2');
		echo '<div class="notice notice-warning"><p>' . esc_html($message) . '</p></div>';
	}
}
