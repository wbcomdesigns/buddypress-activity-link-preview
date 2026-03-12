<?php
/**
 * BuddyPress Activity Link preview
 *
 * Plugin Name:       Activity Link Preview For BuddyPress
 * Plugin URI:        https://wbcomdesigns.com/downloads/buddypress-activity-link-preview/
 * Description:       BuddyPress activity link preview displays as image title and description from the site when links are used in activity posts.
 * Version:           1.7.3
 * Author:            wbcomdesigns
 * Author URI:        https://wbcomdesigns.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       buddypress-activity-link-preview
 * Domain Path:       /languages
 *
 * @package           Buddypress-activity-link-preview
 * @link              https://wbcomdesigns.com/
 * @since             1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BP_ACTIVITY_LINK_PREVIEW_VERSION', '1.7.3' );
define( 'BP_ACTIVITY_LINK_PREVIEW_URL', plugin_dir_url( __FILE__ ) );
define( 'BP_ACTIVITY_LINK_PREVIEW_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Check if BuddyPress or BuddyBoss Platform is active.
 *
 * @since 1.7.1
 * @return bool True if either BuddyPress or BuddyBoss is active.
 */
function bp_activity_link_preview_is_bp_active() {
	// Check for BuddyPress.
	if ( class_exists( 'BuddyPress' ) ) {
		return true;
	}
	// Check for BuddyBoss Platform.
	if ( defined( 'BP_PLATFORM_VERSION' ) || class_exists( 'BuddyBoss_Platform' ) ) {
		return true;
	}
	return false;
}

/**
 * Display admin notice if BuddyPress/BuddyBoss is not active.
 *
 * @since 1.7.1
 */
function bp_activity_link_preview_admin_notice() {
	?>
	<div class="notice notice-error">
		<p>
			<strong><?php esc_html_e( 'Activity Link Preview For BuddyPress', 'buddypress-activity-link-preview' ); ?></strong>
			<?php esc_html_e( 'requires BuddyPress or BuddyBoss Platform to be installed and active.', 'buddypress-activity-link-preview' ); ?>
		</p>
	</div>
	<?php
}

/**
 * Deactivate plugin if BuddyPress/BuddyBoss is not active.
 *
 * @since 1.7.1
 */
function bp_activity_link_preview_requires_buddypress() {
	if ( ! bp_activity_link_preview_is_bp_active() ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		add_action( 'admin_notices', 'bp_activity_link_preview_admin_notice' );
	}
}
add_action( 'admin_init', 'bp_activity_link_preview_requires_buddypress' );

/**
 * Bootstrap the plugin - register all hooks.
 * This is called from plugins_loaded only when BuddyPress/BuddyBoss is active.
 *
 * @since 1.7.1
 */
function bp_activity_link_preview_bootstrap() {
	// Enqueue scripts and styles.
	add_action( 'wp_enqueue_scripts', 'bp_activity_link_preview_enqueue_scripts' );

	// Remove BuddyBoss duplicate preview.
	add_action( 'bp_init', 'bp_activity_link_preview_disable_buddyboss_preview', 999 );

	// AJAX handler.
	add_action( 'wp_ajax_bp_activity_parse_url_preview', 'bp_activity_parse_url_preview' );

	// Save link preview data.
	add_action( 'bp_activity_after_save', 'bp_activity_link_preview_save_link_data', 10, 1 );

	// Render link preview in activity content.
	add_filter( 'bp_get_activity_content_body', 'bp_activity_link_preview_content_body_with_comments', 8, 2 );

	// Initialize comment filter.
	add_action( 'bp_init', 'bp_activity_link_preview_init_comment_filter' );

	// Allow additional HTML tags.
	add_filter( 'bp_activity_allowed_tags', 'bp_activity_link_preview_allowed_tags' );

	// REST API integration.
	add_filter( 'bp_rest_activity_prepare_value', 'bp_activity_link_preview_data_embed_rest_api', 10, 3 );

	// Facebook SDK root div.
	add_action( 'wp_head', 'bp_activity_link_preview_add_facebook_root_div' );
}

// Check dependency on plugins_loaded - either bootstrap or show admin notice.
add_action(
	'plugins_loaded',
	function () {
		if ( bp_activity_link_preview_is_bp_active() ) {
			bp_activity_link_preview_bootstrap();
		} else {
			add_action( 'admin_notices', 'bp_activity_link_preview_admin_notice' );
		}
	},
	20
);

