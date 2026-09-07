<?php
/**
 * Shared helpers: option access, colour maths, font and icon lists.
 *
 * @package Appyn_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Read a single Appyn Pro option, falling back to its schema default.
 *
 * @param string $key     Field key.
 * @param mixed  $default Optional override for the schema default.
 * @return mixed
 */
function apx_opt( $key, $default = null ) {
	return APX_Settings::get( $key, $default );
}

/**
 * Read a repeater option and always return a list of rows.
 *
 * @param string $key Field key.
 * @return array
 */
function apx_rows( $key ) {
	$rows = APX_Settings::get( $key );
	return is_array( $rows ) ? array_values( $rows ) : array();
}

/**
 * True when a toggle option is on.
 *
 * @param string $key Field key.
 * @return bool
 */
function apx_on( $key ) {
	return (bool) APX_Settings::get( $key );
}

/**
 * Turn a hex colour plus opacity into an rgba() string.
 *
 * @param string $hex     Hex colour, with or without the hash.
 * @param float  $opacity 0 - 1.
 * @return string
 */
function apx_hex_to_rgba( $hex, $opacity = 1 ) {
	$hex = trim( (string) $hex );

	if ( 0 === strpos( $hex, 'rgb' ) ) {
		return $hex;
	}

	$hex = ltrim( $hex, '#' );

	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}

	if ( 6 !== strlen( $hex ) || ! ctype_xdigit( $hex ) ) {
		return 'rgba(0,0,0,0)';
	}

	$r = hexdec( substr( $hex, 0, 2 ) );
	$g = hexdec( substr( $hex, 2, 2 ) );
	$b = hexdec( substr( $hex, 4, 2 ) );

	$opacity = max( 0, min( 1, (float) $opacity ) );

	return sprintf( 'rgba(%d,%d,%d,%s)', $r, $g, $b, rtrim( rtrim( number_format( $opacity, 3, '.', '' ), '0' ), '.' ) );
}

/**
 * Build a CSS font stack from a Google font family name.
 *
 * @param string $family Family name.
 * @param string $type   heading|body.
 * @return string
 */
function apx_font_stack( $family, $type = 'body' ) {
	$family = trim( (string) $family );

	if ( '' === $family || 'System' === $family ) {
		return '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif';
	}

	$fallback = ( 'heading' === $type ) ? 'Georgia, "Times New Roman", serif' : 'Helvetica, Arial, sans-serif';
	$serif    = array( 'Merriweather', 'Playfair Display', 'Lora', 'Roboto Slab', 'Bitter' );

	if ( ! in_array( $family, $serif, true ) ) {
		$fallback = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif';
	}

	return '"' . $family . '", ' . $fallback;
}

/**
 * Google fonts offered in the typography pickers.
 *
 * @return array
 */
function apx_google_fonts() {
	return array(
		'System'           => 'System UI (no download)',
		'Poppins'          => 'Poppins',
		'Inter'            => 'Inter',
		'Roboto'           => 'Roboto',
		'Open Sans'        => 'Open Sans',
		'Montserrat'       => 'Montserrat',
		'Lato'             => 'Lato',
		'Nunito'           => 'Nunito',
		'Nunito Sans'      => 'Nunito Sans',
		'Rubik'            => 'Rubik',
		'Manrope'          => 'Manrope',
		'DM Sans'          => 'DM Sans',
		'Work Sans'        => 'Work Sans',
		'Outfit'           => 'Outfit',
		'Plus Jakarta Sans'=> 'Plus Jakarta Sans',
		'Source Sans 3'    => 'Source Sans 3',
		'Raleway'          => 'Raleway',
		'Quicksand'        => 'Quicksand',
		'Urbanist'         => 'Urbanist',
		'Figtree'          => 'Figtree',
		'Space Grotesk'    => 'Space Grotesk',
		'Barlow'           => 'Barlow',
		'Karla'            => 'Karla',
		'Mulish'           => 'Mulish',
		'Heebo'            => 'Heebo',
		'Cairo'            => 'Cairo',
		'Roboto Slab'      => 'Roboto Slab',
		'Merriweather'     => 'Merriweather',
		'Playfair Display' => 'Playfair Display',
		'Lora'             => 'Lora',
		'Bitter'           => 'Bitter',
	);
}

