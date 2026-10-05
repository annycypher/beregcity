<?php
/**
 * Компонент карточки организации — сетка каталога и «Похожие рядом».
 * Разметка 1:1 с design/catalog.html (.org-card).
 *
 * @package BC
 */

$bc_phone   = get_field( 'field_bc_phone', get_the_ID() );
$bc_address = get_field( 'field_bc_address', get_the_ID() );
$bc_excerpt = get_field( 'field_bc_excerpt', get_the_ID() );
$bc_cats    = get_the_terms( get_the_ID(), 'bc_cat' );
$bc_cat     = ( $bc_cats && ! is_wp_error( $bc_cats ) ) ? $bc_cats[0]->name : '';
$bc_feats   = get_the_terms( get_the_ID(), 'features' );
?>
<article class="org-card">
	<div class="org-cover">
		<?php if ( has_post_thumbnail() ) : ?>
			<?php the_post_thumbnail( 'medium_large' ); ?>
		<?php else : ?>
			<svg class="ico" viewBox="0 0 24 24"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/></svg>
		<?php endif; ?>
	</div>
	<div class="org-body">
		<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
		<span class="org-sub"><?php echo esc_html( trim( $bc_cat . ( $bc_address ? ' · ' . $bc_address : '' ) ) ); ?></span>
		<?php if ( $bc_feats && ! is_wp_error( $bc_feats ) ) : ?>
		<div class="org-feats">
			<?php $bc_i = 0; foreach ( $bc_feats as $bc_f ) : if ( ++$bc_i > 7 ) { break; } ?>
				<span class="feat" title="<?php echo esc_attr( $bc_f->name ); ?>"><svg class="ico" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg></span>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>
		<?php if ( $bc_excerpt ) : ?><p class="org-desc"><?php echo esc_html( $bc_excerpt ); ?></p><?php endif; ?>
		<div class="org-foot">
			<?php if ( $bc_phone ) : ?>
				<a class="org-phone" href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $bc_phone ) ); ?>"><svg class="ico" viewBox="0 0 24 24"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3-8.6A2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/></svg><?php echo esc_html( $bc_phone ); ?></a>
			<?php endif; ?>
			<a class="btn btn-glass btn-sm" href="<?php the_permalink(); ?>">Подробнее</a>
		</div>
	</div>
</article>
