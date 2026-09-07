<?php
/**
 * The complete Appyn Pro settings registry.
 *
 * Every panel field lives here once. The admin panel renders this array, the
 * sanitiser validates against it and the CSS generator turns the values that
 * declare a `css_var` into design tokens. Add a field here and it shows up in
 * all three places automatically.
 *
 * Field keys used by the renderer / generator:
 *   type     color|colora|slider|toggle|select|text|textarea|editor|image|icon|
 *            font|spacing|border|shadow|gradient|code|repeater|info
 *   default  Default value.
 *   css_var  CSS custom property emitted for this value.
 *   unit     Unit appended to numeric values.
 *   scope    root (default) or dark - which selector the token lands in.
 *   media    ''|tablet|mobile - which media query the token lands in.
 *   section  Card heading the field is grouped under.
 *
 * @package Appyn_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The whole schema, tab by tab.
 *
 * @return array
 */
function apx_settings_schema() {
	static $schema = null;

	if ( null !== $schema ) {
		return $schema;
	}

	$schema = array(
		'global'      => array(
			'label'  => __( 'Global', 'appyn-pro' ),
			'icon'   => 'dashicons-admin-site-alt3',
			'fields' => apx_schema_global(),
		),
		'typography'  => array(
			'label'  => __( 'Typography', 'appyn-pro' ),
			'icon'   => 'dashicons-editor-textcolor',
			'fields' => apx_schema_typography(),
		),
		'header'      => array(
			'label'  => __( 'Header', 'appyn-pro' ),
			'icon'   => 'dashicons-align-center',
			'fields' => apx_schema_header(),
		),
		'hero'        => array(
			'label'  => __( 'Hero Slider', 'appyn-pro' ),
			'icon'   => 'dashicons-images-alt2',
			'fields' => apx_schema_hero(),
		),
		'categories'  => array(
			'label'  => __( 'Categories', 'appyn-pro' ),
			'icon'   => 'dashicons-category',
			'fields' => apx_schema_categories(),
		),
		'cards'       => array(
			'label'  => __( 'App Cards', 'appyn-pro' ),
			'icon'   => 'dashicons-grid-view',
			'fields' => apx_schema_cards(),
		),
		'news'        => array(
			'label'  => __( 'News / Blog', 'appyn-pro' ),
			'icon'   => 'dashicons-media-document',
			'fields' => apx_schema_news(),
		),
		'detail'      => array(
			'label'  => __( 'App Detail Page', 'appyn-pro' ),
			'icon'   => 'dashicons-smartphone',
			'fields' => apx_schema_detail(),
		),
		'footer'      => array(
			'label'  => __( 'Footer', 'appyn-pro' ),
			'icon'   => 'dashicons-align-wide',
			'fields' => apx_schema_footer(),
		),
		'animations'  => array(
			'label'  => __( 'Animations', 'appyn-pro' ),
			'icon'   => 'dashicons-controls-play',
			'fields' => apx_schema_animations(),
		),
		'dark'        => array(
			'label'  => __( 'Dark Mode', 'appyn-pro' ),
			'icon'   => 'dashicons-moon',
			'fields' => apx_schema_dark(),
		),
		'responsive'  => array(
			'label'  => __( 'Responsive', 'appyn-pro' ),
			'icon'   => 'dashicons-tablet',
			'fields' => apx_schema_responsive(),
		),
		'custom_code' => array(
			'label'  => __( 'Custom Code', 'appyn-pro' ),
			'icon'   => 'dashicons-editor-code',
			'fields' => apx_schema_custom_code(),
		),
		'tools'       => array(
			'label'  => __( 'Import / Export', 'appyn-pro' ),
			'icon'   => 'dashicons-database-export',
			'fields' => apx_schema_tools(),
		),
	);

	/**
	 * Filter the settings schema so plugins or a grandchild theme can extend it.
	 *
	 * @param array $schema Schema array.
	 */
	$schema = apply_filters( 'apx_settings_schema', $schema );

	return $schema;
}

/**
 * Flat map of key => field definition across every tab.
 *
 * @return array
 */
function apx_schema_flat() {
	static $flat = null;

	if ( null !== $flat ) {
		return $flat;
	}

	$flat = array();

	foreach ( apx_settings_schema() as $tab ) {
		foreach ( $tab['fields'] as $key => $field ) {
			$flat[ $key ] = $field;
		}
	}

	return $flat;
}

/* ---------------------------------------------------------------------------
 * Tab 1 - Global
 * ------------------------------------------------------------------------ */

/**
 * Global colours, radius and motion.
 *
 * @return array
 */
function apx_schema_global() {
	return array(
		'primary_color'   => array(
			'type'    => 'color',
			'section' => __( 'Brand colours', 'appyn-pro' ),
			'label'   => __( 'Primary colour', 'appyn-pro' ),
			'desc'    => __( 'Buttons, active states and highlights.', 'appyn-pro' ),
			'default' => '#4CAF50',
			'css_var' => '--apx-primary',
		),
		'secondary_color' => array(
			'type'    => 'color',
			'label'   => __( 'Secondary colour', 'appyn-pro' ),
			'default' => '#FF9800',
			'css_var' => '--apx-secondary',
		),
		'accent_color'    => array(
			'type'    => 'color',
			'label'   => __( 'Accent colour', 'appyn-pro' ),
			'default' => '#9C27B0',
			'css_var' => '--apx-accent',
		),
		'link_color'      => array(
			'type'    => 'color',
			'label'   => __( 'Link colour', 'appyn-pro' ),
			'default' => '#2196F3',
			'css_var' => '--apx-link',
		),
		'link_hover_color'=> array(
			'type'    => 'color',
			'label'   => __( 'Link hover colour', 'appyn-pro' ),
			'default' => '#1769aa',
			'css_var' => '--apx-link-hover',
		),
		'success_color'   => array(
			'type'    => 'color',
			'section' => __( 'State colours', 'appyn-pro' ),
			'label'   => __( 'Success colour', 'appyn-pro' ),
			'default' => '#4CAF50',
			'css_var' => '--apx-success',
		),
		'warning_color'   => array(
			'type'    => 'color',
			'label'   => __( 'Warning colour', 'appyn-pro' ),
			'default' => '#FF9800',
			'css_var' => '--apx-warning',
		),
		'error_color'     => array(
			'type'    => 'color',
			'label'   => __( 'Error colour', 'appyn-pro' ),
			'default' => '#F44336',
			'css_var' => '--apx-error',
		),
		'bg_color'        => array(
			'type'    => 'color',
			'section' => __( 'Surfaces', 'appyn-pro' ),
			'label'   => __( 'Page background', 'appyn-pro' ),
			'default' => '#f5f6f8',
			'css_var' => '--apx-bg',
		),
		'surface_color'   => array(
			'type'    => 'color',
			'label'   => __( 'Surface (cards, boxes)', 'appyn-pro' ),
			'default' => '#ffffff',
			'css_var' => '--apx-surface',
		),
		'surface_alt_color' => array(
			'type'    => 'color',
			'label'   => __( 'Raised surface', 'appyn-pro' ),
			'default' => '#fafbfc',
			'css_var' => '--apx-surface-alt',
		),
		'border_color'    => array(
			'type'    => 'color',
			'label'   => __( 'Border colour', 'appyn-pro' ),
			'default' => '#e6e8ec',
			'css_var' => '--apx-border',
		),
		'text_primary'    => array(
			'type'    => 'color',
			'label'   => __( 'Primary text', 'appyn-pro' ),
			'default' => '#1a1a2e',
			'css_var' => '--apx-text',
		),
		'text_secondary'  => array(
			'type'    => 'color',
			'label'   => __( 'Secondary text', 'appyn-pro' ),
			'default' => '#6b7280',
			'css_var' => '--apx-text-2',
		),
		'container_width' => array(
			'type'    => 'slider',
			'section' => __( 'Layout & motion', 'appyn-pro' ),
			'label'   => __( 'Container width', 'appyn-pro' ),
			'default' => 1200,
			'min'     => 960,
			'max'     => 1600,
			'step'    => 10,
			'unit'    => 'px',
			'css_var' => '--apx-container',
		),
		'global_radius'   => array(
			'type'    => 'slider',
			'label'   => __( 'Global border radius', 'appyn-pro' ),
			'default' => 12,
			'min'     => 0,
			'max'     => 50,
			'unit'    => 'px',
			'css_var' => '--apx-radius',
		),
		'anim_speed'      => array(
			'type'    => 'slider',
			'label'   => __( 'Animation speed', 'appyn-pro' ),
			'default' => 0.3,
			'min'     => 0.1,
			'max'     => 2,
			'step'    => 0.05,
			'unit'    => 's',
			'css_var' => '--apx-speed',
		),
		'anim_enable'     => array(
			'type'    => 'toggle',
			'label'   => __( 'Enable animations', 'appyn-pro' ),
			'desc'    => __( 'Master switch. Turn off for the fastest possible pages.', 'appyn-pro' ),
			'default' => 1,
		),
		'takeover'        => array(
			'type'    => 'toggle',
			'section' => __( 'Take over parent templates', 'appyn-pro' ),
			'label'   => __( 'Use Appyn Pro header & footer', 'appyn-pro' ),
			'desc'    => __( 'On: the customizable header and footer below replace the parent theme ones. Off: the parent Appyn markup is used and only colours apply.', 'appyn-pro' ),
			'default' => 1,
		),
		'takeover_cards'  => array(
			'type'    => 'toggle',
			'label'   => __( 'Use Appyn Pro app & news cards', 'appyn-pro' ),
			'default' => 1,
		),
	);
}

/* ---------------------------------------------------------------------------
 * Tab 2 - Typography
 * ------------------------------------------------------------------------ */

/**
 * Font families, sizes and rhythm.
 *
 * @return array
 */
function apx_schema_typography() {
	$weights = array(
		'300' => '300 Light',
		'400' => '400 Regular',
		'500' => '500 Medium',
		'600' => '600 Semi bold',
		'700' => '700 Bold',
		'800' => '800 Extra bold',
		'900' => '900 Black',
	);

	return array(
		'heading_font'    => array(
			'type'    => 'font',
			'section' => __( 'Font families', 'appyn-pro' ),
			'label'   => __( 'Heading font', 'appyn-pro' ),
			'default' => 'Poppins',
		),
		'body_font'       => array(
			'type'    => 'font',
			'label'   => __( 'Body font', 'appyn-pro' ),
			'default' => 'Inter',
		),
		'font_display'    => array(
			'type'    => 'select',
			'label'   => __( 'Font loading', 'appyn-pro' ),
			'default' => 'swap',
			'choices' => array(
				'swap'     => 'swap (show fallback first - fastest)',
				'block'    => 'block',
				'optional' => 'optional',
				'fallback' => 'fallback',
			),
		),
		'base_font_size'  => array(
			'type'    => 'slider',
			'section' => __( 'Sizes & rhythm', 'appyn-pro' ),
			'label'   => __( 'Base font size', 'appyn-pro' ),
			'default' => 16,
			'min'     => 12,
			'max'     => 20,
			'unit'    => 'px',
			'css_var' => '--apx-font-size',
		),
		'heading_weight'  => array(
			'type'    => 'select',
			'label'   => __( 'Heading weight', 'appyn-pro' ),
			'default' => '700',
			'choices' => $weights,
			'css_var' => '--apx-heading-weight',
		),
		'body_weight'     => array(
			'type'    => 'select',
			'label'   => __( 'Body weight', 'appyn-pro' ),
			'default' => '400',
			'choices' => array(
				'300' => '300 Light',
				'400' => '400 Regular',
				'500' => '500 Medium',
				'600' => '600 Semi bold',
			),
			'css_var' => '--apx-body-weight',
		),
		'line_height'     => array(
			'type'    => 'slider',
			'label'   => __( 'Body line height', 'appyn-pro' ),
			'default' => 1.6,
			'min'     => 1.2,
			'max'     => 2,
			'step'    => 0.05,
			'css_var' => '--apx-line-height',
		),
		'heading_line_height' => array(
			'type'    => 'slider',
			'label'   => __( 'Heading line height', 'appyn-pro' ),
			'default' => 1.25,
			'min'     => 1,
			'max'     => 1.8,
			'step'    => 0.05,
			'css_var' => '--apx-heading-line-height',
		),
		'letter_spacing'  => array(
			'type'    => 'slider',
			'label'   => __( 'Letter spacing', 'appyn-pro' ),
			'default' => 0,
			'min'     => -2,
			'max'     => 5,
			'step'    => 0.1,
			'unit'    => 'px',
			'css_var' => '--apx-letter-spacing',
		),
		'heading_letter_spacing' => array(
			'type'    => 'slider',
			'label'   => __( 'Heading letter spacing', 'appyn-pro' ),
			'default' => -0.2,
			'min'     => -2,
			'max'     => 5,
			'step'    => 0.1,
			'unit'    => 'px',
			'css_var' => '--apx-heading-letter-spacing',
		),
		'h1_size'         => array(
			'type'    => 'slider',
			'label'   => __( 'H1 size', 'appyn-pro' ),
			'default' => 34,
			'min'     => 20,
			'max'     => 72,
			'unit'    => 'px',
			'css_var' => '--apx-h1',
		),
		'h2_size'         => array(
			'type'    => 'slider',
			'label'   => __( 'H2 size', 'appyn-pro' ),
			'default' => 26,
			'min'     => 16,
			'max'     => 56,
			'unit'    => 'px',
			'css_var' => '--apx-h2',
		),
		'h3_size'         => array(
			'type'    => 'slider',
			'label'   => __( 'H3 size', 'appyn-pro' ),
			'default' => 20,
			'min'     => 14,
			'max'     => 40,
			'unit'    => 'px',
			'css_var' => '--apx-h3',
		),
	);
}

/* ---------------------------------------------------------------------------
 * Tab 3 - Header
 * ------------------------------------------------------------------------ */

/**
 * Top bar, navbar, sticky behaviour and the mobile header.
 *
 * @return array
 */
