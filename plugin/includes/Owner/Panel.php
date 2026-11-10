<?php

namespace Tucuma\MeuDelivery\Owner;

if (!defined('ABSPATH')) {
	exit;
}

final class Panel {
	public static function enqueue_assets() {
		if (!is_singular()) {
			return;
		}

		$post = get_post();
		if (!$post || !has_shortcode($post->post_content, 'meu_delivery_app')) {
			return;
		}

		wp_enqueue_style(
			'md2-app',
			MD2_PLUGIN_URL . 'assets/css/app.css',
			array(),
			MD2_VERSION
		);
		wp_enqueue_script(
			'md2-app',
			MD2_PLUGIN_URL . 'assets/js/app.js',
			array(),
			MD2_VERSION,
			true
		);
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
		ob_start();
		?>
		<div class="md2-app" data-md2-app>
			<button class="md2-backdrop" type="button" data-md2-close aria-label="<?php echo esc_attr__('Fechar navegação', 'meu-delivery-2'); ?>" hidden></button>
			<aside class="md2-sidebar" id="md2-sidebar" aria-label="<?php echo esc_attr__('Navegação principal', 'meu-delivery-2'); ?>">
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
			<main class="md2-main" id="md2-overview">
				<header class="md2-topbar"><button class="md2-menu-toggle" type="button" aria-controls="md2-sidebar" aria-expanded="false" data-md2-open><span aria-hidden="true">☰</span><span class="screen-reader-text"><?php echo esc_html__('Abrir menu', 'meu-delivery-2'); ?></span></button><div class="md2-breadcrumb"><span><?php echo esc_html__('Meu Delivery', 'meu-delivery-2'); ?></span><b>/</b><strong><?php echo esc_html__('Visão geral', 'meu-delivery-2'); ?></strong></div><span class="md2-version-chip">2.0 · FOUNDATION</span></header>
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
