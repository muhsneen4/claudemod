<?php
/**
 * The Appyn Pro theme options panel.
 *
 * Renders the schema as a tabbed admin screen and handles saving, importing,
 * exporting, presets and resetting.
 *
 * @package Appyn_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin panel.
 */
class APX_Admin {

	/**
	 * Menu slug.
	 */
	const SLUG = 'appyn-pro';

	/**
	 * Hook in.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		add_action( 'admin_post_apx_save', array( $this, 'handle_save' ) );
		add_action( 'admin_post_apx_tools', array( $this, 'handle_tools' ) );
	}

	/**
	 * Register the menu pages.
	 *
	 * @return void
	 */
	public function menu() {
		add_menu_page(
			__( 'Appyn Pro', 'appyn-pro' ),
			__( 'Appyn Pro', 'appyn-pro' ),
			'edit_theme_options',
			self::SLUG,
			array( $this, 'render' ),
			'dashicons-admin-customizer',
			59
		);

		add_theme_page(
			__( 'Appyn Pro options', 'appyn-pro' ),
			__( 'Appyn Pro options', 'appyn-pro' ),
			'edit_theme_options',
			self::SLUG,
			array( $this, 'render' )
		);
	}

	/**
	 * Panel assets.
	 *
	 * @param string $hook Current admin page.
	 * @return void
	 */
	public function assets( $hook ) {
		if ( false === strpos( $hook, self::SLUG ) ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style( 'apx-admin', APX_URI . '/assets/admin/css/admin.css', array( 'wp-color-picker' ), APX_VERSION );

		$editor = wp_enqueue_code_editor( array( 'type' => 'text/css' ) );

		wp_enqueue_script(
			'apx-admin',
			APX_URI . '/assets/admin/js/admin.js',
			array( 'jquery', 'wp-color-picker', 'jquery-ui-sortable' ),
			APX_VERSION,
			true
		);

		wp_localize_script(
			'apx-admin',
			'APX_ADMIN',
			array(
				'icons'      => apx_icon_list(),
				'codeEditor' => ( false === $editor ) ? null : $editor,
				'i18n'       => array(
					'confirmDelete' => __( 'Remove this item?', 'appyn-pro' ),
					'confirmReset'  => __( 'Reset every Appyn Pro setting back to its default? This cannot be undone.', 'appyn-pro' ),
					'search'        => __( 'Search icons…', 'appyn-pro' ),
					'row'           => __( 'Item', 'appyn-pro' ),
				),
			)
		);
	}

	/**
	 * Save the panel.
	 *
	 * @return void
	 */
	public function handle_save() {
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to edit theme options.', 'appyn-pro' ) );
		}

		check_admin_referer( 'apx_save', 'apx_nonce' );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nonce checked above, values sanitised per field by APX_Settings::sanitize().
		$raw = isset( $_POST['apx'] ) ? wp_unslash( $_POST['apx'] ) : array();

		if ( ! is_array( $raw ) ) {
			$raw = array();
		}

		APX_Settings::save( APX_Settings::sanitize( $raw ) );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$tab = isset( $_POST['apx_tab'] ) ? sanitize_key( wp_unslash( $_POST['apx_tab'] ) ) : 'global';

		wp_safe_redirect( $this->panel_url( $tab, array( 'saved' => 1 ) ) );
		exit;
	}

	/**
	 * Import, export, presets and reset.
	 *
	 * @return void
	 */
	public function handle_tools() {
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to edit theme options.', 'appyn-pro' ) );
		}

		check_admin_referer( 'apx_tools', 'apx_nonce' );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$action = isset( $_POST['apx_action'] ) ? sanitize_key( wp_unslash( $_POST['apx_action'] ) ) : '';