function apx_schema_header() {
	return array(
		'topbar_enable'      => array(
			'type'    => 'toggle',
			'section' => __( 'Top bar', 'appyn-pro' ),
			'label'   => __( 'Show top bar', 'appyn-pro' ),
			'default' => 0,
		),
		'topbar_bg'          => array(
			'type'    => 'color',
			'label'   => __( 'Top bar background', 'appyn-pro' ),
			'default' => '#1a1a2e',
			'css_var' => '--apx-topbar-bg',
		),
		'topbar_text'        => array(
			'type'    => 'color',
			'label'   => __( 'Top bar text', 'appyn-pro' ),
			'default' => '#ffffff',
			'css_var' => '--apx-topbar-text',
		),
		'topbar_height'      => array(
			'type'    => 'slider',
			'label'   => __( 'Top bar height', 'appyn-pro' ),
			'default' => 36,
			'min'     => 24,
			'max'     => 70,
			'unit'    => 'px',
			'css_var' => '--apx-topbar-height',
		),
		'topbar_font_size'   => array(
			'type'    => 'slider',
			'label'   => __( 'Top bar font size', 'appyn-pro' ),
			'default' => 12,
			'min'     => 10,
			'max'     => 18,
			'unit'    => 'px',
			'css_var' => '--apx-topbar-size',
		),
		'topbar_border'      => array(
			'type'    => 'border',
			'label'   => __( 'Top bar bottom border', 'appyn-pro' ),
			'default' => array(
				'width' => 0,
				'style' => 'solid',
				'color' => '#00000022',
			),
			'css_var' => '--apx-topbar-border',
		),
		'topbar_links'       => array(
			'type'    => 'repeater',
			'label'   => __( 'Top bar links', 'appyn-pro' ),
			'desc'    => __( 'Small links shown on the left of the top bar.', 'appyn-pro' ),
			'default' => array(),
			'fields'  => array(
				'label' => array(
					'type'    => 'text',
					'label'   => __( 'Label', 'appyn-pro' ),
					'default' => '',
				),
				'icon'  => array(
					'type'    => 'icon',
					'label'   => __( 'Icon', 'appyn-pro' ),
					'default' => '',
				),
				'url'   => array(
					'type'    => 'text',
					'label'   => __( 'URL', 'appyn-pro' ),
					'default' => '',
				),
				'color' => array(
					'type'    => 'color',
					'label'   => __( 'Colour', 'appyn-pro' ),
					'default' => '#ffffff',
				),
			),
		),
		'header_bg'          => array(
			'type'    => 'color',
			'section' => __( 'Main navbar', 'appyn-pro' ),
			'label'   => __( 'Header background', 'appyn-pro' ),
			'default' => '#ffffff',
			'css_var' => '--apx-header-bg',
		),
		'header_text'        => array(
			'type'    => 'color',
			'label'   => __( 'Header text', 'appyn-pro' ),
			'default' => '#1a1a2e',
			'css_var' => '--apx-header-text',
		),
		'header_height'      => array(
			'type'    => 'slider',
			'label'   => __( 'Header height', 'appyn-pro' ),
			'default' => 70,
			'min'     => 48,
			'max'     => 140,
			'unit'    => 'px',
			'css_var' => '--apx-header-height',
		),
		'header_logo'        => array(
			'type'    => 'image',
			'label'   => __( 'Logo', 'appyn-pro' ),
			'desc'    => __( 'Leave empty to use the parent theme logo.', 'appyn-pro' ),
			'default' => '',
		),
		'header_logo_height' => array(
			'type'    => 'slider',
			'label'   => __( 'Logo height', 'appyn-pro' ),
			'default' => 40,
			'min'     => 20,
			'max'     => 150,
			'unit'    => 'px',
			'css_var' => '--apx-logo-height',
		),
		'header_border'      => array(
			'type'    => 'border',
			'label'   => __( 'Header bottom border', 'appyn-pro' ),
			'default' => array(
				'width' => 1,
				'style' => 'solid',
				'color' => '#e6e8ec',
			),
			'css_var' => '--apx-header-border',
		),
		'header_shadow'      => array(
			'type'    => 'shadow',
			'label'   => __( 'Header shadow', 'appyn-pro' ),
			'default' => array(
				'enable' => 1,
				'x'      => 0,
				'y'      => 2,
				'blur'   => 14,
				'spread' => 0,
				'color'  => '#0f172a14',
			),
			'css_var' => '--apx-header-shadow',
		),
		'nav_items'          => array(
			'type'    => 'repeater',
			'section' => __( 'Navigation', 'appyn-pro' ),
			'label'   => __( 'Navigation items', 'appyn-pro' ),
			'desc'    => __( 'Leave empty to fall back to the WordPress menu assigned to "Menú".', 'appyn-pro' ),
			'default' => array(),
			'fields'  => array(
				'label'     => array(
					'type'    => 'text',
					'label'   => __( 'Label', 'appyn-pro' ),
					'default' => '',
				),
				'icon'      => array(
					'type'    => 'icon',
					'label'   => __( 'Icon', 'appyn-pro' ),
					'default' => '',
				),
				'url'       => array(
					'type'    => 'text',
					'label'   => __( 'Link URL', 'appyn-pro' ),
					'default' => '',
				),
				'color'     => array(
					'type'    => 'color',
					'label'   => __( 'Icon colour', 'appyn-pro' ),
					'default' => '#4CAF50',
				),
				'hover'     => array(
					'type'    => 'color',
					'label'   => __( 'Hover colour', 'appyn-pro' ),
					'default' => '#3d8b40',
				),
				'css_class' => array(
					'type'    => 'text',
					'label'   => __( 'Custom CSS class', 'appyn-pro' ),
					'default' => '',
				),
				'new_tab'   => array(
					'type'    => 'toggle',
					'label'   => __( 'Open in new tab', 'appyn-pro' ),
					'default' => 0,
				),
			),
		),
		'nav_style'          => array(
			'type'    => 'select',
			'label'   => __( 'Navigation style', 'appyn-pro' ),
			'default' => 'pill',
			'choices' => array(
				'pill'    => __( 'Coloured icon pills', 'appyn-pro' ),
				'text'    => __( 'Plain text', 'appyn-pro' ),
				'underline' => __( 'Text with underline', 'appyn-pro' ),
				'boxed'   => __( 'Boxed', 'appyn-pro' ),
			),
		),
		'nav_font_size'      => array(
			'type'    => 'slider',
			'label'   => __( 'Navigation font size', 'appyn-pro' ),
			'default' => 15,
			'min'     => 11,
			'max'     => 22,
			'unit'    => 'px',
			'css_var' => '--apx-nav-size',
		),
		'nav_font_weight'    => array(
			'type'    => 'select',
			'label'   => __( 'Navigation weight', 'appyn-pro' ),
			'default' => '600',
			'choices' => array(
				'400' => '400',
				'500' => '500',
				'600' => '600',
				'700' => '700',
			),
			'css_var' => '--apx-nav-weight',
		),
		'nav_spacing'        => array(
			'type'    => 'slider',
			'label'   => __( 'Space between items', 'appyn-pro' ),
			'default' => 18,
			'min'     => 0,
			'max'     => 60,
			'unit'    => 'px',
			'css_var' => '--apx-nav-gap',
		),
		'nav_icon_size'      => array(
			'type'    => 'slider',
			'label'   => __( 'Nav icon size', 'appyn-pro' ),
			'default' => 34,
			'min'     => 18,
			'max'     => 60,
			'unit'    => 'px',
			'css_var' => '--apx-nav-icon',
		),
		'header_search'      => array(
			'type'    => 'toggle',
			'label'   => __( 'Show search icon', 'appyn-pro' ),
			'default' => 1,
		),
		'header_search_color'=> array(
			'type'    => 'color',
			'label'   => __( 'Search icon colour', 'appyn-pro' ),
			'default' => '#6b7280',
			'css_var' => '--apx-search-color',
		),
		'header_user_menu'   => array(
			'type'    => 'toggle',
			'label'   => __( 'Show user menu', 'appyn-pro' ),
			'default' => 0,
		),
		'header_dark_toggle' => array(
			'type'    => 'toggle',
			'label'   => __( 'Show dark mode switch', 'appyn-pro' ),
			'default' => 1,
		),
		'sticky_header'      => array(
			'type'    => 'toggle',
			'section' => __( 'Sticky behaviour', 'appyn-pro' ),
			'label'   => __( 'Sticky header', 'appyn-pro' ),
			'default' => 1,
		),
		'sticky_height'      => array(
			'type'    => 'slider',
			'label'   => __( 'Height when stuck', 'appyn-pro' ),
			'default' => 58,
			'min'     => 40,
			'max'     => 120,
			'unit'    => 'px',
			'css_var' => '--apx-sticky-height',
		),
		'sticky_bg'          => array(
			'type'    => 'colora',
			'label'   => __( 'Background when stuck', 'appyn-pro' ),
			'default' => array(
				'color'   => '#ffffff',
				'opacity' => 0.92,
			),
			'css_var' => '--apx-sticky-bg',
		),
		'sticky_blur'        => array(
			'type'    => 'slider',
			'label'   => __( 'Background blur', 'appyn-pro' ),
			'default' => 10,
			'min'     => 0,
			'max'     => 30,
			'unit'    => 'px',
			'css_var' => '--apx-sticky-blur',
		),
		'sticky_transition'  => array(
			'type'    => 'slider',
			'label'   => __( 'Sticky transition', 'appyn-pro' ),
			'default' => 0.25,
			'min'     => 0,
			'max'     => 1.5,
			'step'    => 0.05,
			'unit'    => 's',
			'css_var' => '--apx-sticky-speed',
		),
		'hamburger_color'    => array(
			'type'    => 'color',
			'section' => __( 'Mobile header', 'appyn-pro' ),
			'label'   => __( 'Hamburger colour', 'appyn-pro' ),
			'default' => '#1a1a2e',
			'css_var' => '--apx-burger-color',
		),
		'hamburger_anim'     => array(
			'type'    => 'select',
			'label'   => __( 'Hamburger animation', 'appyn-pro' ),
			'default' => 'morph',
			'choices' => array(
				'morph' => __( 'Morph to X', 'appyn-pro' ),
				'fade'  => __( 'Fade', 'appyn-pro' ),
				'none'  => __( 'None', 'appyn-pro' ),
			),
		),
		'mobile_menu_bg'     => array(
			'type'    => 'color',
			'label'   => __( 'Mobile menu background', 'appyn-pro' ),
			'default' => '#ffffff',
			'css_var' => '--apx-mobile-menu-bg',
		),
		'mobile_overlay'     => array(
			'type'    => 'colora',
			'label'   => __( 'Mobile overlay', 'appyn-pro' ),
			'default' => array(
				'color'   => '#0f172a',
				'opacity' => 0.5,
			),
			'css_var' => '--apx-mobile-overlay',
		),
		'mobile_menu_anim'   => array(
			'type'    => 'select',
			'label'   => __( 'Mobile menu animation', 'appyn-pro' ),
			'default' => 'slide-left',
			'choices' => array(
				'slide-left' => __( 'Slide from left', 'appyn-pro' ),
				'slide-down' => __( 'Slide down', 'appyn-pro' ),
				'fade'       => __( 'Fade', 'appyn-pro' ),
				'zoom'       => __( 'Zoom', 'appyn-pro' ),
			),
		),
		'mobile_tabbar'      => array(
			'type'    => 'toggle',
			'label'   => __( 'Bottom tab bar on phones', 'appyn-pro' ),
			'desc'    => __( 'Uses the same navigation items as the header.', 'appyn-pro' ),
			'default' => 0,
		),
	);
}

/* ---------------------------------------------------------------------------
 * Tab 4 - Hero slider
 * ------------------------------------------------------------------------ */

/**
 * Home page hero slider.
 *
 * @return array
 */
function apx_schema_hero() {
	return array(
		'hero_enable'       => array(
			'type'    => 'toggle',
			'section' => __( 'Slider', 'appyn-pro' ),
			'label'   => __( 'Enable hero slider', 'appyn-pro' ),
			'default' => 1,
		),
		'hero_full_width'   => array(
			'type'    => 'toggle',
			'label'   => __( 'Full width', 'appyn-pro' ),
			'default' => 0,
		),
		'hero_height'       => array(
			'type'    => 'slider',
			'label'   => __( 'Height (desktop)', 'appyn-pro' ),
			'default' => 420,
			'min'     => 200,
			'max'     => 700,
			'unit'    => 'px',
			'css_var' => '--apx-hero-height',
		),
		'hero_height_tablet'=> array(
			'type'    => 'slider',
			'label'   => __( 'Height (tablet)', 'appyn-pro' ),
			'default' => 340,
			'min'     => 160,
			'max'     => 600,
			'unit'    => 'px',
			'css_var' => '--apx-hero-height',
			'media'   => 'tablet',
		),
		'hero_height_mobile'=> array(
			'type'    => 'slider',
			'label'   => __( 'Height (mobile)', 'appyn-pro' ),
			'default' => 240,
			'min'     => 140,
			'max'     => 500,
			'unit'    => 'px',
			'css_var' => '--apx-hero-height',
			'media'   => 'mobile',
		),
		'hero_radius'       => array(
			'type'    => 'slider',
			'label'   => __( 'Corner radius', 'appyn-pro' ),
			'default' => 16,
			'min'     => 0,
			'max'     => 40,
			'unit'    => 'px',
			'css_var' => '--apx-hero-radius',
		),
		'hero_overlay'      => array(
			'type'    => 'colora',
			'label'   => __( 'Overlay colour', 'appyn-pro' ),
			'default' => array(
				'color'   => '#0b1020',
				'opacity' => 0.35,
			),
			'css_var' => '--apx-hero-overlay',
		),
		'hero_gradient'     => array(
			'type'    => 'gradient',
			'label'   => __( 'Overlay gradient', 'appyn-pro' ),
			'default' => array(
				'enable' => 1,
				'angle'  => 90,
				'from'   => '#0b1020cc',
				'to'     => '#0b102000',
			),
			'css_var' => '--apx-hero-gradient',
		),
		'hero_autoplay'     => array(
			'type'    => 'toggle',
			'section' => __( 'Motion', 'appyn-pro' ),
			'label'   => __( 'Autoplay', 'appyn-pro' ),
			'default' => 1,
		),
		'hero_autoplay_speed' => array(
			'type'    => 'slider',
			'label'   => __( 'Autoplay speed', 'appyn-pro' ),
			'default' => 6,
			'min'     => 3,
			'max'     => 10,
			'step'    => 0.5,
			'unit'    => 's',
		),
		'hero_pause_hover'  => array(
			'type'    => 'toggle',
			'label'   => __( 'Pause on hover', 'appyn-pro' ),
			'default' => 1,
		),
		'hero_loop'         => array(
			'type'    => 'toggle',
			'label'   => __( 'Loop slides', 'appyn-pro' ),
			'default' => 1,
		),
		'hero_effect'       => array(
			'type'    => 'select',
			'label'   => __( 'Transition effect', 'appyn-pro' ),
			'default' => 'slide',
			'choices' => array(
				'slide' => __( 'Slide', 'appyn-pro' ),
				'fade'  => __( 'Fade', 'appyn-pro' ),
				'zoom'  => __( 'Zoom', 'appyn-pro' ),
				'flip'  => __( '3D flip', 'appyn-pro' ),
			),
		),
		'hero_effect_speed' => array(
			'type'    => 'slider',
			'label'   => __( 'Transition speed', 'appyn-pro' ),
			'default' => 0.6,
			'min'     => 0.3,
			'max'     => 1.5,
			'step'    => 0.05,
			'unit'    => 's',
			'css_var' => '--apx-hero-speed',
		),
		'hero_arrows'       => array(
			'type'    => 'toggle',
			'section' => __( 'Arrows & dots', 'appyn-pro' ),
			'label'   => __( 'Show arrows', 'appyn-pro' ),
			'default' => 1,
		),
		'hero_arrow_style'  => array(
			'type'    => 'select',
			'label'   => __( 'Arrow style', 'appyn-pro' ),
			'default' => 'circle',
			'choices' => array(
				'circle'  => __( 'Circle', 'appyn-pro' ),
				'square'  => __( 'Square', 'appyn-pro' ),
				'minimal' => __( 'Minimal', 'appyn-pro' ),
			),
		),
		'hero_arrow_size'   => array(
			'type'    => 'slider',
			'label'   => __( 'Arrow size', 'appyn-pro' ),
			'default' => 44,
			'min'     => 26,
			'max'     => 80,
			'unit'    => 'px',
			'css_var' => '--apx-hero-arrow-size',
		),
		'hero_arrow_bg'     => array(
			'type'    => 'colora',
			'label'   => __( 'Arrow background', 'appyn-pro' ),
			'default' => array(
				'color'   => '#000000',
				'opacity' => 0.35,
			),
			'css_var' => '--apx-hero-arrow-bg',
		),
		'hero_arrow_bg_hover' => array(
			'type'    => 'colora',
			'label'   => __( 'Arrow background (hover)', 'appyn-pro' ),
			'default' => array(
				'color'   => '#000000',
				'opacity' => 0.6,
			),
			'css_var' => '--apx-hero-arrow-bg-hover',
		),
		'hero_arrow_color'  => array(
			'type'    => 'color',
			'label'   => __( 'Arrow icon colour', 'appyn-pro' ),
			'default' => '#ffffff',
			'css_var' => '--apx-hero-arrow-color',
		),
		'hero_dots'         => array(
			'type'    => 'toggle',
			'label'   => __( 'Show dots', 'appyn-pro' ),
			'default' => 1,
		),
		'hero_dot_style'    => array(
			'type'    => 'select',
			'label'   => __( 'Dot style', 'appyn-pro' ),
			'default' => 'pill',
			'choices' => array(
				'pill'   => __( 'Pill (active expands)', 'appyn-pro' ),
				'dots'   => __( 'Dots', 'appyn-pro' ),
				'lines'  => __( 'Lines', 'appyn-pro' ),
				'numbers'=> __( 'Numbers', 'appyn-pro' ),
			),
		),
		'hero_dot_size'     => array(
			'type'    => 'slider',
			'label'   => __( 'Dot size', 'appyn-pro' ),
			'default' => 8,
			'min'     => 4,
			'max'     => 20,
			'unit'    => 'px',
			'css_var' => '--apx-hero-dot-size',
		),
		'hero_dot_active_width' => array(
			'type'    => 'slider',
			'label'   => __( 'Active dot width', 'appyn-pro' ),
			'default' => 26,
			'min'     => 6,
			'max'     => 60,
			'unit'    => 'px',
			'css_var' => '--apx-hero-dot-active',
		),
		'hero_dot_color'    => array(
			'type'    => 'colora',
			'label'   => __( 'Dot colour', 'appyn-pro' ),
			'default' => array(
				'color'   => '#ffffff',
				'opacity' => 0.5,
			),
			'css_var' => '--apx-hero-dot-bg',
		),
		'hero_dot_active_color' => array(
			'type'    => 'color',
			'label'   => __( 'Active dot colour', 'appyn-pro' ),
			'default' => '#ffffff',
			'css_var' => '--apx-hero-dot-active-bg',
		),
		'hero_slides'       => array(
			'type'    => 'repeater',
			'section' => __( 'Slides', 'appyn-pro' ),
			'label'   => __( 'Slides', 'appyn-pro' ),
			'default' => array(),
			'fields'  => array(
				'image'      => array(
					'type'    => 'image',
					'label'   => __( 'Background image', 'appyn-pro' ),
					'default' => '',
				),
				'bg_color'   => array(
					'type'    => 'color',
					'label'   => __( 'Background colour (if no image)', 'appyn-pro' ),
					'default' => '#12203a',
				),
				'title'      => array(
					'type'    => 'text',
					'label'   => __( 'Title', 'appyn-pro' ),
					'default' => '',
				),
				'text'       => array(
					'type'    => 'textarea',
					'label'   => __( 'Description', 'appyn-pro' ),
					'default' => '',
				),
				'title_color'=> array(
					'type'    => 'color',
					'label'   => __( 'Title colour', 'appyn-pro' ),
					'default' => '#ffffff',
				),
				'title_size' => array(
					'type'    => 'slider',
					'label'   => __( 'Title size', 'appyn-pro' ),
					'default' => 38,
					'min'     => 16,
					'max'     => 80,
					'unit'    => 'px',
				),
				'text_color' => array(
					'type'    => 'color',
					'label'   => __( 'Text colour', 'appyn-pro' ),
					'default' => '#e5e7eb',
				),
				'btn_text'   => array(
					'type'    => 'text',
					'label'   => __( 'Button text', 'appyn-pro' ),
					'default' => '',
				),
				'btn_link'   => array(
					'type'    => 'text',
					'label'   => __( 'Button link', 'appyn-pro' ),
					'default' => '',
				),
				'btn_bg'     => array(
					'type'    => 'color',
					'label'   => __( 'Button background', 'appyn-pro' ),
					'default' => '#4CAF50',
				),
				'btn_color'  => array(
					'type'    => 'color',
					'label'   => __( 'Button text colour', 'appyn-pro' ),
					'default' => '#ffffff',
				),
				'btn_hover_bg' => array(
					'type'    => 'color',
					'label'   => __( 'Button hover background', 'appyn-pro' ),
					'default' => '#3d8b40',
				),
				'position'   => array(
					'type'    => 'select',
					'label'   => __( 'Content position', 'appyn-pro' ),
					'default' => 'left',
					'choices' => array(
						'left'   => __( 'Left', 'appyn-pro' ),
						'center' => __( 'Center', 'appyn-pro' ),
						'right'  => __( 'Right', 'appyn-pro' ),
						'bottom' => __( 'Bottom', 'appyn-pro' ),
					),
				),
				'animation'  => array(
					'type'    => 'select',
					'label'   => __( 'Text animation', 'appyn-pro' ),
					'default' => 'fade-up',
					'choices' => array(
						'fade-up'    => __( 'Fade up', 'appyn-pro' ),
						'fade'       => __( 'Fade in', 'appyn-pro' ),
						'slide-left' => __( 'Slide from left', 'appyn-pro' ),
						'zoom'       => __( 'Zoom in', 'appyn-pro' ),
						'none'       => __( 'None', 'appyn-pro' ),
					),
				),
				'css'        => array(
					'type'    => 'textarea',
					'label'   => __( 'Custom CSS for this slide', 'appyn-pro' ),
					'default' => '',
				),
			),
		),
	);
}

