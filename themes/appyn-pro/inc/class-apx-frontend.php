<?php
/**
 * Front-end wiring: assets, design tokens, data attributes and custom code.
 *
 * @package Appyn_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loads everything the visitor sees.
 */
class APX_Frontend {

	/**
	 * Hook in.
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'assets' ), 20 );
		add_action( 'wp_head', array( $this, 'head_boot' ), 1 );
		add_action( 'wp_head', array( $this, 'custom_head_js' ), 99 );
		add_filter( 'body_class', array( $this, 'body_class' ) );
		add_action( 'wp_footer', array( $this, 'footer_widgets' ), 5 );
		add_action( 'wp_footer', array( $this, 'custom_footer_js' ), 100 );
		add_action( 'wp_body_open', array( $this, 'preloader' ) );
		add_action( 'init', array( $this, 'replace_parent_parts' ) );
	}

	/**
	 * Drop the parent pieces this theme replaces.
	 *
	 * @return void
	 */
	public function replace_parent_parts() {
		if ( apx_on( 'btt_show' ) ) {
			remove_action( 'wp_footer', 'px_backtotop' );
		}
	}

	/**
	 * Styles, scripts and Google fonts.
	 *
	 * @return void
	 */
	public function assets() {
		if ( function_exists( 'is_amp_px' ) && is_amp_px() ) {
			return;
		}

		wp_enqueue_style( 'appyn-pro', APX_URI . '/assets/css/theme.css', array( 'style' ), APX_VERSION );

		$fonts = $this->google_fonts_url();

		if ( $fonts ) {
			wp_enqueue_style( 'appyn-pro-fonts', $fonts, array(), null );
		}

		wp_add_inline_style( 'appyn-pro', $this->tokens() );

		wp_enqueue_script( 'appyn-pro', APX_URI . '/assets/js/theme.js', array(), APX_VERSION, true );
		wp_localize_script( 'appyn-pro', 'APX_CONFIG', $this->js_config() );
	}

	/**
	 * The generated design tokens, cached until settings change.
	 *
	 * @return string
	 */
	protected function tokens() {
		$css = get_transient( 'apx_css_cache' );

		if ( ! is_string( $css ) || '' === $css ) {
			$css = APX_CSS::build();
			set_transient( 'apx_css_cache', $css, DAY_IN_SECONDS );
		}

		return $css;
	}

	/**
	 * Build the Google fonts request for the chosen families.
	 *
	 * @return string
	 */
	protected function google_fonts_url() {
		$families = array();

		foreach ( array( apx_opt( 'heading_font' ), apx_opt( 'body_font' ) ) as $family ) {
			if ( '' === $family || 'System' === $family || in_array( $family, $families, true ) ) {
				continue;
			}

			$families[] = $family;
		}

		if ( empty( $families ) ) {
			return '';
		}

		$parts = array();

		foreach ( $families as $family ) {
			$parts[] = 'family=' . str_replace( ' ', '+', $family ) . ':wght@300;400;500;600;700;800';
		}

		$display = apx_opt( 'font_display' );

		return 'https://fonts.googleapis.com/css2?' . implode( '&', $parts ) . '&display=' . rawurlencode( $display );
	}

