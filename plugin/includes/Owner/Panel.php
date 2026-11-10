<?php

namespace Tucuma\MeuDelivery\Owner;

use Tucuma\MeuDelivery\Core\Plugin;

if (!defined('ABSPATH')) {
	exit;
}

final class Panel {
	private static $instance = 0;
	private static $assets_enqueued = false;
	private static $visited_templates = array();
	const ELEMENTOR_TEMPLATE_CACHE = 'tucuma_meu_delivery_2_elementor_panel_templates';

	public static function enqueue_assets() {
		if (self::$assets_enqueued || !self::page_uses_panel()) {
			return;
		}
		self::register_assets();
	}

	public static function before_elementor_widget($widget) {
		if (self::$assets_enqueued || !is_object($widget) || !method_exists($widget, 'get_settings_for_display')) {
			return;
		}
		$settings = $widget->get_settings_for_display();
		if (self::contains_panel_shortcode($settings)) {
			self::register_assets();
		}
	}

	private static function page_uses_panel() {
		$post = get_post();
		if ($post) {
			$shortcodes = array(Plugin::SHORTCODE, Plugin::SHORTCODE_FALLBACK, Plugin::SHORTCODE_ALTERNATE, Plugin::SHORTCODE_TERTIARY);
			foreach ($shortcodes as $shortcode) {
				if (has_shortcode($post->post_content, $shortcode)) {
					return true;
				}
			}
			$elementor_data = get_post_meta($post->ID, '_elementor_data', true);
			if (self::contains_panel_shortcode($elementor_data)) {
				return true;
			}
		}
		return class_exists('\\Elementor\\Plugin') && self::elementor_template_uses_panel();
	}

	public static function clear_elementor_template_cache($post_id = 0) {
		delete_transient(self::ELEMENTOR_TEMPLATE_CACHE);
	}

	private static function elementor_template_uses_panel() {
		$cached = get_transient(self::ELEMENTOR_TEMPLATE_CACHE);
		if ($cached !== false) {
			return (bool) $cached;
		}
		$shortcodes = array(Plugin::SHORTCODE, Plugin::SHORTCODE_FALLBACK, Plugin::SHORTCODE_ALTERNATE, Plugin::SHORTCODE_TERTIARY);
		$meta_query = array('relation' => 'OR');
		foreach ($shortcodes as $shortcode) {
			$meta_query[] = array('key' => '_elementor_data', 'value' => $shortcode, 'compare' => 'LIKE');
		}
		$templates = get_posts(array(
			'post_type' => 'elementor_library',
			'post_status' => 'publish',
			'posts_per_page' => 1,
			'fields' => 'ids',
			'no_found_rows' => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'meta_query' => $meta_query,
		));
		$uses_panel = !empty($templates);
		set_transient(self::ELEMENTOR_TEMPLATE_CACHE, $uses_panel ? 1 : 0, 5 * MINUTE_IN_SECONDS);
		return $uses_panel;
	}

	private static function contains_panel_shortcode($value) {
		if (is_string($value)) {
			$decoded = json_decode($value, true);
			if (is_array($decoded)) {
				return self::contains_panel_shortcode($decoded);
			}
			if (strpos($value, '[') === false) {
				return false;
			}
		}
		if (is_array($value)) {
			foreach ($value as $key => $item) {
				if (in_array($key, array('template_id', 'templateID'), true) && is_numeric($item)) {
					$template_id = absint($item);
					if ($template_id && empty(self::$visited_templates[$template_id])) {
						self::$visited_templates[$template_id] = true;
						$template_data = get_post_meta($template_id, '_elementor_data', true);
						if (self::contains_panel_shortcode($template_data)) {
							return true;
						}
					}
				}
				if (self::contains_panel_shortcode($item)) {
					return true;
				}
			}
			return false;
		}
		if (!is_string($value) || strpos($value, '[') === false) {
			return false;
		}
		return has_shortcode($value, Plugin::SHORTCODE) || has_shortcode($value, Plugin::SHORTCODE_FALLBACK) || has_shortcode($value, Plugin::SHORTCODE_ALTERNATE) || has_shortcode($value, Plugin::SHORTCODE_TERTIARY);
	}

	private static function register_assets() {
		if (self::$assets_enqueued) {
			return;
		}
		$plugin_file = dirname(__DIR__, 2) . '/meu-delivery.php';
		$plugin_url = plugin_dir_url($plugin_file);

		wp_enqueue_style(
			'meu-delivery-2-app',
			$plugin_url . 'assets/css/app.css',
			array(),
			Plugin::VERSION
		);
		wp_enqueue_script(
			'meu-delivery-2-app',
			$plugin_url . 'assets/js/app.js',
			array(),
			Plugin::VERSION,
			true
		);
		self::$assets_enqueued = true;
	}

