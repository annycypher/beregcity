<?php
/**
 * Гутенберг-паттерны (Этап 4.4, D2/D12): шаблоны статей организаций (премиум).
 *
 * @package BC_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function bc_register_patterns() {
	if ( ! function_exists( 'register_block_pattern' ) ) {
		return;
	}

	register_block_pattern_category( 'beregcity', array( 'label' => 'БерегСити — статьи' ) );

	register_block_pattern(
		'bc/article',
		array(
			'title'       => 'Статья организации',
			'description' => 'Контентная статья: заголовок, лид, основной текст (премиум, D2).',
			'categories'  => array( 'beregcity' ),
			'content'     => '<!-- wp:heading {"level":2} --><h2>Заголовок статьи</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Лид — одна фраза о главном.</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Основной текст. Расскажите конкретно: что, где, когда и чем вы отличаетесь. Без «лучшие в городе».</p><!-- /wp:paragraph -->',
		)
	);

	register_block_pattern(
		'bc/photo-text',
		array(
			'title'       => 'Фото + текст',
			'description' => 'Изображение с подписью и абзацем текста.',
			'categories'  => array( 'beregcity' ),
			'content'     => '<!-- wp:image {"sizeSlug":"large"} --><figure class="wp-block-image size-large"><img src="" alt=""/><figcaption>Подпись к фото</figcaption></figure><!-- /wp:image --><!-- wp:paragraph --><p>Текст после изображения — детали, адрес, часы работы.</p><!-- /wp:paragraph -->',
		)
	);

	register_block_pattern(
		'bc/promo-cta',
		array(
			'title'       => 'Акция с кнопкой',
			'description' => 'Акция организации с условиями и кнопкой-призывом.',
			'categories'  => array( 'beregcity' ),
			'content'     => '<!-- wp:heading {"level":3} --><h3>Акция: условие одним предложением</h3><!-- /wp:heading --><!-- wp:paragraph --><p>Условия акции, срок действия и промокод (если есть).</p><!-- /wp:paragraph --><!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button">Узнать подробнее</a></div><!-- /wp:button --></div><!-- /wp:buttons -->',
		)
	);
}
add_action( 'init', 'bc_register_patterns' );
