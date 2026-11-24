'use strict';
module.exports = function ( grunt ) {

	// load all grunt tasks matching the `grunt-*` pattern
	require( 'load-grunt-tasks' )( grunt );

	// Project configuration
	grunt.initConfig( {
		pkg: grunt.file.readJSON( 'package.json' ),

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

	// The release zip is built by bin/build-release.sh only (folder = WordPress.org slug).
	// NOTE: the shipped POT is produced by `wp i18n make-pot` (the whole free series does
	// this); grunt-wp-i18n regenerates it with degraded headers, so the release build never
	// runs makepot.

	// Default task
	grunt.registerTask( 'default', [ 'checktextdomain', 'makepot' ] );
};