/** Bp_activity_link_preview_enqueue_scripts */
function bp_activity_link_preview_enqueue_scripts() {
	wp_enqueue_style( 'bp-activity-link-preview-css', BP_ACTIVITY_LINK_PREVIEW_URL . 'assets/css/bp-activity-link-preview.css', array(), BP_ACTIVITY_LINK_PREVIEW_VERSION, 'all' );
	wp_enqueue_script( 'twitter-js', 'https://platform.twitter.com/widgets.js', array( 'jquery' ), BP_ACTIVITY_LINK_PREVIEW_VERSION, true );
	wp_enqueue_script( 'facebook-js', 'https://connect.facebook.net/en_US/sdk.js#xfbml=1&version=v21.0', array( 'jquery' ), BP_ACTIVITY_LINK_PREVIEW_VERSION, true );
	wp_enqueue_script( 'bp-activity-link-preview-js', BP_ACTIVITY_LINK_PREVIEW_URL . 'assets/js/bp-activity-link-preview.js', array( 'jquery' ), BP_ACTIVITY_LINK_PREVIEW_VERSION, true );

	// Detect if BuddyBoss is active with its own link preview.
	$buddyboss_active              = function_exists( 'buddyboss_theme' ) || class_exists( 'BuddyBoss_Platform' );
	$buddyboss_link_preview_active = $buddyboss_active && function_exists( 'bp_is_activity_link_preview_active' ) && bp_is_activity_link_preview_active();

	// Detect if Youzify is active with its URL preview feature.
	$youzify_active              = defined( 'YOUZIFY_VERSION' ) || class_exists( 'Youzify' );
	$youzify_url_preview_enabled = $youzify_active && function_exists( 'youzify_option' ) && 'on' === youzify_option( 'youzify_enable_wall_url_preview', 'on' );

	// Add localized data for comment handling.
	wp_localize_script(
		'bp-activity-link-preview-js',
		'bp_activity_link_preview',
		array(
			'ajaxurl'                       => admin_url( 'admin-ajax.php' ),
			'nonce'                         => wp_create_nonce( 'bp_activity_link_preview_nonce' ),
			'enable_comments'               => apply_filters( 'bp_activity_link_preview_enable_comments', true ),
			'buddyboss_active'              => $buddyboss_active,
			'buddyboss_link_preview_active' => $buddyboss_link_preview_active,
			'youzify_active'                => $youzify_active,
			'youzify_url_preview_active'    => $youzify_url_preview_enabled,
		)
	);
}

/**
 * Remove BuddyBoss Platform's link preview filter when our plugin is active.
 * BuddyBoss adds bp_activity_link_preview at priority 20 which causes duplicate previews.
 *
 * @since 1.7.1
 */
function bp_activity_link_preview_disable_buddyboss_preview() {
	// Check if BuddyBoss Platform is active.
	if ( defined( 'BP_PLATFORM_VERSION' ) && function_exists( 'bp_activity_link_preview' ) ) {
		remove_filter( 'bp_get_activity_content_body', 'bp_activity_link_preview', 20 );
	}
}

/** Bp_activity_parse_url_preview */
function bp_activity_parse_url_preview() {
	// Check if user is logged in first.
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => __( 'You must be logged in to perform this action.', 'buddypress-activity-link-preview' ) ) );
	}

	// Verify nonce - required for security.
	$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'bp_activity_link_preview_nonce' ) ) {
		wp_send_json_error( array( 'message' => __( 'Security check failed.', 'buddypress-activity-link-preview' ) ) );
	}

	// Get URL and comment ID (if it's a comment).
	$url        = ! empty( $_POST['url'] ) ? filter_var( wp_unslash( $_POST['url'] ), FILTER_VALIDATE_URL ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- URL is validated with FILTER_VALIDATE_URL.
	$comment_id = ! empty( $_POST['comment_id'] ) ? sanitize_text_field( wp_unslash( $_POST['comment_id'] ) ) : '';

	// Check if URL is validated.
	if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
		wp_send_json( array( 'error' => __( 'The URL you entered is not valid.', 'buddypress-activity-link-preview' ) ) );
	}

	// Parse URL to get host.
	$parsed_url = wp_parse_url( $url );
	$host       = isset( $parsed_url['host'] ) ? $parsed_url['host'] : '';

	// Block requests to private/internal IP ranges and localhost.
	if ( empty( $host ) ||
		( filter_var( $host, FILTER_VALIDATE_IP ) &&
		( false === filter_var( $host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) ) ||
		'127.0.0.1' === $host ||
		'localhost' === $host ||
		preg_match( '/^(10\.|172\.(1[6-9]|2[0-9]|3[0-1])\.|192\.168\.)/', $host )
	) {
		wp_send_json( array( 'error' => __( 'This URL cannot be previewed for security reasons.', 'buddypress-activity-link-preview' ) ) );
	}

	$parse_url_data = bp_activity_link_parse_url( $url );

	// If empty data then send error.
	if ( empty( $parse_url_data ) ) {
		wp_send_json( array( 'error' => __( 'Sorry! Preview is not available right now. Please try again later.', 'buddypress-activity-link-preview' ) ) );
	}

	// Add comment_id to response if it exists.
	if ( ! empty( $comment_id ) ) {
		$parse_url_data['comment_id'] = $comment_id;
	}

	// Apply filter to allow modification of parsed data.
	$parse_url_data = apply_filters( 'bp_activity_parse_url_preview', $parse_url_data, $url );

	// send json success.
	wp_send_json( $parse_url_data );
}