/* ---------------------------------------------------------------------------
 * Tab 5 - Category navigation
 * ------------------------------------------------------------------------ */

/**
 * Category strip shown under the hero.
 *
 * @return array
 */
function apx_schema_categories() {
	return array(
		'cat_enable'      => array(
			'type'    => 'toggle',
			'section' => __( 'Section', 'appyn-pro' ),
			'label'   => __( 'Show category bar', 'appyn-pro' ),
			'default' => 1,
		),
		'cat_title'       => array(
			'type'    => 'text',
			'label'   => __( 'Section title', 'appyn-pro' ),
			'default' => 'Browse categories',
		),
		'cat_title_icon'  => array(
			'type'    => 'icon',
			'label'   => __( 'Section icon', 'appyn-pro' ),
			'default' => 'fas fa-th-large',
		),
		'cat_title_color' => array(
			'type'    => 'color',
			'label'   => __( 'Title colour', 'appyn-pro' ),
			'default' => '#1a1a2e',
			'css_var' => '--apx-cat-title-color',
		),
		'cat_title_size'  => array(
			'type'    => 'slider',
			'label'   => __( 'Title size', 'appyn-pro' ),
			'default' => 20,
			'min'     => 12,
			'max'     => 40,
			'unit'    => 'px',
			'css_var' => '--apx-cat-title-size',
		),
		'cat_section_bg'  => array(
			'type'    => 'color',
			'label'   => __( 'Section background', 'appyn-pro' ),
			'default' => '#ffffff',
			'css_var' => '--apx-cat-section-bg',
		),
		'cat_layout'      => array(
			'type'    => 'select',
			'section' => __( 'Layout', 'appyn-pro' ),
			'label'   => __( 'Layout', 'appyn-pro' ),
			'default' => 'scroll',
			'choices' => array(
				'scroll' => __( 'Horizontal scroll', 'appyn-pro' ),
				'grid'   => __( 'Grid', 'appyn-pro' ),
				'pills'  => __( 'Pills', 'appyn-pro' ),
				'list'   => __( 'List', 'appyn-pro' ),
			),
		),
		'cat_card_style'  => array(
			'type'    => 'select',
			'label'   => __( 'Card style', 'appyn-pro' ),
			'default' => 'elevated',
			'choices' => array(
				'flat'     => __( 'Flat', 'appyn-pro' ),
				'elevated' => __( 'Elevated', 'appyn-pro' ),
				'outlined' => __( 'Outlined', 'appyn-pro' ),
				'glass'    => __( 'Glass', 'appyn-pro' ),
			),
		),
		'cat_per_row'     => array(
			'type'    => 'slider',
			'label'   => __( 'Items per row (grid)', 'appyn-pro' ),
			'default' => 6,
			'min'     => 2,
			'max'     => 8,
			'css_var' => '--apx-cat-cols',
		),
		'cat_gap'         => array(
			'type'    => 'slider',
			'label'   => __( 'Gap', 'appyn-pro' ),
			'default' => 14,
			'min'     => 4,
			'max'     => 40,
			'unit'    => 'px',
			'css_var' => '--apx-cat-gap',
		),
		'cat_icon_size'   => array(
			'type'    => 'slider',
			'section' => __( 'Item design', 'appyn-pro' ),
			'label'   => __( 'Icon size', 'appyn-pro' ),
			'default' => 46,
			'min'     => 24,
			'max'     => 96,
			'unit'    => 'px',
			'css_var' => '--apx-cat-icon-size',
		),
		'cat_icon_style'  => array(
			'type'    => 'select',
			'label'   => __( 'Icon shape', 'appyn-pro' ),
			'default' => 'circle',
			'choices' => array(
				'circle'  => __( 'Circle', 'appyn-pro' ),
				'rounded' => __( 'Rounded square', 'appyn-pro' ),
				'square'  => __( 'Square', 'appyn-pro' ),
				'none'    => __( 'No background', 'appyn-pro' ),
			),
		),
		'cat_icon_color'  => array(
			'type'    => 'color',
			'label'   => __( 'Icon colour', 'appyn-pro' ),
			'default' => '#ffffff',
			'css_var' => '--apx-cat-icon-color',
		),
		'cat_label_color' => array(
			'type'    => 'color',
			'label'   => __( 'Label colour', 'appyn-pro' ),
			'default' => '#374151',
			'css_var' => '--apx-cat-label-color',
		),
		'cat_label_size'  => array(
			'type'    => 'slider',
			'label'   => __( 'Label size', 'appyn-pro' ),
			'default' => 13,
			'min'     => 10,
			'max'     => 20,
			'unit'    => 'px',
			'css_var' => '--apx-cat-label-size',
		),
		'cat_count_color' => array(
			'type'    => 'color',
			'label'   => __( 'Count colour', 'appyn-pro' ),
			'default' => '#9ca3af',
			'css_var' => '--apx-cat-count-color',
		),
		'cat_show_count'  => array(
			'type'    => 'toggle',
			'label'   => __( 'Show app count', 'appyn-pro' ),
			'default' => 1,
		),
		'cat_hover_bg'    => array(
			'type'    => 'color',
			'label'   => __( 'Hover background', 'appyn-pro' ),
			'default' => '#f3f4f6',
			'css_var' => '--apx-cat-hover-bg',
		),
		'cat_hover_transform' => array(
			'type'    => 'select',
			'label'   => __( 'Hover movement', 'appyn-pro' ),
			'default' => 'lift',
			'choices' => array(
				'lift'  => __( 'Lift', 'appyn-pro' ),
				'scale' => __( 'Scale', 'appyn-pro' ),
				'none'  => __( 'None', 'appyn-pro' ),
			),
		),
		'cat_arrows'      => array(
			'type'    => 'toggle',
			'label'   => __( 'Scroll arrows (horizontal layout)', 'appyn-pro' ),
			'default' => 1,
		),
		'cat_items'       => array(
			'type'    => 'repeater',
			'section' => __( 'Categories', 'appyn-pro' ),
			'label'   => __( 'Category items', 'appyn-pro' ),
			'desc'    => __( 'Drag to reorder. Leave empty to use the parent theme category bar.', 'appyn-pro' ),
			'default' => array(),
			'fields'  => array(
				'name'     => array(
					'type'    => 'text',
					'label'   => __( 'Name', 'appyn-pro' ),
					'default' => '',
				),
				'icon'     => array(
					'type'    => 'icon',
					'label'   => __( 'Icon', 'appyn-pro' ),
					'default' => 'fas fa-folder',
				),
				'image'    => array(
					'type'    => 'image',
					'label'   => __( 'Icon image (optional)', 'appyn-pro' ),
					'default' => '',
				),
				'color'    => array(
					'type'    => 'color',
					'label'   => __( 'Icon background', 'appyn-pro' ),
					'default' => '#4CAF50',
				),
				'url'      => array(
					'type'    => 'text',
					'label'   => __( 'Link', 'appyn-pro' ),
					'default' => '',
				),
				'count'    => array(
					'type'    => 'text',
					'label'   => __( 'Count text', 'appyn-pro' ),
					'default' => '',
				),
				'visible'  => array(
					'type'    => 'toggle',
					'label'   => __( 'Show', 'appyn-pro' ),
					'default' => 1,
				),
				'css_class'=> array(
					'type'    => 'text',
					'label'   => __( 'Custom CSS class', 'appyn-pro' ),
					'default' => '',
				),
			),
		),
		'cat_custom_css'  => array(
			'type'    => 'code',
			'label'   => __( 'Custom CSS for this section', 'appyn-pro' ),
			'default' => '',
		),
	);
}

/* ---------------------------------------------------------------------------
 * Tab 6 - App cards
 * ------------------------------------------------------------------------ */

/**
 * App card design and grid.
 *
 * @return array
 */
