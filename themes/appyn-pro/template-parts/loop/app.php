<?php
/**
 * Appyn Pro app card.
 *
 * Uses the parent theme data helpers, so ratings, versions and thumbnails keep
 * working exactly as before - only the markup and the design tokens change.
 *
 * @package Appyn_Pro
 */

if ( ! apx_on( 'takeover_cards' ) ) {
	load_template( get_template_directory() . '/template-parts/loop/app.php', false );
	return;
}

global $post;

$apx_id     = get_the_ID();
$apx_info   = get_post_meta( $apx_id, 'datos_informacion', true );
$apx_status = ( is_array( $apx_info ) && ! empty( $apx_info['app_status'] ) ) ? $apx_info['app_status'] : '';
$apx_is_mod = (bool) get_post_meta( $apx_id, 'app_type', true );
$apx_rating = function_exists( 'count_rating' ) ? count_rating( $apx_id ) : array(
	'average' => 0,
	'users'   => 0,
);
$apx_avg    = isset( $apx_rating['average'] ) ? (float) $apx_rating['average'] : 0;

// The parent theme's "list view" option keeps working: it only swaps the class.
$apx_view = ( function_exists( 'appyn_options' ) && appyn_options( 'view_apps' ) ) ? 'bav2' : 'bav1';
?>
<div class="bav <?php echo esc_attr( $apx_view ); ?> apx-appcard">
	<?php if ( $apx_is_mod ) : ?>
		<span class="apx-badge apx-badge--mod"><?php esc_html_e( 'MOD', 'appyn-pro' ); ?></span>
	<?php elseif ( 'new' === $apx_status ) : ?>
		<span class="apx-badge apx-badge--new"><?php esc_html_e( 'NEW', 'appyn-pro' ); ?></span>
	<?php elseif ( 'updated' === $apx_status ) : ?>
		<span class="apx-badge apx-badge--choice"><?php esc_html_e( 'UPDATED', 'appyn-pro' ); ?></span>
	<?php endif; ?>

	<a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>">
		<?php
		if ( function_exists( 'px_post_thumbnail' ) ) {
			echo px_post_thumbnail(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- parent theme output.
		}
		?>

		<span class="title"><?php the_title(); ?></span>

		<?php if ( apx_on( 'card_show_dev' ) && function_exists( 'app_developer' ) ) : ?>
			<?php echo app_developer(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- parent theme output. ?>
		<?php endif; ?>

		<?php if ( apx_on( 'card_show_tags' ) ) : ?>
			<span class="apx-appcard__tags">
				<?php
				if ( function_exists( 'app_version' ) ) {
					echo app_version(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- parent theme output.
				}

				if ( function_exists( 'app_size' ) ) {
					echo app_size(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- parent theme output.
				}
				?>
			</span>
		<?php endif; ?>

		<?php
		if ( function_exists( 'app_date' ) ) {
			echo app_date(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- parent theme output.
		}
		?>

		<?php if ( apx_on( 'star_show' ) ) : ?>
			<span class="px-postmeta apx-rating">
				<span class="apx-stars" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: rating out of five. */ __( 'Rated %s out of 5', 'appyn-pro' ), number_format_i18n( $apx_avg, 1 ) ) ); ?>">
					<?php for ( $apx_star = 1; $apx_star <= 5; $apx_star++ ) : ?>
						<i class="fas fa-star<?php echo ( $apx_star <= round( $apx_avg ) ) ? ' is-on' : ''; ?>" aria-hidden="true"></i>
					<?php endfor; ?>
				</span>
				<?php if ( apx_on( 'rating_show_number' ) && $apx_avg > 0 ) : ?>
					<span class="apx-rating__value"><?php echo esc_html( number_format_i18n( $apx_avg, 1 ) ); ?></span>
				<?php endif; ?>
			</span>
		<?php endif; ?>
	</a>
</div>
