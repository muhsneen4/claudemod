<?php
/**
 * Front-end building blocks: hero slider, category bar, navigation, footer
 * pieces and the app detail banner.
 *
 * @package Appyn_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the pieces the panel controls.
 */
class APX_Blocks {

	/**
	 * Hook in.
	 */
	public function __construct() {
		add_action( 'subheader', array( $this, 'detail_banner' ), 1 );
		add_action( 'subheader', array( $this, 'hero' ), 6 );
		add_action( 'do_home', array( $this, 'categories' ), 5 );
		add_action( 'init', array( $this, 'maybe_replace_catbar' ), 20 );
		add_filter( 'body_class', array( $this, 'body_class' ) );
	}

	/**
	 * Extra body classes owned by the blocks.
	 *
	 * @param array $classes Existing classes.
	 * @return array
	 */
	public function body_class( $classes ) {
		$classes[] = 'apx-crumb-' . sanitize_html_class( apx_opt( 'breadcrumb_style' ) );
		$classes[] = 'apx-hero-' . sanitize_html_class( apx_opt( 'hero_effect' ) );
		$classes[] = 'apx-dots-' . sanitize_html_class( apx_opt( 'hero_dot_style' ) );
		$classes[] = 'apx-arrows-' . sanitize_html_class( apx_opt( 'hero_arrow_style' ) );

		return $classes;
	}

	/**
	 * Take the parent category bar off the home page when ours is configured.
	 *
	 * @return void
	 */
	public function maybe_replace_catbar() {
		if ( apx_on( 'cat_enable' ) && apx_rows( 'cat_items' ) ) {
			remove_action( 'do_home', 'func_action_home_catbar' );
		}
	}

	/* ---------------------------------------------------------------- *
	 * Hero slider
	 * ---------------------------------------------------------------- */

	/**
	 * Home page hero slider.
	 *
	 * @return void
	 */
	public function hero() {
		if ( ! apx_on( 'hero_enable' ) || ! ( is_front_page() || is_home() ) ) {
			return;
		}

		if ( function_exists( 'is_amp_px' ) && is_amp_px() ) {
			return;
		}

		$slides = apx_rows( 'hero_slides' );

		if ( empty( $slides ) ) {
			return;
		}

		$wrap_open  = apx_on( 'hero_full_width' ) ? '<div class="apx-hero-wrap apx-hero-wrap--full">' : '<div class="container apx-hero-wrap">';
		$wrap_close = '</div>';

		echo $wrap_open; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static markup.
		?>
		<div class="apx-hero" data-apx-hero>
			<div class="apx-hero__track">
				<?php foreach ( $slides as $index => $slide ) : ?>
					<?php
					$style = '';

					if ( ! empty( $slide['image'] ) ) {
						$style .= 'background-image:url(' . esc_url( $slide['image'] ) . ');';
					} elseif ( ! empty( $slide['bg_color'] ) ) {
						$style .= 'background-color:' . esc_attr( $slide['bg_color'] ) . ';';
					}

					$position = ! empty( $slide['position'] ) ? $slide['position'] : 'left';
					$anim     = ! empty( $slide['animation'] ) ? $slide['animation'] : 'fade-up';
					?>
					<div class="apx-hero__slide apx-slide-<?php echo (int) $index; ?> apx-hero__slide--<?php echo esc_attr( $position ); ?><?php echo 0 === $index ? ' is-active' : ''; ?>"
						style="<?php echo esc_attr( $style ); ?>"
						data-anim="<?php echo esc_attr( $anim ); ?>">
						<div class="apx-hero__overlay"></div>
						<div class="apx-hero__content">
							<?php if ( ! empty( $slide['title'] ) ) : ?>
								<h2 class="apx-hero__title" style="color:<?php echo esc_attr( $slide['title_color'] ); ?>;font-size:<?php echo (int) $slide['title_size']; ?>px">
									<?php echo esc_html( $slide['title'] ); ?>
								</h2>
							<?php endif; ?>

							<?php if ( ! empty( $slide['text'] ) ) : ?>
								<p class="apx-hero__text" style="color:<?php echo esc_attr( $slide['text_color'] ); ?>">
									<?php echo esc_html( wp_strip_all_tags( $slide['text'] ) ); ?>
								</p>
							<?php endif; ?>

							<?php if ( ! empty( $slide['btn_text'] ) ) : ?>
								<a class="apx-hero__btn" href="<?php echo esc_url( $slide['btn_link'] ); ?>"
									style="--btn-bg:<?php echo esc_attr( $slide['btn_bg'] ); ?>;--btn-color:<?php echo esc_attr( $slide['btn_color'] ); ?>;--btn-hover-bg:<?php echo esc_attr( $slide['btn_hover_bg'] ); ?>">
									<?php echo esc_html( $slide['btn_text'] ); ?>
								</a>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<?php if ( apx_on( 'hero_arrows' ) && count( $slides ) > 1 ) : ?>
				<button type="button" class="apx-hero__arrow apx-hero__arrow--prev" aria-label="<?php esc_attr_e( 'Previous slide', 'appyn-pro' ); ?>"><i class="fas fa-chevron-left" aria-hidden="true"></i></button>
				<button type="button" class="apx-hero__arrow apx-hero__arrow--next" aria-label="<?php esc_attr_e( 'Next slide', 'appyn-pro' ); ?>"><i class="fas fa-chevron-right" aria-hidden="true"></i></button>
			<?php endif; ?>

			<?php if ( apx_on( 'hero_dots' ) && count( $slides ) > 1 ) : ?>
				<div class="apx-hero__dots">
					<?php foreach ( $slides as $index => $slide ) : ?>
						<button type="button" class="apx-hero__dot<?php echo 0 === $index ? ' is-active' : ''; ?>" data-index="<?php echo (int) $index; ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %d: slide number. */ __( 'Go to slide %d', 'appyn-pro' ), $index + 1 ) ); ?>">
							<span><?php echo (int) $index + 1; ?></span>
						</button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
		echo $wrap_close; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static markup.
	}

