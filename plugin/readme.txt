=== Meu Delivery 2.0 ===
Contributors: tucumafood
Requires at least: 6.0
Requires PHP: 7.4
Stable tag: 2.0.0-alpha.1
License: GPLv2 or later
Text Domain: meu-delivery-2

Base modular do Meu Delivery 2.0 para WordPress. Esta versão alpha contém a fundação técnica e um esqueleto de painel; ainda não oferece funções de operação comercial.

== Fase 1 — Fundação ==
* Criado carregador modular com namespace próprio.
* Criadas capacidades e o papel Gerente Meu Delivery.
* Criado schema inicial versionado e migração aditiva com verificação das tabelas.
* Criada rota REST de status protegida por capacidade.
* Criado shortcode [meu_delivery_app] com shell responsivo do painel.
* Dados do site não são apagados automaticamente na desinstalação.
* Nenhum site foi instalado ou atualizado por esta entrega.

== Shortcode ==
Use [meu_delivery_app] em uma página privada. Se outro plugin já registrar esse nome, o Meu Delivery 2.0 seleciona um nome alternativo livre: [md2_meu_delivery_app], [meu_delivery_2_app] ou [md2_meu_delivery_2_app]. O painel exige um usuário WordPress com a capacidade md2_access_panel.

== Estado da versão ==
Esta é uma versão de desenvolvimento para revisão da fundação. Catálogo, pedidos, checkout, autenticação própria do restaurante, Salão e relatórios ainda serão desenvolvidos em fases posteriores.