function apx_schema_cards() {
	return array(
		'card_bg'          => array(
			'type'    => 'color',
			'section' => __( 'Card container', 'appyn-pro' ),
			'label'   => __( 'Card background', 'appyn-pro' ),
			'default' => '#ffffff',
			'css_var' => '--apx-card-bg',
		),
		'card_border'      => array(
			'type'    => 'border',
			'label'   => __( 'Card border', 'appyn-pro' ),
			'default' => array(
				'width' => 1,
				'style' => 'solid',
				'color' => '#eceef2',
			),
			'css_var' => '--apx-card-border',
		),
		'card_radius'      => array(
			'type'    => 'slider',
			'label'   => __( 'Card radius', 'appyn-pro' ),
			'default' => 14,
			'min'     => 0,
			'max'     => 40,
			'unit'    => 'px',
			'css_var' => '--apx-card-radius',
		),
		'card_padding'     => array(
			'type'    => 'spacing',
			'label'   => __( 'Card padding', 'appyn-pro' ),
			'default' => array(
				'top'    => 14,
				'right'  => 10,
				'bottom' => 14,
				'left'   => 10,
			),
			'css_var' => '--apx-card-padding',
		),
		'card_shadow'      => array(
			'type'    => 'shadow',
			'label'   => __( 'Card shadow', 'appyn-pro' ),
			'default' => array(
				'enable' => 1,
				'x'      => 0,
				'y'      => 2,
				'blur'   => 10,
				'spread' => 0,
				'color'  => '#0f172a10',
			),
			'css_var' => '--apx-card-shadow',
		),
		'icon_size'        => array(
			'type'    => 'slider',
			'section' => __( 'App icon', 'appyn-pro' ),
			'label'   => __( 'Icon size', 'appyn-pro' ),
			'default' => 84,
			'min'     => 48,
			'max'     => 120,
			'unit'    => 'px',
			'css_var' => '--apx-card-icon',
		),
		'icon_size_mobile' => array(
			'type'    => 'slider',
			'label'   => __( 'Icon size (mobile)', 'appyn-pro' ),
			'default' => 64,
			'min'     => 36,
			'max'     => 110,
			'unit'    => 'px',
			'css_var' => '--apx-card-icon',
			'media'   => 'mobile',
		),
		'icon_radius'      => array(
			'type'    => 'slider',
			'label'   => __( 'Icon radius', 'appyn-pro' ),
			'default' => 20,
			'min'     => 0,
			'max'     => 50,
			'unit'    => '%',
			'css_var' => '--apx-card-icon-radius',
		),
		'icon_shadow'      => array(
			'type'    => 'shadow',
			'label'   => __( 'Icon shadow', 'appyn-pro' ),
			'default' => array(
				'enable' => 1,
				'x'      => 0,
				'y'      => 4,
				'blur'   => 12,
				'spread' => 0,
				'color'  => '#0f172a1f',
			),
			'css_var' => '--apx-card-icon-shadow',
		),
		'icon_hover_scale' => array(
			'type'    => 'slider',
			'label'   => __( 'Icon hover scale', 'appyn-pro' ),
			'default' => 1.06,
			'min'     => 1,
			'max'     => 1.3,
			'step'    => 0.01,
			'css_var' => '--apx-card-icon-scale',
		),
		'card_title_size'  => array(
			'type'    => 'slider',
			'section' => __( 'Title & meta', 'appyn-pro' ),
			'label'   => __( 'Title size', 'appyn-pro' ),
			'default' => 14,
			'min'     => 10,
			'max'     => 24,
			'unit'    => 'px',
			'css_var' => '--apx-card-title-size',
		),
		'card_title_weight'=> array(
			'type'    => 'select',
			'label'   => __( 'Title weight', 'appyn-pro' ),
			'default' => '600',
			'choices' => array(
				'400' => '400',
				'500' => '500',
				'600' => '600',
				'700' => '700',
				'800' => '800',
			),
			'css_var' => '--apx-card-title-weight',
		),
		'card_title_color' => array(
			'type'    => 'color',
			'label'   => __( 'Title colour', 'appyn-pro' ),
			'default' => '#1a1a2e',
			'css_var' => '--apx-card-title-color',
		),
		'card_title_hover' => array(
			'type'    => 'color',
			'label'   => __( 'Title hover colour', 'appyn-pro' ),
			'default' => '#4CAF50',
			'css_var' => '--apx-card-title-hover',
		),
		'card_title_lines' => array(
			'type'    => 'select',
			'label'   => __( 'Title lines', 'appyn-pro' ),
			'default' => '2',
			'choices' => array(
				'1' => '1',
				'2' => '2',
				'3' => '3',
				'0' => __( 'Unlimited', 'appyn-pro' ),
			),
			'css_var' => '--apx-card-title-lines',
		),
		'card_show_dev'    => array(
			'type'    => 'toggle',
			'label'   => __( 'Show developer name', 'appyn-pro' ),
			'default' => 1,
		),
		'card_dev_color'   => array(
			'type'    => 'color',
			'label'   => __( 'Developer colour', 'appyn-pro' ),
			'default' => '#8b95a5',
			'css_var' => '--apx-card-dev-color',
		),
		'card_dev_size'    => array(
			'type'    => 'slider',
			'label'   => __( 'Developer size', 'appyn-pro' ),
			'default' => 12,
			'min'     => 9,
			'max'     => 18,
			'unit'    => 'px',
			'css_var' => '--apx-card-dev-size',
		),
		'card_show_tags'   => array(
			'type'    => 'toggle',
			'label'   => __( 'Show version / size tags', 'appyn-pro' ),
			'default' => 1,
		),
		'tag_bg'           => array(
			'type'    => 'color',
			'label'   => __( 'Tag background', 'appyn-pro' ),
			'default' => '#f1f5f9',
			'css_var' => '--apx-tag-bg',
		),
		'tag_color'        => array(
			'type'    => 'color',
			'label'   => __( 'Tag text colour', 'appyn-pro' ),
			'default' => '#64748b',
			'css_var' => '--apx-tag-color',
		),
		'tag_radius'       => array(
			'type'    => 'slider',
			'label'   => __( 'Tag radius', 'appyn-pro' ),
			'default' => 20,
			'min'     => 0,
			'max'     => 30,
			'unit'    => 'px',
			'css_var' => '--apx-tag-radius',
		),
		'tag_font_size'    => array(
			'type'    => 'slider',
			'label'   => __( 'Tag font size', 'appyn-pro' ),
			'default' => 11,
			'min'     => 8,
			'max'     => 16,
			'unit'    => 'px',
			'css_var' => '--apx-tag-size',
		),
		'star_show'        => array(
			'type'    => 'toggle',
			'section' => __( 'Rating', 'appyn-pro' ),
			'label'   => __( 'Show rating stars', 'appyn-pro' ),
			'default' => 1,
		),
		'star_filled'      => array(
			'type'    => 'color',
			'label'   => __( 'Filled star colour', 'appyn-pro' ),
			'default' => '#FFC107',
			'css_var' => '--apx-star',
		),
		'star_empty'       => array(
			'type'    => 'color',
			'label'   => __( 'Empty star colour', 'appyn-pro' ),
			'default' => '#e2e5ea',
			'css_var' => '--apx-star-empty',
		),
		'star_size'        => array(
			'type'    => 'slider',
			'label'   => __( 'Star size', 'appyn-pro' ),
			'default' => 14,
			'min'     => 10,
			'max'     => 24,
			'unit'    => 'px',
			'css_var' => '--apx-star-size',
		),
		'rating_show_number' => array(
			'type'    => 'toggle',
			'label'   => __( 'Show numeric rating', 'appyn-pro' ),
			'default' => 1,
		),
		'rating_color'     => array(
			'type'    => 'color',
			'label'   => __( 'Numeric rating colour', 'appyn-pro' ),
			'default' => '#6b7280',
			'css_var' => '--apx-rating-color',
		),
		'badge_style'      => array(
			'type'    => 'select',
			'section' => __( 'Badges', 'appyn-pro' ),
			'label'   => __( 'Badge style', 'appyn-pro' ),
			'default' => 'pill',
			'choices' => array(
				'pill'   => __( 'Pill', 'appyn-pro' ),
				'square' => __( 'Square', 'appyn-pro' ),
				'ribbon' => __( 'Ribbon', 'appyn-pro' ),
				'dot'    => __( 'Dot', 'appyn-pro' ),
			),
		),
		'badge_position'   => array(
			'type'    => 'select',
			'label'   => __( 'Badge position', 'appyn-pro' ),
			'default' => 'top-left',
			'choices' => array(
				'top-left'     => __( 'Top left', 'appyn-pro' ),
				'top-right'    => __( 'Top right', 'appyn-pro' ),
				'bottom-left'  => __( 'Bottom left', 'appyn-pro' ),
				'bottom-right' => __( 'Bottom right', 'appyn-pro' ),
			),
		),
		'badge_font_size'  => array(
			'type'    => 'slider',
			'label'   => __( 'Badge font size', 'appyn-pro' ),
			'default' => 10,
			'min'     => 8,
			'max'     => 16,
			'unit'    => 'px',
			'css_var' => '--apx-badge-size',
		),
		'badge_radius'     => array(
			'type'    => 'slider',
			'label'   => __( 'Badge radius', 'appyn-pro' ),
			'default' => 20,
			'min'     => 0,
			'max'     => 30,
			'unit'    => 'px',
			'css_var' => '--apx-badge-radius',
		),
		'badge_mod_bg'     => array(
			'type'    => 'color',
			'label'   => __( 'MOD badge background', 'appyn-pro' ),
			'default' => '#FF5722',
			'css_var' => '--apx-badge-mod-bg',
		),
		'badge_mod_color'  => array(
			'type'    => 'color',
			'label'   => __( 'MOD badge text', 'appyn-pro' ),
			'default' => '#ffffff',
			'css_var' => '--apx-badge-mod-color',
		),
		'badge_premium_bg' => array(
			'type'    => 'color',
			'label'   => __( 'Premium badge background', 'appyn-pro' ),
			'default' => '#8BC34A',
			'css_var' => '--apx-badge-premium-bg',
		),
		'badge_premium_color' => array(
			'type'    => 'color',
			'label'   => __( 'Premium badge text', 'appyn-pro' ),
			'default' => '#ffffff',
			'css_var' => '--apx-badge-premium-color',
		),
		'badge_choice_bg'  => array(
			'type'    => 'color',
			'label'   => __( "Editor's choice background", 'appyn-pro' ),
			'default' => '#FF9800',
			'css_var' => '--apx-badge-choice-bg',
		),
		'badge_choice_color' => array(
			'type'    => 'color',
			'label'   => __( "Editor's choice text", 'appyn-pro' ),
			'default' => '#ffffff',
			'css_var' => '--apx-badge-choice-color',
		),
		'badge_new_bg'     => array(
			'type'    => 'color',
			'label'   => __( 'New badge background', 'appyn-pro' ),
			'default' => '#2196F3',
			'css_var' => '--apx-badge-new-bg',
		),
		'badge_new_color'  => array(
			'type'    => 'color',
			'label'   => __( 'New badge text', 'appyn-pro' ),
			'default' => '#ffffff',
			'css_var' => '--apx-badge-new-color',
		),
		'badge_animation'  => array(
			'type'    => 'select',
			'label'   => __( 'Badge animation', 'appyn-pro' ),
			'default' => 'none',
			'choices' => array(
				'none'  => __( 'None', 'appyn-pro' ),
				'pulse' => __( 'Pulse', 'appyn-pro' ),
				'glow'  => __( 'Glow', 'appyn-pro' ),
				'bounce'=> __( 'Bounce', 'appyn-pro' ),
			),
		),
		'card_hover_lift'  => array(
			'type'    => 'slider',
			'section' => __( 'Hover & click', 'appyn-pro' ),
			'label'   => __( 'Hover lift', 'appyn-pro' ),
			'default' => 8,
			'min'     => 0,
			'max'     => 20,
			'unit'    => 'px',
			'css_var' => '--apx-card-lift',
		),
		'card_hover_scale' => array(
			'type'    => 'slider',
			'label'   => __( 'Hover scale', 'appyn-pro' ),
			'default' => 1.03,
			'min'     => 1,
			'max'     => 1.2,
			'step'    => 0.01,
			'css_var' => '--apx-card-scale',
		),
		'card_hover_shadow'=> array(
			'type'    => 'shadow',
			'label'   => __( 'Hover shadow', 'appyn-pro' ),
			'default' => array(
				'enable' => 1,
				'x'      => 0,
				'y'      => 14,
				'blur'   => 30,
				'spread' => -6,
				'color'  => '#0f172a2e',
			),
			'css_var' => '--apx-card-hover-shadow',
		),
		'card_hover_border'=> array(
			'type'    => 'color',
			'label'   => __( 'Hover border colour', 'appyn-pro' ),
			'default' => '#d7f0d9',
			'css_var' => '--apx-card-hover-border',
		),
		'card_hover_bg'    => array(
			'type'    => 'color',
			'label'   => __( 'Hover background', 'appyn-pro' ),
			'default' => '#ffffff',
			'css_var' => '--apx-card-hover-bg',
		),
		'card_hover_speed' => array(
			'type'    => 'slider',
			'label'   => __( 'Hover speed', 'appyn-pro' ),
			'default' => 0.28,
			'min'     => 0.05,
			'max'     => 1,
			'step'    => 0.01,
			'unit'    => 's',
			'css_var' => '--apx-card-speed',
		),
		'card_hover_easing'=> array(
			'type'    => 'select',
			'label'   => __( 'Hover easing', 'appyn-pro' ),
			'default' => 'cubic-bezier(.22,1,.36,1)',
			'choices' => 'easings',
			'css_var' => '--apx-card-easing',
		),
		'card_glow'        => array(
			'type'    => 'toggle',
			'label'   => __( 'Hover glow', 'appyn-pro' ),
			'default' => 0,
		),
		'card_glow_color'  => array(
			'type'    => 'colora',
			'label'   => __( 'Glow colour', 'appyn-pro' ),
			'default' => array(
				'color'   => '#4CAF50',
				'opacity' => 0.45,
			),
			'css_var' => '--apx-card-glow',
		),
		'card_glow_blur'   => array(
			'type'    => 'slider',
			'label'   => __( 'Glow blur', 'appyn-pro' ),
			'default' => 24,
			'min'     => 4,
			'max'     => 60,
			'unit'    => 'px',
			'css_var' => '--apx-card-glow-blur',
		),
		'card_click_scale' => array(
			'type'    => 'slider',
			'label'   => __( 'Click scale', 'appyn-pro' ),
			'default' => 0.98,
			'min'     => 0.9,
			'max'     => 1,
			'step'    => 0.01,
			'css_var' => '--apx-card-click-scale',
		),
		'grid_cols_desktop'=> array(
			'type'    => 'slider',
			'section' => __( 'Grid', 'appyn-pro' ),
			'label'   => __( 'Columns (desktop)', 'appyn-pro' ),
			'default' => 6,
			'min'     => 2,
			'max'     => 8,
			'css_var' => '--apx-grid-cols',
		),
		'grid_cols_tablet' => array(
			'type'    => 'slider',
			'label'   => __( 'Columns (tablet)', 'appyn-pro' ),
			'default' => 4,
			'min'     => 2,
			'max'     => 6,
			'css_var' => '--apx-grid-cols',
			'media'   => 'tablet',
		),
		'grid_cols_mobile' => array(
			'type'    => 'slider',
			'label'   => __( 'Columns (mobile)', 'appyn-pro' ),
			'default' => 2,
			'min'     => 1,
			'max'     => 4,
			'css_var' => '--apx-grid-cols',
			'media'   => 'mobile',
		),
		'grid_gap'         => array(
			'type'    => 'slider',
			'label'   => __( 'Column gap', 'appyn-pro' ),
			'default' => 16,
			'min'     => 8,
			'max'     => 48,
			'unit'    => 'px',
			'css_var' => '--apx-grid-gap',
		),
		'grid_row_gap'     => array(
			'type'    => 'slider',
			'label'   => __( 'Row gap', 'appyn-pro' ),
			'default' => 16,
			'min'     => 8,
			'max'     => 48,
			'unit'    => 'px',
			'css_var' => '--apx-grid-row-gap',
		),
		'section_title_size' => array(
			'type'    => 'slider',
			'section' => __( 'Section header', 'appyn-pro' ),
			'label'   => __( 'Section title size', 'appyn-pro' ),
			'default' => 19,
			'min'     => 12,
			'max'     => 40,
			'unit'    => 'px',
			'css_var' => '--apx-section-title-size',
		),
		'section_title_color' => array(
			'type'    => 'color',
			'label'   => __( 'Section title colour', 'appyn-pro' ),
			'default' => '#1a1a2e',
			'css_var' => '--apx-section-title-color',
		),
		'section_title_weight' => array(
			'type'    => 'select',
			'label'   => __( 'Section title weight', 'appyn-pro' ),
			'default' => '700',
			'choices' => array(
				'500' => '500',
				'600' => '600',
				'700' => '700',
				'800' => '800',
			),
			'css_var' => '--apx-section-title-weight',
		),
		'section_accent'   => array(
			'type'    => 'color',
			'label'   => __( 'Section title accent bar', 'appyn-pro' ),
			'default' => '#4CAF50',
			'css_var' => '--apx-section-accent',
		),
		'more_link_text'   => array(
			'type'    => 'text',
			'label'   => __( '"More" link text', 'appyn-pro' ),
			'default' => 'More',
		),
		'more_link_color'  => array(
			'type'    => 'color',
			'label'   => __( '"More" link colour', 'appyn-pro' ),
			'default' => '#4CAF50',
			'css_var' => '--apx-more-color',
		),
		'more_link_hover'  => array(
			'type'    => 'color',
			'label'   => __( '"More" link hover', 'appyn-pro' ),
			'default' => '#2e7d32',
			'css_var' => '--apx-more-hover',
		),
		'card_scroll_anim' => array(
			'type'    => 'select',
			'section' => __( 'Entrance animation', 'appyn-pro' ),
			'label'   => __( 'Card entrance', 'appyn-pro' ),
			'default' => 'fade-up',
			'choices' => array(
				'fade-up' => __( 'Fade up', 'appyn-pro' ),
				'fade'    => __( 'Fade in', 'appyn-pro' ),
				'zoom'    => __( 'Zoom in', 'appyn-pro' ),
				'slide-left' => __( 'Slide left', 'appyn-pro' ),
				'flip'    => __( 'Flip', 'appyn-pro' ),
				'none'    => __( 'None', 'appyn-pro' ),
			),
		),
		'card_stagger'     => array(
			'type'    => 'slider',
			'label'   => __( 'Stagger between cards', 'appyn-pro' ),
			'default' => 0.05,
			'min'     => 0,
			'max'     => 0.3,
			'step'    => 0.01,
			'unit'    => 's',
			'css_var' => '--apx-stagger',
		),
	);
}

/* ---------------------------------------------------------------------------
 * Tab 7 - News / blog cards
 * ------------------------------------------------------------------------ */

/**
 * Blog card design.
 *
 * @return array
 */
