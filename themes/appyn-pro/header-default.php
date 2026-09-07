<?php
/**
 * Appyn Pro header.
 *
 * When "Use Appyn Pro header & footer" is off, the parent theme header runs
 * instead, so nothing breaks for people who only want the colour tokens.
 *
 * @package Appyn_Pro
 */

if ( ! apx_on( 'takeover' ) ) {
	load_template( get_template_directory() . '/header-default.php', false, isset( $args ) ? $args : array() );
	return;
}

$apx_logo      = apx_opt( 'header_logo' );
$apx_dark_logo = apx_opt( 'dark_logo' );
$apx_topbar    = apx_rows( 'topbar_links' );
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php
if ( function_exists( 'wp_body_open' ) ) {
	wp_body_open();
}
?>
<div class="wrapper-page">
	<div class="wrapper-inside">

		<?php if ( apx_on( 'topbar_enable' ) ) : ?>
			<div class="apx-topbar-strip">
				<div class="container">
					<ul>
						<?php foreach ( $apx_topbar as $apx_link ) : ?>
							<li>
								<a href="<?php echo esc_url( $apx_link['url'] ); ?>" style="color:<?php echo esc_attr( $apx_link['color'] ); ?>">
									<?php echo apx_icon( $apx_link['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- sanitised icon class. ?>
									<?php echo esc_html( $apx_link['label'] ); ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
					<?php if ( apx_on( 'dark_enable' ) && 'header' === apx_opt( 'dark_toggle_position' ) ) : ?>
						<?php echo APX_Blocks::dark_toggle( 'apx-dark--top' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside. ?>
					<?php endif; ?>
				</div>
			</div>
		<?php endif; ?>

		<header id="apx-header" class="apx-header">
			<div class="container apx-header__inner">
				<div class="apx-header__logo logo">
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
						<?php if ( $apx_logo ) : ?>
							<img src="<?php echo esc_url( $apx_logo ); ?>" alt="<?php bloginfo( 'name' ); ?>" class="apx-logo-light">
							<?php if ( $apx_dark_logo ) : ?>
								<img src="<?php echo esc_url( $apx_dark_logo ); ?>" alt="<?php bloginfo( 'name' ); ?>" class="apx-logo-dark">
							<?php endif; ?>
						<?php elseif ( function_exists( 'px_logo' ) ) : ?>
							<?php px_logo(); ?>
						<?php else : ?>
							<span class="apx-header__name"><?php bloginfo( 'name' ); ?></span>
						<?php endif; ?>
					</a>
				</div>

				<?php echo APX_Blocks::nav(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside. ?>

				<div class="apx-header__tools">
					<?php if ( apx_on( 'header_search' ) ) : ?>
						<button type="button" class="apx-iconbtn" data-apx-search aria-label="<?php esc_attr_e( 'Search', 'appyn-pro' ); ?>"><i class="fas fa-search" aria-hidden="true"></i></button>
					<?php endif; ?>

					<?php if ( apx_on( 'header_user_menu' ) ) : ?>
						<a class="apx-iconbtn" href="<?php echo esc_url( wp_login_url() ); ?>" aria-label="<?php esc_attr_e( 'Account', 'appyn-pro' ); ?>"><i class="fas fa-user" aria-hidden="true"></i></a>
					<?php endif; ?>

					<?php if ( apx_on( 'dark_enable' ) && apx_on( 'header_dark_toggle' ) && 'header' === apx_opt( 'dark_toggle_position' ) && ! apx_on( 'topbar_enable' ) ) : ?>
						<?php echo APX_Blocks::dark_toggle(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside. ?>
					<?php endif; ?>

					<button type="button" class="apx-iconbtn apx-burger" aria-expanded="false" aria-label="<?php esc_attr_e( 'Menu', 'appyn-pro' ); ?>">
						<span></span><span></span><span></span>
					</button>
				</div>
			</div>

			<?php if ( apx_on( 'header_search' ) ) : ?>
				<div class="apx-search">
					<div class="container">
						<form action="<?php echo esc_url( home_url( '/' ) ); ?>" role="search">
							<input type="text" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'Search apps and games…', 'appyn-pro' ); ?>" aria-label="<?php esc_attr_e( 'Search', 'appyn-pro' ); ?>">
							<button type="submit"><i class="fas fa-search" aria-hidden="true"></i></button>
						</form>
					</div>
				</div>
			<?php endif; ?>
		</header>

		<div class="apx-header-spacer" aria-hidden="true"></div>

		<div class="apx-drawer" id="apx-drawer">
			<div class="apx-drawer__veil"></div>
			<div class="apx-drawer__panel">
				<?php echo APX_Blocks::nav( 'apx-nav--drawer' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside. ?>
				<?php if ( has_nav_menu( 'menu-mobile' ) ) : ?>
					<?php
					wp_nav_menu(
						array(
							'theme_location' => 'menu-mobile',
							'container'      => '',
							'menu_class'     => 'apx-drawer__menu',
							'fallback_cb'    => '',
						)
					);
					?>
				<?php endif; ?>
			</div>
		</div>

		<main id="main-site">
		<?php
		if ( ! isset( $args['ws'] ) ) {
			do_action( 'subheader' );

			if ( function_exists( 'px_ads' ) ) {
				echo px_ads( 'ads_header' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- parent theme output.
			}
		}
		?>
