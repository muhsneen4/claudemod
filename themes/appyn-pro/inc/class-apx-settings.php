<?php
/**
 * Storage and validation for the Appyn Pro options.
 *
 * All values live in one option row so import, export and presets stay simple.
 *
 * @package Appyn_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings repository.
 */
class APX_Settings {

	/**
	 * Runtime cache of the stored values.
	 *
	 * @var array|null
	 */
	protected static $cache = null;

	/**
	 * Runtime cache of the schema defaults.
	 *
	 * @var array|null
	 */
	protected static $defaults = null;

	/**
	 * Every default value from the schema.
	 *
	 * @return array
	 */
	public static function defaults() {
		if ( null !== self::$defaults ) {
			return self::$defaults;
		}

		self::$defaults = array();

		foreach ( apx_schema_flat() as $key => $field ) {
			self::$defaults[ $key ] = isset( $field['default'] ) ? $field['default'] : '';
		}

		return self::$defaults;
	}

	/**
	 * All stored values merged over the defaults.
	 *
	 * @return array
	 */
	public static function all() {
		if ( null !== self::$cache ) {
			return self::$cache;
		}

		$stored = get_option( APX_OPTION, array() );

		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		self::$cache = array_merge( self::defaults(), $stored );

		return self::$cache;
	}

	/**
	 * Read one value.
	 *
	 * @param string $key     Field key.
	 * @param mixed  $default Optional fallback.
	 * @return mixed
	 */
	public static function get( $key, $default = null ) {
		$all = self::all();

		if ( array_key_exists( $key, $all ) && '' !== $all[ $key ] && null !== $all[ $key ] ) {
			return $all[ $key ];
		}

		if ( null !== $default ) {
			return $default;
		}

		$defaults = self::defaults();

		return isset( $defaults[ $key ] ) ? $defaults[ $key ] : '';
	}

	/**
	 * Save a full set of values (already sanitised).
	 *
	 * @param array $values Values.
	 * @return void
	 */
	public static function save( array $values ) {
		update_option( APX_OPTION, $values );
		self::$cache = null;
		delete_transient( 'apx_css_cache' );
	}

	/**
	 * Reset everything back to the schema defaults.
	 *
	 * @return void
	 */
	public static function reset() {
		delete_option( APX_OPTION );
		self::$cache = null;
		delete_transient( 'apx_css_cache' );
	}

	/**
	 * Sanitise a raw payload against the schema.
	 *
	 * @param array $raw Raw values keyed by field key.
	 * @return array
	 */
	public static function sanitize( array $raw ) {
		$clean  = array();
		$schema = apx_schema_flat();

		foreach ( $schema as $key => $field ) {
			$type = isset( $field['type'] ) ? $field['type'] : 'text';

			if ( 'info' === $type ) {
				continue;
			}

			$value = isset( $raw[ $key ] ) ? $raw[ $key ] : null;

			if ( 'toggle' === $type ) {
				// A form always posts the hidden 0, so a missing key means the
				// payload is partial (an import): keep the default instead.
				if ( ! array_key_exists( $key, $raw ) ) {
					$clean[ $key ] = empty( $field['default'] ) ? 0 : 1;
					continue;
				}

				$clean[ $key ] = ( '0' !== (string) $value && '' !== (string) $value ) ? 1 : 0;
				continue;
			}

			if ( null === $value ) {
				$clean[ $key ] = isset( $field['default'] ) ? $field['default'] : '';
				continue;
			}

			$clean[ $key ] = self::sanitize_value( $value, $field );
		}

		return $clean;
	}