function apx_schema_news() {
	return array(
		'news_title'       => array(
			'type'    => 'text',
			'section' => __( 'Section header', 'appyn-pro' ),
			'label'   => __( 'Section title', 'appyn-pro' ),
			'default' => 'Latest news',
		),
		'news_title_icon'  => array(
			'type'    => 'icon',
			'label'   => __( 'Section icon', 'appyn-pro' ),
			'default' => 'fas fa-newspaper',
		),
		'news_more_text'   => array(
			'type'    => 'text',
			'label'   => __( '"More" link text', 'appyn-pro' ),
			'default' => 'View all',
		),
		'news_layout'      => array(
			'type'    => 'select',
			'section' => __( 'Layout', 'appyn-pro' ),
			'label'   => __( 'Layout', 'appyn-pro' ),
			'default' => 'grid',
			'choices' => array(
				'grid'       => __( 'Grid', 'appyn-pro' ),
				'list'       => __( 'List', 'appyn-pro' ),
				'carousel'   => __( 'Carousel', 'appyn-pro' ),
				'masonry'    => __( 'Masonry', 'appyn-pro' ),
			),
		),
		'news_columns'     => array(
			'type'    => 'slider',
			'label'   => __( 'Columns', 'appyn-pro' ),
			'default' => 4,
			'min'     => 1,
			'max'     => 4,
			'css_var' => '--apx-news-cols',
		),
		'news_columns_mobile' => array(
			'type'    => 'slider',
			'label'   => __( 'Columns (mobile)', 'appyn-pro' ),
			'default' => 1,
			'min'     => 1,
			'max'     => 2,
			'css_var' => '--apx-news-cols',
			'media'   => 'mobile',
		),
		'news_card_style'  => array(
			'type'    => 'select',
			'label'   => __( 'Card style', 'appyn-pro' ),
			'default' => 'card',
			'choices' => array(
				'card'      => __( 'Card', 'appyn-pro' ),
				'overlay'   => __( 'Image overlay', 'appyn-pro' ),
				'horizontal'=> __( 'Horizontal', 'appyn-pro' ),
				'minimal'   => __( 'Minimal', 'appyn-pro' ),
			),
		),
		'news_card_bg'     => array(
			'type'    => 'color',
			'label'   => __( 'Card background', 'appyn-pro' ),
			'default' => '#ffffff',
			'css_var' => '--apx-news-bg',
		),
		'news_card_radius' => array(
			'type'    => 'slider',
			'label'   => __( 'Card radius', 'appyn-pro' ),
			'default' => 14,
			'min'     => 0,
			'max'     => 40,
			'unit'    => 'px',
			'css_var' => '--apx-news-radius',
		),
		'news_card_border' => array(
			'type'    => 'border',
			'label'   => __( 'Card border', 'appyn-pro' ),
			'default' => array(
				'width' => 1,
				'style' => 'solid',
				'color' => '#eceef2',
			),
			'css_var' => '--apx-news-border',
		),
		'news_card_shadow' => array(
			'type'    => 'shadow',
			'label'   => __( 'Card shadow', 'appyn-pro' ),
			'default' => array(
				'enable' => 1,
				'x'      => 0,
				'y'      => 2,
				'blur'   => 12,
				'spread' => 0,
				'color'  => '#0f172a12',
			),
			'css_var' => '--apx-news-shadow',
		),
		'news_image_height'=> array(
			'type'    => 'slider',
			'section' => __( 'Featured image', 'appyn-pro' ),
			'label'   => __( 'Image height', 'appyn-pro' ),
			'default' => 180,
			'min'     => 120,
			'max'     => 400,
			'unit'    => 'px',
			'css_var' => '--apx-news-img-height',
		),
		'news_image_radius'=> array(
			'type'    => 'slider',
			'label'   => __( 'Image radius', 'appyn-pro' ),
			'default' => 10,
			'min'     => 0,
			'max'     => 24,
			'unit'    => 'px',
			'css_var' => '--apx-news-img-radius',
		),
		'news_image_fit'   => array(
			'type'    => 'select',
			'label'   => __( 'Image fit', 'appyn-pro' ),
			'default' => 'cover',
			'choices' => array(
				'cover'   => 'cover',
				'contain' => 'contain',
			),
			'css_var' => '--apx-news-img-fit',
		),
		'news_image_hover_scale' => array(
			'type'    => 'slider',
			'label'   => __( 'Image hover zoom', 'appyn-pro' ),
			'default' => 1.07,
			'min'     => 1,
			'max'     => 1.3,
			'step'    => 0.01,
			'css_var' => '--apx-news-img-scale',
		),
		'news_image_hover_filter' => array(
			'type'    => 'select',
			'label'   => __( 'Image hover filter', 'appyn-pro' ),
			'default' => 'none',
			'choices' => array(
				'none'       => __( 'None', 'appyn-pro' ),
				'brightness' => __( 'Brighten', 'appyn-pro' ),
				'saturate'   => __( 'Saturate', 'appyn-pro' ),
				'grayscale'  => __( 'Grayscale off', 'appyn-pro' ),
			),
		),
		'news_badge_show'  => array(
			'type'    => 'toggle',
			'section' => __( 'Category badge', 'appyn-pro' ),
			'label'   => __( 'Show category badge', 'appyn-pro' ),
			'default' => 1,
		),
		'news_badge_position' => array(
			'type'    => 'select',
			'label'   => __( 'Badge position', 'appyn-pro' ),
			'default' => 'image',
			'choices' => array(
				'image'  => __( 'On the image', 'appyn-pro' ),
				'below'  => __( 'Below the image', 'appyn-pro' ),
			),
		),
		'news_badge_bg'    => array(
			'type'    => 'color',
			'label'   => __( 'Badge background', 'appyn-pro' ),
			'default' => '#4CAF50',
			'css_var' => '--apx-news-badge-bg',
		),
		'news_badge_color' => array(
			'type'    => 'color',
			'label'   => __( 'Badge text', 'appyn-pro' ),
			'default' => '#ffffff',
			'css_var' => '--apx-news-badge-color',
		),
		'news_badge_radius'=> array(
			'type'    => 'slider',
			'label'   => __( 'Badge radius', 'appyn-pro' ),
			'default' => 20,
			'min'     => 0,
			'max'     => 30,
			'unit'    => 'px',
			'css_var' => '--apx-news-badge-radius',
		),
		'news_title_size'  => array(
			'type'    => 'slider',
			'section' => __( 'Text', 'appyn-pro' ),
			'label'   => __( 'Title size', 'appyn-pro' ),
			'default' => 16,
			'min'     => 12,
			'max'     => 28,
			'unit'    => 'px',
			'css_var' => '--apx-news-title-size',
		),
		'news_title_weight'=> array(
			'type'    => 'select',
			'label'   => __( 'Title weight', 'appyn-pro' ),
			'default' => '600',
			'choices' => array(
				'500' => '500',
				'600' => '600',
				'700' => '700',
				'800' => '800',
			),
			'css_var' => '--apx-news-title-weight',
		),
		'news_title_color' => array(
			'type'    => 'color',
			'label'   => __( 'Title colour', 'appyn-pro' ),
			'default' => '#1a1a2e',
			'css_var' => '--apx-news-title-color',
		),
		'news_title_hover' => array(
			'type'    => 'color',
			'label'   => __( 'Title hover colour', 'appyn-pro' ),
			'default' => '#4CAF50',
			'css_var' => '--apx-news-title-hover',
		),
		'news_title_lines' => array(
			'type'    => 'slider',
			'label'   => __( 'Title lines', 'appyn-pro' ),
			'default' => 2,
			'min'     => 1,
			'max'     => 5,
			'css_var' => '--apx-news-title-lines',
		),
		'news_excerpt_show'=> array(
			'type'    => 'toggle',
			'label'   => __( 'Show excerpt', 'appyn-pro' ),
			'default' => 1,
		),
		'news_excerpt_length' => array(
			'type'    => 'slider',
			'label'   => __( 'Excerpt length (words)', 'appyn-pro' ),
			'default' => 18,
			'min'     => 5,
			'max'     => 60,
		),
		'news_excerpt_color' => array(
			'type'    => 'color',
			'label'   => __( 'Excerpt colour', 'appyn-pro' ),
			'default' => '#6b7280',
			'css_var' => '--apx-news-excerpt-color',
		),
		'news_excerpt_size'=> array(
			'type'    => 'slider',
			'label'   => __( 'Excerpt size', 'appyn-pro' ),
			'default' => 13,
			'min'     => 10,
			'max'     => 20,
			'unit'    => 'px',
			'css_var' => '--apx-news-excerpt-size',
		),
		'news_meta_color'  => array(
			'type'    => 'color',
			'section' => __( 'Meta & read more', 'appyn-pro' ),
			'label'   => __( 'Meta colour', 'appyn-pro' ),
			'default' => '#9ca3af',
			'css_var' => '--apx-news-meta-color',
		),
		'news_meta_size'   => array(
			'type'    => 'slider',
			'label'   => __( 'Meta size', 'appyn-pro' ),
			'default' => 12,
			'min'     => 9,
			'max'     => 18,
			'unit'    => 'px',
			'css_var' => '--apx-news-meta-size',
		),
		'news_show_date'   => array(
			'type'    => 'toggle',
			'label'   => __( 'Show date', 'appyn-pro' ),
			'default' => 1,
		),
		'news_show_author' => array(
			'type'    => 'toggle',
			'label'   => __( 'Show author', 'appyn-pro' ),
			'default' => 0,
		),
		'news_show_readtime' => array(
			'type'    => 'toggle',
			'label'   => __( 'Show read time', 'appyn-pro' ),
			'default' => 1,
		),
		'news_show_views'  => array(
			'type'    => 'toggle',
			'label'   => __( 'Show views', 'appyn-pro' ),
			'default' => 0,
		),
		'news_readmore_show' => array(
			'type'    => 'toggle',
			'label'   => __( 'Show "read more"', 'appyn-pro' ),
			'default' => 1,
		),
		'news_readmore_text' => array(
			'type'    => 'text',
			'label'   => __( '"Read more" text', 'appyn-pro' ),
			'default' => 'Read more',
		),
		'news_readmore_style' => array(
			'type'    => 'select',
			'label'   => __( '"Read more" style', 'appyn-pro' ),
			'default' => 'arrow',
			'choices' => array(
				'arrow'  => __( 'Text with arrow', 'appyn-pro' ),
				'text'   => __( 'Plain text', 'appyn-pro' ),
				'button' => __( 'Button', 'appyn-pro' ),
			),
		),
		'news_readmore_color' => array(
			'type'    => 'color',
			'label'   => __( '"Read more" colour', 'appyn-pro' ),
			'default' => '#4CAF50',
			'css_var' => '--apx-news-more-color',
		),
		'news_hover_lift'  => array(
			'type'    => 'slider',
			'section' => __( 'Hover', 'appyn-pro' ),
			'label'   => __( 'Hover lift', 'appyn-pro' ),
			'default' => 6,
			'min'     => 0,
			'max'     => 20,
			'unit'    => 'px',
			'css_var' => '--apx-news-lift',
		),
		'news_hover_shadow'=> array(
			'type'    => 'shadow',
			'label'   => __( 'Hover shadow', 'appyn-pro' ),
			'default' => array(
				'enable' => 1,
				'x'      => 0,
				'y'      => 16,
				'blur'   => 32,
				'spread' => -8,
				'color'  => '#0f172a2b',
			),
			'css_var' => '--apx-news-hover-shadow',
		),
		'news_hover_border'=> array(
			'type'    => 'color',
			'label'   => __( 'Hover border colour', 'appyn-pro' ),
			'default' => '#d7f0d9',
			'css_var' => '--apx-news-hover-border',
		),
	);
}

/* ---------------------------------------------------------------------------
 * Tab 8 - App detail page
 * ------------------------------------------------------------------------ */

/**
 * Single app page design.
 *
 * @return array
 */
