<?php
/**
 * BuddyPress Activity Link preview
 *
 * Plugin Name:       Activity Link Preview For BuddyPress
 * Plugin URI:        https://wbcomdesigns.com/downloads/buddypress-activity-link-preview/
 * Description:       BuddyPress activity link preview displays as image title and description from the site when links are used in activity posts.
 * Version:           1.7.4
 * Author:            wbcomdesigns
 * Author URI:        https://wbcomdesigns.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       buddypress-activity-link-preview
 * Domain Path:       /languages
 * Requires at least: 5.9
 * Requires PHP:      7.4
 *
 * @package           Buddypress-activity-link-preview
 * @link              https://wbcomdesigns.com/
 * @since             1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BP_ACTIVITY_LINK_PREVIEW_VERSION', '1.7.4' );
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

/**
 * Determine whether the current page renders a BuddyPress activity stream
 * and therefore needs the link-preview assets (own CSS/JS + Twitter/Facebook SDKs).
 *
 * @since 1.7.4
 * @return bool True when assets should be enqueued.
 */
function bp_activity_link_preview_should_load_assets() {
	$should_load = function_exists( 'bp_is_activity_component' )
		&& ( bp_is_activity_component() || bp_is_activity_directory() || bp_is_user_activity() || bp_is_group() );

	/**
	 * Filter whether the link-preview assets load on the current page.
	 *
	 * Lets site owners load assets on custom pages that embed an activity
	 * stream (e.g. via shortcode/widget) or unload them from group screens.
	 *
	 * @since 1.7.4
	 * @param bool $should_load Whether the current page is an activity context.
	 */
	return (bool) apply_filters( 'bp_activity_link_preview_load_assets', $should_load );
}

