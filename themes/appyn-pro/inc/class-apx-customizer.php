<?php
/**
 * Customizer mirror for the most used tokens, so colours, fonts and sizes can
 * be tuned with a live preview next to the real site.
 *
 * The full set of options still lives in the Appyn Pro panel; this exposes the
 * everyday ones and writes to the very same option row.
 *
 * @package Appyn_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Customizer integration.
 */
class APX_Customizer {

	/**
	 * Keys exposed in the customizer, grouped by section.
	 *
	 * @var array
	 */
	protected $sections = array(
		'apx_colors'     => array(
			'title'  => 'Appyn Pro · Colours',
			'fields' => array( 'primary_color', 'secondary_color', 'accent_color', 'link_color', 'bg_color', 'surface_color', 'border_color', 'text_primary', 'text_secondary' ),
		),
		'apx_typography' => array(
			'title'  => 'Appyn Pro · Typography',
			'fields' => array( 'heading_font', 'body_font', 'base_font_size', 'line_height', 'letter_spacing' ),
		),
		'apx_layout'     => array(
			'title'  => 'Appyn Pro · Layout',
			'fields' => array( 'container_width', 'global_radius', 'grid_cols_desktop', 'grid_cols_mobile', 'grid_gap', 'card_radius', 'card_hover_lift' ),
		),
		'apx_darkmode'   => array(
			'title'  => 'Appyn Pro · Dark mode',
			'fields' => array( 'dark_enable', 'dark_default', 'dark_bg', 'dark_surface', 'dark_text', 'dark_primary' ),
		),
	);

	/**
	 * Hook in.
	 */
	public function __construct() {
		add_action( 'customize_register', array( $this, 'register' ) );
		add_action( 'customize_preview_init', array( $this, 'preview_js' ) );
		add_action( 'customize_save_after', array( $this, 'flush' ) );
	}

	/**
	 * Register panel, sections, settings and controls.
	 *
	 * @param WP_Customize_Manager $wp_customize Customizer manager.
	 * @return void
	 */
	public function register( $wp_customize ) {
		$wp_customize->add_panel(
			'apx_panel',
			array(
				'title'       => __( 'Appyn Pro', 'appyn-pro' ),
				'description' => __( 'Quick access to the everyday design tokens. The full panel lives under Appearance → Appyn Pro options.', 'appyn-pro' ),
				'priority'    => 20,
			)
		);

		$schema = apx_schema_flat();

		foreach ( $this->sections as $section_id => $section ) {
			$wp_customize->add_section(
				$section_id,
				array(
					'title' => $section['title'],
					'panel' => 'apx_panel',
				)
			);

			foreach ( $section['fields'] as $key ) {
				if ( ! isset( $schema[ $key ] ) ) {
					continue;
				}

				$field   = $schema[ $key ];
				$setting = APX_OPTION . '[' . $key . ']';

				// Tokens can be repainted live; anything else needs a reload.
				$transport = empty( $field['css_var'] ) ? 'refresh' : 'postMessage';

				$wp_customize->add_setting(
					$setting,
					array(
						'type'              => 'option',
						'capability'        => 'edit_theme_options',
						'default'           => isset( $field['default'] ) ? $field['default'] : '',
						'transport'         => $transport,
						'sanitize_callback' => function ( $value ) use ( $field ) {
							return APX_Settings::sanitize_value( $value, $field );
						},
					)
				);

				$this->add_control( $wp_customize, $setting, $section_id, $key, $field );
			}
		}
	}

	/**
	 * Add the right control type for a field.
	 *
	 * @param WP_Customize_Manager $wp_customize Manager.
	 * @param string               $setting      Setting id.
	 * @param string               $section      Section id.
	 * @param string               $key          Field key.
	 * @param array                $field        Field definition.
	 * @return void
	 */
	protected function add_control( $wp_customize, $setting, $section, $key, $field ) {
		$label = $field['label'];

		switch ( $field['type'] ) {
			case 'color':
				$wp_customize->add_control(
					new WP_Customize_Color_Control(
						$wp_customize,
						'apx_' . $key,
						array(
							'label'    => $label,
							'section'  => $section,
							'settings' => $setting,
						)
					)
				);
				break;

			case 'slider':
				$wp_customize->add_control(
					'apx_' . $key,
					array(
						'label'       => $label,
						'section'     => $section,
						'settings'    => $setting,
						'type'        => 'number',
						'input_attrs' => array(
							'min'  => isset( $field['min'] ) ? $field['min'] : 0,
							'max'  => isset( $field['max'] ) ? $field['max'] : 100,
							'step' => isset( $field['step'] ) ? $field['step'] : 1,
						),
					)
				);
				break;

			case 'toggle':
				$wp_customize->add_control(
					'apx_' . $key,
					array(
						'label'    => $label,
						'section'  => $section,
						'settings' => $setting,
						'type'     => 'checkbox',
					)
				);
				break;

			case 'font':
				$wp_customize->add_control(
					'apx_' . $key,
					array(
						'label'    => $label,
						'section'  => $section,
						'settings' => $setting,
						'type'     => 'select',
						'choices'  => apx_google_fonts(),
					)
				);
				break;

			case 'select':
				$wp_customize->add_control(
					'apx_' . $key,
					array(
						'label'    => $label,
						'section'  => $section,
						'settings' => $setting,
						'type'     => 'select',
						'choices'  => APX_Settings::choices( $field ),
					)
				);
				break;

			default:
				$wp_customize->add_control(
					'apx_' . $key,
					array(
						'label'    => $label,
						'section'  => $section,
						'settings' => $setting,
						'type'     => 'text',
					)
				);
				break;
		}
	}

	/**
	 * Live preview script: rewrite the tokens without reloading.
	 *
	 * @return void
	 */
	public function preview_js() {
		wp_enqueue_script(
			'apx-customizer-preview',
			APX_URI . '/assets/admin/js/customizer.js',
			array( 'customize-preview' ),
			APX_VERSION,
			true
		);

		$map = array();

		foreach ( $this->sections as $section ) {
			foreach ( $section['fields'] as $key ) {
				$schema = apx_schema_flat();

				if ( empty( $schema[ $key ]['css_var'] ) ) {
					continue;
				}

				$map[ APX_OPTION . '[' . $key . ']' ] = array(
					'var'   => $schema[ $key ]['css_var'],
					'unit'  => isset( $schema[ $key ]['unit'] ) ? $schema[ $key ]['unit'] : '',
					'scope' => ( isset( $schema[ $key ]['scope'] ) && 'dark' === $schema[ $key ]['scope'] ) ? 'dark' : 'root',
					'type'  => $schema[ $key ]['type'],
				);
			}
		}

		wp_localize_script( 'apx-customizer-preview', 'APX_PREVIEW', $map );
	}

	/**
	 * Drop the CSS cache after a customizer save.
	 *
	 * @return void
	 */
	public function flush() {
		delete_transient( 'apx_css_cache' );
	}
}
