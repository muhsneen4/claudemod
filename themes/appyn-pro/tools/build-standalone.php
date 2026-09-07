<?php
/**
 * Build a standalone "Appyn Pro" theme: the Appyn parent theme with every
 * Appyn Pro modification merged in, so it installs and runs on its own.
 *
 * Usage:
 *   php tools/build-standalone.php /path/to/appyn.zip
 *   php tools/build-standalone.php /path/to/appyn-folder
 *
 * Output: dist/appyn-pro-standalone.zip (containing the folder appyn-pro/)
 *
 * Re-run this after every Appyn update: it always starts from the untouched
 * parent files, so an update never overwrites the Appyn Pro code.
 */

$child = dirname( __DIR__ );
$dist  = $child . '/dist';
$work  = sys_get_temp_dir() . '/apx-build-' . getmypid();
$build = $work . '/appyn-pro';

$source = isset( $argv[1] ) ? $argv[1] : '';

if ( '' === $source || ! file_exists( $source ) ) {
	fwrite( STDERR, "Usage: php tools/build-standalone.php <appyn.zip|appyn-folder>\n" );
	exit( 1 );
}

/* ---------------------------------------------------------------- *
 * Small helpers
 * ---------------------------------------------------------------- */

function say( $line ) {
	echo $line . "\n";
}

function rmrf( $path ) {
	if ( ! file_exists( $path ) ) {
		return;
	}

	if ( is_file( $path ) || is_link( $path ) ) {
		unlink( $path );
		return;
	}

	foreach ( scandir( $path ) as $entry ) {
		if ( '.' === $entry || '..' === $entry ) {
			continue;
		}

		rmrf( $path . '/' . $entry );
	}

	rmdir( $path );
}

function copy_tree( $from, $to ) {
	if ( is_file( $from ) ) {
		@mkdir( dirname( $to ), 0755, true );
		copy( $from, $to );
		return 1;
	}

	@mkdir( $to, 0755, true );
	$count = 0;

	foreach ( scandir( $from ) as $entry ) {
		if ( '.' === $entry || '..' === $entry ) {
			continue;
		}

		$count += copy_tree( $from . '/' . $entry, $to . '/' . $entry );
	}

	return $count;
}

function patch_file( $path, $search, $replace ) {
	$contents = file_get_contents( $path );
	$patched  = str_replace( $search, $replace, $contents );

	file_put_contents( $path, $patched );

	return $contents !== $patched;
}

function zip_dir( $dir, $zip_path, $inner_name ) {
	$zip = new ZipArchive();

	if ( true !== $zip->open( $zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
		fwrite( STDERR, "Could not create $zip_path\n" );
		exit( 1 );
	}

	$files    = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::SELF_FIRST );
	$base_len = strlen( $dir ) + 1;
	$count    = 0;

	foreach ( $files as $file ) {
		$relative = $inner_name . '/' . str_replace( '\\', '/', substr( $file->getPathname(), $base_len ) );

		if ( $file->isDir() ) {
			$zip->addEmptyDir( $relative );
			continue;
		}

		$zip->addFile( $file->getPathname(), $relative );
		$count++;
	}

	$zip->close();

	return $count;
}

/* ---------------------------------------------------------------- *
 * 1. Unpack the parent theme
 * ---------------------------------------------------------------- */

rmrf( $work );
mkdir( $work, 0755, true );

if ( is_dir( $source ) ) {
	$parent_root = rtrim( $source, '/' );
	say( 'Parent source: folder ' . $parent_root );
} else {
	$zip = new ZipArchive();

	if ( true !== $zip->open( $source ) ) {
		fwrite( STDERR, "Could not open $source\n" );
		exit( 1 );
	}

	$zip->extractTo( $work . '/parent' );
	$zip->close();

	$parent_root = $work . '/parent';

	// The theme may sit one level down inside the zip.
	if ( ! file_exists( $parent_root . '/functions.php' ) ) {
		foreach ( scandir( $parent_root ) as $entry ) {
			if ( '.' === $entry || '..' === $entry ) {
				continue;
			}

			if ( file_exists( $parent_root . '/' . $entry . '/functions.php' ) ) {
				$parent_root = $parent_root . '/' . $entry;
				break;
			}
		}
	}

	say( 'Parent source: ' . basename( $source ) );
}

if ( ! file_exists( $parent_root . '/functions.php' ) || ! file_exists( $parent_root . '/style.css' ) ) {
	fwrite( STDERR, "That does not look like the Appyn theme (no functions.php / style.css).\n" );
	exit( 1 );
}

$parent_version = '';

if ( preg_match( '/^\s*Version:\s*(.+)$/mi', file_get_contents( $parent_root . '/style.css' ), $m ) ) {
	$parent_version = trim( $m[1] );
}

say( 'Parent version: ' . ( $parent_version ? $parent_version : 'unknown' ) );

copy_tree( $parent_root, $build );

