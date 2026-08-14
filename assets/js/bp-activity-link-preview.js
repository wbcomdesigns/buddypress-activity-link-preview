(function ($) {
	'use strict';

	var loadURLAjax = null;
	var loadedURLs = [];
	var currentCommentId = null;
	var currentlyLoadingUrl = null; // Track URL currently being loaded to prevent duplicate requests

	// Track initialized Twitter widgets to prevent duplicates
	var initializedTwitterWidgets = new Set();

	// BuddyBoss AJAX interceptor - inject link preview data into post_update requests
	// BuddyBoss constructs its own AJAX data and doesn't serialize form hidden fields
	jQuery(document).ajaxSend(function (event, jqXHR, settings) {
		// Skip if BuddyBoss has its own link preview active
		if (typeof bp_activity_link_preview !== 'undefined' && bp_activity_link_preview.buddyboss_link_preview_active) {
			return;
		}

		// Only intercept post_update actions
		if (!settings.data || typeof settings.data !== 'string') {
			return;
		}

		// Check if this is a post_update action (could be JSON or URL-encoded)
		var isPostUpdate = false;
		var dataObj = null;

		try {
			// Try parsing as JSON first (BuddyBoss format)
			dataObj = JSON.parse(settings.data);
			if (dataObj.action === 'post_update') {
				isPostUpdate = true;
			}
		} catch (e) {
			// Try URL-encoded format
			if (settings.data.indexOf('action=post_update') !== -1) {
				isPostUpdate = true;
			}
		}

		if (!isPostUpdate) {
			return;
		}

		// Get link preview data from hidden fields
		var $linkUrl = $('input[name="link_url"]');
		var $linkTitle = $('input[name="link_title"]');
		var $linkDescription = $('input[name="link_description"]');
		var $linkImage = $('input[name="link_image"]');

		// Only add if we have link preview data
		if ($linkUrl.length > 0 && $linkUrl.val()) {
			if (dataObj) {
				// JSON format - add to object and re-stringify
				dataObj.link_url = $linkUrl.val();
				dataObj.link_title = $linkTitle.val() || '';
				dataObj.link_description = $linkDescription.val() || '';
				if (!dataObj.link_image && $linkImage.length > 0) {
					dataObj.link_image = $linkImage.val();
				}
				settings.data = JSON.stringify(dataObj);
			} else {
				// URL-encoded format - append parameters
				settings.data += '&link_url=' + encodeURIComponent($linkUrl.val());
				settings.data += '&link_title=' + encodeURIComponent($linkTitle.val() || '');
				settings.data += '&link_description=' + encodeURIComponent($linkDescription.val() || '');
				if (settings.data.indexOf('link_image=') === -1 && $linkImage.length > 0) {
					settings.data += '&link_image=' + encodeURIComponent($linkImage.val());
				}
			}
		}
	});

	// Tweets render inside Twitter's own iframe, so the plugin's CSS tokens cannot
	// reach them. Read the host theme's explicit dark-mode attribute instead, the
	// same signal the stylesheet keys its dark scope off.
	var getEmbedTheme = function () {
		return 'dark' === document.documentElement.getAttribute('data-bx-mode') ? 'dark' : 'light';
	};

	// Fill the empty <div data-url> containers that PHP renders for Twitter/X and
	// Facebook links.
	//
	// This MUST run on page load as well as after the activity AJAX calls. Saved
	// previews are server-rendered, so on an activity permalink - or any theme that
	// paints the stream without BuddyPress Nouveau's AJAX bootstrap - nothing else
	// would ever trigger it and the embed stays a blank box.
	var initSocialEmbeds = function () {
		// Handle both original and comment containers
		$(document).find(".activity-link-preview-container, .activity-comment-link-preview-container").each(function (index, element) {
			var $container = $(element);
			var url = $container.data("url");

			if (!url) return;

			// Skip if already has rendered content (iframe or widget)
			if ($container.find('iframe, .twitter-tweet-rendered').length > 0) {
				return;
			}

			// Check if this is a Twitter URL
			const tweetIdMatch = url.match(/status\/(\d+)/);
			if (!tweetIdMatch || !tweetIdMatch[1]) return;

			const tweetId = tweetIdMatch[1];

			// Get activity ID for unique widget tracking (fixes re-post issue)
			var activityId = $container.closest('.activity-item, [data-bp-activity-id]').data('bp-activity-id') ||
				$container.closest('.activity').attr('id') ||
				'container-' + index;

			const widgetId = 'twitter-widget-' + activityId + '-' + tweetId;

			// Skip if already initialized
			if (initializedTwitterWidgets.has(widgetId)) return;

			// Bail before claiming the id when the SDK is not ready yet: marking it
			// first would permanently retire this container, so a slow widgets.js
			// would leave the tweet blank for the rest of the page's life. Leaving
			// it unclaimed lets the twttr.ready() pass below pick it up.
			if (typeof twttr === 'undefined' || !twttr.widgets) {
				return;
			}

			// Mark as initialized
			initializedTwitterWidgets.add(widgetId);

			twttr.widgets.createTweet(
				tweetId,
				element,
				{ theme: getEmbedTheme() }
			).then(function () {
				// Widget created successfully
			}).catch(function () {
				// Allow a later pass to retry this container.
				initializedTwitterWidgets.delete(widgetId);
			});
		});

		// Facebook: the SDK is loaded with #xfbml=1 so it parses server-rendered
		// markup itself, but AJAX-injected .fb-post nodes arrive after that pass.
		if (typeof FB !== 'undefined' && FB.XFBML) {
			try {
				FB.XFBML.parse();
			} catch (e) {
				// A failed parse must not break the rest of the composer.
			}
		}
	};

	$(function () {
		initSocialEmbeds();

		// widgets.js may still be fetching its widget bundle at DOM ready; twttr.ready
		// fires once twttr.widgets exists, which is when the pass above can succeed.
		if (typeof twttr !== 'undefined' && 'function' === typeof twttr.ready) {
			twttr.ready(function () {
				initSocialEmbeds();
			});
		}
	});

	// Enhanced AJAX complete handler with backward compatibility
	jQuery(document).ajaxComplete(function (event, xhr, settings) {
		const params = new URLSearchParams(settings.data);
		const parsedData = Object.fromEntries(params.entries());

		// Only proceed for relevant actions
		if (!parsedData.action || !(parsedData.action.includes('activity_filter') ||
			parsedData.action.includes('post_update') ||
			parsedData.action.includes('new_activity_comment'))) {
			return;
		}

		setTimeout(initSocialEmbeds, 200);
	});

	// Enhanced URL scraping function with backward compatibility
	var scrap_URL = function (inputurlText, isComment, commentId) {
		var urlString = '';

		if (inputurlText === null) {
			return;
		}

		if (inputurlText.indexOf('<img') >= 0) {
			inputurlText = inputurlText.replace(/<img .*?>/g, '');
		}

		if (inputurlText.indexOf('http://') >= 0) {
			urlString = getURL('http://', inputurlText);
		} else if (inputurlText.indexOf('https://') >= 0) {
			urlString = getURL('https://', inputurlText);
		} else if (inputurlText.indexOf('www.') >= 0) {
			urlString = getURL('www', inputurlText);
		}

		if (urlString !== '') {
			var url_a = document.createElement('a');
			url_a.href = urlString;
			var hostname = url_a.hostname;
			loadLinkPreview(urlString, isComment, commentId);
		} else {
			// No URL left in the input: remove any stale preview so it does
			// not stay attached after the user deletes the link text.
			removeLinkPreview(isComment, commentId);
		}
	}

	// Remove an existing link preview (and its hidden fields/storage) when
	// the URL has been deleted from the input. Counterpart of loadLinkPreview.
	var removeLinkPreview = function (isComment, commentId) {
		var attachmentContainer = (isComment && commentId) ? '#comment-attachments-' + commentId : '#whats-new-attachments';
		var containerClass = (isComment && commentId) ? 'activity-comment-url-scrapper-container' : 'activity-url-scrapper-container';
		var storageKey = (isComment && commentId) ? 'bp-activity-comment-link-preview-' + commentId : 'bp-activity-link-preview';

		if ($(attachmentContainer + ' .' + containerClass).length === 0) {
			return;
		}

		// Abort any in-flight parse request for the removed URL.
		if (loadURLAjax != null) {
			loadURLAjax.abort();
			loadURLAjax = null;
		}
		currentlyLoadingUrl = null;

		$(attachmentContainer + ' .' + containerClass).remove();
		setLinkPreviewStorage(storageKey, 'link-preview');
	}

	// Show a transient, dismiss-on-timeout error notice when the parse
	// endpoint rejects a URL (invalid, blocked host, preview unavailable).
	var showPreviewError = function (message, isComment, commentId) {
		var attachmentContainer = (isComment && commentId) ? '#comment-attachments-' + commentId : '#whats-new-attachments';

		// BP Nouveau markup has no #whats-new-attachments until a preview
		// creates it — ensure it exists, same as setURLResponse does.
		if (!(isComment && commentId) && $('#whats-new-attachments').length === 0) {
			$('#whats-new-content').after('<div id="whats-new-attachments"></div>');
		}

		$(attachmentContainer + ' .bpalp-preview-error').remove();

		// Use .text() so the server message is inserted as plain text.
		var $error = $('<div class="bpalp-preview-error" role="alert"></div>').text(message);
		$(attachmentContainer).append($error);

		setTimeout(function () {
			$error.fadeOut(200, function () {
				$(this).remove();
			});
		}, 5000);
	}

	// Enhanced link preview loading function
	var loadLinkPreview = function (url, isComment, commentId) {
		var regexp = /^(http:\/\/www\.|https:\/\/www\.|http:\/\/|https:\/\/)?[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,24}(:[0-9]{1,5})?(\/.*)?$/;
		url = $.trim(url);
		
		if (regexp.test(url)) {
			var urlResponse = false;
			if (loadedURLs.length) {
				$.each(loadedURLs, function (index, urlObj) {
					if (urlObj.url == url) {
						urlResponse = urlObj.response;
						return false;
					}
				});
			}

			// If URL is already in cache, use it directly
			if (urlResponse) {
				setURLResponse(urlResponse, url, isComment, commentId);
				return;
			}

			// Prevent duplicate requests for the same URL that's already loading
			if (currentlyLoadingUrl === url) {
				return;
			}

			if (loadURLAjax != null) {
				loadURLAjax.abort();
				loadURLAjax = null;
			}

			currentlyLoadingUrl = url; // Mark this URL as being loaded

			if (!urlResponse) {
				var ajaxData = {
					action: 'bp_activity_parse_url_preview',
					'url': url
				};

				// Add nonce if available
				if (typeof bp_activity_link_preview !== 'undefined' && bp_activity_link_preview.nonce) {
					ajaxData.nonce = bp_activity_link_preview.nonce;
				}

				// Add comment ID if it's a comment
				if (isComment && commentId) {
					ajaxData.comment_id = commentId;
				}

				loadURLAjax = jQuery.post(ajaxurl, ajaxData, function (response) {
					currentlyLoadingUrl = null; // Reset when request completes
					// Handle both old and new response formats
					if (response && response.success) {
						setURLResponse(response.data, url, isComment, commentId);
					} else if (response && response.error) {
						// Parse endpoint rejected the URL - surface the message.
						showPreviewError(response.error, isComment, commentId);
					} else if (response && response.success === false && response.data && response.data.message) {
						// wp_send_json_error() shape (auth/nonce failures).
						showPreviewError(response.data.message, isComment, commentId);
					} else if (response) {
						// Backward compatibility with old response format
						setURLResponse(response, url, isComment, commentId);
					}
				}).fail(function (jqXHR, textStatus) {
					// Only reset if this is still the current URL being loaded
					if (currentlyLoadingUrl === url) {
						currentlyLoadingUrl = null;
					}
				});
			}
		}
	}

	// Storage functions (enhanced but backward compatible)
	var getLinkPreviewStorage = function (type, property) {
		var store = sessionStorage.getItem(type);

		if (store) {
			store = JSON.parse(store);
		} else {
			store = {};
		}

		if (undefined !== property) {
			return store[property] || false;
		}

		return store;
	}

	var setLinkPreviewStorage = function (type, property, value) {
		var store = getLinkPreviewStorage(type);

		if (undefined === value && undefined !== store[property]) {
			delete store[property];
		} else {
			store[property] = value;
		}

		sessionStorage.setItem(type, JSON.stringify(store));
		return sessionStorage.getItem(type) !== null;
	}

	// Enhanced URL response function with backward compatibility
	var setURLResponse = function (response, url, isComment, commentId) {
		var attachmentContainer = '#whats-new-attachments';
		var storageKey = 'bp-activity-link-preview';
		var fieldPrefix = 'link_';

		// Handle comment attachments differently
		if (isComment && commentId) {
			// Create comment-specific container if it doesn't exist
			if ($('#comment-attachments-' + commentId).length === 0) {
				$('#ac-form-' + commentId + ' .ac-reply-content').after('<div id="comment-attachments-' + commentId + '" class="comment-attachments"></div>');
			}
			attachmentContainer = '#comment-attachments-' + commentId;
			storageKey = 'bp-activity-comment-link-preview-' + commentId;
			fieldPrefix = 'comment_link_';
		} else {
			// Original main activity handling
			if ($('#whats-new-attachments').length === 0) {
				$('#whats-new-content').after('<div id="whats-new-attachments"></div>');
			}
		}

		var title = response.title || '';
		var description = response.description || '';
		var image = (response.images && Array.isArray(response.images) && response.images.length > 0) ? response.images[0] : '';
		var image_count = (response.images && Array.isArray(response.images)) ? response.images.length : 0;

		setLinkPreviewStorage(storageKey, 'link-preview', {
			link_success: true,
			link_url: url,
			link_title: response.title,
			link_description: response.description,
			link_images: response.images,
			link_image_index: 0,
		});

		var image_nav = '';
		if (image_count === 0) {
			image_nav = 'display:none;';
		}

		var containerClass = isComment ? 'activity-comment-url-scrapper-container' : 'activity-url-scrapper-container';
		var previewClass = isComment ? 'activity-comment-link-preview-container' : 'activity-link-preview-container';
		var closeId = isComment ? 'activity-close-comment-link-suggestion-' + commentId : 'activity-close-link-suggestion';
		var imageCloseId = isComment ? 'activity-comment-link-preview-close-image-' + commentId : 'activity-link-preview-close-image';
		var prevButtonId = isComment ? 'activity-comment-url-prevPicButton-' + commentId : 'activity-url-prevPicButton';
		var nextButtonId = isComment ? 'activity-comment-url-nextPicButton-' + commentId : 'activity-url-nextPicButton';
		var imageCountId = isComment ? 'activity-comment-url-scrapper-img-count-' + commentId : 'activity-url-scrapper-img-count';

		// Escape all scraped/user-derived values before injecting into the DOM.
		// escapeHtml() encodes & < > " ' so it is safe for both text nodes and
		// double-quoted attributes. The HTML parser decodes the entities back to
		// the raw value on submit, so the server still receives the original
		// string to sanitize -- no double-encoding. See task #1 (XSS fix).
		var eTitle       = escapeHtml(title);
		var eDescription = escapeHtml(description);
		var eImage       = escapeHtml(image);
		var eUrl         = escapeHtml(url);

		// Translated UI strings (seeded from PHP; see the i18n array in
		// bp_activity_link_preview_enqueue_scripts). Escaped because they are
		// injected into double-quoted attributes and text nodes below.
		var tCancelPreview      = escapeHtml(bpalpText('cancelPreview', 'Cancel Preview'));
		var tCancelPreviewImage = escapeHtml(bpalpText('cancelPreviewImage', 'Cancel Preview Image'));
		var tPreviousImage      = escapeHtml(bpalpText('previousImage', 'Previous image'));
		var tNextImage          = escapeHtml(bpalpText('nextImage', 'Next image'));
		var tImageCount         = escapeHtml(bpalpSprintf(bpalpText('imageCount', 'Image %1$s of %2$s'), [1, image_count]));

		// oEmbed videos (YouTube, Vimeo, etc.): response.description is the trusted
		// WP-oEmbed iframe (whitelisted providers, generated server-side), so it is
		// rendered raw to show the player. The hidden wp_embed flag tells the save
		// handler to regenerate + persist the embed so it survives to the feed.
		// Every scraped value (title/description/image/url) stays escaped above.
		var isEmbed = !!response.wp_embed;
		var link_preview;
		if (isEmbed) {
			// Emit all four hidden fields the save handler requires (url/title/
			// description/image) plus the wp_embed flag; the server regenerates the
			// embed from the URL, so the escaped title/description here are harmless.
			link_preview = '<div class="' + containerClass + '"><div class="' + previewClass + ' activity-video-preview">' + description + '<a title="' + tCancelPreview + '" href="#" id="' + closeId + '"><i class="dashicons dashicons-no-alt"></i></a></div><div class="bp-link-preview-hidden"><input type="hidden" name="' + fieldPrefix + 'url" value="' + eUrl + '" /><input type="hidden" name="' + fieldPrefix + 'title" value="' + eTitle + '" /><input type="hidden" name="' + fieldPrefix + 'description" value="' + eDescription + '" /><input type="hidden" name="' + fieldPrefix + 'image" value="' + eImage + '" /><input type="hidden" name="' + fieldPrefix + 'wp_embed" value="1" /></div></div>';
		} else {
			link_preview = '<div class="' + containerClass + '"><div class="' + previewClass + '"><p class="activity-link-preview-title">' + eTitle + '</p><div id="activity-url-scrapper-img-holder" style="' + image_nav + '"><div class="activity-link-preview-image"><img src="' + eImage + '" alt=""><a title="' + tCancelPreviewImage + '" href="#" id="' + imageCloseId + '"><i class="dashicons dashicons-no-alt"></i></a></div><div class="activity-url-thumb-nav"><button type="button" aria-label="' + tPreviousImage + '" id="' + prevButtonId + '"><span class="dashicons dashicons-arrow-left-alt2" aria-hidden="true"></span></button><button type="button" aria-label="' + tNextImage + '" id="' + nextButtonId + '"><span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span></button><div id="' + imageCountId + '">' + tImageCount + '</div></div></div><div class="activity-link-preview-excerpt"><p>' + eDescription + '</p></div><a title="' + tCancelPreview + '" href="#" id="' + closeId + '"><i class="dashicons dashicons-no-alt"></i></a></div><div class="bp-link-preview-hidden"><input type="hidden" name="' + fieldPrefix + 'url" value="' + eUrl + '" /><input type="hidden" name="' + fieldPrefix + 'title" value="' + eTitle + '" /><input type="hidden" name="' + fieldPrefix + 'image" value="' + eImage + '" /></div></div>';
		}

		$(attachmentContainer + ' .' + containerClass).remove();
		$(attachmentContainer).append(link_preview);

		// Handle special cases for Twitter and Facebook
		if (url.includes('x.com')) {
			const tweetIdMatch = url.match(/status\/(\d+)/);
			var tweetId = '';
			if (tweetIdMatch && tweetIdMatch[1]) {
				tweetId = tweetIdMatch[1];
			}
			$($(attachmentContainer).find("." + previewClass)[0]).html('<a title="' + tCancelPreview + '" href="#" id="' + closeId + '"><i class="dashicons dashicons-no-alt"></i></a>');
			if (tweetId) {
				twttr.widgets.createTweet(
					tweetId,
					$(attachmentContainer).find("." + previewClass)[0],
					{ theme: getEmbedTheme() }
				);
			}
		}
		
		if (url.includes('facebook.com')) {
			$($(attachmentContainer).find("." + previewClass)[0]).html('<a title="' + tCancelPreview + '" href="#" id="' + closeId + '"><i class="dashicons dashicons-no-alt"></i></a><div class="fb-post" data-href="' + eUrl + '" data-width="500" data-height="500"></div>');
			if (typeof FB !== 'undefined') {
				FB.XFBML.parse();
			} else {
				console.error('Facebook SDK not loaded.');
			}
		}
	}

	// i18n: read a translated string injected by wp_localize_script() in
	// bp_activity_link_preview_enqueue_scripts(). The English literal passed as
	// `fallback` is a safety net only (asset load order / stale cache) and is
	// never the translatable source - every key MUST exist in the PHP i18n
	// array, which is what the POT scanner reads.
	//
	// Defined at module scope on purpose: several functions declare a local
	// `var bp_activity_link_preview` (the sessionStorage payload) that shadows
	// the localized global, so the lookup must not happen inside them.
	var bpalpText = function (key, fallback) {
		if (typeof bp_activity_link_preview !== 'undefined' &&
			bp_activity_link_preview.i18n &&
			bp_activity_link_preview.i18n[key]) {
			return bp_activity_link_preview.i18n[key];
		}
		return fallback;
	};

	// Minimal sprintf for the %1$s-style placeholders used by the i18n strings.
	// Translations must be free to reorder the arguments, so the whole phrase
	// stays one string rather than concatenated fragments.
	var bpalpSprintf = function (format, args) {
		return String(format).replace(/%(\d+)\$s/g, function (match, position) {
			var value = args[parseInt(position, 10) - 1];
			return (typeof value === 'undefined') ? match : String(value);
		});
	};

	// Helper functions (unchanged)
	var escapeHtml = function (text) {
		if (!text) {
			return text;
		}
		return text
			.replace(/&/g, "&amp;")
			.replace(/</g, "&lt;")
			.replace(/>/g, "&gt;")
			.replace(/"/g, "&quot;")
			.replace(/'/g, "&#039;");
	}

	var getURL = function (prefix, urlText) {
		var urlString = '';
		var responseUrl = '';

		// Prefer an anchor href anywhere in the markup. Contenteditable
		// editors (BuddyBoss) auto-link typed URLs, and the anchor's href
		// attribute is the authoritative URL even when plain text precedes
		// or follows the anchor. The previous check only looked at the
		// first top-level parsed node, so "text before <a href=...>" fell
		// through to the fragile character loop below.
		var parsedNodes = $.parseHTML(urlText);
		if (parsedNodes && parsedNodes.length) {
			var $anchor = $('<div></div>').append(parsedNodes).find('a[href]').filter(function () {
				return String($(this).attr('href')).indexOf(prefix) !== -1;
			}).first();
			if ($anchor.length) {
				urlString = $anchor.attr('href');
			}
		}

		if (urlString === '') {
			// Plain-text fallback. Normalize element boundaries (<br>, <p>,
			// block wrappers) and non-breaking spaces to whitespace first so
			// text on the following line never gets concatenated into the
			// detected URL (e.g. "https://example.com/<br>Test").
			var plainText = urlText
				.replace(/<[^>]*>/g, ' ')
				.replace(/&nbsp;/gi, ' ');
			var startIndex = plainText.indexOf(prefix);

			if (startIndex === -1) {
				return '';
			}

			for (var i = startIndex; i < plainText.length; i++) {
				if (plainText[i] === ' ' || plainText[i] === '\n' || plainText[i] === '\r' || plainText[i] === '\t') {
					break;
				} else {
					urlString += plainText[i];
				}
			}
			if (prefix === 'www') {
				prefix = 'http://';
				urlString = prefix + urlString;
			}
		}

		var div = document.createElement('div');
		div.innerHTML = urlString;
		var elements = div.getElementsByTagName('*');

		while (elements[0]) {
			elements[0].parentNode.removeChild(elements[0]);
		}

		if (div.innerHTML.length > 0) {
			responseUrl = div.innerHTML;
		}

		return responseUrl;
	}

	// Enhanced next/previous functions for comments
	var setURLNextPreviousResponse = function (isComment, commentId) {
		var attachmentContainer = isComment ? '#comment-attachments-' + commentId : '#whats-new-attachments';
		var storageKey = isComment ? 'bp-activity-comment-link-preview-' + commentId : 'bp-activity-link-preview';
		var containerClass = isComment ? 'activity-comment-url-scrapper-container' : 'activity-url-scrapper-container';
		var fieldPrefix = isComment ? 'comment_link_' : 'link_';

		if ($(attachmentContainer).length === 0) {
			if (isComment) {
				$('#ac-form-' + commentId + ' .ac-reply-content').after('<div id="comment-attachments-' + commentId + '" class="comment-attachments"></div>');
			} else {
				$('#whats-new-content').after('<div id="whats-new-attachments"></div>');
			}
		}

		var bp_activity_link_preview = getLinkPreviewStorage(storageKey, 'link-preview');

		// Safely get values with defaults
		var link_images = (bp_activity_link_preview && Array.isArray(bp_activity_link_preview.link_images)) ? bp_activity_link_preview.link_images : [];
		var link_image_index = (bp_activity_link_preview && typeof bp_activity_link_preview.link_image_index !== 'undefined') ? bp_activity_link_preview.link_image_index : 0;
		var url = (bp_activity_link_preview && bp_activity_link_preview.link_url) ? bp_activity_link_preview.link_url : '';
		var title = (bp_activity_link_preview && bp_activity_link_preview.link_title) ? bp_activity_link_preview.link_title : '';
		var description = (bp_activity_link_preview && bp_activity_link_preview.link_description) ? bp_activity_link_preview.link_description : '';
		var image = (link_images.length > link_image_index) ? link_images[link_image_index] : '';
		var image_count = link_images.length;

		var closeId = isComment ? 'activity-close-comment-link-suggestion-' + commentId : 'activity-close-link-suggestion';
		var imageCloseId = isComment ? 'activity-comment-link-preview-close-image-' + commentId : 'activity-link-preview-close-image';
		var prevButtonId = isComment ? 'activity-comment-url-prevPicButton-' + commentId : 'activity-url-prevPicButton';
		var nextButtonId = isComment ? 'activity-comment-url-nextPicButton-' + commentId : 'activity-url-nextPicButton';
		var imageCountId = isComment ? 'activity-comment-url-scrapper-img-count-' + commentId : 'activity-url-scrapper-img-count';

		// Escape all scraped/user-derived values before injecting into the DOM
		// (same rationale as setURLResponse -- task #1 XSS fix).
		var eTitle       = escapeHtml(title);
		var eDescription = escapeHtml(description);
		var eImage       = escapeHtml(image);
		var eUrl         = escapeHtml(url);

		// Translated UI strings (seeded from PHP; see setURLResponse).
		var tCancelPreview      = escapeHtml(bpalpText('cancelPreview', 'Cancel Preview'));
		var tCancelPreviewImage = escapeHtml(bpalpText('cancelPreviewImage', 'Cancel Preview Image'));
		var tPreviousImage      = escapeHtml(bpalpText('previousImage', 'Previous image'));
		var tNextImage          = escapeHtml(bpalpText('nextImage', 'Next image'));
		var tImageCount         = escapeHtml(bpalpSprintf(bpalpText('imageCount', 'Image %1$s of %2$s'), [link_image_index + 1, image_count]));

		var link_preview = '<div class="' + containerClass + '"><div class="activity-link-preview-container"><p class="activity-link-preview-title">' + eTitle + '</p><div id="activity-url-scrapper-img-holder"><div class="activity-link-preview-image"><img src="' + eImage + '" alt=""><a title="' + tCancelPreviewImage + '" href="#" id="' + imageCloseId + '"><i class="dashicons dashicons-no-alt"></i></a></div><div class="activity-url-thumb-nav"><button type="button" aria-label="' + tPreviousImage + '" id="' + prevButtonId + '"><span class="dashicons dashicons-arrow-left-alt2" aria-hidden="true"></span></button><button type="button" aria-label="' + tNextImage + '" id="' + nextButtonId + '"><span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span></button><div id="' + imageCountId + '">' + tImageCount + '</div></div></div><div class="activity-link-preview-excerpt"><p>' + eDescription + '</p></div><a title="' + tCancelPreview + '" href="#" id="' + closeId + '"><i class="dashicons dashicons-no-alt"></i></a></div><div class="bp-link-preview-hidden"><input type="hidden" name="' + fieldPrefix + 'url" value="' + eUrl + '" /><input type="hidden" name="' + fieldPrefix + 'title" value="' + eTitle + '" /><input type="hidden" name="' + fieldPrefix + 'description" value="' + eDescription + '" /><input type="hidden" name="' + fieldPrefix + 'image" value="' + eImage + '" /></div></div>';

		$(attachmentContainer + ' .' + containerClass).remove();
		$(attachmentContainer).append(link_preview);
	}

	// Helper function to get input value from textarea or contenteditable div (BuddyBoss compatibility)
	var getInputValue = function ($element) {
		if ($element.is('textarea')) {
			return $element.val();
		} else if ($element.attr('contenteditable') === 'true') {
			// BuddyBoss uses a contenteditable div instead of a textarea.
			// Return HTML (not text) so auto-generated anchor tags keep
			// their href attribute and element boundaries (<br>, <p>)
			// survive for URL boundary detection in getURL().
			return $element.html();
		}
		return $element.val() || $element.text();
	};

	$(document).ready(function () {
		// Main activity form handler (works with both BuddyPress textarea and BuddyBoss contenteditable div)
		$(document).on('keyup input', '#whats-new', function () {
			var $whatsNew = $(this);
			setTimeout(function () {
				scrap_URL(getInputValue($whatsNew), false, null);
			}, 500);
		});

		// Comment form handlers (works with both textarea and contenteditable)
		$(document).on('keyup input', '.ac-input', function () {
			var $this = $(this);
			var commentId = $this.closest('.ac-form').attr('id');
			if (commentId) {
				commentId = commentId.replace('ac-form-', '');
				currentCommentId = commentId;

				setTimeout(function () {
					scrap_URL(getInputValue($this), true, commentId);
				}, 500);
			}
		});

		// Original prev button handler
		$(document).on('click', '#activity-url-prevPicButton', function () {
			var bp_activity_link_preview = getLinkPreviewStorage('bp-activity-link-preview', 'link-preview');
			var imageIndex = bp_activity_link_preview.link_image_index;
			var images = bp_activity_link_preview.link_images;
			var url = bp_activity_link_preview.link_url;
			var link_success = bp_activity_link_preview.link_success;
			var link_title = bp_activity_link_preview.link_title;
			var link_description = bp_activity_link_preview.link_description;

			if (imageIndex > 0) {
				setLinkPreviewStorage('bp-activity-link-preview', 'link-preview', {
					link_success: true,
					link_url: url,
					link_title: link_title,
					link_description: link_description,
					link_images: images,
					link_image_index: imageIndex - 1,
				});

				setURLNextPreviousResponse(false, null);
			}
		});

		// Original next button handler
		$(document).on('click', '#activity-url-nextPicButton', function () {
			var bp_activity_link_preview = getLinkPreviewStorage('bp-activity-link-preview', 'link-preview');
			var imageIndex = bp_activity_link_preview.link_image_index;
			var images = bp_activity_link_preview.link_images;
			var url = bp_activity_link_preview.link_url;
			var link_success = bp_activity_link_preview.link_success;
			var link_title = bp_activity_link_preview.link_title;
			var link_description = bp_activity_link_preview.link_description;

			if (imageIndex < images.length - 1) {
				setLinkPreviewStorage('bp-activity-link-preview', 'link-preview', {
					link_success: true,
					link_url: url,
					link_title: link_title,
					link_description: link_description,
					link_images: images,
					link_image_index: imageIndex + 1,
				});

				setURLNextPreviousResponse(false, null);
			}
		});

		// Enhanced navigation button handlers for comments
		$(document).on('click', '[id^="activity-comment-url-prevPicButton"]', function () {
			var buttonId = $(this).attr('id');
			var commentId = buttonId.replace('activity-comment-url-prevPicButton-', '');
			var storageKey = 'bp-activity-comment-link-preview-' + commentId;

			var bp_activity_link_preview = getLinkPreviewStorage(storageKey, 'link-preview');
			var imageIndex = bp_activity_link_preview.link_image_index;
			var images = bp_activity_link_preview.link_images;
			var url = bp_activity_link_preview.link_url;
			var link_success = bp_activity_link_preview.link_success;
			var link_title = bp_activity_link_preview.link_title;
			var link_description = bp_activity_link_preview.link_description;

			if (imageIndex > 0) {
				setLinkPreviewStorage(storageKey, 'link-preview', {
					link_success: true,
					link_url: url,
					link_title: link_title,
					link_description: link_description,
					link_images: images,
					link_image_index: imageIndex - 1,
				});

				setURLNextPreviousResponse(true, commentId);
			}
		});

		$(document).on('click', '[id^="activity-comment-url-nextPicButton"]', function () {
			var buttonId = $(this).attr('id');
			var commentId = buttonId.replace('activity-comment-url-nextPicButton-', '');
			var storageKey = 'bp-activity-comment-link-preview-' + commentId;

			var bp_activity_link_preview = getLinkPreviewStorage(storageKey, 'link-preview');
			var imageIndex = bp_activity_link_preview.link_image_index;
			var images = bp_activity_link_preview.link_images;
			var url = bp_activity_link_preview.link_url;
			var link_success = bp_activity_link_preview.link_success;
			var link_title = bp_activity_link_preview.link_title;
			var link_description = bp_activity_link_preview.link_description;

			if (imageIndex < images.length - 1) {
				setLinkPreviewStorage(storageKey, 'link-preview', {
					link_success: true,
					link_url: url,
					link_title: link_title,
					link_description: link_description,
					link_images: images,
					link_image_index: imageIndex + 1,
				});

				setURLNextPreviousResponse(true, commentId);
			}
		});

		// Original submit handler
		$(document).on('click', '#buddypress #aw-whats-new-submit', function () {
			setTimeout(function () {
				$('.activity-url-scrapper-container').remove();
			}, 500);
		});

		// Comment form submit handlers (new)
		$(document).on('click', '.ac-reply-submit', function (e) {
			var $form = $(this).closest('.ac-form');
			var commentId = $form.attr('id');
			if (commentId) {
				commentId = commentId.replace('ac-form-', '');
				
				// Move comment link preview data to the form before submission
				var $commentAttachments = $('#comment-attachments-' + commentId);
				if ($commentAttachments.length > 0) {
					var $hiddenFields = $commentAttachments.find('.bp-link-preview-hidden input[type="hidden"]');
					if ($hiddenFields.length > 0) {
						// Clone the hidden fields to the form
						$hiddenFields.each(function() {
							var $clonedField = $(this).clone();
							$form.append($clonedField);
						});
					}
				}
				
				setTimeout(function () {
					$('#comment-attachments-' + commentId + ' .activity-comment-url-scrapper-container').remove();
				}, 500);
			}
		});

		// Original close handler
		$(document).on('click', '#activity-close-link-suggestion', function (e) {
			e.preventDefault();
			$('.activity-url-scrapper-container').remove();
		});

		// Enhanced close handlers for comments
		$(document).on('click', '[id^="activity-close-comment-link-suggestion"]', function (e) {
			e.preventDefault();
			var buttonId = $(this).attr('id');
			var commentId = buttonId.replace('activity-close-comment-link-suggestion-', '');
			$('#comment-attachments-' + commentId + ' .activity-comment-url-scrapper-container').remove();
		});

		// Enhanced image close handlers
		$(document).on('click', '[id^="activity-comment-link-preview-close-image"]', function (e) {
			e.preventDefault();
			var buttonId = $(this).attr('id');
			var commentId = buttonId.replace('activity-comment-link-preview-close-image-', '');
			$('#comment-attachments-' + commentId + ' #activity-url-scrapper-img-holder').hide();
		});

		// Main activity image close handler
		$(document).on('click', '#activity-link-preview-close-image', function (e) {
			e.preventDefault();
			$('#activity-url-scrapper-img-holder').hide();
		});
	});

})(jQuery);