	/**
	 * Sanitise a single value for a field definition.
	 *
	 * @param mixed $value Raw value.
	 * @param array $field Field definition.
	 * @return mixed
	 */
	public static function sanitize_value( $value, $field ) {
		$type    = isset( $field['type'] ) ? $field['type'] : 'text';
		$default = isset( $field['default'] ) ? $field['default'] : '';

		switch ( $type ) {
			case 'color':
				return self::sanitize_color( $value, $default );

			case 'colora':
				$value = is_array( $value ) ? $value : array();

				return array(
					'color'   => self::sanitize_color( isset( $value['color'] ) ? $value['color'] : '', isset( $default['color'] ) ? $default['color'] : '#000000' ),
					'opacity' => max( 0, min( 1, isset( $value['opacity'] ) ? (float) $value['opacity'] : 1 ) ),
				);

			case 'slider':
				$number = is_numeric( $value ) ? (float) $value : (float) $default;

				if ( isset( $field['min'] ) ) {
					$number = max( (float) $field['min'], $number );
				}

				if ( isset( $field['max'] ) ) {
					$number = min( (float) $field['max'], $number );
				}

				return ( floor( $number ) === $number ) ? (int) $number : $number;

			case 'toggle':
				return $value ? 1 : 0;

			case 'select':
				$choices = self::choices( $field );

				return array_key_exists( (string) $value, $choices ) ? (string) $value : (string) $default;

			case 'font':
				$fonts = apx_google_fonts();

				return array_key_exists( (string) $value, $fonts ) ? (string) $value : (string) $default;

			case 'icon':
				return preg_replace( '/[^a-z0-9 \-]/i', '', (string) $value );

			case 'image':
				return esc_url_raw( (string) $value );

			case 'textarea':
			case 'editor':
				return wp_kses_post( (string) $value );

			case 'code':
				return self::sanitize_code( (string) $value );

			case 'spacing':
				$value = is_array( $value ) ? $value : array();
				$out   = array();

				foreach ( array( 'top', 'right', 'bottom', 'left' ) as $side ) {
					$out[ $side ] = ( isset( $value[ $side ] ) && is_numeric( $value[ $side ] ) ) ? (float) $value[ $side ] : 0;
				}

				return $out;

			case 'border':
				$value  = is_array( $value ) ? $value : array();
				$styles = array( 'solid', 'dashed', 'dotted', 'double', 'none' );

				return array(
					'width' => ( isset( $value['width'] ) && is_numeric( $value['width'] ) ) ? (float) $value['width'] : 0,
					'style' => ( isset( $value['style'] ) && in_array( $value['style'], $styles, true ) ) ? $value['style'] : 'solid',
					'color' => self::sanitize_color( isset( $value['color'] ) ? $value['color'] : '', '#000000' ),
				);

			case 'shadow':
				$value = is_array( $value ) ? $value : array();
				$out   = array( 'enable' => empty( $value['enable'] ) ? 0 : 1 );

				foreach ( array( 'x', 'y', 'blur', 'spread' ) as $part ) {
					$out[ $part ] = ( isset( $value[ $part ] ) && is_numeric( $value[ $part ] ) ) ? (float) $value[ $part ] : 0;
				}

				$out['color'] = self::sanitize_color( isset( $value['color'] ) ? $value['color'] : '', '#00000022' );

				return $out;

			case 'gradient':
				$value = is_array( $value ) ? $value : array();

				return array(
					'enable' => empty( $value['enable'] ) ? 0 : 1,
					'angle'  => ( isset( $value['angle'] ) && is_numeric( $value['angle'] ) ) ? (int) $value['angle'] : 90,
					'from'   => self::sanitize_color( isset( $value['from'] ) ? $value['from'] : '', '#000000' ),
					'to'     => self::sanitize_color( isset( $value['to'] ) ? $value['to'] : '', '#00000000' ),
				);

			case 'repeater':
				return self::sanitize_repeater( $value, $field );

			case 'text':
			default:
				return sanitize_text_field( (string) $value );
		}
	}

	/**
	 * Sanitise repeater rows against their sub field definitions.
	 *
	 * @param mixed $value Raw rows.
	 * @param array $field Field definition.
	 * @return array
	 */
	protected static function sanitize_repeater( $value, $field ) {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$sub  = isset( $field['fields'] ) ? $field['fields'] : array();
		$rows = array();

		foreach ( $value as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$clean = array();
			$empty = true;

			foreach ( $sub as $sub_key => $sub_field ) {
				if ( 'toggle' === $sub_field['type'] ) {
					$clean[ $sub_key ] = ( isset( $row[ $sub_key ] ) && '0' !== $row[ $sub_key ] ) ? 1 : 0;
					continue;
				}

				$raw               = isset( $row[ $sub_key ] ) ? $row[ $sub_key ] : '';
				$clean[ $sub_key ] = self::sanitize_value( $raw, $sub_field );

				if ( in_array( $sub_field['type'], array( 'text', 'textarea', 'image', 'icon' ), true ) && '' !== $clean[ $sub_key ] ) {
					$empty = false;
				}
			}

			if ( ! $empty ) {
				$rows[] = $clean;
			}
		}

		return $rows;
	}

