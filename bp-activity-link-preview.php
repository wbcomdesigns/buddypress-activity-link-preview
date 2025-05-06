<?php
/**
 * BuddyPress Activity Link preview
 *
 * Plugin Name:       Activity Link Preview For BuddyPress
 * Plugin URI:        https://wbcomdesigns.com/downloads/buddypress-activity-link-preview/
 * Description:       BuddyPress activity link preview display as image title and description from the site When links are used in activity posts.
 * Version:           1.6.0
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

define( 'BP_ACTIVITY_LINK_PREVIEW_URL', plugin_dir_url( __FILE__ ) );
define( 'BP_ACTIVITY_LINK_PREVIEW_PATH', plugin_dir_path( __FILE__ ) );

/** Bp_activity_link_preview_enqueue_scripts */
function bp_activity_link_preview_enqueue_scripts() {
	wp_enqueue_style( 'bp-activity-link-preview-css', BP_ACTIVITY_LINK_PREVIEW_URL . 'assets/css/bp-activity-link-preview.css', array(), '1.0.0', 'all' );
	wp_enqueue_script( 'twitter-js', 'https://platform.twitter.com/widgets.js', array( 'jquery' ), '1.0.0' );
	wp_enqueue_script( 'facebook-js', 'https://connect.facebook.net/en_US/sdk.js#xfbml=1&version=v14.0', array( 'jquery' ), '1.0.0' );
	wp_enqueue_script( 'bp-activity-link-preview-js', BP_ACTIVITY_LINK_PREVIEW_URL . 'assets/js/bp-activity-link-preview.js', array( 'jquery' ), '1.0.0' );
}
add_action( 'wp_enqueue_scripts', 'bp_activity_link_preview_enqueue_scripts' );


/** Bp_activity_parse_url_preview */
function bp_activity_parse_url_preview() {

	// Check if user is logged in
	if ( ! is_user_logged_in() ) {
		wp_send_json( array( 'error' => __( 'You must be logged in to perform this action.', 'buddypress-activity-link-preview' ) ) );
	}
	// Get URL.
    $url = ! empty( $_POST['url'] ) ? filter_var( $_POST['url'], FILTER_VALIDATE_URL ) : '';// phpcs:ignore

	// Check if URL is validated.
	if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
		wp_send_json( array( 'error' => __( 'The URL you entered is not valid.', 'buddypress-activity-link-preview' ) ) );
	}

	// Parse URL to get host
	$parsed_url = parse_url( $url );
	$host       = isset( $parsed_url['host'] ) ? $parsed_url['host'] : '';

	// Block requests to private/internal IP ranges and localhost
	if ( empty( $host ) ||
		( filter_var( $host, FILTER_VALIDATE_IP ) &&
		( filter_var( $host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) === false ) ) ||
		$host === '127.0.0.1' ||
		$host === 'localhost' ||
		preg_match( '/^(10\.|172\.(1[6-9]|2[0-9]|3[0-1])\.|192\.168\.)/', $host )
	) {
		wp_send_json( array( 'error' => __( 'This URL cannot be previewed for security reasons.', 'buddypress-activity-link-preview' ) ) );
	}

	$parse_url_data = bp_activity_link_parse_url( $url );

	// If empty data then send error.
	if ( empty( $parse_url_data ) ) {
		wp_send_json( array( 'error' => __( 'Sorry! Preview is not available right now. Please try again later.', 'buddypress-activity-link-preview' ) ) );
	}

	// Apply filter to allow modification of parsed data
	$parse_url_data = apply_filters( 'bp_activity_parse_url_preview', $parse_url_data, $url );

	// send json success.
	wp_send_json( $parse_url_data );
}

add_action( 'wp_ajax_bp_activity_parse_url_preview', 'bp_activity_parse_url_preview' );
add_action( 'wp_ajax_nopriv_bp_activity_parse_url_preview', 'bp_activity_parse_url_preview' );

/**
 * Bp_activity_link_parse_url
 *
 * @param url $url url.
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

			@file_get_contents( $url, null, stream_context_create( $context ) );
			if ( isset( $http_response_header ) && isset( $http_response_header[6] ) ) {
				$new_url = str_replace( 'Location: ', '', $http_response_header[6] );
				if ( filter_var( $new_url, FILTER_VALIDATE_URL ) ) {
					$url = $new_url;
				}
			}
		}
	}

	$cache_key = 'bp_oembed_' . md5( maybe_serialize( $url ) );

	// get transient data for url.
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
			$args['sslverify'] = false;
		}

		// safely get URL and response body.
		$response = wp_safe_remote_get( $url, $args );
		$body     = wp_remote_retrieve_body( $response );

		// if response is not empty.
		if ( ! is_wp_error( $body ) && ! empty( $body ) ) {

			// Load HTML to DOM Object.
			$dom = new DOMDocument();
			@$dom->loadHTML( mb_convert_encoding( $body, 'HTML-ENTITIES', 'UTF-8' ) );

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
						if ( $tag[0] == 'og:title' ) {
							$title = $tag[1];
						}
						if ( $tag[0] == 'og:description' || 'description' === strtolower( $tag[0] ) ) {
							$description = html_entity_decode( $tag[1], ENT_QUOTES, 'utf-8' );
						}
						if ( $tag[0] == 'og:image' ) {
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

	/**
	 * Filters parsed URL data.
	 *
	 * @since 1.4.6
	 *
	 * * @param array $parsed_url_data Parse URL data.
	 */
	return apply_filters( 'bp_activity_link_parse_url', $parsed_url_data );
}