function apx_schema_detail() {
	return array(
		'detail_hero_enable' => array(
			'type'    => 'toggle',
			'section' => __( 'Hero banner', 'appyn-pro' ),
			'label'   => __( 'Blurred app banner behind the header', 'appyn-pro' ),
			'default' => 1,
		),
		'detail_hero_height' => array(
			'type'    => 'slider',
			'label'   => __( 'Banner height', 'appyn-pro' ),
			'default' => 380,
			'min'     => 200,
			'max'     => 700,
			'unit'    => 'px',
			'css_var' => '--apx-detail-hero-height',
		),
		'detail_hero_overlay' => array(
			'type'    => 'gradient',
			'label'   => __( 'Banner overlay', 'appyn-pro' ),
			'default' => array(
				'enable' => 1,
				'angle'  => 180,
				'from'   => '#0b1020a6',
				'to'     => '#0b1020f2',
			),
			'css_var' => '--apx-detail-hero-overlay',
		),
		'detail_hero_blur'   => array(
			'type'    => 'slider',
			'label'   => __( 'Banner blur', 'appyn-pro' ),
			'default' => 26,
			'min'     => 0,
			'max'     => 60,
			'unit'    => 'px',
			'css_var' => '--apx-detail-hero-blur',
		),
		'detail_hero_radius' => array(
			'type'    => 'slider',
			'label'   => __( 'Bottom corner radius', 'appyn-pro' ),
			'default' => 0,
			'min'     => 0,
			'max'     => 60,
			'unit'    => 'px',
			'css_var' => '--apx-detail-hero-radius',
		),
		'detail_hero_parallax' => array(
			'type'    => 'toggle',
			'label'   => __( 'Parallax banner', 'appyn-pro' ),
			'default' => 0,
		),
		'breadcrumb_style'   => array(
			'type'    => 'select',
			'section' => __( 'Breadcrumb', 'appyn-pro' ),
			'label'   => __( 'Separator style', 'appyn-pro' ),
			'default' => 'arrow',
			'choices' => array(
				'arrow' => __( 'Arrow', 'appyn-pro' ),
				'slash' => __( 'Slash', 'appyn-pro' ),
				'dot'   => __( 'Dot', 'appyn-pro' ),
				'simple'=> __( 'Space', 'appyn-pro' ),
			),
		),
		'breadcrumb_color'   => array(
			'type'    => 'color',
			'label'   => __( 'Inactive colour', 'appyn-pro' ),
			'default' => '#9aa4b2',
			'css_var' => '--apx-crumb-color',
		),
		'breadcrumb_active'  => array(
			'type'    => 'color',
			'label'   => __( 'Active colour', 'appyn-pro' ),
			'default' => '#4CAF50',
			'css_var' => '--apx-crumb-active',
		),
		'breadcrumb_size'    => array(
			'type'    => 'slider',
			'label'   => __( 'Font size', 'appyn-pro' ),
			'default' => 13,
			'min'     => 10,
			'max'     => 20,
			'unit'    => 'px',
			'css_var' => '--apx-crumb-size',
		),
		'detail_icon_size'   => array(
			'type'    => 'slider',
			'section' => __( 'App icon & title', 'appyn-pro' ),
			'label'   => __( 'App icon size', 'appyn-pro' ),
			'default' => 140,
			'min'     => 80,
			'max'     => 200,
			'unit'    => 'px',
			'css_var' => '--apx-detail-icon',
		),
		'detail_icon_radius' => array(
			'type'    => 'slider',
			'label'   => __( 'App icon radius', 'appyn-pro' ),
			'default' => 26,
			'min'     => 0,
			'max'     => 100,
			'unit'    => 'px',
			'css_var' => '--apx-detail-icon-radius',
		),
		'detail_icon_shadow' => array(
			'type'    => 'shadow',
			'label'   => __( 'App icon shadow', 'appyn-pro' ),
			'default' => array(
				'enable' => 1,
				'x'      => 0,
				'y'      => 18,
				'blur'   => 40,
				'spread' => -10,
				'color'  => '#000000a6',
			),
			'css_var' => '--apx-detail-icon-shadow',
		),
		'detail_icon_tilt'   => array(
			'type'    => 'toggle',
			'label'   => __( '3D tilt on hover', 'appyn-pro' ),
			'default' => 1,
		),
		'detail_title_size'  => array(
			'type'    => 'slider',
			'label'   => __( 'Title size', 'appyn-pro' ),
			'default' => 38,
			'min'     => 20,
			'max'     => 72,
			'unit'    => 'px',
			'css_var' => '--apx-detail-title-size',
		),
		'detail_title_color' => array(
			'type'    => 'color',
			'label'   => __( 'Title colour', 'appyn-pro' ),
			'default' => '#ffffff',
			'css_var' => '--apx-detail-title-color',
		),
		'detail_title_weight'=> array(
			'type'    => 'select',
			'label'   => __( 'Title weight', 'appyn-pro' ),
			'default' => '700',
			'choices' => array(
				'500' => '500',
				'600' => '600',
				'700' => '700',
				'800' => '800',
				'900' => '900',
			),
			'css_var' => '--apx-detail-title-weight',
		),
		'detail_dev_color'   => array(
			'type'    => 'color',
			'label'   => __( 'Developer colour', 'appyn-pro' ),
			'default' => '#8BC34A',
			'css_var' => '--apx-detail-dev-color',
		),
		'detail_mod_color'   => array(
			'type'    => 'color',
			'label'   => __( 'MOD info text colour', 'appyn-pro' ),
			'default' => '#FF9800',
			'css_var' => '--apx-detail-mod-color',
		),
		'info_bar_bg'        => array(
			'type'    => 'color',
			'section' => __( 'Info bar', 'appyn-pro' ),
			'label'   => __( 'Background', 'appyn-pro' ),
			'default' => '#ffffff',
			'css_var' => '--apx-info-bg',
		),
		'info_bar_border'    => array(
			'type'    => 'border',
			'label'   => __( 'Border', 'appyn-pro' ),
			'default' => array(
				'width' => 1,
				'style' => 'solid',
				'color' => '#e6e8ec',
			),
			'css_var' => '--apx-info-border',
		),
		'info_bar_radius'    => array(
			'type'    => 'slider',
			'label'   => __( 'Radius', 'appyn-pro' ),
			'default' => 14,
			'min'     => 0,
			'max'     => 40,
			'unit'    => 'px',
			'css_var' => '--apx-info-radius',
		),
		'info_bar_padding'   => array(
			'type'    => 'spacing',
			'label'   => __( 'Padding', 'appyn-pro' ),
			'default' => array(
				'top'    => 14,
				'right'  => 18,
				'bottom' => 14,
				'left'   => 18,
			),
			'css_var' => '--apx-info-padding',
		),
		'info_icon_color'    => array(
			'type'    => 'color',
			'label'   => __( 'Icon colour', 'appyn-pro' ),
			'default' => '#4CAF50',
			'css_var' => '--apx-info-icon-color',
		),
		'info_label_color'   => array(
			'type'    => 'color',
			'label'   => __( 'Label colour', 'appyn-pro' ),
			'default' => '#9ca3af',
			'css_var' => '--apx-info-label-color',
		),
		'info_value_color'   => array(
			'type'    => 'color',
			'label'   => __( 'Value colour', 'appyn-pro' ),
			'default' => '#1a1a2e',
			'css_var' => '--apx-info-value-color',
		),
		'download_btn_text'  => array(
			'type'    => 'text',
			'section' => __( 'Download button', 'appyn-pro' ),
			'label'   => __( 'Button text', 'appyn-pro' ),
			'desc'    => __( 'Leave empty to keep the parent theme text.', 'appyn-pro' ),
			'default' => '',
		),
		'download_btn_icon'  => array(
			'type'    => 'icon',
			'label'   => __( 'Button icon', 'appyn-pro' ),
			'default' => 'fas fa-download',
		),
		'download_btn_bg'    => array(
			'type'    => 'color',
			'label'   => __( 'Background', 'appyn-pro' ),
			'default' => '#4CAF50',
			'css_var' => '--apx-dl-bg',
		),
		'download_btn_hover_bg' => array(
			'type'    => 'color',
			'label'   => __( 'Background (hover)', 'appyn-pro' ),
			'default' => '#43a047',
			'css_var' => '--apx-dl-hover-bg',
		),
		'download_btn_active_bg' => array(
			'type'    => 'color',
			'label'   => __( 'Background (pressed)', 'appyn-pro' ),
			'default' => '#388e3c',
			'css_var' => '--apx-dl-active-bg',
		),
		'download_btn_loading_bg' => array(
			'type'    => 'color',
			'label'   => __( 'Background (loading)', 'appyn-pro' ),
			'default' => '#607d8b',
			'css_var' => '--apx-dl-loading-bg',
		),
		'download_btn_success_bg' => array(
			'type'    => 'color',
			'label'   => __( 'Background (success)', 'appyn-pro' ),
			'default' => '#2e7d32',
			'css_var' => '--apx-dl-success-bg',
		),
		'download_btn_color' => array(
			'type'    => 'color',
			'label'   => __( 'Text colour', 'appyn-pro' ),
			'default' => '#ffffff',
			'css_var' => '--apx-dl-color',
		),
		'download_btn_radius'=> array(
			'type'    => 'slider',
			'label'   => __( 'Radius', 'appyn-pro' ),
			'default' => 12,
			'min'     => 0,
			'max'     => 50,
			'unit'    => 'px',
			'css_var' => '--apx-dl-radius',
		),
		'download_btn_padding' => array(
			'type'    => 'spacing',
			'label'   => __( 'Padding', 'appyn-pro' ),
			'default' => array(
				'top'    => 16,
				'right'  => 28,
				'bottom' => 16,
				'left'   => 28,
			),
			'css_var' => '--apx-dl-padding',
		),
		'download_btn_size'  => array(
			'type'    => 'slider',
			'label'   => __( 'Font size', 'appyn-pro' ),
			'default' => 18,
			'min'     => 12,
			'max'     => 28,
			'unit'    => 'px',
			'css_var' => '--apx-dl-size',
		),
		'download_btn_width' => array(
			'type'    => 'select',
			'label'   => __( 'Width', 'appyn-pro' ),
			'default' => 'full',
			'choices' => array(
				'full' => __( 'Full width', 'appyn-pro' ),
				'auto' => __( 'Fit content', 'appyn-pro' ),
			),
		),
		'download_btn_shadow'=> array(
			'type'    => 'shadow',
			'label'   => __( 'Shadow', 'appyn-pro' ),
			'default' => array(
				'enable' => 1,
				'x'      => 0,
				'y'      => 8,
				'blur'   => 20,
				'spread' => -6,
				'color'  => '#4caf5099',
			),
			'css_var' => '--apx-dl-shadow',
		),
		'download_btn_hover_effect' => array(
			'type'    => 'select',
			'label'   => __( 'Hover effect', 'appyn-pro' ),
			'default' => 'lift',
			'choices' => array(
				'lift'  => __( 'Lift', 'appyn-pro' ),
				'glow'  => __( 'Glow', 'appyn-pro' ),
				'scale' => __( 'Scale', 'appyn-pro' ),
				'pulse' => __( 'Pulse', 'appyn-pro' ),
				'none'  => __( 'None', 'appyn-pro' ),
			),
		),
		'download_btn_icon_anim' => array(
			'type'    => 'select',
			'label'   => __( 'Icon animation', 'appyn-pro' ),
			'default' => 'bounce',
			'choices' => array(
				'bounce' => __( 'Bounce', 'appyn-pro' ),
				'pulse'  => __( 'Pulse', 'appyn-pro' ),
				'shake'  => __( 'Shake', 'appyn-pro' ),
				'none'   => __( 'None', 'appyn-pro' ),
			),
		),
		'download_loading_style' => array(
			'type'    => 'select',
			'label'   => __( 'Loading animation', 'appyn-pro' ),
			'default' => 'spinner',
			'choices' => array(
				'spinner'  => __( 'Spinner', 'appyn-pro' ),
				'progress' => __( 'Progress bar', 'appyn-pro' ),
				'dots'     => __( 'Dots', 'appyn-pro' ),
				'none'     => __( 'None', 'appyn-pro' ),
			),
		),
		'download_success_text' => array(
			'type'    => 'text',
			'label'   => __( 'Success text', 'appyn-pro' ),
			'default' => 'Starting download…',
		),
		'download_success_anim' => array(
			'type'    => 'select',
			'label'   => __( 'Success animation', 'appyn-pro' ),
			'default' => 'check',
			'choices' => array(
				'check'    => __( 'Check mark', 'appyn-pro' ),
				'bounce'   => __( 'Bounce', 'appyn-pro' ),
				'confetti' => __( 'Confetti', 'appyn-pro' ),
				'none'     => __( 'None', 'appyn-pro' ),
			),
		),
		'download_ripple'    => array(
			'type'    => 'toggle',
			'label'   => __( 'Ripple on click', 'appyn-pro' ),
			'default' => 1,
		),
		'secondary_btn_style'=> array(
			'type'    => 'select',
			'section' => __( 'Secondary buttons', 'appyn-pro' ),
			'label'   => __( 'Style', 'appyn-pro' ),
			'default' => 'outline',
			'choices' => array(
				'outline' => __( 'Outline', 'appyn-pro' ),
				'ghost'   => __( 'Ghost', 'appyn-pro' ),
				'solid'   => __( 'Solid', 'appyn-pro' ),
			),
		),
		'secondary_btn_color'=> array(
			'type'    => 'color',
			'label'   => __( 'Colour', 'appyn-pro' ),
			'default' => '#4CAF50',
			'css_var' => '--apx-btn2-color',
		),
		'secondary_btn_hover_bg' => array(
			'type'    => 'color',
			'label'   => __( 'Hover background', 'appyn-pro' ),
			'default' => '#4CAF50',
			'css_var' => '--apx-btn2-hover-bg',
		),
		'secondary_btn_hover_color' => array(
			'type'    => 'color',
			'label'   => __( 'Hover text colour', 'appyn-pro' ),
			'default' => '#ffffff',
			'css_var' => '--apx-btn2-hover-color',
		),
		'tab_style'          => array(
			'type'    => 'select',
			'section' => __( 'Content tabs & boxes', 'appyn-pro' ),
			'label'   => __( 'Tab style', 'appyn-pro' ),
			'default' => 'underline',
			'choices' => array(
				'underline' => __( 'Underline', 'appyn-pro' ),
				'pill'      => __( 'Pill', 'appyn-pro' ),
				'box'       => __( 'Box', 'appyn-pro' ),
				'line'      => __( 'Line', 'appyn-pro' ),
			),
		),
		'tab_active_color'   => array(
			'type'    => 'color',
			'label'   => __( 'Active colour', 'appyn-pro' ),
			'default' => '#4CAF50',
			'css_var' => '--apx-tab-active',
		),
		'tab_inactive_color' => array(
			'type'    => 'color',
			'label'   => __( 'Inactive colour', 'appyn-pro' ),
			'default' => '#6b7280',
			'css_var' => '--apx-tab-inactive',
		),
		'tab_hover_color'    => array(
			'type'    => 'color',
			'label'   => __( 'Hover colour', 'appyn-pro' ),
			'default' => '#1a1a2e',
			'css_var' => '--apx-tab-hover',
		),
		'tab_radius'         => array(
			'type'    => 'slider',
			'label'   => __( 'Tab radius', 'appyn-pro' ),
			'default' => 10,
			'min'     => 0,
			'max'     => 30,
			'unit'    => 'px',
			'css_var' => '--apx-tab-radius',
		),
		'box_bg'             => array(
			'type'    => 'color',
			'label'   => __( 'Content box background', 'appyn-pro' ),
			'default' => '#ffffff',
			'css_var' => '--apx-box-bg',
		),
		'box_radius'         => array(
			'type'    => 'slider',
			'label'   => __( 'Content box radius', 'appyn-pro' ),
			'default' => 16,
			'min'     => 0,
			'max'     => 40,
			'unit'    => 'px',
			'css_var' => '--apx-box-radius',
		),
		'box_shadow'         => array(
			'type'    => 'shadow',
			'label'   => __( 'Content box shadow', 'appyn-pro' ),
			'default' => array(
				'enable' => 1,
				'x'      => 0,
				'y'      => 2,
				'blur'   => 14,
				'spread' => 0,
				'color'  => '#0f172a12',
			),
			'css_var' => '--apx-box-shadow',
		),
		'screenshot_radius'  => array(
			'type'    => 'slider',
			'section' => __( 'Screenshots', 'appyn-pro' ),
			'label'   => __( 'Screenshot radius', 'appyn-pro' ),
			'default' => 12,
			'min'     => 0,
			'max'     => 30,
			'unit'    => 'px',
			'css_var' => '--apx-shot-radius',
		),
		'screenshot_gap'     => array(
			'type'    => 'slider',
			'label'   => __( 'Screenshot gap', 'appyn-pro' ),
			'default' => 12,
			'min'     => 0,
			'max'     => 40,
			'unit'    => 'px',
			'css_var' => '--apx-shot-gap',
		),
		'screenshot_hover_scale' => array(
			'type'    => 'slider',
			'label'   => __( 'Screenshot hover scale', 'appyn-pro' ),
			'default' => 1.04,
			'min'     => 1,
			'max'     => 1.2,
			'step'    => 0.01,
			'css_var' => '--apx-shot-scale',
		),
	);
}

/* ---------------------------------------------------------------------------
 * Tab 9 - Footer
 * ------------------------------------------------------------------------ */

/**
 * Footer layout, columns, social icons and back to top.
 *
 * @return array
 */
