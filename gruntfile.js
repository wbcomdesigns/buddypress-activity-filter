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
						'admin/**/*.php',
						'includes/**/*.php',
						'!node_modules/**',
						'!tests/**',
						'!vendor/**'
					],
					expand: true
				}]
			}
		},
		
		// Generate POT file
		makepot: {
			target: {
				options: {
					cwd: '.',
					domainPath: 'languages/',
					exclude: [ 'node_modules', 'tests', 'vendor', '.git' ],
					mainFile: 'buddypress-activity-filter.php',
					potFilename: 'bp-activity-filter.pot',
					potHeaders: {
						poedit: true,
						'Last-Translator': 'Varun Dubey',
						'Language-Team': 'Wbcom Designs',
						'report-msgid-bugs-to': '',
						'x-poedit-keywordslist': true
					},
					type: 'wp-plugin',
					updateTimestamp: true
				}
			}
		}
	});


	// Custom task to sync PO files with POT
	grunt.registerTask( 'update-po', 'Update all PO files with the latest POT file', function() {
		var done = this.async();
		var shelljs = require('shelljs');
		var path = require('path');
		
		// Check if msgmerge is available
		if (!shelljs.which('msgmerge')) {
			grunt.log.error('msgmerge command not found. Please install gettext.');
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
			
			// Update PO file with POT
			var result = shelljs.exec('msgmerge -U "' + poFile + '" "' + potFile + '" --backup=none', {silent: false});
			
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
	
	// Register tasks
	grunt.registerTask( 'default', [ 'checktextdomain', 'makepot' ] );
	grunt.registerTask( 'translate', [ 'checktextdomain', 'makepot', 'update-po', 'compile-mo' ] );
	grunt.registerTask( 'pot', [ 'makepot' ] );
	grunt.registerTask( 'po', [ 'update-po' ] );
	grunt.registerTask( 'mo', [ 'compile-mo' ] );
	grunt.registerTask( 'sync', [ 'makepot', 'update-po', 'compile-mo' ] );
};
