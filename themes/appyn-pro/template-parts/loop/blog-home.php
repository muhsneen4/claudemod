<?php
/**
 * Appyn Pro news card.
 *
 * @package Appyn_Pro
 */

if ( ! apx_on( 'takeover_cards' ) ) {
	load_template( get_template_directory() . '/template-parts/loop/blog-home.php', false );
	return;
}

$apx_terms    = get_the_terms( get_the_ID(), 'cblog' );
$apx_category = ( is_array( $apx_terms ) && ! empty( $apx_terms ) ) ? $apx_terms[0]->name : '';
$apx_words    = (int) apx_opt( 'news_excerpt_length' );
$apx_readtime = max( 1, (int) ceil( str_word_count( wp_strip_all_tags( get_the_content() ) ) / 200 ) );
?>
<div class="px-col">
	<article class="apx-news">
		<a class="apx-news__media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
			<?php if ( has_post_thumbnail() ) : ?>
				<?php the_post_thumbnail( 'medium_large', array( 'loading' => apx_on( 'lazy_images' ) ? 'lazy' : 'eager' ) ); ?>
			<?php endif; ?>

			<?php if ( apx_on( 'news_badge_show' ) && $apx_category && 'image' === apx_opt( 'news_badge_position' ) ) : ?>
				<span class="apx-news__badge"><?php echo esc_html( $apx_category ); ?></span>
			<?php endif; ?>
		</a>

		<div class="apx-news__body">
			<?php if ( apx_on( 'news_badge_show' ) && $apx_category && 'below' === apx_opt( 'news_badge_position' ) ) : ?>
				<span class="apx-news__badge apx-news__badge--inline"><?php echo esc_html( $apx_category ); ?></span>
			<?php endif; ?>

			<a class="apx-news__title" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>

			<div class="apx-news__meta">
				<?php if ( apx_on( 'news_show_date' ) ) : ?>
					<span><i class="far fa-calendar" aria-hidden="true"></i> <?php echo esc_html( get_the_date() ); ?></span>
				<?php endif; ?>

				<?php if ( apx_on( 'news_show_author' ) ) : ?>
					<span><i class="fas fa-user" aria-hidden="true"></i> <?php the_author(); ?></span>
				<?php endif; ?>

				<?php if ( apx_on( 'news_show_readtime' ) ) : ?>
					<span><i class="far fa-clock" aria-hidden="true"></i>
						<?php
						/* translators: %d: minutes needed to read the post. */
						echo esc_html( sprintf( _n( '%d min read', '%d min read', $apx_readtime, 'appyn-pro' ), $apx_readtime ) );
						?>
					</span>
				<?php endif; ?>

				<?php if ( apx_on( 'news_show_views' ) && function_exists( 'getPostViews' ) ) : ?>
					<span><i class="fas fa-eye" aria-hidden="true"></i> <?php echo esc_html( number_format_i18n( (int) getPostViews( get_the_ID() ) ) ); ?></span>
				<?php endif; ?>
			</div>

			<?php if ( apx_on( 'news_excerpt_show' ) ) : ?>
				<p class="apx-news__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), $apx_words, '…' ) ); ?></p>
			<?php endif; ?>

			<?php if ( apx_on( 'news_readmore_show' ) ) : ?>
				<a class="apx-news__more" href="<?php the_permalink(); ?>">
					<?php echo esc_html( apx_opt( 'news_readmore_text' ) ); ?>
					<i class="fas fa-arrow-right" aria-hidden="true"></i>
				</a>
			<?php endif; ?>
		</div>
	</article>
</div>