	/**
	 * Colour sanitiser that also accepts 8 digit hex (with alpha) and rgba().
	 *
	 * @param string $value   Raw colour.
	 * @param string $default Fallback.
	 * @return string
	 */
	public static function sanitize_color( $value, $default = '' ) {
		$value = trim( (string) $value );

		if ( '' === $value ) {
			return is_string( $default ) ? $default : '';
		}

		if ( preg_match( '/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6}|[A-Fa-f0-9]{8})$/', $value ) ) {
			return $value;
		}

		if ( preg_match( '/^rgba?\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*(,\s*(0|1|0?\.\d+)\s*)?\)$/', $value ) ) {
			return $value;
		}

		if ( in_array( $value, array( 'transparent', 'inherit', 'currentColor' ), true ) ) {
			return $value;
		}

		return is_string( $default ) ? $default : '';
	}

	/**
	 * Strip anything that could break out of a style or script block.
	 *
	 * @param string $code Raw code.
	 * @return string
	 */
	public static function sanitize_code( $code ) {
		$code = (string) $code;
		$code = preg_replace( '#</\s*(script|style)[^>]*>?#i', '', $code );

		return wp_check_invalid_utf8( $code, true );
	}

	/**
	 * Resolve the choice list of a select field.
	 *
	 * @param array $field Field definition.
	 * @return array
	 */
	public static function choices( $field ) {
		if ( ! isset( $field['choices'] ) ) {
			return array();
		}

		if ( 'easings' === $field['choices'] ) {
			return apx_easings();
		}

		return is_array( $field['choices'] ) ? $field['choices'] : array();
	}

	/* ---------------------------------------------------------------- *
	 * Import / export / presets
	 * ---------------------------------------------------------------- */

	/**
	 * All values as a JSON string.
	 *
	 * @return string
	 */
	public static function export_json() {
		return wp_json_encode(
			array(
				'theme'   => 'appyn-pro',
				'version' => APX_VERSION,
				'date'    => gmdate( 'c' ),
				'values'  => self::all(),
			),
			JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
		);
	}

	/**
	 * Replace the stored values from an exported JSON payload.
	 *
	 * @param string $json Raw JSON.
	 * @return true|WP_Error
	 */
	public static function import_json( $json ) {
		$data = json_decode( (string) $json, true );

		if ( ! is_array( $data ) ) {
			return new WP_Error( 'apx_import', __( 'That file is not valid JSON.', 'appyn-pro' ) );
		}

		$values = ( isset( $data['values'] ) && is_array( $data['values'] ) ) ? $data['values'] : $data;

		if ( empty( $values ) ) {
			return new WP_Error( 'apx_import', __( 'That file has no settings in it.', 'appyn-pro' ) );
		}

		self::save( self::sanitize( $values ) );

		return true;
	}

	/**
	 * Stored presets.
	 *
	 * @return array
	 */
	public static function presets() {
		$presets = get_option( APX_PRESETS_OPTION, array() );

		return is_array( $presets ) ? $presets : array();
	}

	/**
	 * Store the current settings under a name.
	 *
	 * @param string $name Preset name.
	 * @return void
	 */
	public static function save_preset( $name ) {
		$name = sanitize_text_field( $name );

		if ( '' === $name ) {
			return;
		}

		$presets          = self::presets();
		$presets[ $name ] = self::all();

		update_option( APX_PRESETS_OPTION, $presets );
	}

	/**
	 * Load a preset over the current settings.
	 *
	 * @param string $name Preset name.
	 * @return bool
	 */
	public static function load_preset( $name ) {
		$presets = self::presets();

		if ( ! isset( $presets[ $name ] ) ) {
			return false;
		}

		self::save( self::sanitize( $presets[ $name ] ) );

		return true;
	}

	/**
	 * Delete a preset.
	 *
	 * @param string $name Preset name.
	 * @return void
	 */
	public static function delete_preset( $name ) {
		$presets = self::presets();

		unset( $presets[ $name ] );

		update_option( APX_PRESETS_OPTION, $presets );
	}
}
