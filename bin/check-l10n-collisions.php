#!/usr/bin/env php
<?php
/**
 * Fail when two msgids share one translation in a compiled .l10n.php.
 *
 * The companion to bin/check-po-collisions.py, asked of the artifact WordPress
 * actually loads. WordPress 6.5+ prefers languages/*.l10n.php over the .mo, so
 * this file is the ground truth for what users see - checking only the .po can
 * pass while the shipped file is wrong.
 *
 * Every entry here is served, so unlike the .po there is no fuzzy tier: any
 * shared translation is user-visible. A clean run also proves the build stripped
 * fuzzy entries, since `wp i18n make-php` includes them by default and fuzzy
 * carry-over is what produces these collisions in the first place.
 *
 * Usage: php bin/check-l10n-collisions.php languages/foo-fr_FR.l10n.php
 *
 * @package BuddyPress_Activity_Filter
 */

/*
 * This is a build-time CLI script: it never loads in WordPress, so two sniffs
 * do not apply. WP_Filesystem cannot write to STDERR, and escaping is for
 * output rendered as HTML, not for a terminal exit status.
 *
 * phpcs:disable WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
 * phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 1 );
}

if ( $argc < 2 ) {
	fwrite( STDERR, "usage: check-l10n-collisions.php <file.l10n.php>...\n" );
	exit( 2 );
}

$exit_code = 0;

foreach ( array_slice( $argv, 1 ) as $file ) {
	if ( ! is_readable( $file ) ) {
		fwrite( STDERR, sprintf( "    FAIL %s: not readable\n", basename( $file ) ) );
		$exit_code = 1;
		continue;
	}

	$data = include $file;

	if ( ! is_array( $data ) ) {
		fwrite( STDERR, sprintf( "    FAIL %s: did not return an array\n", basename( $file ) ) );
		$exit_code = 1;
		continue;
	}

	$messages = isset( $data['messages'] ) ? $data['messages'] : $data;

	// Plural entries are stored with a NUL separator; compare them whole.
	$counts = array_count_values( array_map( 'strval', $messages ) );
	$dupes  = array_filter(
		$counts,
		static function ( $n ) {
			return $n > 1;
		}
	);

	if ( ! $dupes ) {
		printf(
			"    ok   %s: %d served strings, all distinct\n",
			basename( $file ),
			count( $messages )
		);
		continue;
	}

	foreach ( $dupes as $msgstr => $n ) {
		fwrite(
			STDERR,
			sprintf(
				"    FAIL %s: %d served msgids share \"%s\"\n",
				basename( $file ),
				$n,
				$msgstr
			)
		);
		foreach ( array_keys( $messages, $msgstr, true ) as $msgid ) {
			fwrite( STDERR, sprintf( "           <- \"%s\"\n", $msgid ) );
		}
	}

	$exit_code = 1;
}

exit( $exit_code );