	/**
	 * Values the front-end script needs.
	 *
	 * @return array
	 */
	protected function js_config() {
		return array(
			'animations'    => apx_on( 'anim_enable' ) ? 1 : 0,
			'respectMotion' => apx_on( 'anim_respect_motion' ) ? 1 : 0,
			'reduceMobile'  => apx_on( 'anim_reduce_mobile' ) ? 1 : 0,
			'disableMobile' => apx_on( 'mobile_disable_anim' ) ? 1 : 0,
			'scrollAnim'    => apx_opt( 'scroll_anim' ),
			'scrollOffset'  => (int) apx_opt( 'scroll_offset' ),
			'scrollOnce'    => apx_on( 'scroll_once' ) ? 1 : 0,
			'clickFeedback' => apx_opt( 'click_feedback' ),
			'parallax'      => apx_on( 'parallax' ) ? (float) apx_opt( 'parallax_intensity' ) : 0,
			'tilt'          => apx_on( 'tilt' ) ? (float) apx_opt( 'tilt_intensity' ) : 0,
			'detailTilt'    => apx_on( 'detail_icon_tilt' ) ? 1 : 0,
			'heroAutoplay'  => apx_on( 'hero_autoplay' ) ? (float) apx_opt( 'hero_autoplay_speed' ) * 1000 : 0,
			'heroPause'     => apx_on( 'hero_pause_hover' ) ? 1 : 0,
			'heroLoop'      => apx_on( 'hero_loop' ) ? 1 : 0,
			'heroEffect'    => apx_opt( 'hero_effect' ),
			'bttTrigger'    => (int) apx_opt( 'btt_trigger' ),
			'sticky'        => apx_on( 'sticky_header' ) ? 1 : 0,
			'darkEnabled'   => apx_on( 'dark_enable' ) ? 1 : 0,
			'darkDefault'   => apx_opt( 'dark_default' ),
			'preloader'     => apx_on( 'preloader' ) ? (int) apx_opt( 'preloader_min_time' ) : -1,
			'downloadText'  => (string) apx_opt( 'download_success_text' ),
			'downloadAnim'  => apx_opt( 'download_success_anim' ),
			'loadingStyle'  => apx_opt( 'download_loading_style' ),
			'ripple'        => apx_on( 'download_ripple' ) ? 1 : 0,
			'mobileMenu'    => apx_opt( 'mobile_menu_anim' ),
		);
	}

	/**
	 * Set the colour mode before the first paint so the page never flashes.
	 *
	 * @return void
	 */
	public function head_boot() {
		if ( ! apx_on( 'dark_enable' ) ) {
			return;
		}

		$default = esc_js( apx_opt( 'dark_default' ) );

		echo "<script id=\"apx-boot\">(function(){try{var d='" . $default . "';var s=localStorage.getItem('apx_theme');var m=s||(d==='system'?(window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light'):d);document.documentElement.setAttribute('data-apx-theme',m);if(m==='dark'){localStorage.setItem('px_light_dark_option',1);}}catch(e){}})();</script>\n";
	}

	/**
	 * Body classes that switch the style variants.
	 *
	 * @param array $classes Existing classes.
	 * @return array
	 */
	public function body_class( $classes ) {
		$classes[] = 'apx';
		$classes[] = 'apx-nav-' . sanitize_html_class( apx_opt( 'nav_style' ) );
		$classes[] = 'apx-cat-' . sanitize_html_class( apx_opt( 'cat_layout' ) );
		$classes[] = 'apx-catcard-' . sanitize_html_class( apx_opt( 'cat_card_style' ) );
		$classes[] = 'apx-badge-' . sanitize_html_class( apx_opt( 'badge_style' ) );
		$classes[] = 'apx-badgepos-' . sanitize_html_class( apx_opt( 'badge_position' ) );
		$classes[] = 'apx-news-' . sanitize_html_class( apx_opt( 'news_card_style' ) );
		$classes[] = 'apx-newsmore-' . sanitize_html_class( apx_opt( 'news_readmore_style' ) );
		$classes[] = 'apx-tabs-' . sanitize_html_class( apx_opt( 'tab_style' ) );
		$classes[] = 'apx-btn2-' . sanitize_html_class( apx_opt( 'secondary_btn_style' ) );
		$classes[] = 'apx-dl-' . sanitize_html_class( apx_opt( 'download_btn_hover_effect' ) );
		$classes[] = 'apx-dlicon-' . sanitize_html_class( apx_opt( 'download_btn_icon_anim' ) );
		$classes[] = 'apx-dlwidth-' . sanitize_html_class( apx_opt( 'download_btn_width' ) );
		$classes[] = 'apx-cathover-' . sanitize_html_class( apx_opt( 'cat_hover_transform' ) );
		$classes[] = 'apx-social-' . sanitize_html_class( apx_opt( 'social_hover_anim' ) );
		$classes[] = 'apx-footerlink-' . sanitize_html_class( apx_opt( 'footer_link_hover_style' ) );
		$classes[] = 'apx-btt-' . sanitize_html_class( apx_opt( 'btt_style' ) ) . ' apx-bttpos-' . sanitize_html_class( apx_opt( 'btt_position' ) );
		$classes[] = 'apx-entrance-' . sanitize_html_class( apx_opt( 'page_entrance' ) );
		$classes[] = 'apx-badgeanim-' . sanitize_html_class( apx_opt( 'badge_animation' ) );
		$classes[] = 'apx-newsimg-' . sanitize_html_class( apx_opt( 'news_image_hover_filter' ) );
		$classes[] = 'apx-burger-' . sanitize_html_class( apx_opt( 'hamburger_anim' ) );

		if ( ! apx_on( 'anim_enable' ) ) {
			$classes[] = 'apx-no-anim';
		}

		if ( apx_on( 'card_glow' ) ) {
			$classes[] = 'apx-card-glow';
		}

		if ( apx_on( 'skeleton' ) ) {
			$classes[] = 'apx-skeleton-on';
		}

		if ( apx_on( 'mobile_simplify' ) ) {
			$classes[] = 'apx-simplify-mobile';
		}

		if ( apx_on( 'anim_gpu' ) ) {
			$classes[] = 'apx-gpu';
		}

		if ( apx_on( 'mobile_tabbar' ) ) {
			$classes[] = 'apx-has-tabbar';
		}

		if ( apx_on( 'takeover' ) ) {
			$classes[] = 'apx-takeover';
		}

		return $classes;
	}