	/* ---------------------------------------------------------------- *
	 * Category bar
	 * ---------------------------------------------------------------- */

	/**
	 * Category strip on the home page.
	 *
	 * @return void
	 */
	public function categories() {
		if ( ! apx_on( 'cat_enable' ) ) {
			return;
		}

		$items = array_filter(
			apx_rows( 'cat_items' ),
			static function ( $item ) {
				return ! empty( $item['visible'] ) && ! empty( $item['name'] );
			}
		);

		if ( empty( $items ) ) {
			return;
		}

		$title = apx_opt( 'cat_title' );
		?>
		<div class="apx-cats apx-section">
			<?php if ( $title ) : ?>
				<div class="apx-section__head">
					<h2 class="apx-section__title">
						<?php echo apx_icon( apx_opt( 'cat_title_icon' ), 'apx-section__icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- sanitised icon class. ?>
						<span><?php echo esc_html( $title ); ?></span>
					</h2>
				</div>
			<?php endif; ?>

			<div class="apx-cats__viewport">
				<?php if ( apx_on( 'cat_arrows' ) ) : ?>
					<button type="button" class="apx-cats__arrow apx-cats__arrow--prev" aria-label="<?php esc_attr_e( 'Scroll left', 'appyn-pro' ); ?>"><i class="fas fa-chevron-left" aria-hidden="true"></i></button>
					<button type="button" class="apx-cats__arrow apx-cats__arrow--next" aria-label="<?php esc_attr_e( 'Scroll right', 'appyn-pro' ); ?>"><i class="fas fa-chevron-right" aria-hidden="true"></i></button>
				<?php endif; ?>

				<ul class="apx-cats__list" data-apx-cats>
					<?php foreach ( $items as $item ) : ?>
						<li class="apx-cats__item <?php echo esc_attr( isset( $item['css_class'] ) ? $item['css_class'] : '' ); ?>">
							<a href="<?php echo esc_url( $item['url'] ? $item['url'] : '#' ); ?>" style="--cat-color:<?php echo esc_attr( $item['color'] ); ?>">
								<span class="apx-cats__icon">
									<?php if ( ! empty( $item['image'] ) ) : ?>
										<img src="<?php echo esc_url( $item['image'] ); ?>" alt="" loading="lazy" width="48" height="48">
									<?php else : ?>
										<?php echo apx_icon( $item['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- sanitised icon class. ?>
									<?php endif; ?>
								</span>
								<span class="apx-cats__label"><?php echo esc_html( $item['name'] ); ?></span>
								<?php if ( apx_on( 'cat_show_count' ) && ! empty( $item['count'] ) ) : ?>
									<span class="apx-cats__count"><?php echo esc_html( $item['count'] ); ?></span>
								<?php endif; ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
		<?php
	}

	/* ---------------------------------------------------------------- *
	 * App detail banner
	 * ---------------------------------------------------------------- */

	/**
	 * Blurred artwork banner behind the app header.
	 *
	 * @return void
	 */
	public function detail_banner() {
		if ( ! apx_on( 'detail_hero_enable' ) || ! is_singular( 'post' ) ) {
			return;
		}

		if ( function_exists( 'is_amp_px' ) && is_amp_px() ) {
			return;
		}

		$image = get_the_post_thumbnail_url( get_the_ID(), 'full' );

		if ( ! $image ) {
			return;
		}

		printf(
			'<div class="apx-detail-banner%1$s" style="background-image:url(%2$s)" aria-hidden="true"><span class="apx-detail-banner__veil"></span></div>',
			apx_on( 'detail_hero_parallax' ) ? ' apx-detail-banner--parallax' : '',
			esc_url( $image )
		);
	}

	/* ---------------------------------------------------------------- *
	 * Shared pieces used by the header and footer templates
	 * ---------------------------------------------------------------- */

	/**
	 * Header navigation, from the repeater or the WordPress menu.
	 *
	 * @param string $extra_class Extra CSS class for the wrapper.
	 * @return string
	 */
	public static function nav( $extra_class = '' ) {
		$items = apx_rows( 'nav_items' );

		if ( empty( $items ) ) {
			return wp_nav_menu(
				array(
					'theme_location' => 'menu',
					'container'      => 'nav',
					'menu_class'     => trim( 'apx-nav__list ' . $extra_class ),
					'fallback_cb'    => '__return_empty_string',
					'echo'           => false,
				)
			);
		}

		$out = '<nav class="apx-nav ' . esc_attr( $extra_class ) . '"><ul class="apx-nav__list">';

		foreach ( $items as $item ) {
			if ( empty( $item['label'] ) ) {
				continue;
			}

			$out .= sprintf(
				'<li class="apx-nav__item %1$s" style="--nav-color:%2$s;--nav-hover:%3$s"><a href="%4$s"%5$s>%6$s<span>%7$s</span></a></li>',
				esc_attr( isset( $item['css_class'] ) ? $item['css_class'] : '' ),
				esc_attr( $item['color'] ),
				esc_attr( $item['hover'] ),
				esc_url( $item['url'] ),
				! empty( $item['new_tab'] ) ? ' target="_blank" rel="noopener"' : '',
				$item['icon'] ? '<span class="apx-nav__icon">' . apx_icon( $item['icon'] ) . '</span>' : '',
				esc_html( $item['label'] )
			);
		}

		$out .= '</ul></nav>';

		return $out;
	}

	/**
	 * The light / dark switch.
	 *
	 * @param string $extra_class Extra CSS class.
	 * @return string
	 */
	public static function dark_toggle( $extra_class = '' ) {
		if ( ! apx_on( 'dark_enable' ) ) {
			return '';
		}

		return sprintf(
			'<button type="button" class="apx-dark apx-dark--%1$s %2$s" data-apx-dark aria-label="%3$s"><i class="fas fa-sun apx-dark__sun" aria-hidden="true"></i><i class="fas fa-moon apx-dark__moon" aria-hidden="true"></i><span class="apx-dark__knob"></span></button>',
			esc_attr( apx_opt( 'dark_toggle_style' ) ),
			esc_attr( $extra_class ),
			esc_attr__( 'Switch light and dark mode', 'appyn-pro' )
		);
	}

	/**
	 * Mobile bottom tab bar built from the navigation items.
	 *
	 * @return string
	 */
	public static function tabbar() {
		$items = apx_rows( 'nav_items' );

		if ( empty( $items ) ) {
			return '';
		}

		$out = '<nav class="apx-tabbar" aria-label="' . esc_attr__( 'Mobile navigation', 'appyn-pro' ) . '"><ul>';

		foreach ( array_slice( $items, 0, 5 ) as $item ) {
			$out .= sprintf(
				'<li style="--nav-color:%1$s"><a href="%2$s">%3$s<span>%4$s</span></a></li>',
				esc_attr( $item['color'] ),
				esc_url( $item['url'] ),
				apx_icon( $item['icon'] ),
				esc_html( $item['label'] )
			);
		}

		$out .= '</ul></nav>';

		return $out;
	}

	/**
	 * Social icon list for the footer.
	 *
	 * @return string
	 */
	public static function social() {
		$icons = apx_rows( 'social_icons' );

		if ( empty( $icons ) ) {
			return '';
		}

		$out = '<ul class="apx-social">';

		foreach ( $icons as $icon ) {
			if ( empty( $icon['url'] ) ) {
				continue;
			}

			$out .= sprintf(
				'<li><a href="%1$s" target="_blank" rel="noopener nofollow" style="--s-color:%2$s;--s-bg:%3$s;--s-hover-color:%4$s;--s-hover-bg:%5$s" aria-label="%6$s">%7$s</a></li>',
				esc_url( $icon['url'] ),
				esc_attr( $icon['color'] ),
				esc_attr( $icon['bg'] ),
				esc_attr( $icon['hover_color'] ),
				esc_attr( $icon['hover_bg'] ),
				esc_attr__( 'Social link', 'appyn-pro' ),
				apx_icon( $icon['icon'] )
			);
		}

		$out .= '</ul>';

		return $out;
	}

	/**
	 * Footer link columns.
	 *
	 * Link lists accept one item per line as "Label|URL|icon class".
	 *
	 * @return string
	 */
	public static function footer_columns() {
		$columns = apx_rows( 'footer_cols' );

		if ( empty( $columns ) ) {
			return '';
		}

		$out = '';

		foreach ( $columns as $column ) {
			$out .= '<div class="apx-footer__col ' . esc_attr( isset( $column['css_class'] ) ? $column['css_class'] : '' ) . '">';

			if ( ! empty( $column['title'] ) ) {
				$out .= '<h3 class="apx-footer__title">' . esc_html( $column['title'] ) . '</h3>';
			}

			$type    = isset( $column['type'] ) ? $column['type'] : 'links';
			$content = isset( $column['content'] ) ? $column['content'] : '';

			switch ( $type ) {
				case 'menu':
					$out .= wp_nav_menu(
						array(
							'menu'        => sanitize_text_field( $content ),
							'container'   => '',
							'menu_class'  => 'apx-footer__links',
							'fallback_cb' => '__return_empty_string',
							'echo'        => false,
						)
					);
					break;

				case 'html':
					$out .= '<div class="apx-footer__html">' . wp_kses_post( $content ) . '</div>';
					break;

				case 'text':
					$out .= '<div class="apx-footer__text">' . wpautop( wp_kses_post( $content ) ) . '</div>';
					break;

				case 'links':
				default:
					$lines = array_filter( array_map( 'trim', explode( "\n", (string) $content ) ) );

					if ( $lines ) {
						$out .= '<ul class="apx-footer__links">';

						foreach ( $lines as $line ) {
							$parts = array_map( 'trim', explode( '|', $line ) );
							$label = isset( $parts[0] ) ? $parts[0] : '';
							$url   = isset( $parts[1] ) ? $parts[1] : '#';
							$icon  = isset( $parts[2] ) ? $parts[2] : '';

							if ( '' === $label ) {
								continue;
							}

							$out .= '<li><a href="' . esc_url( $url ) . '">' . apx_icon( $icon ) . esc_html( $label ) . '</a></li>';
						}

						$out .= '</ul>';
					}
					break;
			}

			$out .= '</div>';
		}

		return $out;
	}
}