function apx_schema_footer() {
	return array(
		'footer_bg'        => array(
			'type'    => 'color',
			'section' => __( 'Layout', 'appyn-pro' ),
			'label'   => __( 'Background', 'appyn-pro' ),
			'default' => '#ffffff',
			'css_var' => '--apx-footer-bg',
		),
		'footer_bg_image'  => array(
			'type'    => 'image',
			'label'   => __( 'Background image', 'appyn-pro' ),
			'default' => '',
		),
		'footer_text'      => array(
			'type'    => 'color',
			'label'   => __( 'Text colour', 'appyn-pro' ),
			'default' => '#4b5563',
			'css_var' => '--apx-footer-text',
		),
		'footer_link'      => array(
			'type'    => 'color',
			'label'   => __( 'Link colour', 'appyn-pro' ),
			'default' => '#374151',
			'css_var' => '--apx-footer-link',
		),
		'footer_link_hover'=> array(
			'type'    => 'color',
			'label'   => __( 'Link hover colour', 'appyn-pro' ),
			'default' => '#4CAF50',
			'css_var' => '--apx-footer-link-hover',
		),
		'footer_link_hover_style' => array(
			'type'    => 'select',
			'label'   => __( 'Link hover effect', 'appyn-pro' ),
			'default' => 'slide',
			'choices' => array(
				'slide'     => __( 'Slide right', 'appyn-pro' ),
				'underline' => __( 'Underline', 'appyn-pro' ),
				'color'     => __( 'Colour only', 'appyn-pro' ),
			),
		),
		'footer_padding'   => array(
			'type'    => 'spacing',
			'label'   => __( 'Padding', 'appyn-pro' ),
			'default' => array(
				'top'    => 56,
				'right'  => 0,
				'bottom' => 24,
				'left'   => 0,
			),
			'css_var' => '--apx-footer-padding',
		),
		'footer_border'    => array(
			'type'    => 'border',
			'label'   => __( 'Top border', 'appyn-pro' ),
			'default' => array(
				'width' => 1,
				'style' => 'solid',
				'color' => '#e6e8ec',
			),
			'css_var' => '--apx-footer-border',
		),
		'footer_columns'   => array(
			'type'    => 'slider',
			'label'   => __( 'Columns', 'appyn-pro' ),
			'default' => 4,
			'min'     => 1,
			'max'     => 5,
			'css_var' => '--apx-footer-cols',
		),
		'footer_widget_title_color' => array(
			'type'    => 'color',
			'label'   => __( 'Column title colour', 'appyn-pro' ),
			'default' => '#111827',
			'css_var' => '--apx-footer-title-color',
		),
		'footer_widget_title_size'  => array(
			'type'    => 'slider',
			'label'   => __( 'Column title size', 'appyn-pro' ),
			'default' => 14,
			'min'     => 10,
			'max'     => 24,
			'unit'    => 'px',
			'css_var' => '--apx-footer-title-size',
		),
		'footer_title_underline'    => array(
			'type'    => 'toggle',
			'label'   => __( 'Underline under column titles', 'appyn-pro' ),
			'default' => 1,
		),
		'footer_title_underline_color' => array(
			'type'    => 'color',
			'label'   => __( 'Underline colour', 'appyn-pro' ),
			'default' => '#4CAF50',
			'css_var' => '--apx-footer-underline',
		),
		'footer_title_underline_width' => array(
			'type'    => 'slider',
			'label'   => __( 'Underline width', 'appyn-pro' ),
			'default' => 28,
			'min'     => 8,
			'max'     => 120,
			'unit'    => 'px',
			'css_var' => '--apx-footer-underline-w',
		),
		'footer_logo'      => array(
			'type'    => 'image',
			'section' => __( 'Brand column', 'appyn-pro' ),
			'label'   => __( 'Footer logo', 'appyn-pro' ),
			'default' => '',
		),
		'footer_logo_height' => array(
			'type'    => 'slider',
			'label'   => __( 'Footer logo height', 'appyn-pro' ),
			'default' => 40,
			'min'     => 20,
			'max'     => 120,
			'unit'    => 'px',
			'css_var' => '--apx-footer-logo-height',
		),
		'footer_desc'      => array(
			'type'    => 'editor',
			'label'   => __( 'Description', 'appyn-pro' ),
			'default' => '',
		),
		'footer_desc_size' => array(
			'type'    => 'slider',
			'label'   => __( 'Description size', 'appyn-pro' ),
			'default' => 14,
			'min'     => 10,
			'max'     => 20,
			'unit'    => 'px',
			'css_var' => '--apx-footer-desc-size',
		),
		'footer_cols'      => array(
			'type'    => 'repeater',
			'section' => __( 'Link columns', 'appyn-pro' ),
			'label'   => __( 'Columns', 'appyn-pro' ),
			'desc'    => __( 'Each row is one footer column. Add links inside the column text, one per line, as "Label|URL|icon".', 'appyn-pro' ),
			'default' => array(),
			'fields'  => array(
				'title'     => array(
					'type'    => 'text',
					'label'   => __( 'Column title', 'appyn-pro' ),
					'default' => '',
				),
				'type'      => array(
					'type'    => 'select',
					'label'   => __( 'Content type', 'appyn-pro' ),
					'default' => 'links',
					'choices' => array(
						'links' => __( 'Link list', 'appyn-pro' ),
						'text'  => __( 'Text', 'appyn-pro' ),
						'html'  => __( 'HTML', 'appyn-pro' ),
						'menu'  => __( 'WordPress menu (by name)', 'appyn-pro' ),
					),
				),
				'content'   => array(
					'type'    => 'textarea',
					'label'   => __( 'Content', 'appyn-pro' ),
					'default' => '',
				),
				'css_class' => array(
					'type'    => 'text',
					'label'   => __( 'Custom CSS class', 'appyn-pro' ),
					'default' => '',
				),
			),
		),
		'social_icons'     => array(
			'type'    => 'repeater',
			'section' => __( 'Social icons', 'appyn-pro' ),
			'label'   => __( 'Social icons', 'appyn-pro' ),
			'default' => array(),
			'fields'  => array(
				'icon'       => array(
					'type'    => 'icon',
					'label'   => __( 'Icon', 'appyn-pro' ),
					'default' => 'fab fa-facebook-f',
				),
				'url'        => array(
					'type'    => 'text',
					'label'   => __( 'URL', 'appyn-pro' ),
					'default' => '',
				),
				'color'      => array(
					'type'    => 'color',
					'label'   => __( 'Icon colour', 'appyn-pro' ),
					'default' => '#374151',
				),
				'bg'         => array(
					'type'    => 'color',
					'label'   => __( 'Background', 'appyn-pro' ),
					'default' => '#f3f4f6',
				),
				'hover_color'=> array(
					'type'    => 'color',
					'label'   => __( 'Hover icon colour', 'appyn-pro' ),
					'default' => '#ffffff',
				),
				'hover_bg'   => array(
					'type'    => 'color',
					'label'   => __( 'Hover background', 'appyn-pro' ),
					'default' => '#4CAF50',
				),
			),
		),
		'social_size'      => array(
			'type'    => 'slider',
			'label'   => __( 'Icon size', 'appyn-pro' ),
			'default' => 40,
			'min'     => 24,
			'max'     => 72,
			'unit'    => 'px',
			'css_var' => '--apx-social-size',
		),
		'social_radius'    => array(
			'type'    => 'slider',
			'label'   => __( 'Icon radius', 'appyn-pro' ),
			'default' => 50,
			'min'     => 0,
			'max'     => 50,
			'unit'    => '%',
			'css_var' => '--apx-social-radius',
		),
		'social_gap'       => array(
			'type'    => 'slider',
			'label'   => __( 'Icon spacing', 'appyn-pro' ),
			'default' => 10,
			'min'     => 0,
			'max'     => 40,
			'unit'    => 'px',
			'css_var' => '--apx-social-gap',
		),
		'social_hover_anim'=> array(
			'type'    => 'select',
			'label'   => __( 'Hover animation', 'appyn-pro' ),
			'default' => 'lift',
			'choices' => array(
				'lift'   => __( 'Lift', 'appyn-pro' ),
				'scale'  => __( 'Scale', 'appyn-pro' ),
				'rotate' => __( 'Rotate', 'appyn-pro' ),
				'none'   => __( 'None', 'appyn-pro' ),
			),
		),
		'bottom_bar_show'  => array(
			'type'    => 'toggle',
			'section' => __( 'Bottom bar', 'appyn-pro' ),
			'label'   => __( 'Show bottom bar', 'appyn-pro' ),
			'default' => 1,
		),
		'bottom_bar_bg'    => array(
			'type'    => 'color',
			'label'   => __( 'Background', 'appyn-pro' ),
			'default' => '#fafbfc',
			'css_var' => '--apx-bottom-bg',
		),
		'bottom_bar_border'=> array(
			'type'    => 'border',
			'label'   => __( 'Top border', 'appyn-pro' ),
			'default' => array(
				'width' => 1,
				'style' => 'solid',
				'color' => '#e6e8ec',
			),
			'css_var' => '--apx-bottom-border',
		),
		'bottom_bar_size'  => array(
			'type'    => 'slider',
			'label'   => __( 'Font size', 'appyn-pro' ),
			'default' => 13,
			'min'     => 10,
			'max'     => 18,
			'unit'    => 'px',
			'css_var' => '--apx-bottom-size',
		),
		'copyright_left'   => array(
			'type'    => 'textarea',
			'label'   => __( 'Left text', 'appyn-pro' ),
			'desc'    => __( 'You can use {year} and {site_name}.', 'appyn-pro' ),
			'default' => '© {year} {site_name}. All rights reserved.',
		),
		'copyright_right'  => array(
			'type'    => 'textarea',
			'label'   => __( 'Right text', 'appyn-pro' ),
			'default' => '',
		),
		'btt_show'         => array(
			'type'    => 'toggle',
			'section' => __( 'Back to top', 'appyn-pro' ),
			'label'   => __( 'Show back to top button', 'appyn-pro' ),
			'default' => 1,
		),
		'btt_icon'         => array(
			'type'    => 'icon',
			'label'   => __( 'Icon', 'appyn-pro' ),
			'default' => 'fas fa-chevron-up',
		),
		'btt_style'        => array(
			'type'    => 'select',
			'label'   => __( 'Shape', 'appyn-pro' ),
			'default' => 'circle',
			'choices' => array(
				'circle' => __( 'Circle', 'appyn-pro' ),
				'square' => __( 'Rounded square', 'appyn-pro' ),
				'pill'   => __( 'Pill', 'appyn-pro' ),
			),
		),
		'btt_size'         => array(
			'type'    => 'slider',
			'label'   => __( 'Size', 'appyn-pro' ),
			'default' => 48,
			'min'     => 40,
			'max'     => 80,
			'unit'    => 'px',
			'css_var' => '--apx-btt-size',
		),
		'btt_bg'           => array(
			'type'    => 'color',
			'label'   => __( 'Background', 'appyn-pro' ),
			'default' => '#4CAF50',
			'css_var' => '--apx-btt-bg',
		),
		'btt_bg_hover'     => array(
			'type'    => 'color',
			'label'   => __( 'Background (hover)', 'appyn-pro' ),
			'default' => '#3d8b40',
			'css_var' => '--apx-btt-bg-hover',
		),
		'btt_icon_color'   => array(
			'type'    => 'color',
			'label'   => __( 'Icon colour', 'appyn-pro' ),
			'default' => '#ffffff',
			'css_var' => '--apx-btt-color',
		),
		'btt_icon_size'    => array(
			'type'    => 'slider',
			'label'   => __( 'Icon size', 'appyn-pro' ),
			'default' => 18,
			'min'     => 10,
			'max'     => 32,
			'unit'    => 'px',
			'css_var' => '--apx-btt-icon-size',
		),
		'btt_position'     => array(
			'type'    => 'select',
			'label'   => __( 'Position', 'appyn-pro' ),
			'default' => 'bottom-right',
			'choices' => array(
				'bottom-right'  => __( 'Bottom right', 'appyn-pro' ),
				'bottom-left'   => __( 'Bottom left', 'appyn-pro' ),
				'bottom-center' => __( 'Bottom center', 'appyn-pro' ),
			),
		),
		'btt_offset_x'     => array(
			'type'    => 'slider',
			'label'   => __( 'Side offset', 'appyn-pro' ),
			'default' => 22,
			'min'     => 0,
			'max'     => 80,
			'unit'    => 'px',
			'css_var' => '--apx-btt-x',
		),
		'btt_offset_y'     => array(
			'type'    => 'slider',
			'label'   => __( 'Bottom offset', 'appyn-pro' ),
			'default' => 22,
			'min'     => 0,
			'max'     => 120,
			'unit'    => 'px',
			'css_var' => '--apx-btt-y',
		),
		'btt_trigger'      => array(
			'type'    => 'slider',
			'label'   => __( 'Show after scrolling', 'appyn-pro' ),
			'default' => 400,
			'min'     => 100,
			'max'     => 1000,
			'step'    => 20,
			'unit'    => 'px',
		),
		'btt_anim'         => array(
			'type'    => 'select',
			'label'   => __( 'Appear animation', 'appyn-pro' ),
			'default' => 'slide',
			'choices' => array(
				'slide'  => __( 'Slide up', 'appyn-pro' ),
				'fade'   => __( 'Fade', 'appyn-pro' ),
				'bounce' => __( 'Bounce', 'appyn-pro' ),
				'zoom'   => __( 'Zoom', 'appyn-pro' ),
			),
		),
		'btt_shadow'       => array(
			'type'    => 'shadow',
			'label'   => __( 'Shadow', 'appyn-pro' ),
			'default' => array(
				'enable' => 1,
				'x'      => 0,
				'y'      => 8,
				'blur'   => 22,
				'spread' => -6,
				'color'  => '#0f172a59',
			),
			'css_var' => '--apx-btt-shadow',
		),
	);
}

/* ---------------------------------------------------------------------------
 * Tab 10 - Animations
 * ------------------------------------------------------------------------ */

/**
 * Global motion settings.
 *
 * @return array
 */
function apx_schema_animations() {
	return array(
		'anim_respect_motion' => array(
			'type'    => 'toggle',
			'section' => __( 'Master', 'appyn-pro' ),
			'label'   => __( 'Respect "reduce motion" setting', 'appyn-pro' ),
			'default' => 1,
		),
		'anim_style'       => array(
			'type'    => 'select',
			'label'   => __( 'Motion style', 'appyn-pro' ),
			'default' => 'smooth',
			'choices' => array(
				'smooth' => __( 'Smooth', 'appyn-pro' ),
				'bouncy' => __( 'Bouncy', 'appyn-pro' ),
				'snappy' => __( 'Snappy', 'appyn-pro' ),
			),
		),
		'preloader'        => array(
			'type'    => 'toggle',
			'section' => __( 'Page load', 'appyn-pro' ),
			'label'   => __( 'Show preloader', 'appyn-pro' ),
			'default' => 0,
		),
		'preloader_style'  => array(
			'type'    => 'select',
			'label'   => __( 'Preloader style', 'appyn-pro' ),
			'default' => 'spinner',
			'choices' => array(
				'spinner'  => __( 'Spinner', 'appyn-pro' ),
				'progress' => __( 'Progress bar', 'appyn-pro' ),
				'logo'     => __( 'Logo pulse', 'appyn-pro' ),
				'dots'     => __( 'Dots', 'appyn-pro' ),
			),
		),
		'preloader_bg'     => array(
			'type'    => 'color',
			'label'   => __( 'Preloader background', 'appyn-pro' ),
			'default' => '#ffffff',
			'css_var' => '--apx-preloader-bg',
		),
		'preloader_color'  => array(
			'type'    => 'color',
			'label'   => __( 'Preloader colour', 'appyn-pro' ),
			'default' => '#4CAF50',
			'css_var' => '--apx-preloader-color',
		),
		'preloader_min_time' => array(
			'type'    => 'slider',
			'label'   => __( 'Minimum display time', 'appyn-pro' ),
			'default' => 300,
			'min'     => 0,
			'max'     => 2000,
			'step'    => 50,
			'unit'    => 'ms',
		),
		'page_entrance'    => array(
			'type'    => 'select',
			'label'   => __( 'Page entrance', 'appyn-pro' ),
			'default' => 'fade',
			'choices' => array(
				'fade'     => __( 'Fade', 'appyn-pro' ),
				'slide-up' => __( 'Slide up', 'appyn-pro' ),
				'zoom'     => __( 'Zoom', 'appyn-pro' ),
				'none'     => __( 'None', 'appyn-pro' ),
			),
		),
		'scroll_anim'      => array(
			'type'    => 'select',
			'section' => __( 'Scroll animations', 'appyn-pro' ),
			'label'   => __( 'Default scroll animation', 'appyn-pro' ),
			'default' => 'fade-up',
			'choices' => array(
				'fade-up'    => __( 'Fade up', 'appyn-pro' ),
				'fade'       => __( 'Fade in', 'appyn-pro' ),
				'slide-left' => __( 'Slide left', 'appyn-pro' ),
				'zoom'       => __( 'Zoom in', 'appyn-pro' ),
				'none'       => __( 'None', 'appyn-pro' ),
			),
		),
		'scroll_duration'  => array(
			'type'    => 'slider',
			'label'   => __( 'Duration', 'appyn-pro' ),
			'default' => 600,
			'min'     => 100,
			'max'     => 2000,
			'step'    => 50,
			'unit'    => 'ms',
			'css_var' => '--apx-scroll-duration',
		),
		'scroll_offset'    => array(
			'type'    => 'slider',
			'label'   => __( 'Trigger offset', 'appyn-pro' ),
			'default' => 80,
			'min'     => 0,
			'max'     => 400,
			'step'    => 10,
			'unit'    => 'px',
		),
		'scroll_distance'  => array(
			'type'    => 'slider',
			'label'   => __( 'Travel distance', 'appyn-pro' ),
			'default' => 24,
			'min'     => 0,
			'max'     => 120,
			'unit'    => 'px',
			'css_var' => '--apx-scroll-distance',
		),
		'scroll_easing'    => array(
			'type'    => 'select',
			'label'   => __( 'Easing', 'appyn-pro' ),
			'default' => 'cubic-bezier(.22,1,.36,1)',
			'choices' => 'easings',
			'css_var' => '--apx-scroll-easing',
		),
		'scroll_once'      => array(
			'type'    => 'toggle',
			'label'   => __( 'Animate once', 'appyn-pro' ),
			'default' => 1,
		),
		'hover_duration'   => array(
			'type'    => 'slider',
			'section' => __( 'Interaction', 'appyn-pro' ),
			'label'   => __( 'Hover transition speed', 'appyn-pro' ),
			'default' => 0.25,
			'min'     => 0.05,
			'max'     => 1,
			'step'    => 0.05,
			'unit'    => 's',
			'css_var' => '--apx-hover-speed',
		),
		'click_feedback'   => array(
			'type'    => 'select',
			'label'   => __( 'Click feedback', 'appyn-pro' ),
			'default' => 'ripple',
			'choices' => array(
				'ripple' => __( 'Ripple', 'appyn-pro' ),
				'press'  => __( 'Press', 'appyn-pro' ),
				'glow'   => __( 'Glow', 'appyn-pro' ),
				'none'   => __( 'None', 'appyn-pro' ),
			),
		),
		'ripple_color'     => array(
			'type'    => 'colora',
			'label'   => __( 'Ripple colour', 'appyn-pro' ),
			'default' => array(
				'color'   => '#ffffff',
				'opacity' => 0.45,
			),
			'css_var' => '--apx-ripple',
		),
		'skeleton'         => array(
			'type'    => 'toggle',
			'label'   => __( 'Loading skeletons', 'appyn-pro' ),
			'default' => 1,
		),
		'skeleton_color'   => array(
			'type'    => 'color',
			'label'   => __( 'Skeleton colour', 'appyn-pro' ),
			'default' => '#eef0f4',
			'css_var' => '--apx-skeleton',
		),
		'parallax'         => array(
			'type'    => 'toggle',
			'section' => __( 'Special effects', 'appyn-pro' ),
			'label'   => __( 'Parallax effects', 'appyn-pro' ),
			'default' => 0,
		),
		'parallax_intensity' => array(
			'type'    => 'slider',
			'label'   => __( 'Parallax intensity', 'appyn-pro' ),
			'default' => 0.25,
			'min'     => 0.05,
			'max'     => 1,
			'step'    => 0.05,
		),
		'tilt'             => array(
			'type'    => 'toggle',
			'label'   => __( '3D tilt effects', 'appyn-pro' ),
			'default' => 0,
		),
		'tilt_intensity'   => array(
			'type'    => 'slider',
			'label'   => __( 'Tilt intensity', 'appyn-pro' ),
			'default' => 8,
			'min'     => 1,
			'max'     => 25,
			'unit'    => 'deg',
		),
		'anim_reduce_mobile' => array(
			'type'    => 'toggle',
			'section' => __( 'Performance', 'appyn-pro' ),
			'label'   => __( 'Reduce animations on phones', 'appyn-pro' ),
			'default' => 1,
		),
		'anim_gpu'         => array(
			'type'    => 'toggle',
			'label'   => __( 'GPU acceleration hints', 'appyn-pro' ),
			'default' => 1,
		),
		'lazy_images'      => array(
			'type'    => 'toggle',
			'label'   => __( 'Lazy load images added by this theme', 'appyn-pro' ),
			'default' => 1,
		),
	);
}

