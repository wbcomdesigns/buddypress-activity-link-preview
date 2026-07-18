'use strict';
module.exports = function ( grunt ) {

	// load all grunt tasks matching the `grunt-*` pattern
	require( 'load-grunt-tasks' )( grunt );

	// Project configuration
	grunt.initConfig( {
		pkg: grunt.file.readJSON( 'package.json' ),

		// Clean build directory
		clean: {
			build: [ 'build/' ],
			dist: [ 'dist/' ]
		},

		// Copy files to build directory
		copy: {
			build: {
				files: [
					{
						expand: true,
						src: [
							'**',
							'!node_modules/**',
							'!build/**',
							'!dist/**',
							'!.git/**',
							'!.github/**',
							'!.gitignore',
							'!gruntfile.js',
							'!Gruntfile.js',
							'!package.json',
							'!package-lock.json',
							'!composer.json',
							'!composer.lock',
							'!phpcs.xml',
							'!phpunit.xml',
							'!.eslintrc',
							'!.stylelintrc',
							'!.editorconfig',
							'!*.zip',
							'!**/*.map',
							'!tests/**',
							'!docs/**',
							'!bin/**',
							'!audit/**',
							'!.DS_Store',
							'!**/.DS_Store',
							'!Thumbs.db',
							'!**/Thumbs.db',
							'!*.md',
							'!**/*.md'
						],
						dest: 'build/buddypress-activity-link-preview/'
					}
				]
			}
		},

		// Create zip archive
		compress: {
			build: {
				options: {
					archive: 'dist/buddypress-activity-link-preview-<%= pkg.version %>.zip',
					mode: 'zip'
				},
				files: [
					{
						expand: true,
						cwd: 'build/',
						src: [ '**' ],
						dest: ''
					}
				]
			}
		},

		// Check text domain
		checktextdomain: {
			options: {
				text_domain: [ 'buddypress-activity-link-preview' ],
				keywords: [
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
				files: [
					{
						src: [
							'*.php',
							'**/*.php',
							'!node_modules/**',
							'!build/**',
							'!tests/**'
						],
						expand: true
					}
				]
			}
		},

		// Make POT file
		makepot: {
			target: {
				options: {
					cwd: '.',
					domainPath: 'languages/',
					exclude: [ 'node_modules/*', 'build/*', 'tests/*' ],
					mainFile: 'bp-activity-link-preview.php',
					potFilename: 'buddypress-activity-link-preview.pot',
					potHeaders: {
						poedit: true,
						'Last-Translator': 'Wbcom Designs',
						'Language-Team': 'Wbcom Designs',
						'report-msgid-bugs-to': 'developer@developer.developer',
						'x-poedit-keywordslist': true
					},
					type: 'wp-plugin',
					updateTimestamp: true
				}
			}
		}
	} );

	// Build distribution zip
	// NOTE: build does NOT run makepot. The shipped POT is produced by
	// `wp i18n make-pot` (the whole free series does this). grunt-wp-i18n
	// regenerates it with degraded headers (Report-Msgid-Bugs-To lost,
	// X-Generator changed) and a fresh timestamp, so letting it run here bakes a
	// worse POT into the zip than the one committed. Run `grunt makepot` by hand
	// only if you deliberately want to regenerate the template.
	grunt.registerTask( 'build', [
		'clean:build',
		'clean:dist',
		'checktextdomain',
		'copy:build',
		'compress:build',
		'clean:build'
	] );

	// Default task
	grunt.registerTask( 'default', [ 'checktextdomain', 'makepot' ] );
};
