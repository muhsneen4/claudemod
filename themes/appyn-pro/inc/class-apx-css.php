<?php
/**
 * Turns the stored settings into CSS custom properties.
 *
 * Every field in the schema that declares a `css_var` lands here automatically,
 * in the right selector (light or dark) and the right media query.
 *
 * @package Appyn_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Design token generator.
 */
class APX_CSS {

	/**
	 * Build the full stylesheet: tokens, derived values and custom CSS.
	 *
	 * @return string
	 */
	public static function build() {
		$buckets = self::buckets();
		$css     = '';

		$css .= ':root{' . self::render( $buckets['root'][''] ) . self::derived() . '}';

		$dark = self::render( $buckets['dark'][''] );

		if ( apx_on( 'dark_enable' ) && '' !== $dark ) {
			$css .= 'html[data-apx-theme="dark"]{' . $dark . '}';

			if ( 'system' === apx_opt( 'dark_default' ) ) {
				$css .= '@media (prefers-color-scheme:dark){html:not([data-apx-theme="light"]){' . $dark . '}}';
			}
		}

		$tablet_bp = (int) apx_opt( 'bp_desktop' ) - 1;
		$mobile_bp = (int) apx_opt( 'bp_tablet' ) - 1;

		$tablet = self::render( $buckets['root']['tablet'] );
		$mobile = self::render( $buckets['root']['mobile'] );

		if ( '' !== $tablet ) {
			$css .= '@media (max-width:' . $tablet_bp . 'px){:root{' . $tablet . '}}';
		}

		if ( '' !== $mobile ) {
			$css .= '@media (max-width:' . $mobile_bp . 'px){:root{' . $mobile . '}}';
		}

		$dark_tablet = self::render( $buckets['dark']['tablet'] );
		$dark_mobile = self::render( $buckets['dark']['mobile'] );

		if ( apx_on( 'dark_enable' ) && '' !== $dark_tablet ) {
			$css .= '@media (max-width:' . $tablet_bp . 'px){html[data-apx-theme="dark"]{' . $dark_tablet . '}}';
		}

		if ( apx_on( 'dark_enable' ) && '' !== $dark_mobile ) {
			$css .= '@media (max-width:' . $mobile_bp . 'px){html[data-apx-theme="dark"]{' . $dark_mobile . '}}';
		}

		$css .= self::font_scaling( $tablet_bp, $mobile_bp );
		$css .= self::slide_css();
		$css .= self::custom_css( $tablet_bp, $mobile_bp );

		/**
		 * Filter the generated stylesheet.
		 *
		 * @param string $css Generated CSS.
		 */
		return apply_filters( 'apx_generated_css', $css );
	}

	/**
	 * Sort every token into scope and media buckets.
	 *
	 * @return array
	 */
	protected static function buckets() {
		$buckets = array(
			'root' => array(
				''       => array(),
				'tablet' => array(),
				'mobile' => array(),
			),
			'dark' => array(
				''       => array(),
				'tablet' => array(),
				'mobile' => array(),
			),
		);

		foreach ( apx_schema_flat() as $key => $field ) {
			if ( empty( $field['css_var'] ) ) {
				continue;
			}

			$scope = ( isset( $field['scope'] ) && 'dark' === $field['scope'] ) ? 'dark' : 'root';
			$media = isset( $field['media'] ) ? $field['media'] : '';

			if ( ! isset( $buckets[ $scope ][ $media ] ) ) {
				$media = '';
			}

			$value = self::token( apx_opt( $key ), $field );

			if ( '' === $value ) {
				continue;
			}

			$buckets[ $scope ][ $media ][ $field['css_var'] ] = $value;
		}

		return $buckets;
	}

	/**
	 * Render one bucket as `--name:value;` declarations.
	 *
	 * @param array $tokens Tokens.
	 * @return string
	 */
	protected static function render( $tokens ) {
		$out = '';

		foreach ( $tokens as $name => $value ) {
			$out .= $name . ':' . $value . ';';
		}

		return $out;
	}

	/**
	 * Format a stored value as a CSS token.
	 *
	 * @param mixed $value Stored value.
	 * @param array $field Field definition.
	 * @return string
	 */
	public static function token( $value, $field ) {
		$type = isset( $field['type'] ) ? $field['type'] : 'text';
		$unit = isset( $field['unit'] ) ? $field['unit'] : '';

		switch ( $type ) {
			case 'color':
				return (string) $value;

			case 'colora':
				$value = is_array( $value ) ? $value : array();

				return apx_hex_to_rgba(
					isset( $value['color'] ) ? $value['color'] : '#000000',
					isset( $value['opacity'] ) ? $value['opacity'] : 1
				);

			case 'slider':
				return ( is_numeric( $value ) ? rtrim( rtrim( number_format( (float) $value, 3, '.', '' ), '0' ), '.' ) : '0' ) . $unit;

			case 'select':
				return (string) $value;

			case 'spacing':
				$value = is_array( $value ) ? $value : array();
				$parts = array();

				foreach ( array( 'top', 'right', 'bottom', 'left' ) as $side ) {
					$parts[] = ( isset( $value[ $side ] ) ? (float) $value[ $side ] : 0 ) . 'px';
				}

				return implode( ' ', $parts );

			case 'border':
				$value = is_array( $value ) ? $value : array();
				$width = isset( $value['width'] ) ? (float) $value['width'] : 0;
				$style = isset( $value['style'] ) ? $value['style'] : 'solid';
				$color = isset( $value['color'] ) ? $value['color'] : 'transparent';

				if ( $width <= 0 || 'none' === $style ) {
					return '0 solid transparent';
				}

				return $width . 'px ' . $style . ' ' . $color;

			case 'shadow':
				$value = is_array( $value ) ? $value : array();

				if ( empty( $value['enable'] ) ) {
					return 'none';
				}

				return sprintf(
					'%spx %spx %spx %spx %s',
					isset( $value['x'] ) ? (float) $value['x'] : 0,
					isset( $value['y'] ) ? (float) $value['y'] : 0,
					isset( $value['blur'] ) ? (float) $value['blur'] : 0,
					isset( $value['spread'] ) ? (float) $value['spread'] : 0,
					isset( $value['color'] ) ? $value['color'] : 'transparent'
				);

			case 'gradient':
				$value = is_array( $value ) ? $value : array();

				if ( empty( $value['enable'] ) ) {
					return 'none';
				}

				return sprintf(
					'linear-gradient(%ddeg, %s, %s)',
					isset( $value['angle'] ) ? (int) $value['angle'] : 90,
					isset( $value['from'] ) ? $value['from'] : 'transparent',
					isset( $value['to'] ) ? $value['to'] : 'transparent'
				);

			default:
				return is_scalar( $value ) ? (string) $value : '';
		}
	}