/* ---------------------------------------------------------------- *
 * 2. Keep the original templates for the fallback switches
 * ---------------------------------------------------------------- */

$overridden = array(
	'header-default.php',
	'footer-default.php',
	'template-parts/loop/app.php',
	'template-parts/loop/blog-home.php',
);

foreach ( $overridden as $relative ) {
	$from = $build . '/' . $relative;
	$to   = $build . '/appyn-original/' . $relative;

	if ( ! file_exists( $from ) ) {
		continue;
	}

	@mkdir( dirname( $to ), 0755, true );
	rename( $from, $to );
}

say( 'Kept ' . count( $overridden ) . ' original templates in appyn-original/' );

/* ---------------------------------------------------------------- *
 * 3. Merge the Appyn Pro code in
 * ---------------------------------------------------------------- */

$merge = array(
	'inc'                                 => 'inc',
	'assets/admin'                        => 'assets/admin',
	'assets/css/theme.css'                => 'assets/css/theme.css',
	'assets/js/theme.js'                  => 'assets/js/theme.js',
	'screenshot.png'                      => 'screenshot.png',
	'README.md'                           => 'README.md',
	'header-default.php'                  => 'header-default.php',
	'footer-default.php'                  => 'footer-default.php',
	'template-parts/loop/app.php'         => 'template-parts/loop/app.php',
	'template-parts/loop/blog-home.php'   => 'template-parts/loop/blog-home.php',
);

$merged = 0;

foreach ( $merge as $from => $to ) {
	$merged += copy_tree( $child . '/' . $from, $build . '/' . $to );
}

say( "Merged $merged Appyn Pro files" );

/* ---------------------------------------------------------------- *
 * 4. Point the fallback switches at the kept originals
 * ---------------------------------------------------------------- */

foreach ( $overridden as $relative ) {
	patch_file(
		$build . '/' . $relative,
		"get_template_directory() . '/",
		"get_template_directory() . '/appyn-original/"
	);
}

/* ---------------------------------------------------------------- *
 * 5. Boot the Appyn Pro code from the theme's own functions.php
 * ---------------------------------------------------------------- */

$bootstrap = file_get_contents( $child . '/functions.php' );
$bootstrap = str_replace( 'load_child_theme_textdomain', 'load_theme_textdomain', $bootstrap );
$bootstrap = str_replace(
	' * Appyn Pro - child theme bootstrap.',
	' * Appyn Pro - bootstrap (standalone build).',
	$bootstrap
);

file_put_contents( $build . '/inc/appyn-pro-bootstrap.php', $bootstrap );

$hook = "\n\n/* --------------------------------------------------------------------\n"
	. " * Appyn Pro\n"
	. " * --------------------------------------------------------------------\n"
	. " * Options panel, design tokens and front-end code. Everything it needs\n"
	. " * lives in inc/ and assets/, so nothing above this line was changed.\n"
	. " */\n"
	. "require_once get_template_directory() . '/inc/appyn-pro-bootstrap.php';\n";

file_put_contents( $build . '/functions.php', $hook, FILE_APPEND );

say( 'Wired inc/appyn-pro-bootstrap.php into functions.php' );

/* ---------------------------------------------------------------- *
 * 6. Give the merged theme its own header
 * ---------------------------------------------------------------- */

$child_version = '1.0.0';

if ( preg_match( "/define\(\s*'APX_VERSION',\s*'([^']+)'/", file_get_contents( $child . '/functions.php' ), $m ) ) {
	$child_version = $m[1];
}

$style  = file_get_contents( $build . '/style.css' );
$header = "/*\n"
	. "Theme Name: Appyn Pro\n"
	. "Theme URI: https://themespixel.net/en/theme/appyn/\n"
	. "Author: ThemesPixel (base theme), Appyn Pro (design system)\n"
	. "Author URI: https://themespixel.net\n"
	. "Description: Appyn for APK download sites, with the Appyn Pro design system merged in. Every colour, font, size, spacing, shadow, animation and layout choice is edited from the Appyn Pro options panel. Built on Appyn " . ( $parent_version ? $parent_version : '2.x' ) . ".\n"
	. "Version: " . $child_version . "\n"
	. "Text Domain: appyn\n"
	. "Tags: apk, apps, games, dark-mode, customizable\n"
	. "*/";

$style = preg_replace( '#/\*.*?\*/#s', $header, $style, 1 );

file_put_contents( $build . '/style.css', $style );

say( 'Theme header set to Appyn Pro ' . $child_version . ' (base Appyn ' . $parent_version . ')' );

/* ---------------------------------------------------------------- *
 * 7. Package
 * ---------------------------------------------------------------- */

@mkdir( $dist, 0755, true );

$zip_path = $dist . '/appyn-pro-standalone.zip';
$count    = zip_dir( $build, $zip_path, 'appyn-pro' );

rmrf( $work );

say( "Built $zip_path ($count files, " . round( filesize( $zip_path ) / 1048576, 2 ) . " MB)" );