/** Bp_activity_link_preview_enqueue_scripts */
function bp_activity_link_preview_enqueue_scripts() {
	// Do not load plugin assets (or third-party SDKs) sitewide - activity contexts only.
	if ( ! bp_activity_link_preview_should_load_assets() ) {
		return;
	}

	// The composer JS renders dashicon markup (close/prev/next buttons); dashicons
	// is not guaranteed on the frontend (only loads with the admin bar), so enqueue it.
	wp_enqueue_style( 'dashicons' );
	wp_enqueue_style( 'bp-activity-link-preview-css', BP_ACTIVITY_LINK_PREVIEW_URL . 'assets/css/bp-activity-link-preview.css', array(), BP_ACTIVITY_LINK_PREVIEW_VERSION, 'all' );
	wp_enqueue_script( 'twitter-js', 'https://platform.twitter.com/widgets.js', array( 'jquery' ), BP_ACTIVITY_LINK_PREVIEW_VERSION, true );
	wp_enqueue_script( 'facebook-js', 'https://connect.facebook.net/en_US/sdk.js#xfbml=1&version=v21.0', array( 'jquery' ), BP_ACTIVITY_LINK_PREVIEW_VERSION, true );
	wp_enqueue_script( 'bp-activity-link-preview-js', BP_ACTIVITY_LINK_PREVIEW_URL . 'assets/js/bp-activity-link-preview.js', array( 'jquery' ), BP_ACTIVITY_LINK_PREVIEW_VERSION, true );

	// Detect if BuddyBoss is active with its own link preview (the JS stands
	// down for BuddyBoss's native preview to avoid duplicate cards).
	$buddyboss_active              = function_exists( 'buddyboss_theme' ) || class_exists( 'BuddyBoss_Platform' );
	$buddyboss_link_preview_active = $buddyboss_active && function_exists( 'bp_is_activity_link_preview_active' ) && bp_is_activity_link_preview_active();

	// Only the keys the frontend script actually reads are localized. Youzify
	// stand-down is handled server-side in the render path, so no Youzify flags
	// are passed to JS.
	wp_localize_script(
		'bp-activity-link-preview-js',
		'bp_activity_link_preview',
		array(
			'ajaxurl'                       => admin_url( 'admin-ajax.php' ),
			'nonce'                         => wp_create_nonce( 'bp_activity_link_preview_nonce' ),
			'buddyboss_link_preview_active' => $buddyboss_link_preview_active,
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
	if ( bp_activity_link_preview_is_blocked_host( $host ) ) {
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
 * Determine whether a host points at a private, loopback, or reserved address.
 *
 * Centralizes the SSRF host guard so it can be applied both to the original
 * input URL in the AJAX handler and to any redirect-resolved URL during
 * short-URL resolution. Returns true when the host should be BLOCKED.
 *
 * @since 1.7.4
 * @param string $host The host portion of a URL (no scheme/path).
 * @return bool True if the host must be blocked for security reasons.
 */
function bp_activity_link_preview_is_blocked_host( $host ) {
	if ( empty( $host ) ) {
		return true;
	}

	if ( filter_var( $host, FILTER_VALIDATE_IP ) &&
		( false === filter_var( $host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) )
	) {
		return true;
	}

	if ( '127.0.0.1' === $host || 'localhost' === $host ) {
		return true;
	}

	if ( preg_match( '/^(10\.|172\.(1[6-9]|2[0-9]|3[0-1])\.|192\.168\.)/', $host ) ) {
		return true;
	}

	return false;
}

/**
 * Parse a URL and return preview data.
 *
 * @param string $url         The URL to parse.
 * @param bool   $cached_only When true, never performs a remote HTTP request:
 *                            only the transient cache and the internal-URL
 *                            (local DB) fast path are consulted. Used by
 *                            render-time code so page views never block on
 *                            external fetches.
 * @return array Parsed URL data (empty array when unavailable).
 */
function bp_activity_link_parse_url( $url, $cached_only = false ) {
	$parse_url_data = wp_parse_url( $url, PHP_URL_HOST );
	$original_url   = $url;

	if ( ! $cached_only && in_array( $parse_url_data, apply_filters( 'bp_activity_link_parse_url_shorten_url_provider', array( 'bit.ly', 'snip.ly', 'rb.gy', 'tinyurl.com', 'tiny.one', 'rotf.lol', 'b.link', '4ubr.short.gy', '' ) ), true ) ) {
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
			// Fallback: resolve the short URL through the WordPress HTTP API so the
			// request honours WP_HTTP_BLOCK_EXTERNAL and the configured transports.
			// Follow a single redirect and read the resolved Location.
			$head_response = wp_safe_remote_head(
				$url,
				array(
					'redirection' => 1,
					'timeout'     => 5,
					'headers'     => array(
						'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:71.0) Gecko/20100101 Firefox/71.0',
					),
				)
			);

			if ( ! is_wp_error( $head_response ) ) {
				$new_url = wp_remote_retrieve_header( $head_response, 'location' );

				if ( ! empty( $new_url ) && filter_var( $new_url, FILTER_VALIDATE_URL ) ) {
					// Re-validate the redirect-resolved host against the same SSRF
					// guard used on the original input URL. A malicious or compromised
					// short-URL provider could redirect to a private/loopback address;
					// if so, abort resolution and keep the original URL.
					$resolved_host = wp_parse_url( $new_url, PHP_URL_HOST );
					if ( ! bp_activity_link_preview_is_blocked_host( $resolved_host ) ) {
						$url = $new_url;
					}
				}
			}
		}
	}

	$cache_key       = 'bp_oembed_' . md5( maybe_serialize( $url ) );
	$parsed_url_data = get_transient( $cache_key );

	// Negative cache hit: a recent parse failed - do not retry on every call.
	if ( 'bpalp_failed' === $parsed_url_data ) {
		return apply_filters( 'bp_activity_link_parse_url', array() );
	}

	if ( ! empty( $parsed_url_data ) ) {
		return $parsed_url_data;
	}

	$parsed_url_data = array();

	if ( strstr( $url, site_url() ) && ( strstr( $url, 'download_document_file' ) || strstr( $url, 'download_media_file' ) || strstr( $url, 'download_video_file' ) ) ) {
		return array();
	}

	if ( $cached_only ) {
		// Internal URLs resolve from the local database (no HTTP), so they are
		// still allowed in cached-only mode; anything else returns empty rather
		// than fetching a remote page synchronously during render.
		if ( bp_is_same_site_url( $url ) ) {
			$internal_data = bp_activity_link_parse_internal_url( $url );
			if ( ! empty( $internal_data ) ) {
				set_transient( $cache_key, $internal_data, DAY_IN_SECONDS );
				return apply_filters( 'bp_activity_link_parse_url', $internal_data );
			}
		}
		return apply_filters( 'bp_activity_link_parse_url', array() );
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
		// Try to parse internal URLs directly without HTTP request (avoids self-request performance issues).
		if ( bp_is_same_site_url( $url ) ) {
			$internal_data = bp_activity_link_parse_internal_url( $url );
			if ( ! empty( $internal_data ) ) {
				$parsed_url_data = $internal_data;
				// Cache the result.
				if ( ! empty( $parsed_url_data ) ) {
					set_transient( $cache_key, $parsed_url_data, DAY_IN_SECONDS );
				}
				return apply_filters( 'bp_activity_link_parse_url', $parsed_url_data );
			}
		}

		$args = array(
			'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:71.0) Gecko/20100101 Firefox/71.0',
			'timeout'    => 15,
		);

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
							// DOMDocument already decodes attribute entities once; decoding again
							// would turn a double-encoded payload on a remote page into live markup.
							$description = $tag[1];
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
	} else {
		// Negative cache: remember the failure for a short time so slow,
		// blocked, or unreachable URLs are not re-fetched on every request.
		set_transient( $cache_key, 'bpalp_failed', 15 * MINUTE_IN_SECONDS );
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
 * Parse internal BuddyPress/BuddyBoss URLs directly without HTTP requests.
 * This avoids self-requests that cause performance issues on live sites.
 *
 * @param string $url The internal URL to parse.
 * @return array|false Preview data array or false if not an internal BP page.
 */
function bp_activity_link_parse_internal_url( $url ) {
	if ( ! bp_is_same_site_url( $url ) ) {
		return false;
	}

	$parsed_url = wp_parse_url( $url );
	$path       = isset( $parsed_url['path'] ) ? trim( $parsed_url['path'], '/' ) : '';

	if ( empty( $path ) ) {
		return false;
	}

	// Remove the site path if it's in the URL (for subdirectory installs).
	$site_path = trim( wp_parse_url( home_url( '/' ), PHP_URL_PATH ), '/' );
	if ( ! empty( $site_path ) && 0 === strpos( $path, $site_path ) ) {
		$path = trim( substr( $path, strlen( $site_path ) ), '/' );
	}

	$path_parts = explode( '/', $path );

	// Check for BuddyPress member profile: /members/username/ or /user/username/.
	if ( ( isset( $path_parts[0] ) && in_array( $path_parts[0], array( 'members', 'user' ), true ) ) && ! empty( $path_parts[1] ) ) {
		$username = sanitize_user( $path_parts[1] );
		$user     = get_user_by( 'slug', $username );

		if ( ! $user && is_numeric( $username ) ) {
			$user = get_user_by( 'id', $username );
		}

		if ( $user ) {
			// Get user data.
			$display_name = $user->display_name;

			// Try to get BuddyPress/XProfile data.
			if ( function_exists( 'bp_get_profile_field_data' ) ) {
				$args  = array(
					'field'   => 'About',
					'user_id' => $user->ID,
				);
				$about = bp_get_profile_field_data( $args );
				if ( empty( $about ) ) {
					$args['field'] = 'Description';
					$about         = bp_get_profile_field_data( $args );
				}
				if ( empty( $about ) ) {
					$about = get_user_meta( $user->ID, 'description', true );
				}
			} else {
				$about = get_user_meta( $user->ID, 'description', true );
			}

			// Get user avatar URL.
			$avatar_url = '';
			if ( function_exists( 'bp_core_fetch_avatar' ) ) {
				$avatar_url = bp_core_fetch_avatar(
					array(
						'item_id' => $user->ID,
						'object'  => 'user',
						'type'    => 'full',
						'html'    => false,
					)
				);
			}
			if ( empty( $avatar_url ) ) {
				$avatar_url = get_avatar_url( $user->ID, array( 'size' => 150 ) );
			}

			return array(
				'title'       => $display_name,
				/* translators: %s: member display name. */
				'description' => ! empty( $about ) ? wp_trim_words( $about, 30, '...' ) : sprintf( __( 'View %s\'s profile', 'buddypress-activity-link-preview' ), $display_name ),
				'images'      => ! empty( $avatar_url ) ? array( $avatar_url ) : array(),
				'error'       => '',
			);
		}
	}

	// Check for BuddyPress groups: /groups/group-name/.
	if ( isset( $path_parts[0] ) && 'groups' === $path_parts[0] && ! empty( $path_parts[1] ) ) {
		$group_slug = sanitize_text_field( $path_parts[1] );

		if ( function_exists( 'groups_get_groups' ) ) {
			$groups = groups_get_groups(
				array(
					'slug'     => $group_slug,
					'per_page' => 1,
				)
			);

			if ( ! empty( $groups['groups'] ) ) {
				$group = $groups['groups'][0];

				// Get group avatar.
				$avatar_url = '';
				if ( function_exists( 'bp_core_fetch_avatar' ) ) {
					$avatar_url = bp_core_fetch_avatar(
						array(
							'item_id'    => $group->id,
							'object'     => 'group',
							'type'       => 'full',
							'html'       => false,
							'avatar_dir' => 'group-avatars',
						)
					);
				}

				// Get group description.
				$description = '';
				if ( ! empty( $group->description ) ) {
					$description = wp_trim_words( $group->description, 30, '...' );
				}

				return array(
					'title'       => $group->name,
					/* translators: %s: group name. */
					'description' => ! empty( $description ) ? $description : sprintf( __( 'View the %s group', 'buddypress-activity-link-preview' ), $group->name ),
					'images'      => ! empty( $avatar_url ) ? array( $avatar_url ) : array(),
					'error'       => '',
				);
			}
		}
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
		$link_wp_embed            = ! empty( $_POST['link_wp_embed'] );
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

		// Persist oEmbed videos (YouTube, Vimeo, etc.). The composer flags these
		// with link_wp_embed; the embed HTML is regenerated server-side from the
		// URL (wp_oembed_get, whitelisted providers only) so the saved activity
		// renders the player without a live external fetch at render time.
		if ( $link_wp_embed && ! empty( $link_url ) ) {
			$embed_html = wp_oembed_get( $link_url );
			if ( ! empty( $embed_html ) ) {
				$link_preview_data['wp_embed']   = true;
				$link_preview_data['embed_html'] = $embed_html;
			}
		}

		bp_activity_update_meta( $activity->id, '_bp_activity_link_preview_data', $link_preview_data );
	}

	// Handle comment link previews (only if enabled).
	if ( apply_filters( 'bp_activity_link_preview_enable_comments', true ) && 'activity_comment' === $activity->type && isset( $_POST['comment_link_url'] ) && isset( $_POST['comment_link_title'] ) && isset( $_POST['comment_link_description'] ) && isset( $_POST['comment_link_image'] ) ) {
		$comment_link_url         = ! empty( $_POST['comment_link_url'] ) ? sanitize_text_field( wp_unslash( $_POST['comment_link_url'] ) ) : '';
		$comment_link_title       = ! empty( $_POST['comment_link_title'] ) ? sanitize_text_field( wp_unslash( $_POST['comment_link_title'] ) ) : '';
		$comment_link_description = ! empty( $_POST['comment_link_description'] ) ? sanitize_text_field( wp_unslash( $_POST['comment_link_description'] ) ) : '';
		$comment_link_image       = ! empty( $_POST['comment_link_image'] ) ? sanitize_text_field( wp_unslash( $_POST['comment_link_image'] ) ) : '';
		$comment_link_wp_embed    = ! empty( $_POST['comment_link_wp_embed'] );

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

		// Persist oEmbed videos in comments (see main-activity handling above).
		if ( $comment_link_wp_embed && ! empty( $comment_link_url ) ) {
			$comment_embed_html = wp_oembed_get( $comment_link_url );
			if ( ! empty( $comment_embed_html ) ) {
				$comment_link_preview_data['wp_embed']   = true;
				$comment_link_preview_data['embed_html'] = $comment_embed_html;
			}
		}

		bp_activity_update_meta( $activity->id, '_bp_activity_comment_link_preview_data', $comment_link_preview_data );
	} elseif ( apply_filters( 'bp_activity_link_preview_enable_comments', true ) && 'activity_comment' === $activity->type && ! empty( $activity->content ) ) {
		// Fallback: comment has no saved preview data but its content has URLs, so extract and save (only if enabled).
		$urls = bp_activity_link_preview_extract_urls_from_content( $activity->content, false );
		if ( ! empty( $urls ) ) {
			$url         = $urls[0]; // Use first URL found.
			$parsed_data = bp_activity_link_parse_url( $url );

			// Check if it's a social media URL that uses native embeds (Twitter, Facebook).
			$is_social_media = bp_activity_link_preview_is_social_media_url( $url );

			// Save preview data if we have title/description OR if it's a social media URL.
			if ( ! empty( $parsed_data ) && ( ! empty( $parsed_data['title'] ) || ! empty( $parsed_data['description'] ) || $is_social_media ) ) {
				// Scraped remote content is untrusted - sanitize before storing in activity meta.
				$comment_link_preview_data = array(
					'url'         => esc_url_raw( $url ),
					'title'       => ! empty( $parsed_data['title'] ) ? sanitize_text_field( wp_strip_all_tags( $parsed_data['title'] ) ) : '',
					'description' => ! empty( $parsed_data['description'] ) ? sanitize_text_field( wp_strip_all_tags( $parsed_data['description'] ) ) : '',
					'image_url'   => ! empty( $parsed_data['images'] ) ? esc_url_raw( $parsed_data['images'][0] ) : '',
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
	} elseif ( ! empty( $content ) ) {
		// No saved preview data but the content has URLs: generate from cache only.
		// Render must never block on a live external fetch; save-time extraction
		// (bp_activity_link_preview_save_link_data) is responsible for fetching.
		$urls = bp_activity_link_preview_extract_urls_from_content( $content, false );
		if ( ! empty( $urls ) ) {
			$url         = $urls[0];
			$parsed_data = bp_activity_link_parse_url( $url, true );

			// Check if it's a social media URL that uses native embeds.
			$is_social_media = bp_activity_link_preview_is_social_media_url( $url );

			// Generate preview if we have title/description OR if it's a social media URL.
			if ( ! empty( $parsed_data ) && ( ! empty( $parsed_data['title'] ) || ! empty( $parsed_data['description'] ) || $is_social_media ) ) {
				// Scraped remote content is untrusted - sanitize before storing in activity meta.
				$comment_link_preview_data = array(
					'url'         => esc_url_raw( $url ),
					'title'       => ! empty( $parsed_data['title'] ) ? sanitize_text_field( wp_strip_all_tags( $parsed_data['title'] ) ) : '',
					'description' => ! empty( $parsed_data['description'] ) ? sanitize_text_field( wp_strip_all_tags( $parsed_data['description'] ) ) : '',
					'image_url'   => ! empty( $parsed_data['images'] ) ? esc_url_raw( $parsed_data['images'][0] ) : '',
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

	// oEmbed videos (YouTube, Vimeo, etc.): output the embed HTML that was
	// generated by wp_oembed_get() and persisted at save time. It is filtered
	// through bp_activity_allowed_tags (which allows iframe) on display, so no
	// live external fetch happens at render time.
	if ( ! empty( $preview_data['wp_embed'] ) && ! empty( $preview_data['embed_html'] ) ) {
		$content .= '<div class="' . esc_attr( $css_class ) . ' activity-video-preview">' . $preview_data['embed_html'] . '</div>';
		return $content;
	}

	// For regular URLs, require title or description for the preview.
	if ( empty( trim( $preview_data['title'] ) ) && empty( trim( $preview_data['description'] ) ) ) {
		return $content;
	}

	// Regular link preview. Escape the stored description before output; the
	// "Read more" anchor is appended afterwards so it stays intact.
	$description = esc_html( wp_strip_all_tags( $preview_data['description'] ) );
	$read_more   = ' &hellip; <a class="activity-link-preview-more" href="' . esc_url( $preview_data['url'] ) . '" target="_blank" rel="nofollow">' . esc_html__( 'Read more', 'buddypress-activity-link-preview' ) . '</a>';
	$description = wp_trim_words( $description, 40, $read_more );

	$content = make_clickable( $content );

	$content .= '<div class="' . esc_attr( $css_class ) . '">';
	$content .= '<p class="activity-link-preview-title"><a href="' . esc_url( $preview_data['url'] ) . '" target="_blank" rel="nofollow">' . esc_html( $preview_data['title'] ) . '</a></p>';
	if ( ! empty( $preview_data['image_url'] ) ) {
		$content .= '<div class="activity-link-preview-image">';
		$content .= '<a href="' . esc_url( $preview_data['url'] ) . '" target="_blank"><img src="' . esc_url( $preview_data['image_url'] ) . '" alt="' . esc_attr( $preview_data['title'] ) . '" /></a>';
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

	// Allow oEmbed video iframes (YouTube, Vimeo, etc.). The iframe markup is
	// produced server-side by wp_oembed_get() for whitelisted providers only,
	// so it is trusted; this allowlist scopes which attributes survive display.
	$tags['iframe'] = array(
		'src'             => array(),
		'width'           => array(),
		'height'          => array(),
		'title'           => array(),
		'class'           => array(),
		'style'           => array(),
		'frameborder'     => array(),
		'allow'           => array(),
		'allowfullscreen' => array(),
		'loading'         => array(),
		'referrerpolicy'  => array(),
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
	if ( bp_activity_link_preview_should_load_assets() ) {
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