/**
 * Parse a URL and return preview data.
 *
 * @param string $url The URL to parse.
 * @return array Parsed URL data.
 */
function bp_activity_link_parse_url( $url ) {
	$parse_url_data = wp_parse_url( $url, PHP_URL_HOST );
	$original_url   = $url;

	if ( in_array( $parse_url_data, apply_filters( 'bp_activity_link_parse_url_shorten_url_provider', array( 'bit.ly', 'snip.ly', 'rb.gy', 'tinyurl.com', 'tiny.one', 'rotf.lol', 'b.link', '4ubr.short.gy', '' ) ), true ) ) {
		$response = wp_safe_remote_get(
			$url,
			array(
				'stream'  => true,
				'headers' => array(
					'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:71.0) Gecko/20100101 Firefox/71.0',
				),
			),
		);

		if ( ! is_wp_error( $response ) && ! empty( $response['http_response']->get_response_object()->url ) && $response['http_response']->get_response_object()->url !== $url ) {
			$new_url = $response['http_response']->get_response_object()->url;
			if ( filter_var( $new_url, FILTER_VALIDATE_URL ) ) {
				$url = $new_url;
			}
		}

		if ( $original_url === $url ) {
			$context = array(
				'http' => array(
					'method'        => 'GET',
					'max_redirects' => 1,
				),
			);

			@file_get_contents( $url, null, stream_context_create( $context ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents, WordPress.PHP.NoSilencedErrors.Discouraged -- Fallback for short URL resolution when wp_safe_remote_get fails.
			if ( isset( $http_response_header ) && isset( $http_response_header[6] ) ) {
				$new_url = str_replace( 'Location: ', '', $http_response_header[6] );
				if ( filter_var( $new_url, FILTER_VALIDATE_URL ) ) {
					$url = $new_url;
				}
			}
		}
	}

	$cache_key       = 'bp_oembed_' . md5( maybe_serialize( $url ) );
	$parsed_url_data = get_transient( $cache_key );
	if ( ! empty( $parsed_url_data ) ) {
		return $parsed_url_data;
	}

	$parsed_url_data = array();

	if ( strstr( $url, site_url() ) && ( strstr( $url, 'download_document_file' ) || strstr( $url, 'download_media_file' ) || strstr( $url, 'download_video_file' ) ) ) {
		return array();
	}

	if ( ! function_exists( '_wp_oembed_get_object' ) ) {
		require ABSPATH . WPINC . '/class-oembed.php';
	}

	$embed_code = '';
	$oembed_obj = _wp_oembed_get_object();
	$discover   = apply_filters( 'bp_oembed_discover_support', false, $url );
	$is_oembed  = $oembed_obj->get_data( $url, array( 'discover' => $discover ) );

	if ( $is_oembed ) {
		$embed_code = wp_oembed_get( $url, array( 'discover' => $discover ) );
	}

	// Fetch the oembed code for URL.
	if ( ! empty( $embed_code ) ) {
		$parsed_url_data['title']       = ' ';
		$parsed_url_data['description'] = $embed_code;
		$parsed_url_data['images']      = '';
		$parsed_url_data['error']       = '';
		$parsed_url_data['wp_embed']    = true;
	} else {
		$args = array( 'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:71.0) Gecko/20100101 Firefox/71.0' );

		if ( bp_is_same_site_url( $url ) ) {
			// BuddyBoss Platform compatibility - add JWT token if available.
			if ( function_exists( 'bp_enable_private_network' ) && function_exists( 'bb_create_jwt' ) ) {
				if ( ! bp_enable_private_network() ) {
					// Add the custom header with the JWT token.
					$args['headers'] = array(
						'bb-preview-token' => bb_create_jwt(
							array(
								'url' => $url,
								'iat' => time(),
								'exp' => time() + 120, // Token validity 2 minutes.
							)
						),
					);
				}
			}
			$args['sslverify'] = false;
		}

		// safely get URL and response body.
		$response = wp_safe_remote_get( $url, $args );
		$body     = wp_remote_retrieve_body( $response );

		// if response is not empty.
		if ( ! is_wp_error( $body ) && ! empty( $body ) ) {

			// Load HTML to DOM Object.
			$dom = new DOMDocument();
			// Suppress warnings for malformed HTML and handle encoding properly.
			libxml_use_internal_errors( true );
			$dom->loadHTML( '<?xml encoding="UTF-8">' . $body, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
			libxml_clear_errors();

			$meta_tags   = array();
			$images      = array();
			$description = '';
			$title       = '';

			$xpath       = new DOMXPath( $dom );
			$query       = '//*/meta[starts-with(@property, \'og:\')]';
			$metas_query = $xpath->query( $query );
			foreach ( $metas_query as $meta ) {
				$property    = $meta->getAttribute( 'property' );
				$content     = $meta->getAttribute( 'content' );
				$meta_tags[] = array( $property, $content );
			}

			if ( is_array( $meta_tags ) && ! empty( $meta_tags ) ) {
				foreach ( $meta_tags as $tag ) {
					if ( is_array( $tag ) && ! empty( $tag ) ) {
						if ( 'og:title' === $tag[0] ) {
							$title = $tag[1];
						}
						if ( 'og:description' === $tag[0] || 'description' === strtolower( $tag[0] ) ) {
							$description = html_entity_decode( $tag[1], ENT_QUOTES, 'utf-8' );
						}
						if ( 'og:image' === $tag[0] ) {
							$images[] = $tag[1];
						}
					}
				}
			}

			// Parse DOM to get Title.
			if ( empty( $title ) ) {
				$nodes = $dom->getElementsByTagName( 'title' );
				$title = $nodes && $nodes->length > 0 ? $nodes->item( 0 )->nodeValue : '';
			}

			// Parse DOM to get Meta Description.
			if ( empty( $description ) ) {
				$metas = $dom->getElementsByTagName( 'meta' );
				for ( $i = 0; $i < $metas->length; $i++ ) {
					$meta = $metas->item( $i );
					if ( 'description' === $meta->getAttribute( 'name' ) ) {
						$description = $meta->getAttribute( 'content' );
						break;
					}
				}
			}

			// Parse DOM to get Images.
			$image_elements = $dom->getElementsByTagName( 'img' );
			for ( $i = 0; $i < $image_elements->length; $i++ ) {
				$image = $image_elements->item( $i );
				$src   = $image->getAttribute( 'src' );

				if ( filter_var( $src, FILTER_VALIDATE_URL ) ) {
					$images[] = $src;
				}
			}

			if ( ! empty( $description ) && '' === trim( $title ) ) {
				$title = $description;
			}

			if ( ! empty( $title ) && '' === trim( $description ) ) {
				$description = $title;
			}

			if ( ! empty( $title ) ) {
				$parsed_url_data['title'] = $title;
			}

			if ( ! empty( $description ) ) {
				$parsed_url_data['description'] = $description;
			}

			if ( ! empty( $images ) ) {
				$parsed_url_data['images'] = $images;
			}

			if ( ! empty( $title ) || ! empty( $description ) || ! empty( $images ) ) {
				$parsed_url_data['error'] = '';
			}
		}
	}

	if ( ! empty( $parsed_url_data ) ) {
		// set the transient.
		set_transient( $cache_key, $parsed_url_data, DAY_IN_SECONDS );
	}

	return apply_filters( 'bp_activity_link_parse_url', $parsed_url_data );
}

/**
 * Check if the requested URL is from same site.
 *
 * @param string $url The URL to check.
 * @return bool True if the URL is from the same site.
 */
function bp_is_same_site_url( $url ) {
	$parsed_url = wp_parse_url( $url );
	$home_url   = wp_parse_url( home_url( '/' ) );

	if ( ! empty( $parsed_url['host'] ) && ! empty( $parsed_url['scheme'] ) ) {
		return ( strtolower( $parsed_url['host'] ) === strtolower( $home_url['host'] ) ) && ( $parsed_url['scheme'] === $home_url['scheme'] );
	}

	return false;
}

/**
 * Save link preview data into activity meta
 *
 * @param BP_Activity_Activity $activity Activity object.
 */
function bp_activity_link_preview_save_link_data( $activity ) {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verification is handled by BuddyPress before bp_activity_after_save hook is fired.

	// Handle main activity posts.
	if ( isset( $_POST['link_url'] ) && isset( $_POST['link_title'] ) && isset( $_POST['link_description'] ) && isset( $_POST['link_image'] ) ) {
		$link_url                 = ! empty( $_POST['link_url'] ) ? sanitize_text_field( wp_unslash( $_POST['link_url'] ) ) : '';
		$link_title               = ! empty( $_POST['link_title'] ) ? sanitize_text_field( wp_unslash( $_POST['link_title'] ) ) : '';
		$link_description         = ! empty( $_POST['link_description'] ) ? sanitize_text_field( wp_unslash( $_POST['link_description'] ) ) : '';
		$link_image               = ! empty( $_POST['link_image'] ) ? sanitize_text_field( wp_unslash( $_POST['link_image'] ) ) : '';
		$link_preview_data['url'] = $link_url;

		if ( false !== strpos( $link_preview_data['url'], 'www.reddit.com' ) ) {
			return;
		}

		if ( ! empty( $link_image ) ) {
			$link_preview_data['image_url'] = $link_image;
		}
		if ( ! empty( $link_title ) ) {
			$link_preview_data['title'] = $link_title;
		}
		if ( ! empty( $link_description ) ) {
			$link_preview_data['description'] = $link_description;
		}

		bp_activity_update_meta( $activity->id, '_bp_activity_link_preview_data', $link_preview_data );
	}

	// Handle comment link previews (only if enabled).
	if ( apply_filters( 'bp_activity_link_preview_enable_comments', true ) && 'activity_comment' === $activity->type && isset( $_POST['comment_link_url'] ) && isset( $_POST['comment_link_title'] ) && isset( $_POST['comment_link_description'] ) && isset( $_POST['comment_link_image'] ) ) {
		$comment_link_url         = ! empty( $_POST['comment_link_url'] ) ? sanitize_text_field( wp_unslash( $_POST['comment_link_url'] ) ) : '';
		$comment_link_title       = ! empty( $_POST['comment_link_title'] ) ? sanitize_text_field( wp_unslash( $_POST['comment_link_title'] ) ) : '';
		$comment_link_description = ! empty( $_POST['comment_link_description'] ) ? sanitize_text_field( wp_unslash( $_POST['comment_link_description'] ) ) : '';
		$comment_link_image       = ! empty( $_POST['comment_link_image'] ) ? sanitize_text_field( wp_unslash( $_POST['comment_link_image'] ) ) : '';

		$comment_link_preview_data['url'] = $comment_link_url;

		if ( false !== strpos( $comment_link_preview_data['url'], 'www.reddit.com' ) ) {
			return;
		}

		if ( ! empty( $comment_link_image ) ) {
			$comment_link_preview_data['image_url'] = $comment_link_image;
		}
		if ( ! empty( $comment_link_title ) ) {
			$comment_link_preview_data['title'] = $comment_link_title;
		}
		if ( ! empty( $comment_link_description ) ) {
			$comment_link_preview_data['description'] = $comment_link_description;
		}

		bp_activity_update_meta( $activity->id, '_bp_activity_comment_link_preview_data', $comment_link_preview_data );
	}
	// Fallback: If comment doesn't have preview data but has URLs in content, try to extract and save (only if enabled)
	elseif ( apply_filters( 'bp_activity_link_preview_enable_comments', true ) && $activity->type === 'activity_comment' && ! empty( $activity->content ) ) {
		$urls = bp_activity_link_preview_extract_urls_from_content( $activity->content, false );
		if ( ! empty( $urls ) ) {
			$url         = $urls[0]; // Use first URL found.
			$parsed_data = bp_activity_link_parse_url( $url );

			// Check if it's a social media URL that uses native embeds (Twitter, Facebook).
			$is_social_media = bp_activity_link_preview_is_social_media_url( $url );

			// Save preview data if we have title/description OR if it's a social media URL.
			if ( ! empty( $parsed_data ) && ( ! empty( $parsed_data['title'] ) || ! empty( $parsed_data['description'] ) || $is_social_media ) ) {
				$comment_link_preview_data = array(
					'url'         => $url,
					'title'       => ! empty( $parsed_data['title'] ) ? $parsed_data['title'] : '',
					'description' => ! empty( $parsed_data['description'] ) ? $parsed_data['description'] : '',
					'image_url'   => ! empty( $parsed_data['images'] ) ? $parsed_data['images'][0] : '',
				);

				if ( false === strpos( $url, 'www.reddit.com' ) ) {
					bp_activity_update_meta( $activity->id, '_bp_activity_comment_link_preview_data', $comment_link_preview_data );
				}
			}
		}
	}

	// phpcs:enable WordPress.Security.NonceVerification.Missing
}

/**
 * Extract URLs from content
 *
 * @param string $content       The content to extract URLs from.
 * @param bool   $exclude_internal Whether to exclude same-site URLs (default: true).
 * @return array Array of URLs found in content.
 */
function bp_activity_link_preview_extract_urls_from_content( $content, $exclude_internal = true ) {
	// Strip HTML tags so that URLs only in href attributes (e.g. @mention profile links
	// converted to <a href="...">) are not matched — only URLs visible as plain text are extracted.
	$text_content = wp_strip_all_tags( $content );

	$pattern = '/https?:\/\/[^\s<>"]{2,}/i';
	// preg_match_all( $pattern, $text_content, $matches );
	preg_match_all( $pattern, $text_content, $matches );
	$urls = isset( $matches[0] ) ? $matches[0] : array();

	// Filter out same-site URLs (like @mention profile links) if requested.
	if ( $exclude_internal && ! empty( $urls ) ) {
		$urls = array_filter(
			$urls,
			function ( $url ) {
				return ! bp_is_same_site_url( $url );
			}
		);
		$urls = array_values( $urls ); // Re-index array.
	}

	return $urls;
}

/**
 * Check if URL is a social media URL that uses native embeds.
 * These platforms block scraping but have native embed widgets.
 *
 * @param string $url The URL to check.
 * @return bool True if URL is a social media embed URL.
 */
function bp_activity_link_preview_is_social_media_url( $url ) {
	$social_domains = array(
		'x.com',
		'twitter.com',
		'facebook.com',
	);

	foreach ( $social_domains as $domain ) {
		if ( false !== strpos( $url, $domain ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Handle ONLY main activities (exclude comments completely).
 *
 * @param string               $content  The activity content.
 * @param BP_Activity_Activity $activity The activity object.
 * @return string Modified content.
 */
function bp_activity_link_preview_content_body_with_comments( $content, $activity ) {
	static $processed_activities = array();

	$activity_id = $activity->id;

	// Skip if BuddyBoss is active and has its own link preview feature.
	// BuddyBoss renders link previews with class 'bb-activity-link-preview'.
	if ( function_exists( 'buddyboss_theme' ) || class_exists( 'BuddyBoss_Platform' ) ) {
		// Check if BuddyBoss link preview is enabled.
		if ( function_exists( 'bp_is_activity_link_preview_active' ) && bp_is_activity_link_preview_active() ) {
			return $content;
		}
		// Also check content for BuddyBoss preview markup.
		if ( strpos( $content, 'bb-activity-link-preview' ) !== false || strpos( $content, 'bb-url-scrapper' ) !== false ) {
			return $content;
		}
	}

	// Skip if Youzify is active and has its own URL preview feature.
	// Youzify stores link preview in 'url_preview' meta and renders via its own templates.
	if ( defined( 'YOUZIFY_VERSION' ) || class_exists( 'Youzify' ) ) {
		// Check if Youzify URL preview is enabled.
		if ( function_exists( 'youzify_option' ) && 'on' === youzify_option( 'youzify_enable_wall_url_preview', 'on' ) ) {
			return $content;
		}
		// Also check for Youzify preview meta data.
		$youzify_preview = bp_activity_get_meta( $activity_id, 'url_preview', true );
		if ( ! empty( $youzify_preview ) ) {
			return $content;
		}
	}

	// ONLY process main activities, NOT comments.
	if ( 'activity_comment' === $activity->type ) {
		return $content;
	}

	// Prevent duplicate processing for the same activity.
	if ( isset( $processed_activities[ $activity_id ] ) ) {
		return $content;
	}

	// Check if content already contains a preview to avoid double processing.
	if ( strpos( $content, 'activity-link-preview-container' ) !== false ) {
		return $content;
	}

	// Mark this activity as processed.
	$processed_activities[ $activity_id ] = true;

	// Handle main activity link preview only.
	$preview_data = bp_activity_get_meta( $activity_id, '_bp_activity_link_preview_data', true );
	if ( ! empty( $preview_data ) ) {
		$content = bp_activity_link_preview_render_preview( $content, $preview_data );
	}

	return htmlspecialchars_decode( $content );
}

/**
 * Add comment-specific filter to ensure comments get processed.
 *
 * @param string $content The comment content.
 * @return string Modified content.
 */
function bp_activity_link_preview_comment_content( $content ) {
	// Check if comment link previews are enabled.
	if ( ! apply_filters( 'bp_activity_link_preview_enable_comments', true ) ) {
		return $content;
	}

	global $activities_template;

	// Make sure we have an activity object.
	if ( empty( $activities_template->activity ) ) {
		return $content;
	}

	$activity    = $activities_template->activity;
	$activity_id = $activity->current_comment->id;

	// Check if content already contains a preview to avoid double processing.
	if ( strpos( $content, 'activity-comment-link-preview-container' ) !== false ) {
		return $content;
	}

	// Check if we already have preview data.
	$comment_preview_data = bp_activity_get_meta( $activity_id, '_bp_activity_comment_link_preview_data', true );

	if ( ! empty( $comment_preview_data ) ) {
		$content = bp_activity_link_preview_render_preview( $content, $comment_preview_data, true );
	}
	// If no preview data but content has URLs, generate it.
	elseif ( ! empty( $content ) ) {
		$urls = bp_activity_link_preview_extract_urls_from_content( $content, false );
		if ( ! empty( $urls ) ) {
			$url         = $urls[0];
			$parsed_data = bp_activity_link_parse_url( $url );

			// Check if it's a social media URL that uses native embeds.
			$is_social_media = bp_activity_link_preview_is_social_media_url( $url );

			// Generate preview if we have title/description OR if it's a social media URL.
			if ( ! empty( $parsed_data ) && ( ! empty( $parsed_data['title'] ) || ! empty( $parsed_data['description'] ) || $is_social_media ) ) {
				$comment_link_preview_data = array(
					'url'         => $url,
					'title'       => ! empty( $parsed_data['title'] ) ? $parsed_data['title'] : '',
					'description' => ! empty( $parsed_data['description'] ) ? $parsed_data['description'] : '',
					'image_url'   => ! empty( $parsed_data['images'] ) ? $parsed_data['images'][0] : '',
				);

				if ( false === strpos( $url, 'www.reddit.com' ) ) {
					// Save for future use and render.
					bp_activity_update_meta( $activity_id, '_bp_activity_comment_link_preview_data', $comment_link_preview_data );
					$content = bp_activity_link_preview_render_preview( $content, $comment_link_preview_data, true );
				}
			}
		}
	}

	return $content;
}

/**
 * Register comment content filter after plugins are loaded.
 * This allows other plugins to disable comment previews via filter.
 */
function bp_activity_link_preview_init_comment_filter() {
	if ( apply_filters( 'bp_activity_link_preview_enable_comments', true ) ) {
		add_filter( 'bp_activity_comment_content', 'bp_activity_link_preview_comment_content' );
	}
}

/**
 * Helper function to render preview.
 *
 * @param string $content      The activity content.
 * @param array  $preview_data The preview data array.
 * @param bool   $is_comment   Whether this is a comment preview.
 * @return string Modified content with preview appended.
 */
function bp_activity_link_preview_render_preview( $content, $preview_data, $is_comment = false ) {
	$preview_data = bp_parse_args(
		$preview_data,
		array(
			'title'       => '',
			'description' => '',
		)
	);

	if ( empty( $preview_data['url'] ) ) {
		return $content;
	}

	$css_class = $is_comment ? 'activity-comment-link-preview-container' : 'activity-link-preview-container';

	// Social media embeds use native widgets, so bypass the title/description check.
	if ( true === str_contains( $preview_data['url'], 'x.com' ) || true === str_contains( $preview_data['url'], 'twitter.com' ) ) {
		$content .= '<div class="' . esc_attr( $css_class ) . '" data-url="' . esc_attr( $preview_data['url'] ) . '"></div>';
		return $content;
	} elseif ( true === str_contains( $preview_data['url'], 'facebook.com' ) ) {
		$content .= '<div class="fb-post" data-href="' . esc_attr( $preview_data['url'] ) . '" data-width="500" data-height="500"></div>';
		return $content;
	}

	// For regular URLs, require title or description for the preview.
	if ( empty( trim( $preview_data['title'] ) ) && empty( trim( $preview_data['description'] ) ) ) {
		return $content;
	}

	// Regular link preview.
	$description = $preview_data['description'];
	$read_more   = ' &hellip; <a class="activity-link-preview-more" href="' . esc_url( $preview_data['url'] ) . '" target="_blank" rel="nofollow">' . __( 'Read more', 'buddypress-activity-link-preview' ) . '</a>';
	$description = wp_trim_words( $description, 40, $read_more );

	$content = make_clickable( $content );

	$content .= '<div class="' . esc_attr( $css_class ) . '">';
	$content .= '<p class="activity-link-preview-title"><a href="' . esc_url( $preview_data['url'] ) . '" target="_blank" rel="nofollow">' . esc_html( $preview_data['title'] ) . '</a></p>';
	if ( ! empty( $preview_data['image_url'] ) ) {
		$content .= '<div class="activity-link-preview-image">';
		$content .= '<a href="' . esc_url( $preview_data['url'] ) . '" target="_blank"><img src="' . esc_url( $preview_data['image_url'] ) . '" /></a>';
		$content .= '</div>';
	}
	$content .= '<div class="activity-link-preview-excerpt"><p>' . $description . '</p></div>';
	$content .= '</div>';

	return $content;
}

/**
 * Add allowed HTML tags for link previews in BuddyPress activity content.
 *
 * @param array $tags Allowed HTML tags.
 * @return array Modified allowed tags.
 */
function bp_activity_link_preview_allowed_tags( $tags ) {
	$tags['div'] = array(
		'class'       => array(),
		'id'          => array(),
		'style'       => array(),
		'data-url'    => array(),
		'data-href'   => array(),
		'data-width'  => array(),
		'data-height' => array(),
	);

	$tags['img'] = array(
		'src'    => array(),
		'alt'    => array(),
		'width'  => array(),
		'height' => array(),
		'class'  => array(),
		'id'     => array(),
	);

	$tags['button'] = array(
		'type'  => array(),
		'id'    => array(),
		'class' => array(),
	);

	$tags['span'] = array(
		'class' => array(),
		'id'    => array(),
	);

	$tags['i'] = array(
		'class' => array(),
		'id'    => array(),
	);

	return $tags;
}

/**
 * Embed bp activity link preview data in rest api activity endpoint.
 *
 * @param WP_REST_Response     $response The response object.
 * @param WP_REST_Request      $request  The request object.
 * @param BP_Activity_Activity $activity The activity object.
 * @return WP_REST_Response Modified response.
 */
function bp_activity_link_preview_data_embed_rest_api( $response, $request, $activity ) {
	$bp_activity_link_data              = bp_activity_get_meta( $activity->id, '_bp_activity_link_preview_data', true );
	$response->data['bp_activity_link'] = $bp_activity_link_data;

	// Add comment link preview data if it's a comment.
	if ( 'activity_comment' === $activity->type ) {
		$bp_activity_comment_link_data              = bp_activity_get_meta( $activity->id, '_bp_activity_comment_link_preview_data', true );
		$response->data['bp_activity_comment_link'] = $bp_activity_comment_link_data;
	}

	return $response;
}

/**
 * Outputs a Facebook root div element in specific BuddyPress contexts.
 */
function bp_activity_link_preview_add_facebook_root_div() {
	if ( bp_is_activity_directory() || bp_is_group() || bp_is_user_activity() ) {
		echo '<div id="fb-root"></div>';
	}
}

/**
 * DEVELOPER DOCUMENTATION
 *
 * How to disable comment link previews:
 * Add this to your theme's functions.php or plugin:
 *
 * // Disable comment link previews completely
 * add_filter( 'bp_activity_link_preview_enable_comments', '__return_false' );
 *
 * // Or conditionally disable for specific user roles
 * add_filter( 'bp_activity_link_preview_enable_comments', function( $enabled ) {
 *     if ( current_user_can( 'some_capability' ) ) {
 *         return false; // Disable for users with this capability
 *     }
 *     return $enabled; // Keep enabled for others
 * });
 *
 * // Or disable on specific pages
 * add_filter( 'bp_activity_link_preview_enable_comments', function( $enabled ) {
 *     if ( bp_is_group() ) {
 *         return false; // Disable in groups
 *     }
 *     return $enabled;
 * });
 */