	/**
	 * Tokens that are computed rather than stored one to one.
	 *
	 * @return string
	 */
	protected static function derived() {
		$css = '';

		$css .= '--apx-font-heading:' . apx_font_stack( apx_opt( 'heading_font' ), 'heading' ) . ';';
		$css .= '--apx-font-body:' . apx_font_stack( apx_opt( 'body_font' ), 'body' ) . ';';

		$easings = array(
			'smooth' => 'cubic-bezier(.22,1,.36,1)',
			'bouncy' => 'cubic-bezier(.34,1.56,.64,1)',
			'snappy' => 'cubic-bezier(.4,0,.2,1)',
		);

		$style = apx_opt( 'anim_style' );
		$css  .= '--apx-easing:' . ( isset( $easings[ $style ] ) ? $easings[ $style ] : $easings['smooth'] ) . ';';

		$lines = (int) apx_opt( 'card_title_lines' );
		$css  .= '--apx-card-title-lines:' . ( $lines > 0 ? $lines : 99 ) . ';';

		if ( ! apx_on( 'anim_enable' ) ) {
			$css .= '--apx-speed:0s;--apx-hover-speed:0s;--apx-scroll-duration:0ms;';
		}

		$css .= '--apx-slide-speed:' . ( (float) apx_opt( 'hero_autoplay_speed' ) ) . 's;';
		$css .= '--apx-container-padding:16px;';
		$css .= '--apx-shadow-color:rgba(15,23,42,.12);';

		return $css;
	}

	/**
	 * Per device font scaling.
	 *
	 * @param int $tablet_bp Tablet max width.
	 * @param int $mobile_bp Mobile max width.
	 * @return string
	 */
	protected static function font_scaling( $tablet_bp, $mobile_bp ) {
		$css    = '';
		$tablet = (float) apx_opt( 'tablet_font_scale' );
		$mobile = (float) apx_opt( 'mobile_font_scale' );
		$base   = (float) apx_opt( 'base_font_size' );

		if ( $tablet && 100 !== (int) $tablet ) {
			$css .= '@media (max-width:' . $tablet_bp . 'px){:root{--apx-font-size:' . round( $base * $tablet / 100, 2 ) . 'px;}}';
		}

		if ( $mobile && 100 !== (int) $mobile ) {
			$css .= '@media (max-width:' . $mobile_bp . 'px){:root{--apx-font-size:' . round( $base * $mobile / 100, 2 ) . 'px;}}';
		}

		return $css;
	}

	/**
	 * Per slide custom CSS from the hero repeater.
	 *
	 * @return string
	 */
	protected static function slide_css() {
		$css = '';

		foreach ( apx_rows( 'hero_slides' ) as $index => $slide ) {
			if ( empty( $slide['css'] ) ) {
				continue;
			}

			$css .= '.apx-slide-' . (int) $index . '{' . APX_Settings::sanitize_code( $slide['css'] ) . '}';
		}

		return $css;
	}

	/**
	 * The custom CSS boxes, in cascade order.
	 *
	 * @param int $tablet_bp Tablet max width.
	 * @param int $mobile_bp Mobile max width.
	 * @return string
	 */
	protected static function custom_css( $tablet_bp, $mobile_bp ) {
		$css = '';

		$css .= apx_opt( 'cat_custom_css' );
		$css .= apx_opt( 'css_header' );
		$css .= apx_opt( 'css_footer' );
		$css .= apx_opt( 'css_global' );

		$desktop = apx_opt( 'css_desktop' );
		$tablet  = apx_opt( 'css_tablet' );
		$mobile  = apx_opt( 'css_mobile' );

		if ( '' !== trim( (string) $desktop ) ) {
			$css .= '@media (min-width:' . ( $tablet_bp + 1 ) . 'px){' . $desktop . '}';
		}

		if ( '' !== trim( (string) $tablet ) ) {
			$css .= '@media (min-width:' . ( $mobile_bp + 1 ) . 'px) and (max-width:' . $tablet_bp . 'px){' . $tablet . '}';
		}

		if ( '' !== trim( (string) $mobile ) ) {
			$css .= '@media (max-width:' . $mobile_bp . 'px){' . $mobile . '}';
		}

		return $css;
	}
}
