'use strict';
module.exports = function ( grunt ) {

	// Load all grunt tasks matching the `grunt-*` pattern
	require( 'load-grunt-tasks' )( grunt );
	
	grunt.initConfig({
		// Check text domain
		checktextdomain: {
			options: {
				text_domain: [ 'bp-activity-filter', 'buddypress' ], // Allowed text domains
				keywords: [ // Translation function specifications
					'__:1,2d',
					'_e:1,2d',
					'_x:1,2c,3d',
					'esc_html__:1,2d',
					'esc_html_e:1,2d',
					'esc_html_x:1,2c,3d',
					'esc_attr__:1,2d',
					'esc_attr_e:1,2d',
					'esc_attr_x:1,2c,3d',
					'_ex:1,2c,3d',
					'_n:1,2,4d',
					'_nx:1,2,4c,5d',
					'_n_noop:1,2,3d',
					'_nx_noop:1,2,3c,4d'
				]
			},
			target: {
				files: [{
					src: [
						'*.php',
						'includes/**/*.php',
						'!node_modules/**',
						'!vendor/**'
					],
					expand: true
				}]
			}
		}
	});


	/*
	 * Generate the POT.
	 *
	 * This shells out to wp-cli rather than using grunt-wp-i18n so that
	 * `grunt pot` and `bin/i18n.sh` produce byte-identical templates.
	 * grunt-wp-i18n wrote its own header set (poedit keys, a hardcoded
	 * Last-Translator), so whichever tool ran last rewrote the file and
	 * the diff looked like real churn. One extractor, one output.
	 *
	 * Keep these flags in step with bin/i18n.sh.
	 */
	grunt.registerTask( 'makepot', 'Generate the POT file via wp-cli', function() {
		var done = this.async();
		var shelljs = require('shelljs');

		if ( ! shelljs.which('wp') ) {
			grunt.log.error('wp-cli not found. Required for "wp i18n make-pot".');
			grunt.log.error('Install: https://wp-cli.org/');
			return done(false);
		}

		var result = shelljs.exec(
			'wp i18n make-pot . languages/bp-activity-filter.pot' +
			' --slug=bp-activity-filter' +
			' --exclude=audit,qa-reports,node_modules,bin',
			{silent: true}
		);

		if ( result.code !== 0 ) {
			grunt.log.error('Failed to generate the POT file.');
			if ( result.stderr ) {
				grunt.log.error( result.stderr );
			}
			return done(false);
		}

		grunt.log.ok('Generated languages/bp-activity-filter.pot');
		done();
	});

	// Custom task to sync PO files with POT
	grunt.registerTask( 'update-po', 'Update all PO files with the latest POT file', function() {
		var done = this.async();
		var shelljs = require('shelljs');
		var path = require('path');
		
		// Check if the gettext tools are available
		if (!shelljs.which('msgmerge') || !shelljs.which('msgattrib')) {
			grunt.log.error('msgmerge/msgattrib not found. Please install gettext.');
			grunt.log.error('On macOS: brew install gettext && brew link --force gettext');
			grunt.log.error('On Ubuntu/Debian: sudo apt-get install gettext');
			return done(false);
		}

		var potFile = 'languages/bp-activity-filter.pot';
		
		// Check if POT file exists
		if (!grunt.file.exists(potFile)) {
			grunt.log.error('POT file not found. Run "grunt makepot" first.');
			return done(false);
		}
		
		// Find all PO files
		var poFiles = grunt.file.expand('languages/*.po');
		
		if (poFiles.length === 0) {
			grunt.log.writeln('No PO files found to update.');
			return done();
		}
		
		var errors = 0;
		var updated = 0;
		
		poFiles.forEach(function(poFile) {
			var baseName = path.basename(poFile);
			grunt.log.writeln('Updating ' + baseName + '...');
			
			// Create backup
			var backupFile = poFile + '.bak';
			shelljs.cp(poFile, backupFile);
			
			// Update PO file with POT.
			var result = shelljs.exec('msgmerge --previous -U "' + poFile + '" "' + potFile + '" --backup=none', {silent: false});

			/*
			 * Then drop obsolete (#~) entries. Without this, translations
			 * for strings whose code was deleted linger in the file and
			 * get re-reported as translation bugs even though no user can
			 * reach them - four of the eight bugs filed against 3.2.1 were
			 * exactly that. Use --no-obsolete, never --no-fuzzy: the
			 * latter deletes whole entries instead of clearing the flag.
			 */
			if (result.code === 0) {
				result = shelljs.exec('msgattrib --no-obsolete -o "' + poFile + '" "' + poFile + '"', {silent: true});
			}

			if (result.code !== 0) {
				grunt.log.error('Failed to update ' + baseName);
				// Restore backup
				shelljs.mv(backupFile, poFile);
				errors++;
			} else {
				grunt.log.ok('Updated ' + baseName);
				// Remove backup
				shelljs.rm(backupFile);
				updated++;
			}
		});
		
		if (errors > 0) {
			grunt.log.error(errors + ' file(s) failed to update.');
			done(false);
		} else {
			grunt.log.ok('Updated ' + updated + ' PO file(s) successfully.');
			done();
		}
	});
	
	// Custom task to compile PO to MO files
	grunt.registerTask( 'compile-mo', 'Compile PO files to MO files', function() {
		var done = this.async();
		var shelljs = require('shelljs');
		var path = require('path');
		
		// Check if msgfmt is available
		if (!shelljs.which('msgfmt')) {
			grunt.log.error('msgfmt command not found. Please install gettext.');
			grunt.log.error('On macOS: brew install gettext && brew link --force gettext');
			grunt.log.error('On Ubuntu/Debian: sudo apt-get install gettext');
			return done(false);
		}
		
		// Find all PO files
		var poFiles = grunt.file.expand('languages/*.po');
		
		if (poFiles.length === 0) {
			grunt.log.writeln('No PO files found in languages directory.');
			return done();
		}
		
		var errors = 0;
		
		poFiles.forEach(function(poFile) {
			var moFile = poFile.replace(/\.po$/, '.mo');
			var baseName = path.basename(poFile);
			
			grunt.log.writeln('Compiling ' + baseName + '...');
			
			var result = shelljs.exec('msgfmt -o "' + moFile + '" "' + poFile + '"', {silent: true});
			
			if (result.code !== 0) {
				grunt.log.error('Failed to compile ' + baseName);
				if (result.stderr) {
					grunt.log.error(result.stderr);
				}
				errors++;
			} else {
				grunt.log.ok('Compiled ' + baseName + ' to ' + path.basename(moFile));
			}
		});
		
		if (errors > 0) {
			grunt.log.error(errors + ' file(s) failed to compile.');
			done(false);
		} else {
			grunt.log.ok('All PO files compiled successfully.');
			done();
		}
	});

	/*
	 * Compile PO to .l10n.php.
	 *
	 * This is NOT optional. WordPress 6.5+ loads languages/*.l10n.php in
	 * preference to the .mo, so regenerating only the .mo leaves a stale
	 * .l10n.php in place that silently wins - the translation update
	 * appears to do nothing. Any task that rebuilds .mo must rebuild this
	 * too, which is why compile-mo and compile-php are always paired below.
	 *
	 * It also must not be pointed straight at languages/. `wp i18n make-php`
	 * includes fuzzy entries, while the msgfmt call in compile-mo above drops
	 * them - so the two artifacts disagreed, and the one WordPress prefers was
	 * the one carrying unreviewed machine guesses. Compile from a
	 * fuzzy-stripped copy so both artifacts contain the same reviewed strings.
	 *
	 * --no-fuzzy is safe here only because it runs against a throwaway copy.
	 * Against a .po it deletes whole entries instead of clearing the flag.
	 */
	grunt.registerTask( 'compile-php', 'Compile PO files to .l10n.php (WP 6.5+ loads these before .mo)', function() {
		var done = this.async();
		var shelljs = require('shelljs');
		var path = require('path');

		if ( ! shelljs.which('wp') ) {
			grunt.log.error('wp-cli not found. Required for "wp i18n make-php".');
			grunt.log.error('Install: https://wp-cli.org/  (or run: ./bin/i18n.sh)');
			return done(false);
		}

		if ( ! shelljs.which('msgattrib') ) {
			grunt.log.error('msgattrib not found. Please install gettext.');
			grunt.log.error('On macOS: brew install gettext && brew link --force gettext');
			return done(false);
		}

		var stage = 'languages/.l10n-stage';
		shelljs.rm('-rf', stage);
		shelljs.mkdir('-p', stage);

		var errors = 0;

		grunt.file.expand('languages/*.po').forEach(function(poFile) {
			var staged = path.join(stage, path.basename(poFile));
			var strip = shelljs.exec(
				'msgattrib --no-fuzzy --no-obsolete -o "' + staged + '" "' + poFile + '"',
				{silent: true}
			);
			if (strip.code !== 0) {
				grunt.log.error('Failed to strip fuzzy entries from ' + path.basename(poFile));
				errors++;
			}
		});

		if (errors === 0) {
			var result = shelljs.exec('wp i18n make-php "' + stage + '/"', {silent: true});
			if (result.code !== 0) {
				grunt.log.error('Failed to generate .l10n.php files.');
				if (result.stderr) {
					grunt.log.error(result.stderr);
				}
				errors++;
			} else {
				/*
				 * wp-cli writes these as a bare `<?php return array(...);` with
				 * no direct-access guard, which Plugin Check reports as an error
				 * on every shipped locale. WP include()s the file with ABSPATH
				 * already defined, so the guard is invisible at runtime and only
				 * blocks a direct HTTP request. Keep in step with bin/i18n.sh.
				 */
				grunt.file.expand(path.join(stage, '*.l10n.php')).forEach(function(l10nFile) {
					var body = grunt.file.read(l10nFile).replace(/^<\?php\r?\n?/, '');
					grunt.file.write(
						l10nFile,
						'<?php\nif ( ! defined( "ABSPATH" ) ) { exit; }\n' + body
					);
				});
				shelljs.mv(path.join(stage, '*.l10n.php'), 'languages/');
			}
		}

		shelljs.rm('-rf', stage);

		if (errors > 0) {
			return done(false);
		}

		grunt.log.ok('Generated .l10n.php files (fuzzy excluded).');
		done();
	});

	/*
	 * Guard: every locale must carry the same msgid count as the POT.
	 * A silent count drift means a locale was not merged, or that a
	 * msgattrib flag dropped entries instead of clearing them.
	 */
	grunt.registerTask( 'verify-i18n', 'Assert every PO matches the POT entry count', function() {
		var done = this.async();
		var shelljs = require('shelljs');
		var path = require('path');

		var potFile = 'languages/bp-activity-filter.pot';
		if ( ! grunt.file.exists( potFile ) ) {
			grunt.log.error('POT file not found. Run "grunt pot" first.');
			return done(false);
		}

		function msgidCount( file ) {
			return parseInt( shelljs.exec( 'grep -c "^msgid " "' + file + '"', {silent: true} ).stdout.trim(), 10 );
		}

		var expected = msgidCount( potFile );
		var errors   = 0;

		grunt.file.expand('languages/*.po').forEach(function(poFile) {
			var actual   = msgidCount( poFile );
			var baseName = path.basename( poFile );

			if ( actual !== expected ) {
				grunt.log.error( baseName + ': ' + actual + ' msgids, expected ' + expected + ' - locale is out of sync' );
				errors++;
				return;
			}

			var check = shelljs.exec('msgfmt -c -o /dev/null "' + poFile + '"', {silent: true});
			if ( check.code !== 0 ) {
				grunt.log.error( baseName + ': msgfmt -c failed' );
				if ( check.stderr ) {
					grunt.log.error( check.stderr );
				}
				errors++;
				return;
			}

			grunt.log.ok( baseName + ': ' + actual + ' msgids, msgfmt clean' );
		});

		// A contaminated msgstr - one msgid carrying another's translation -
		// keeps the msgid count correct and passes msgfmt -c, so it slips past
		// both checks above. Same script bin/i18n.sh calls, so the two entry
		// points cannot drift.
		var collisions = shelljs.exec(
			'python3 bin/check-po-collisions.py languages/*.po',
			{silent: true}
		);
		if ( collisions.code !== 0 ) {
			grunt.log.error( collisions.stderr || collisions.stdout );
			errors++;
		}

		// Same question asked of the artifact WordPress actually loads. Every
		// entry in there is served, so a shared translation is user-visible by
		// definition, and it proves no fuzzy entry survived compilation.
		grunt.file.expand('languages/*.l10n.php').forEach(function(l10nFile) {
			var served = shelljs.exec(
				'php bin/check-l10n-collisions.php "' + l10nFile + '"',
				{silent: true}
			);
			if ( served.code !== 0 ) {
				grunt.log.error( served.stderr || served.stdout );
				errors++;
			}
		});

		if ( errors > 0 ) {
			grunt.log.error( errors + ' locale(s) failed verification.' );
			return done(false);
		}

		grunt.log.ok('All locales match the POT (' + expected + ' msgids).');
		done();
	});

	// Register tasks
	grunt.registerTask( 'default', [ 'checktextdomain', 'makepot' ] );
	grunt.registerTask( 'translate', [ 'checktextdomain', 'makepot', 'update-po', 'compile-mo', 'compile-php', 'verify-i18n' ] );
	grunt.registerTask( 'pot', [ 'makepot' ] );
	grunt.registerTask( 'po', [ 'update-po' ] );
	grunt.registerTask( 'mo', [ 'compile-mo', 'compile-php' ] );
	grunt.registerTask( 'verify', [ 'verify-i18n' ] );
	grunt.registerTask( 'sync', [ 'makepot', 'update-po', 'compile-mo', 'compile-php', 'verify-i18n' ] );
};
