<?php
/**
 * Appyn Pro footer.
 *
 * @package Appyn_Pro
 */

if ( ! apx_on( 'takeover' ) ) {
	load_template( get_template_directory() . '/footer-default.php', false );
	return;
}

$apx_footer_bg   = apx_opt( 'footer_bg_image' );
$apx_footer_logo = apx_opt( 'footer_logo' );
$apx_desc        = apx_opt( 'footer_desc' );
$apx_columns     = APX_Blocks::footer_columns();
$apx_social      = APX_Blocks::social();
$apx_underline   = apx_on( 'footer_title_underline' ) ? '' : ' apx-footer--no-underline';
?>
		</main>

		<footer id="apx-footer" class="apx-footer<?php echo esc_attr( $apx_underline ); ?>"<?php echo $apx_footer_bg ? ' style="background-image:url(' . esc_url( $apx_footer_bg ) . ')"' : ''; ?>>
			<div class="container">
				<div class="apx-footer__grid">
					<?php if ( $apx_footer_logo || $apx_desc || $apx_social ) : ?>
						<div class="apx-footer__col apx-footer__brand">
							<?php if ( $apx_footer_logo ) : ?>
								<img src="<?php echo esc_url( $apx_footer_logo ); ?>" alt="<?php bloginfo( 'name' ); ?>">
							<?php endif; ?>

							<?php if ( $apx_desc ) : ?>
								<div class="apx-footer__desc"><?php echo wp_kses_post( wpautop( $apx_desc ) ); ?></div>
							<?php endif; ?>

							<?php echo $apx_social; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside. ?>
						</div>
					<?php endif; ?>

					<?php echo $apx_columns; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside. ?>

					<?php
					if ( is_active_sidebar( 'sidebar-footer' ) ) {
						echo '<div class="apx-footer__col apx-footer__widgets"><ul>';
						dynamic_sidebar( 'sidebar-footer' );
						echo '</ul></div>';
					}
					?>
				</div>
			</div>

			<?php if ( apx_on( 'bottom_bar_show' ) ) : ?>
				<div class="apx-footer__bottom">
					<div class="container">
						<div class="apx-footer__copy">
							<?php echo wp_kses_post( apx_placeholders( apx_opt( 'copyright_left' ) ) ); ?>
							<?php
							wp_nav_menu(
								array(
									'theme_location' => 'menu-footer',
									'container'      => '',
									'menu_class'     => 'apx-footer__bottommenu',
									'fallback_cb'    => '',
									'depth'          => 1,
								)
							);
							?>
						</div>
						<div class="apx-footer__right">
							<?php echo wp_kses_post( apx_placeholders( apx_opt( 'copyright_right' ) ) ); ?>
						</div>
					</div>
				</div>
			<?php endif; ?>
		</footer>
	</div>
</div>
<?php wp_footer(); ?>
</body>
</html>