/* ---------------------------------------------------------------------------
 * Tab 11 - Dark mode
 * ------------------------------------------------------------------------ */

/**
 * Dark palette. Every token here overrides its light counterpart.
 *
 * @return array
 */
function apx_schema_dark() {
	return array(
		'dark_enable'      => array(
			'type'    => 'toggle',
			'section' => __( 'Switch', 'appyn-pro' ),
			'label'   => __( 'Enable dark mode', 'appyn-pro' ),
			'default' => 1,
		),
		'dark_default'     => array(
			'type'    => 'select',
			'label'   => __( 'Default mode', 'appyn-pro' ),
			'default' => 'light',
			'choices' => array(
				'light'  => __( 'Light', 'appyn-pro' ),
				'dark'   => __( 'Dark', 'appyn-pro' ),
				'system' => __( 'Follow the device', 'appyn-pro' ),
			),
		),
		'dark_toggle_style'=> array(
			'type'    => 'select',
			'label'   => __( 'Switch style', 'appyn-pro' ),
			'default' => 'switch',
			'choices' => array(
				'switch' => __( 'Switch', 'appyn-pro' ),
				'icon'   => __( 'Icon only', 'appyn-pro' ),
				'button' => __( 'Button', 'appyn-pro' ),
			),
		),
		'dark_toggle_position' => array(
			'type'    => 'select',
			'label'   => __( 'Switch position', 'appyn-pro' ),
			'default' => 'header',
			'choices' => array(
				'header'   => __( 'Header', 'appyn-pro' ),
				'floating' => __( 'Floating button', 'appyn-pro' ),
			),
		),
		'dark_transition'  => array(
			'type'    => 'slider',
			'label'   => __( 'Transition time', 'appyn-pro' ),
			'default' => 0.25,
			'min'     => 0,
			'max'     => 1,
			'step'    => 0.05,
			'unit'    => 's',
			'css_var' => '--apx-dark-speed',
		),
		'dark_logo'        => array(
			'type'    => 'image',
			'label'   => __( 'Dark mode logo', 'appyn-pro' ),
			'default' => '',
		),
		'dark_bg'          => array(
			'type'    => 'color',
			'section' => __( 'Dark palette', 'appyn-pro' ),
			'label'   => __( 'Page background', 'appyn-pro' ),
			'default' => '#0f0f1a',
			'css_var' => '--apx-bg',
			'scope'   => 'dark',
		),
		'dark_surface'     => array(
			'type'    => 'color',
			'label'   => __( 'Surface', 'appyn-pro' ),
			'default' => '#1a1a2e',
			'css_var' => '--apx-surface',
			'scope'   => 'dark',
		),
		'dark_surface_alt' => array(
			'type'    => 'color',
			'label'   => __( 'Raised surface', 'appyn-pro' ),
			'default' => '#252540',
			'css_var' => '--apx-surface-alt',
			'scope'   => 'dark',
		),
		'dark_card_bg'     => array(
			'type'    => 'color',
			'label'   => __( 'Card background', 'appyn-pro' ),
			'default' => '#1a1a2e',
			'css_var' => '--apx-card-bg',
			'scope'   => 'dark',
		),
		'dark_card_hover_bg' => array(
			'type'    => 'color',
			'label'   => __( 'Card hover background', 'appyn-pro' ),
			'default' => '#20203a',
			'css_var' => '--apx-card-hover-bg',
			'scope'   => 'dark',
		),
		'dark_header_bg'   => array(
			'type'    => 'color',
			'label'   => __( 'Header background', 'appyn-pro' ),
			'default' => '#141426',
			'css_var' => '--apx-header-bg',
			'scope'   => 'dark',
		),
		'dark_sticky_bg'   => array(
			'type'    => 'colora',
			'label'   => __( 'Sticky header background', 'appyn-pro' ),
			'default' => array(
				'color'   => '#141426',
				'opacity' => 0.9,
			),
			'css_var' => '--apx-sticky-bg',
			'scope'   => 'dark',
		),
		'dark_footer_bg'   => array(
			'type'    => 'color',
			'label'   => __( 'Footer background', 'appyn-pro' ),
			'default' => '#141426',
			'css_var' => '--apx-footer-bg',
			'scope'   => 'dark',
		),
		'dark_bottom_bg'   => array(
			'type'    => 'color',
			'label'   => __( 'Footer bottom bar', 'appyn-pro' ),
			'default' => '#101020',
			'css_var' => '--apx-bottom-bg',
			'scope'   => 'dark',
		),
		'dark_box_bg'      => array(
			'type'    => 'color',
			'label'   => __( 'Content box background', 'appyn-pro' ),
			'default' => '#1a1a2e',
			'css_var' => '--apx-box-bg',
			'scope'   => 'dark',
		),
		'dark_info_bg'     => array(
			'type'    => 'color',
			'label'   => __( 'Info bar background', 'appyn-pro' ),
			'default' => '#1a1a2e',
			'css_var' => '--apx-info-bg',
			'scope'   => 'dark',
		),
		'dark_text'        => array(
			'type'    => 'color',
			'label'   => __( 'Primary text', 'appyn-pro' ),
			'default' => '#ffffff',
			'css_var' => '--apx-text',
			'scope'   => 'dark',
		),
		'dark_text_2'      => array(
			'type'    => 'color',
			'label'   => __( 'Secondary text', 'appyn-pro' ),
			'default' => '#a0a0b0',
			'css_var' => '--apx-text-2',
			'scope'   => 'dark',
		),
		'dark_border'      => array(
			'type'    => 'color',
			'label'   => __( 'Borders', 'appyn-pro' ),
			'default' => '#2b2b45',
			'css_var' => '--apx-border',
			'scope'   => 'dark',
		),
		'dark_primary'     => array(
			'type'    => 'color',
			'label'   => __( 'Primary colour', 'appyn-pro' ),
			'default' => '#5DD662',
			'css_var' => '--apx-primary',
			'scope'   => 'dark',
		),
		'dark_secondary'   => array(
			'type'    => 'color',
			'label'   => __( 'Secondary colour', 'appyn-pro' ),
			'default' => '#FFB74D',
			'css_var' => '--apx-secondary',
			'scope'   => 'dark',
		),
		'dark_link'        => array(
			'type'    => 'color',
			'label'   => __( 'Link colour', 'appyn-pro' ),
			'default' => '#64B5F6',
			'css_var' => '--apx-link',
			'scope'   => 'dark',
		),
		'dark_input_bg'    => array(
			'type'    => 'color',
			'label'   => __( 'Input background', 'appyn-pro' ),
			'default' => '#20203a',
			'css_var' => '--apx-input-bg',
			'scope'   => 'dark',
		),
		'dark_card_title'  => array(
			'type'    => 'color',
			'label'   => __( 'Card title colour', 'appyn-pro' ),
			'default' => '#f3f4f6',
			'css_var' => '--apx-card-title-color',
			'scope'   => 'dark',
		),
		'dark_news_bg'     => array(
			'type'    => 'color',
			'label'   => __( 'News card background', 'appyn-pro' ),
			'default' => '#1a1a2e',
			'css_var' => '--apx-news-bg',
			'scope'   => 'dark',
		),
		'dark_news_title'  => array(
			'type'    => 'color',
			'label'   => __( 'News title colour', 'appyn-pro' ),
			'default' => '#f3f4f6',
			'css_var' => '--apx-news-title-color',
			'scope'   => 'dark',
		),
		'dark_section_title' => array(
			'type'    => 'color',
			'label'   => __( 'Section title colour', 'appyn-pro' ),
			'default' => '#f3f4f6',
			'css_var' => '--apx-section-title-color',
			'scope'   => 'dark',
		),
		'dark_footer_text' => array(
			'type'    => 'color',
			'label'   => __( 'Footer text', 'appyn-pro' ),
			'default' => '#a0a0b0',
			'css_var' => '--apx-footer-text',
			'scope'   => 'dark',
		),
		'dark_footer_link' => array(
			'type'    => 'color',
			'label'   => __( 'Footer link', 'appyn-pro' ),
			'default' => '#cbd5e1',
			'css_var' => '--apx-footer-link',
			'scope'   => 'dark',
		),
		'dark_footer_title'=> array(
			'type'    => 'color',
			'label'   => __( 'Footer column title', 'appyn-pro' ),
			'default' => '#ffffff',
			'css_var' => '--apx-footer-title-color',
			'scope'   => 'dark',
		),
		'dark_skeleton'    => array(
			'type'    => 'color',
			'label'   => __( 'Skeleton colour', 'appyn-pro' ),
			'default' => '#20203a',
			'css_var' => '--apx-skeleton',
			'scope'   => 'dark',
		),
		'dark_tag_bg'      => array(
			'type'    => 'color',
			'label'   => __( 'Tag background', 'appyn-pro' ),
			'default' => '#252540',
			'css_var' => '--apx-tag-bg',
			'scope'   => 'dark',
		),
		'dark_tag_color'   => array(
			'type'    => 'color',
			'label'   => __( 'Tag text', 'appyn-pro' ),
			'default' => '#a0a0b0',
			'css_var' => '--apx-tag-color',
			'scope'   => 'dark',
		),
		'dark_cat_hover_bg'=> array(
			'type'    => 'color',
			'label'   => __( 'Category hover background', 'appyn-pro' ),
			'default' => '#252540',
			'css_var' => '--apx-cat-hover-bg',
			'scope'   => 'dark',
		),
		'dark_cat_section_bg' => array(
			'type'    => 'color',
			'label'   => __( 'Category section background', 'appyn-pro' ),
			'default' => '#1a1a2e',
			'css_var' => '--apx-cat-section-bg',
			'scope'   => 'dark',
		),
		'dark_cat_label'   => array(
			'type'    => 'color',
			'label'   => __( 'Category label colour', 'appyn-pro' ),
			'default' => '#cbd5e1',
			'css_var' => '--apx-cat-label-color',
			'scope'   => 'dark',
		),
		'dark_mobile_menu_bg' => array(
			'type'    => 'color',
			'label'   => __( 'Mobile menu background', 'appyn-pro' ),
			'default' => '#141426',
			'css_var' => '--apx-mobile-menu-bg',
			'scope'   => 'dark',
		),
		'dark_burger'      => array(
			'type'    => 'color',
			'label'   => __( 'Hamburger colour', 'appyn-pro' ),
			'default' => '#f3f4f6',
			'css_var' => '--apx-burger-color',
			'scope'   => 'dark',
		),
		'dark_header_text' => array(
			'type'    => 'color',
			'label'   => __( 'Header text', 'appyn-pro' ),
			'default' => '#f3f4f6',
			'css_var' => '--apx-header-text',
			'scope'   => 'dark',
		),
		'dark_shadow_color'=> array(
			'type'    => 'colora',
			'label'   => __( 'Shadow colour', 'appyn-pro' ),
			'default' => array(
				'color'   => '#000000',
				'opacity' => 0.55,
			),
			'css_var' => '--apx-shadow-color',
			'scope'   => 'dark',
		),
	);
}

/* ---------------------------------------------------------------------------
 * Tab 12 - Responsive
 * ------------------------------------------------------------------------ */

/**
 * Breakpoints and per-device tweaks.
 *
 * @return array
 */
function apx_schema_responsive() {
	return array(
		'bp_desktop'       => array(
			'type'    => 'slider',
			'section' => __( 'Breakpoints', 'appyn-pro' ),
			'label'   => __( 'Desktop starts at', 'appyn-pro' ),
			'default' => 1200,
			'min'     => 992,
			'max'     => 1600,
			'step'    => 10,
			'unit'    => 'px',
		),
		'bp_tablet'        => array(
			'type'    => 'slider',
			'label'   => __( 'Tablet starts at', 'appyn-pro' ),
			'default' => 768,
			'min'     => 600,
			'max'     => 1024,
			'step'    => 10,
			'unit'    => 'px',
		),
		'tablet_font_scale'=> array(
			'type'    => 'slider',
			'section' => __( 'Tablet', 'appyn-pro' ),
			'label'   => __( 'Font scale', 'appyn-pro' ),
			'default' => 100,
			'min'     => 80,
			'max'     => 120,
			'unit'    => '%',
		),
		'mobile_font_scale'=> array(
			'type'    => 'slider',
			'section' => __( 'Mobile', 'appyn-pro' ),
			'label'   => __( 'Font scale', 'appyn-pro' ),
			'default' => 95,
			'min'     => 75,
			'max'     => 120,
			'unit'    => '%',
		),
		'touch_target'     => array(
			'type'    => 'slider',
			'label'   => __( 'Minimum touch target', 'appyn-pro' ),
			'default' => 44,
			'min'     => 32,
			'max'     => 64,
			'unit'    => 'px',
			'css_var' => '--apx-touch',
		),
		'mobile_container_padding' => array(
			'type'    => 'slider',
			'label'   => __( 'Side padding', 'appyn-pro' ),
			'default' => 12,
			'min'     => 0,
			'max'     => 40,
			'unit'    => 'px',
			'css_var' => '--apx-container-padding',
			'media'   => 'mobile',
		),
		'mobile_disable_anim' => array(
			'type'    => 'toggle',
			'label'   => __( 'Disable animations on phones', 'appyn-pro' ),
			'default' => 0,
		),
		'mobile_simplify'  => array(
			'type'    => 'toggle',
			'label'   => __( 'Simplify layout on phones', 'appyn-pro' ),
			'desc'    => __( 'Hides decorative shadows, gradients and the hero overlay text on small screens.', 'appyn-pro' ),
			'default' => 0,
		),
	);
}

/* ---------------------------------------------------------------------------
 * Tab 13 - Custom code
 * ------------------------------------------------------------------------ */

/**
 * Custom CSS and JS boxes.
 *
 * @return array
 */
function apx_schema_custom_code() {
	return array(
		'css_global'  => array(
			'type'    => 'code',
			'section' => __( 'Custom CSS', 'appyn-pro' ),
			'label'   => __( 'Global CSS', 'appyn-pro' ),
			'default' => '',
		),
		'css_desktop' => array(
			'type'    => 'code',
			'label'   => __( 'Desktop only CSS', 'appyn-pro' ),
			'default' => '',
		),
		'css_tablet'  => array(
			'type'    => 'code',
			'label'   => __( 'Tablet only CSS', 'appyn-pro' ),
			'default' => '',
		),
		'css_mobile'  => array(
			'type'    => 'code',
			'label'   => __( 'Mobile only CSS', 'appyn-pro' ),
			'default' => '',
		),
		'css_header'  => array(
			'type'    => 'code',
			'label'   => __( 'Header CSS', 'appyn-pro' ),
			'default' => '',
		),
		'css_footer'  => array(
			'type'    => 'code',
			'label'   => __( 'Footer CSS', 'appyn-pro' ),
			'default' => '',
		),
		'js_head'     => array(
			'type'    => 'code',
			'section' => __( 'Custom JavaScript', 'appyn-pro' ),
			'label'   => __( 'Scripts in <head>', 'appyn-pro' ),
			'desc'    => __( 'Plain JavaScript, no <script> tags. Only administrators can edit this.', 'appyn-pro' ),
			'default' => '',
		),
		'js_footer'   => array(
			'type'    => 'code',
			'label'   => __( 'Scripts before </body>', 'appyn-pro' ),
			'default' => '',
		),
	);
}

/* ---------------------------------------------------------------------------
 * Tab 14 - Tools
 * ------------------------------------------------------------------------ */

/**
 * Import, export, presets and reset. Rendered by the admin class.
 *
 * @return array
 */
function apx_schema_tools() {
	return array(
		'tools_notice' => array(
			'type'    => 'info',
			'section' => __( 'Presets & backups', 'appyn-pro' ),
			'label'   => __( 'Save, load and move your design', 'appyn-pro' ),
			'desc'    => __( 'Export writes every setting to a JSON file. Import reads one back. Presets store named copies inside this site so you can switch looks in one click.', 'appyn-pro' ),
			'default' => '',
		),
	);
}