/**
 * Check if the requested URL is from same site.
 *
 * @since 1.4.6
 *
 * @param string $url URL to check.
 *
 * @return bool
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
 * Save link preview data into activity meta key "_bp_activity_link_preview_data"
 *
 * @since BuddyPress 1.0.0
 *
 * @param activity $activity activity.
 */
function bp_activity_link_preview_save_link_data( $activity ) {

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
}


add_action( 'bp_activity_after_save', 'bp_activity_link_preview_save_link_data', 10, 1 );

/**
 * Bp_activity_link_preview_content_body
 *
 * @since BuddyPress 1.0.0
 * @param content  $content content.
 * @param activity $activity activity.
 */
function bp_activity_link_preview_content_body( $content, $activity ) {

	$activity_id = $activity->id;

	$preview_data = bp_activity_get_meta( $activity_id, '_bp_activity_link_preview_data', true );
	$preview_data = bp_parse_args(
		$preview_data,
		array(
			'title'       => '',
			'description' => '',
		)
	);

	if ( empty( $preview_data['url'] ) || ( empty( trim( $preview_data['title'] ) ) && empty( trim( $preview_data['description'] ) ) ) ) {
		return $content;
	}
	if ( true === str_contains( $preview_data['url'], 'x.com' ) ) {
		$content .= '<div class="activity-link-preview-container" data-url="' . esc_attr( $preview_data['url'] ) . '"></div>';
	} elseif ( true === str_contains( $preview_data['url'], 'facebook.com' ) ) {
		$content .= '<div class="fb-post" data-href="' . esc_attr( $preview_data['url'] ) . '" data-width="500" data-height="500"></div>';
	} else {
		$description = $preview_data['description'];
		$read_more   = ' &hellip; <a class="activity-link-preview-more" href="' . esc_url( $preview_data['url'] ) . '" target="_blank" rel="nofollow">' . __( 'Read more', 'buddypress-activity-link-preview' ) . '</a>';
		$description = wp_trim_words( $description, 40, $read_more );

		$content = make_clickable( $content );

		$content .= '<div class="activity-link-preview-container">';
		$content .= '<p class="activity-link-preview-title"><a href="' . esc_url( $preview_data['url'] ) . '" target="_blank" rel="nofollow">' . esc_html( $preview_data['title'] ) . '</a></p>';
		if ( ! empty( $preview_data['image_url'] ) ) {
			$content .= '<div class="activity-link-preview-image">';
			$content .= '<a href="' . esc_url( $preview_data['url'] ) . '" target="_blank"><img src="' . esc_url( $preview_data['image_url'] ) . '" /></a>';
			$content .= '</div>';
		}
		$content .= '<div class="activity-link-preview-excerpt"><p>' . $description . '</p></div>';
		$content .= '</div>';
	}

	return htmlspecialchars_decode( $content );
}

add_filter( 'bp_get_activity_content_body', 'bp_activity_link_preview_content_body', 8, 2 );

/**
 *  Check if buddypress activate.
 */
function bp_activity_link_preview_requires_buddypress() {
	if ( ! class_exists( 'Buddypress' ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		add_action( 'admin_notices', 'bp_activity_link_preview_required_plugin_admin_notice' );
		if ( null !== filter_input( INPUT_GET, 'activate' ) ) {
			$activate = filter_input( INPUT_GET, 'activate' );
			unset( $activate );
		}
	}
}

add_action( 'admin_init', 'bp_activity_link_preview_requires_buddypress' );
/**
 * Throw an Alert to tell the Admin why it didn't activate.
 *
 * @author wbcomdesigns
 * @since  1.2.0
 */
function bp_activity_link_preview_required_plugin_admin_notice() {
	$bpquotes_plugin = esc_html__( 'Activity Link Preview For BuddyPress', 'buddypress-activity-link-preview' );
	$bp_plugin       = esc_html__( 'BuddyPress', 'buddypress-activity-link-preview' );
	echo '<div class="error"><p>';
	/* translators: %s: */
	printf( esc_html__( '%1$s is ineffective because it requires %2$s to be installed and active.', 'buddypress-activity-link-preview' ), '<strong>' . esc_html( $bpquotes_plugin ) . '</strong>', '<strong>' . esc_html( $bp_plugin ) . '</strong>' );
	echo '</p></div>';
	if ( null !== filter_input( INPUT_GET, 'activate' ) ) {
		$activate = filter_input( INPUT_GET, 'activate' );
		unset( $activate );
	}
}

add_filter( 'bp_rest_activity_prepare_value', 'bp_activity_link_preview_data_embed_rest_api', 10, 3 );

/**
 * Embed bp activity link preview data in rest api activity endpoint.
 *
 * @param  object $response get response data.
 * @param  object $request get request data.
 * @param  array  $activity get activity data.
 * @return $response
 */
function bp_activity_link_preview_data_embed_rest_api( $response, $request, $activity ) {
	$bp_activity_link_data              = bp_activity_get_meta( $activity->id, '_bp_activity_link_preview_data', true );
	$response->data['bp_activity_link'] = $bp_activity_link_data;
	return $response;
}


/**
 * Outputs a Facebook root div element in specific BuddyPress contexts.
 *
 * This function checks if the current page is one of the following:
 * - The BuddyPress activity directory
 * - A BuddyPress group page
 * - A BuddyPress user activity page
 *
 * If any of these conditions are met, it echoes a `<div>` element with the ID `fb-root`.
 * This is typically required for Facebook SDK integration.
 *
 * @return void
 */
function bp_activity_link_preview_add_facebook_root_div() {
	if ( bp_is_activity_directory() || bp_is_group() || bp_is_user_activity() ) {
		echo '<div id="fb-root"></div>';
	}
}
add_action( 'wp_head', 'bp_activity_link_preview_add_facebook_root_div' );