	/**
	 * The optional page preloader.
	 *
	 * @return void
	 */
	public function preloader() {
		if ( ! apx_on( 'preloader' ) || ! apx_on( 'anim_enable' ) ) {
			return;
		}

		$style = apx_opt( 'preloader_style' );
		$logo  = apx_opt( 'header_logo' );

		echo '<div id="apx-preloader" class="apx-preloader apx-preloader--' . esc_attr( $style ) . '" role="status" aria-label="' . esc_attr__( 'Loading', 'appyn-pro' ) . '">';

		if ( 'logo' === $style && $logo ) {
			echo '<img src="' . esc_url( $logo ) . '" alt="" class="apx-preloader__logo">';
		} elseif ( 'progress' === $style ) {
			echo '<div class="apx-preloader__bar"><span></span></div>';
		} elseif ( 'dots' === $style ) {
			echo '<div class="apx-preloader__dots"><i></i><i></i><i></i></div>';
		} else {
			echo '<div class="apx-preloader__spinner"></div>';
		}

		echo '</div>';
	}

	/**
	 * Back to top button, floating dark switch and the mobile tab bar.
	 *
	 * @return void
	 */
	public function footer_widgets() {
		if ( function_exists( 'is_amp_px' ) && is_amp_px() ) {
			return;
		}

		if ( apx_on( 'btt_show' ) ) {
			printf(
				'<button type="button" id="apx-backtotop" class="apx-btt apx-btt--%1$s" aria-label="%2$s">%3$s</button>',
				esc_attr( apx_opt( 'btt_anim' ) ),
				esc_attr__( 'Back to top', 'appyn-pro' ),
				apx_icon( apx_opt( 'btt_icon' ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup built from a sanitised icon class.
			);
		}

		if ( apx_on( 'dark_enable' ) && 'floating' === apx_opt( 'dark_toggle_position' ) ) {
			echo APX_Blocks::dark_toggle( 'apx-dark-float' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
		}

		if ( apx_on( 'mobile_tabbar' ) ) {
			echo APX_Blocks::tabbar(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
		}
	}

	/**
	 * Custom head JavaScript from the panel.
	 *
	 * @return void
	 */
	public function custom_head_js() {
		$js = trim( (string) apx_opt( 'js_head' ) );

		if ( '' === $js ) {
			return;
		}

		echo '<script id="apx-head-js">' . $js . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- administrator supplied script.
	}

	/**
	 * Custom footer JavaScript from the panel.
	 *
	 * @return void
	 */
	public function custom_footer_js() {
		$js = trim( (string) apx_opt( 'js_footer' ) );

		if ( '' === $js ) {
			return;
		}

		echo '<script id="apx-footer-js">' . $js . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- administrator supplied script.
	}
}