	public static function render_shortcode() {
		if (!is_user_logged_in()) {
			$login_url = wp_login_url(get_permalink());
			return '<section class="md2-login-required"><h2>' . esc_html__('Acesso ao painel', 'meu-delivery-2') . '</h2><p>' . esc_html__('Entre com sua conta WordPress autorizada para acessar o painel.', 'meu-delivery-2') . '</p><a href="' . esc_url($login_url) . '">' . esc_html__('Entrar', 'meu-delivery-2') . '</a></section>';
		}

		if (!current_user_can('md2_access_panel')) {
			return '<section class="md2-login-required"><h2>' . esc_html__('Acesso não autorizado', 'meu-delivery-2') . '</h2><p>' . esc_html__('Sua conta não possui permissão para acessar este painel.', 'meu-delivery-2') . '</p></section>';
		}

		$user = wp_get_current_user();
		$instance_id = 'md2-panel-' . (++self::$instance);
		$sidebar_id = $instance_id . '-sidebar';
		$main_id = $instance_id . '-main';
		ob_start();
		?>
		<div class="md2-app" id="<?php echo esc_attr($instance_id); ?>" data-md2-app>
			<button class="md2-backdrop" type="button" data-md2-close aria-label="<?php echo esc_attr__('Fechar navegação', 'meu-delivery-2'); ?>" hidden tabindex="-1"></button>
			<aside class="md2-sidebar" id="<?php echo esc_attr($sidebar_id); ?>" aria-label="<?php echo esc_attr__('Navegação principal', 'meu-delivery-2'); ?>">
				<button class="md2-menu-close" type="button" data-md2-close><?php echo esc_html__('Fechar menu', 'meu-delivery-2'); ?><span aria-hidden="true">×</span></button>
				<div class="md2-brand"><span class="md2-brand-mark" aria-hidden="true">M</span><span><strong>Meu Delivery</strong><small>by TUCUMÃ food</small></span></div>
				<div class="md2-store"><span class="md2-store-avatar" aria-hidden="true">T</span><span><strong><?php echo esc_html(get_bloginfo('name')); ?></strong><small><?php echo esc_html__('Painel do restaurante', 'meu-delivery-2'); ?></small></span></div>
				<nav class="md2-nav" aria-label="<?php echo esc_attr__('Menu do painel', 'meu-delivery-2'); ?>">
					<span class="md2-nav-label"><?php echo esc_html__('ESPAÇO DE TRABALHO', 'meu-delivery-2'); ?></span>
					<a class="md2-nav-item is-active" href="#md2-overview" aria-current="page"><span class="md2-nav-icon" aria-hidden="true">▦</span><?php echo esc_html__('Visão geral', 'meu-delivery-2'); ?></a>
					<span class="md2-nav-label md2-nav-label-spaced"><?php echo esc_html__('MÓDULOS', 'meu-delivery-2'); ?></span>
					<span class="md2-nav-placeholder"><span aria-hidden="true">◷</span><?php echo esc_html__('Pedidos', 'meu-delivery-2'); ?><small><?php echo esc_html__('Em breve', 'meu-delivery-2'); ?></small></span>
					<span class="md2-nav-placeholder"><span aria-hidden="true">▤</span><?php echo esc_html__('Cardápio', 'meu-delivery-2'); ?><small><?php echo esc_html__('Em breve', 'meu-delivery-2'); ?></small></span>
					<span class="md2-nav-placeholder"><span aria-hidden="true">♧</span><?php echo esc_html__('Salão', 'meu-delivery-2'); ?><small><?php echo esc_html__('Em breve', 'meu-delivery-2'); ?></small></span>
				</nav>
				<div class="md2-sidebar-footer"><span class="md2-user-dot" aria-hidden="true"><?php echo esc_html(strtoupper(substr($user->display_name, 0, 1))); ?></span><span><strong><?php echo esc_html($user->display_name); ?></strong><small><?php echo esc_html__('Acesso autorizado', 'meu-delivery-2'); ?></small></span></div>
			</aside>
			<main class="md2-main" id="<?php echo esc_attr($main_id); ?>">
				<header class="md2-topbar"><button class="md2-menu-toggle" type="button" aria-controls="<?php echo esc_attr($sidebar_id); ?>" aria-expanded="false" data-md2-open><span aria-hidden="true">☰</span><span class="screen-reader-text"><?php echo esc_html__('Abrir menu', 'meu-delivery-2'); ?></span></button><div class="md2-breadcrumb"><span><?php echo esc_html__('Meu Delivery', 'meu-delivery-2'); ?></span><b>/</b><strong><?php echo esc_html__('Visão geral', 'meu-delivery-2'); ?></strong></div><span class="md2-version-chip">2.0 · FOUNDATION</span></header>
				<section class="md2-content">
					<div class="md2-page-heading"><div><span class="md2-eyebrow"><?php echo esc_html__('CENTRAL DO RESTAURANTE', 'meu-delivery-2'); ?></span><h1><?php echo esc_html__('Bem-vindo ao Meu Delivery', 'meu-delivery-2'); ?></h1><p><?php echo esc_html__('A nova base do sistema está pronta para receber os módulos do restaurante.', 'meu-delivery-2'); ?></p></div><span class="md2-status-pill"><i></i><?php echo esc_html__('Fundação ativa', 'meu-delivery-2'); ?></span></div>
						<div class="md2-hero-card"><div class="md2-hero-copy"><span class="md2-hero-kicker"><?php echo esc_html__('FASE 1 · FUNDAÇÃO', 'meu-delivery-2'); ?></span><h2><?php echo esc_html__('Uma operação mais clara começa aqui.', 'meu-delivery-2'); ?></h2><p><?php echo esc_html__('A estrutura modular, as permissões e o banco inicial foram preparados. Os recursos de operação serão adicionados e validados por fase.', 'meu-delivery-2'); ?></p><span class="md2-hero-note"><span aria-hidden="true">✦</span><?php echo esc_html__('Base preparada para evoluir com segurança', 'meu-delivery-2'); ?></span></div><div class="md2-hero-art" aria-hidden="true"><span class="md2-orbit md2-orbit-one"></span><span class="md2-orbit md2-orbit-two"></span><span class="md2-art-card"><b>MD</b><i></i><i></i><i></i></span><span class="md2-art-dot md2-art-dot-one"></span><span class="md2-art-dot md2-art-dot-two"></span></div></div>
						<div class="md2-section-title"><div><h2><?php echo esc_html__('Preparação do sistema', 'meu-delivery-2'); ?></h2><p><?php echo esc_html__('Estado da fundação técnica desta instalação.', 'meu-delivery-2'); ?></p></div></div>
						<div class="md2-foundation-grid"><article class="md2-foundation-card"><span class="md2-card-icon md2-icon-orange" aria-hidden="true">⌘</span><div><small><?php echo esc_html__('ARQUITETURA', 'meu-delivery-2'); ?></small><h3><?php echo esc_html__('Módulos separados', 'meu-delivery-2'); ?></h3><p><?php echo esc_html__('Carregamento organizado por responsabilidade.', 'meu-delivery-2'); ?></p></div><span class="md2-card-state"><?php echo esc_html__('Pronto', 'meu-delivery-2'); ?></span></article><article class="md2-foundation-card"><span class="md2-card-icon md2-icon-green" aria-hidden="true">✓</span><div><small><?php echo esc_html__('ACESSO', 'meu-delivery-2'); ?></small><h3><?php echo esc_html__('Permissões iniciais', 'meu-delivery-2'); ?></h3><p><?php echo esc_html__('Painel limitado a contas autorizadas.', 'meu-delivery-2'); ?></p></div><span class="md2-card-state"><?php echo esc_html__('Pronto', 'meu-delivery-2'); ?></span></article><article class="md2-foundation-card"><span class="md2-card-icon md2-icon-brown" aria-hidden="true">▦</span><div><small><?php echo esc_html__('DADOS', 'meu-delivery-2'); ?></small><h3><?php echo esc_html__('Schema versionado', 'meu-delivery-2'); ?></h3><p><?php echo esc_html__('Migrações aditivas e verificadas.', 'meu-delivery-2'); ?></p></div><span class="md2-card-state"><?php echo esc_html__('Pronto', 'meu-delivery-2'); ?></span></article></div>
						<div class="md2-next-step"><span class="md2-next-icon" aria-hidden="true">↗</span><div><strong><?php echo esc_html__('Próximo módulo: gerenciamento do catálogo', 'meu-delivery-2'); ?></strong><p><?php echo esc_html__('Produtos, categorias, imagens, adicionais e destaques.', 'meu-delivery-2'); ?></p></div><span class="md2-next-tag"><?php echo esc_html__('Planejado', 'meu-delivery-2'); ?></span></div>
					</section>
			</main>
		</div>
		<?php
		return ob_get_clean();
	}
}