/**
 * Icons offered by the icon picker. Font Awesome 6 ships with the parent theme.
 *
 * @return array
 */
function apx_icon_list() {
	return array(
		'fas fa-home', 'fas fa-gamepad', 'fas fa-th-large', 'fas fa-newspaper', 'fas fa-fire',
		'fas fa-bolt', 'fas fa-star', 'fas fa-heart', 'fas fa-download', 'fas fa-cloud-download-alt',
		'fas fa-mobile-alt', 'fas fa-android', 'fab fa-android', 'fab fa-google-play', 'fab fa-apple',
		'fas fa-shield-alt', 'fas fa-lock', 'fas fa-user-shield', 'fas fa-key', 'fas fa-wallet',
		'fas fa-coins', 'fas fa-money-bill-wave', 'fas fa-dice', 'fas fa-trophy', 'fas fa-crown',
		'fas fa-gem', 'fas fa-magic', 'fas fa-rocket', 'fas fa-bell', 'fas fa-search',
		'fas fa-bars', 'fas fa-times', 'fas fa-chevron-up', 'fas fa-chevron-down', 'fas fa-chevron-left',
		'fas fa-chevron-right', 'fas fa-arrow-up', 'fas fa-arrow-right', 'fas fa-angle-right', 'fas fa-plus',
		'fas fa-check', 'fas fa-check-circle', 'fas fa-info-circle', 'fas fa-exclamation-triangle', 'fas fa-cog',
		'fas fa-sliders-h', 'fas fa-tags', 'fas fa-tag', 'fas fa-folder', 'fas fa-folder-open',
		'fas fa-clock', 'far fa-clock', 'far fa-calendar', 'fas fa-eye', 'fas fa-comments',
		'fas fa-user', 'fas fa-users', 'fas fa-share-alt', 'fas fa-sync-alt', 'fas fa-camera',
		'fas fa-images', 'fas fa-video', 'fas fa-music', 'fas fa-film', 'fas fa-book',
		'fas fa-graduation-cap', 'fas fa-briefcase', 'fas fa-chart-line', 'fas fa-shopping-cart', 'fas fa-store',
		'fas fa-utensils', 'fas fa-plane', 'fas fa-car', 'fas fa-map-marker-alt', 'fas fa-globe',
		'fas fa-language', 'fas fa-sun', 'fas fa-moon', 'fas fa-palette', 'fas fa-paint-brush',
		'fab fa-facebook-f', 'fab fa-twitter', 'fab fa-x-twitter', 'fab fa-instagram', 'fab fa-youtube',
		'fab fa-telegram-plane', 'fab fa-whatsapp', 'fab fa-tiktok', 'fab fa-discord', 'fab fa-reddit-alien',
		'fab fa-pinterest-p', 'fab fa-linkedin-in', 'fab fa-github', 'fab fa-windows', 'fab fa-linux',
	);
}

/**
 * Easing curves offered in the animation pickers.
 *
 * @return array
 */
function apx_easings() {
	return array(
		'ease'                                 => 'Ease',
		'ease-in-out'                          => 'Ease in out',
		'ease-out'                             => 'Ease out',
		'linear'                               => 'Linear',
		'cubic-bezier(.34,1.56,.64,1)'         => 'Bouncy',
		'cubic-bezier(.22,1,.36,1)'            => 'Smooth (expo out)',
		'cubic-bezier(.4,0,.2,1)'              => 'Snappy (material)',
		'cubic-bezier(.68,-.55,.27,1.55)'      => 'Back in out',
	);
}

/**
 * Print an icon element for a stored icon class.
 *
 * @param string $icon  Icon class.
 * @param string $extra Extra classes.
 * @return string
 */
function apx_icon( $icon, $extra = '' ) {
	$icon = trim( (string) $icon );

	if ( '' === $icon ) {
		return '';
	}

	return '<i class="' . esc_attr( trim( $icon . ' ' . $extra ) ) . '" aria-hidden="true"></i>';
}

/**
 * Replace {year} and {site_name} placeholders in footer texts.
 *
 * @param string $text Raw text.
 * @return string
 */
function apx_placeholders( $text ) {
	return str_replace(
		array( '{year}', '{site_name}', '{site_url}' ),
		array( gmdate( 'Y' ), get_bloginfo( 'name' ), home_url( '/' ) ),
		(string) $text
	);
}