		switch ( $action ) {
			case 'export':
				$json = APX_Settings::export_json();

				nocache_headers();
				header( 'Content-Type: application/json; charset=utf-8' );
				header( 'Content-Disposition: attachment; filename=appyn-pro-settings-' . gmdate( 'Y-m-d' ) . '.json' );
				header( 'Content-Length: ' . strlen( $json ) );

				echo $json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON download.
				exit;

			case 'import':
				if ( empty( $_FILES['apx_file']['tmp_name'] ) ) {
					$this->redirect_tools( 'error', __( 'Choose a JSON file first.', 'appyn-pro' ) );
				}

				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- file path from PHP upload handling.
				$contents = file_get_contents( $_FILES['apx_file']['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- reading a local upload.
				$result   = APX_Settings::import_json( $contents );

				if ( is_wp_error( $result ) ) {
					$this->redirect_tools( 'error', $result->get_error_message() );
				}

				$this->redirect_tools( 'ok', __( 'Settings imported.', 'appyn-pro' ) );
				break;

			case 'preset_save':
				// phpcs:ignore WordPress.Security.NonceVerification.Missing
				$name = isset( $_POST['apx_preset_name'] ) ? sanitize_text_field( wp_unslash( $_POST['apx_preset_name'] ) ) : '';

				if ( '' === $name ) {
					$this->redirect_tools( 'error', __( 'Give the preset a name.', 'appyn-pro' ) );
				}

				APX_Settings::save_preset( $name );
				$this->redirect_tools( 'ok', __( 'Preset saved.', 'appyn-pro' ) );
				break;

			case 'preset_load':
				// phpcs:ignore WordPress.Security.NonceVerification.Missing
				$name = isset( $_POST['apx_preset'] ) ? sanitize_text_field( wp_unslash( $_POST['apx_preset'] ) ) : '';

				if ( ! APX_Settings::load_preset( $name ) ) {
					$this->redirect_tools( 'error', __( 'That preset no longer exists.', 'appyn-pro' ) );
				}

				$this->redirect_tools( 'ok', __( 'Preset loaded.', 'appyn-pro' ) );
				break;

			case 'preset_delete':
				// phpcs:ignore WordPress.Security.NonceVerification.Missing
				$name = isset( $_POST['apx_preset'] ) ? sanitize_text_field( wp_unslash( $_POST['apx_preset'] ) ) : '';

				APX_Settings::delete_preset( $name );
				$this->redirect_tools( 'ok', __( 'Preset deleted.', 'appyn-pro' ) );
				break;

			case 'reset':
				APX_Settings::reset();
				$this->redirect_tools( 'ok', __( 'Everything is back to the defaults.', 'appyn-pro' ) );
				break;
		}

		$this->redirect_tools( 'error', __( 'Nothing to do.', 'appyn-pro' ) );
	}

	/**
	 * Send the user back to the tools tab with a message.
	 *
	 * @param string $type    ok|error.
	 * @param string $message Message.
	 * @return void
	 */
	protected function redirect_tools( $type, $message ) {
		wp_safe_redirect(
			$this->panel_url(
				'tools',
				array(
					'apx_msg'  => rawurlencode( $message ),
					'apx_type' => $type,
				)
			)
		);
		exit;
	}

	/**
	 * URL of a panel tab.
	 *
	 * @param string $tab  Tab key.
	 * @param array  $args Extra query args.
	 * @return string
	 */
	protected function panel_url( $tab = 'global', $args = array() ) {
		return add_query_arg(
			array_merge(
				array(
					'page' => self::SLUG,
					'tab'  => $tab,
				),
				$args
			),
			admin_url( 'admin.php' )
		);
	}

	/* ---------------------------------------------------------------- *
	 * Rendering
	 * ---------------------------------------------------------------- */

	/**
	 * The panel screen.
	 *
	 * @return void
	 */
	public function render() {
		$schema = apx_settings_schema();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'global';

		if ( ! isset( $schema[ $current ] ) ) {
			$current = 'global';
		}
		?>
		<div class="wrap apx-wrap">
			<div class="apx-topbar">
				<div class="apx-topbar__brand">
					<span class="dashicons dashicons-admin-customizer"></span>
					<div>
						<h1><?php esc_html_e( 'Appyn Pro', 'appyn-pro' ); ?></h1>
						<p><?php esc_html_e( 'Every colour, size, font and animation of your APK site, in one place.', 'appyn-pro' ); ?></p>
					</div>
				</div>
				<div class="apx-topbar__actions">
					<a class="button" href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank"><?php esc_html_e( 'View site', 'appyn-pro' ); ?></a>
					<a class="button" href="<?php echo esc_url( admin_url( 'customize.php' ) ); ?>"><?php esc_html_e( 'Live preview', 'appyn-pro' ); ?></a>
					<button type="submit" form="apx-form" class="button button-primary"><?php esc_html_e( 'Save changes', 'appyn-pro' ); ?></button>
				</div>
			</div>

			<?php $this->notices(); ?>

			<div class="apx-layout">
				<ul class="apx-tabs">
					<?php foreach ( $schema as $key => $tab ) : ?>
						<li>
							<a href="<?php echo esc_url( $this->panel_url( $key ) ); ?>" class="<?php echo ( $key === $current ) ? 'is-active' : ''; ?>">
								<span class="dashicons <?php echo esc_attr( $tab['icon'] ); ?>"></span>
								<?php echo esc_html( $tab['label'] ); ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>

				<div class="apx-panel">
					<?php if ( 'tools' === $current ) : ?>
						<?php $this->render_tools(); ?>
					<?php else : ?>
						<form id="apx-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<input type="hidden" name="action" value="apx_save">
							<input type="hidden" name="apx_tab" value="<?php echo esc_attr( $current ); ?>">
							<?php wp_nonce_field( 'apx_save', 'apx_nonce' ); ?>

							<?php $this->render_fields( $schema[ $current ]['fields'] ); ?>

							<p class="apx-submit">
								<button type="submit" class="button button-primary button-hero"><?php esc_html_e( 'Save changes', 'appyn-pro' ); ?></button>
							</p>
						</form>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Success / error messages.
	 *
	 * @return void
	 */
	protected function notices() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['saved'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'appyn-pro' ) . '</p></div>';
		}

		if ( isset( $_GET['apx_msg'] ) ) {
			$type  = ( isset( $_GET['apx_type'] ) && 'error' === $_GET['apx_type'] ) ? 'error' : 'success';
			$msg   = sanitize_text_field( rawurldecode( wp_unslash( $_GET['apx_msg'] ) ) );

			echo '<div class="notice notice-' . esc_attr( $type ) . ' is-dismissible"><p>' . esc_html( $msg ) . '</p></div>';
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Render every field of a tab, grouped into section cards.
	 *
	 * @param array $fields Field definitions.
	 * @return void
	 */
	protected function render_fields( $fields ) {
		$open = false;

		foreach ( $fields as $key => $field ) {
			if ( ! empty( $field['section'] ) ) {
				if ( $open ) {
					echo '</div></div>';
				}

				echo '<div class="apx-card"><h2 class="apx-card__title">' . esc_html( $field['section'] ) . '</h2><div class="apx-card__body">';
				$open = true;
			}

			if ( ! $open ) {
				echo '<div class="apx-card"><div class="apx-card__body">';
				$open = true;
			}

			$this->render_field( $key, $field );
		}

		if ( $open ) {
			echo '</div></div>';
		}
	}

	/**
	 * One field row.
	 *
	 * @param string $key   Field key.
	 * @param array  $field Field definition.
	 * @return void
	 */
	protected function render_field( $key, $field ) {
		$value = APX_Settings::get( $key );
		$name  = 'apx[' . $key . ']';
		$type  = isset( $field['type'] ) ? $field['type'] : 'text';
		?>
		<div class="apx-field apx-field--<?php echo esc_attr( $type ); ?>" data-key="<?php echo esc_attr( $key ); ?>">
			<div class="apx-field__label">
				<label for="apx-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $field['label'] ); ?></label>
				<?php if ( ! empty( $field['desc'] ) ) : ?>
					<p class="apx-field__desc"><?php echo esc_html( $field['desc'] ); ?></p>
				<?php endif; ?>
			</div>
			<div class="apx-field__control">
				<?php $this->control( $key, $name, $field, $value ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the input for a field type.
	 *
	 * @param string $key   Field key (for ids).
	 * @param string $name  Input name.
	 * @param array  $field Field definition.
	 * @param mixed  $value Current value.
	 * @return void
	 */
	protected function control( $key, $name, $field, $value ) {
		$type = isset( $field['type'] ) ? $field['type'] : 'text';
		$id   = 'apx-' . preg_replace( '/[^a-z0-9_\-]/i', '-', $key );

		switch ( $type ) {
			case 'info':
				echo '<p class="apx-info">' . esc_html( isset( $field['desc'] ) ? $field['desc'] : '' ) . '</p>';
				break;

			case 'color':
				printf(
					'<input type="text" class="apx-color" id="%1$s" name="%2$s" value="%3$s" data-default-color="%4$s">',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( $value ),
					esc_attr( isset( $field['default'] ) ? $field['default'] : '' )
				);
				break;

			case 'colora':
				$value = is_array( $value ) ? $value : array(
					'color'   => '#000000',
					'opacity' => 1,
				);
				printf(
					'<span class="apx-inline"><input type="text" class="apx-color" name="%1$s[color]" value="%2$s"><label class="apx-mini">%3$s<input type="range" min="0" max="1" step="0.01" name="%1$s[opacity]" value="%4$s" class="apx-range" data-suffix=""><output>%4$s</output></label></span>',
					esc_attr( $name ),
					esc_attr( $value['color'] ),
					esc_html__( 'Opacity', 'appyn-pro' ),
					esc_attr( $value['opacity'] )
				);
				break;

			case 'slider':
				$min  = isset( $field['min'] ) ? $field['min'] : 0;
				$max  = isset( $field['max'] ) ? $field['max'] : 100;
				$step = isset( $field['step'] ) ? $field['step'] : 1;
				$unit = isset( $field['unit'] ) ? $field['unit'] : '';

				printf(
					'<span class="apx-slider"><input type="range" id="%1$s" name="%2$s" value="%3$s" min="%4$s" max="%5$s" step="%6$s" class="apx-range"><input type="number" class="apx-number" name="%2$s" value="%3$s" min="%4$s" max="%5$s" step="%6$s"><span class="apx-unit">%7$s</span></span>',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( $value ),
					esc_attr( $min ),
					esc_attr( $max ),
					esc_attr( $step ),
					esc_html( $unit )
				);
				break;

			case 'toggle':
				printf(
					'<label class="apx-toggle"><input type="hidden" name="%1$s" value="0"><input type="checkbox" id="%2$s" name="%1$s" value="1" %3$s><span class="apx-toggle__track"><span class="apx-toggle__knob"></span></span></label>',
					esc_attr( $name ),
					esc_attr( $id ),
					checked( (bool) $value, true, false )
				);
				break;

			case 'select':
				echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '">';

				foreach ( APX_Settings::choices( $field ) as $option => $label ) {
					printf(
						'<option value="%1$s" %2$s>%3$s</option>',
						esc_attr( $option ),
						selected( (string) $value, (string) $option, false ),
						esc_html( $label )
					);
				}

				echo '</select>';
				break;

			case 'font':
				echo '<select class="apx-font" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '">';

				foreach ( apx_google_fonts() as $family => $label ) {
					printf(
						'<option value="%1$s" %2$s>%3$s</option>',
						esc_attr( $family ),
						selected( (string) $value, (string) $family, false ),
						esc_html( $label )
					);
				}

				echo '</select>';
				break;

			case 'icon':
				printf(
					'<span class="apx-iconpick"><button type="button" class="button apx-iconpick__btn"><i class="%1$s"></i><span>%2$s</span></button><input type="text" class="apx-iconpick__input" id="%3$s" name="%4$s" value="%1$s" placeholder="fas fa-star"></span>',
					esc_attr( $value ),
					esc_html__( 'Pick', 'appyn-pro' ),
					esc_attr( $id ),
					esc_attr( $name )
				);
				break;

			case 'image':
				printf(
					'<span class="apx-image"><span class="apx-image__preview">%1$s</span><input type="text" class="apx-image__input" id="%2$s" name="%3$s" value="%4$s"><button type="button" class="button apx-image__pick">%5$s</button><button type="button" class="button apx-image__clear">%6$s</button></span>',
					$value ? '<img src="' . esc_url( $value ) . '" alt="">' : '',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( $value ),
					esc_html__( 'Choose', 'appyn-pro' ),
					esc_html__( 'Clear', 'appyn-pro' )
				);
				break;

			case 'textarea':
				printf(
					'<textarea id="%1$s" name="%2$s" rows="4">%3$s</textarea>',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_textarea( $value )
				);
				break;

			case 'editor':
				wp_editor(
					(string) $value,
					$id,
					array(
						'textarea_name' => $name,
						'textarea_rows' => 6,
						'media_buttons' => false,
						'teeny'         => true,
					)
				);
				break;

			case 'code':
				printf(
					'<textarea id="%1$s" name="%2$s" rows="8" class="apx-code" spellcheck="false">%3$s</textarea>',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_textarea( $value )
				);
				break;

			case 'spacing':
				$value = is_array( $value ) ? $value : array();

				echo '<span class="apx-spacing">';

				foreach ( array(
					'top'    => __( 'Top', 'appyn-pro' ),
					'right'  => __( 'Right', 'appyn-pro' ),
					'bottom' => __( 'Bottom', 'appyn-pro' ),
					'left'   => __( 'Left', 'appyn-pro' ),
				) as $side => $label ) {
					printf(
						'<label>%1$s<input type="number" name="%2$s[%3$s]" value="%4$s"></label>',
						esc_html( $label ),
						esc_attr( $name ),
						esc_attr( $side ),
						esc_attr( isset( $value[ $side ] ) ? $value[ $side ] : 0 )
					);
				}

				echo '</span>';
				break;

			case 'border':
				$value  = is_array( $value ) ? $value : array();
				$styles = array( 'solid', 'dashed', 'dotted', 'double', 'none' );

				echo '<span class="apx-inline">';
				printf(
					'<label class="apx-mini">%1$s<input type="number" min="0" max="20" name="%2$s[width]" value="%3$s"></label>',
					esc_html__( 'Width', 'appyn-pro' ),
					esc_attr( $name ),
					esc_attr( isset( $value['width'] ) ? $value['width'] : 0 )
				);

				echo '<label class="apx-mini">' . esc_html__( 'Style', 'appyn-pro' ) . '<select name="' . esc_attr( $name ) . '[style]">';

				foreach ( $styles as $style ) {
					printf(
						'<option value="%1$s" %2$s>%1$s</option>',
						esc_attr( $style ),
						selected( isset( $value['style'] ) ? $value['style'] : 'solid', $style, false )
					);
				}

				echo '</select></label>';

				printf(
					'<input type="text" class="apx-color" name="%1$s[color]" value="%2$s">',
					esc_attr( $name ),
					esc_attr( isset( $value['color'] ) ? $value['color'] : '#000000' )
				);
				echo '</span>';
				break;

			case 'shadow':
				$value = is_array( $value ) ? $value : array();

				echo '<span class="apx-inline">';
				printf(
					'<label class="apx-mini apx-mini--check"><input type="hidden" name="%1$s[enable]" value="0"><input type="checkbox" name="%1$s[enable]" value="1" %2$s>%3$s</label>',
					esc_attr( $name ),
					checked( ! empty( $value['enable'] ), true, false ),
					esc_html__( 'On', 'appyn-pro' )
				);

				foreach ( array(
					'x'      => __( 'X', 'appyn-pro' ),
					'y'      => __( 'Y', 'appyn-pro' ),
					'blur'   => __( 'Blur', 'appyn-pro' ),
					'spread' => __( 'Spread', 'appyn-pro' ),
				) as $part => $label ) {
					printf(
						'<label class="apx-mini">%1$s<input type="number" name="%2$s[%3$s]" value="%4$s"></label>',
						esc_html( $label ),
						esc_attr( $name ),
						esc_attr( $part ),
						esc_attr( isset( $value[ $part ] ) ? $value[ $part ] : 0 )
					);
				}

				printf(
					'<input type="text" class="apx-color" name="%1$s[color]" value="%2$s">',
					esc_attr( $name ),
					esc_attr( isset( $value['color'] ) ? $value['color'] : '#00000022' )
				);
				echo '</span>';
				break;

			case 'gradient':
				$value = is_array( $value ) ? $value : array();

				echo '<span class="apx-inline">';
				printf(
					'<label class="apx-mini apx-mini--check"><input type="hidden" name="%1$s[enable]" value="0"><input type="checkbox" name="%1$s[enable]" value="1" %2$s>%3$s</label>',
					esc_attr( $name ),
					checked( ! empty( $value['enable'] ), true, false ),
					esc_html__( 'On', 'appyn-pro' )
				);

				printf(
					'<label class="apx-mini">%1$s<input type="number" min="0" max="360" name="%2$s[angle]" value="%3$s"></label>',
					esc_html__( 'Angle', 'appyn-pro' ),
					esc_attr( $name ),
					esc_attr( isset( $value['angle'] ) ? $value['angle'] : 90 )
				);

				printf(
					'<input type="text" class="apx-color" name="%1$s[from]" value="%2$s"><input type="text" class="apx-color" name="%1$s[to]" value="%3$s">',
					esc_attr( $name ),
					esc_attr( isset( $value['from'] ) ? $value['from'] : '#000000' ),
					esc_attr( isset( $value['to'] ) ? $value['to'] : '#00000000' )
				);
				echo '</span>';
				break;

			case 'repeater':
				$this->repeater( $key, $name, $field, $value );
				break;

			case 'text':
			default:
				printf(
					'<input type="text" id="%1$s" name="%2$s" value="%3$s" class="regular-text">',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( $value )
				);
				break;
		}
	}

	/**
	 * Sortable repeater with an inert template row for the "add" button.
	 *
	 * @param string $key   Field key.
	 * @param string $name  Input name.
	 * @param array  $field Field definition.
	 * @param mixed  $rows  Stored rows.
	 * @return void
	 */
	protected function repeater( $key, $name, $field, $rows ) {
		$rows = is_array( $rows ) ? array_values( $rows ) : array();
		?>
		<div class="apx-rep" data-name="<?php echo esc_attr( $name ); ?>">
			<div class="apx-rep__rows">
				<?php foreach ( $rows as $index => $row ) : ?>
					<?php $this->repeater_row( $key, $name, $field, $row, $index ); ?>
				<?php endforeach; ?>
			</div>

			<script type="text/html" class="apx-rep__tpl">
				<?php $this->repeater_row( $key, $name, $field, array(), '__i__' ); ?>
			</script>

			<button type="button" class="button apx-rep__add"><span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Add item', 'appyn-pro' ); ?></button>
		</div>
		<?php
	}

	/**
	 * One repeater row.
	 *
	 * @param string     $key   Field key.
	 * @param string     $name  Parent input name.
	 * @param array      $field Field definition.
	 * @param array      $row   Row values.
	 * @param string|int $index Row index or the template placeholder.
	 * @return void
	 */
	protected function repeater_row( $key, $name, $field, $row, $index ) {
		$sub   = isset( $field['fields'] ) ? $field['fields'] : array();
		$title = '';

		foreach ( array( 'label', 'name', 'title' ) as $candidate ) {
			if ( ! empty( $row[ $candidate ] ) ) {
				$title = $row[ $candidate ];
				break;
			}
		}
		?>
		<div class="apx-rep__row">
			<div class="apx-rep__head">
				<span class="apx-rep__handle dashicons dashicons-menu"></span>
				<strong class="apx-rep__title"><?php echo esc_html( $title ? $title : __( 'Item', 'appyn-pro' ) ); ?></strong>
				<button type="button" class="apx-rep__toggle button-link"><span class="dashicons dashicons-arrow-down-alt2"></span></button>
				<button type="button" class="apx-rep__del button-link"><span class="dashicons dashicons-trash"></span></button>
			</div>
			<div class="apx-rep__body">
				<?php
				foreach ( $sub as $sub_key => $sub_field ) {
					$sub_name  = $name . '[' . $index . '][' . $sub_key . ']';
					$sub_value = isset( $row[ $sub_key ] ) ? $row[ $sub_key ] : ( isset( $sub_field['default'] ) ? $sub_field['default'] : '' );
					?>
					<div class="apx-field apx-field--<?php echo esc_attr( $sub_field['type'] ); ?>">
						<div class="apx-field__label"><label><?php echo esc_html( $sub_field['label'] ); ?></label></div>
						<div class="apx-field__control">
							<?php $this->control( $key . '-' . $sub_key . '-' . $index, $sub_name, $sub_field, $sub_value ); ?>
						</div>
					</div>
					<?php
				}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * The import / export / presets tab.
	 *
	 * @return void
	 */
	protected function render_tools() {
		$presets = APX_Settings::presets();
		?>
		<div class="apx-card">
			<h2 class="apx-card__title"><?php esc_html_e( 'Export & import', 'appyn-pro' ); ?></h2>
			<div class="apx-card__body">
				<p class="apx-info"><?php esc_html_e( 'Export writes every setting to a JSON file you can keep as a backup or move to another site. Import reads one back and replaces the current design.', 'appyn-pro' ); ?></p>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="apx-toolform">
					<input type="hidden" name="action" value="apx_tools">
					<input type="hidden" name="apx_action" value="export">
					<?php wp_nonce_field( 'apx_tools', 'apx_nonce' ); ?>
					<button type="submit" class="button button-primary"><span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Export settings', 'appyn-pro' ); ?></button>
				</form>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" class="apx-toolform">
					<input type="hidden" name="action" value="apx_tools">
					<input type="hidden" name="apx_action" value="import">
					<?php wp_nonce_field( 'apx_tools', 'apx_nonce' ); ?>
					<input type="file" name="apx_file" accept="application/json,.json" required>
					<button type="submit" class="button"><span class="dashicons dashicons-upload"></span> <?php esc_html_e( 'Import settings', 'appyn-pro' ); ?></button>
				</form>
			</div>
		</div>

		<div class="apx-card">
			<h2 class="apx-card__title"><?php esc_html_e( 'Presets', 'appyn-pro' ); ?></h2>
			<div class="apx-card__body">
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="apx-toolform">
					<input type="hidden" name="action" value="apx_tools">
					<input type="hidden" name="apx_action" value="preset_save">
					<?php wp_nonce_field( 'apx_tools', 'apx_nonce' ); ?>
					<input type="text" name="apx_preset_name" placeholder="<?php esc_attr_e( 'Preset name', 'appyn-pro' ); ?>" required>
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Save current design as a preset', 'appyn-pro' ); ?></button>
				</form>

				<?php if ( $presets ) : ?>
					<table class="apx-presets widefat striped">
						<tbody>
							<?php foreach ( array_keys( $presets ) as $preset_name ) : ?>
								<tr>
									<td><strong><?php echo esc_html( $preset_name ); ?></strong></td>
									<td class="apx-presets__actions">
										<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
											<input type="hidden" name="action" value="apx_tools">
											<input type="hidden" name="apx_action" value="preset_load">
											<input type="hidden" name="apx_preset" value="<?php echo esc_attr( $preset_name ); ?>">
											<?php wp_nonce_field( 'apx_tools', 'apx_nonce' ); ?>
											<button type="submit" class="button"><?php esc_html_e( 'Load', 'appyn-pro' ); ?></button>
										</form>
										<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
											<input type="hidden" name="action" value="apx_tools">
											<input type="hidden" name="apx_action" value="preset_delete">
											<input type="hidden" name="apx_preset" value="<?php echo esc_attr( $preset_name ); ?>">
											<?php wp_nonce_field( 'apx_tools', 'apx_nonce' ); ?>
											<button type="submit" class="button button-link-delete"><?php esc_html_e( 'Delete', 'appyn-pro' ); ?></button>
										</form>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php else : ?>
					<p class="apx-info"><?php esc_html_e( 'No presets yet. Save one to switch between looks in a click.', 'appyn-pro' ); ?></p>
				<?php endif; ?>
			</div>
		</div>

		<div class="apx-card apx-card--danger">
			<h2 class="apx-card__title"><?php esc_html_e( 'Reset', 'appyn-pro' ); ?></h2>
			<div class="apx-card__body">
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="apx-toolform apx-reset">
					<input type="hidden" name="action" value="apx_tools">
					<input type="hidden" name="apx_action" value="reset">
					<?php wp_nonce_field( 'apx_tools', 'apx_nonce' ); ?>
					<p class="apx-info"><?php esc_html_e( 'Put every option back to its default value. Presets and exported files are kept.', 'appyn-pro' ); ?></p>
					<button type="submit" class="button button-link-delete"><?php esc_html_e( 'Reset all settings', 'appyn-pro' ); ?></button>
				</form>
			</div>
		</div>
		<?php
	}
}
