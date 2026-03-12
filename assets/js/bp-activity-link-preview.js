(function ($) {
	'use strict';

	var loadURLAjax = null;
	var loadedURLs = [];
	var currentCommentId = null;

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

		setTimeout(() => {
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

				// Mark as initialized
				initializedTwitterWidgets.add(widgetId);

				// Initialize Twitter widget
				if (typeof twttr !== 'undefined' && twttr.widgets) {
					twttr.widgets.createTweet(
						tweetId,
						element,
						{ theme: 'light' }
					).then(function() {
						// Widget created successfully
					}).catch(function(error) {
						console.error('Error creating Twitter widget:', error);
						// Remove from initialized set to allow retry
						initializedTwitterWidgets.delete(widgetId);
					});
				}
			});

			// Handle Facebook embeds
			if (typeof FB !== 'undefined') {
				try {
					FB.XFBML.parse();
				} catch (e) {
					console.error('Error initializing Facebook widgets:', e);
				}
			} else {
				console.warn('Facebook SDK not loaded.');
			}
		}, 200);
	});

	// Enhanced URL scraping function with backward compatibility
	var scrap_URL = function (inputurlText, isComment, commentId) {
		var urlString = '';

		if (inputurlText === null) {
			return;
		}

		var urlTwitter = inputurlText.indexOf("x.com");
		var urlFacebook = inputurlText.indexOf("facebook.com");
		var urlInsta = inputurlText.indexOf("instagram.com");
		var urlYoutube = inputurlText.indexOf("youtube.com");

		// Original comment (keeping for backward compatibility)
		// if (urlTwitter >= 0 || urlFacebook >= 0 || urlInsta >= 0 || urlYoutube >= 0) {
		// 	return;
		// }
		
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
		}
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

			if (loadURLAjax != null) {
				loadURLAjax.abort();
			}

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
					// Handle both old and new response formats
					if (response.success) {
						setURLResponse(response.data, url, isComment, commentId);
					} else if (response && !response.error) {
						// Backward compatibility with old response format
						setURLResponse(response, url, isComment, commentId);
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

		var link_preview = '<div class="' + containerClass + '"><div class="' + previewClass + '"><p class="activity-link-preview-title">' + title + '</p><div id="activity-url-scrapper-img-holder" style="' + image_nav + '"><div class="activity-link-preview-image"><img src="' + image + '"><a title="Cancel Preview Image" href="#" id="' + imageCloseId + '"><i class="dashicons dashicons-no-alt"></i></a></div><div class="activity-url-thumb-nav"><button type="button" id="' + prevButtonId + '"><span class="dashicons dashicons-arrow-left-alt2"></span></button><button type="button" id="' + nextButtonId + '"><span class="dashicons dashicons-arrow-right-alt2"></span></button><div id="' + imageCountId + '">Image 1&nbsp;of&nbsp;' + image_count + '</div></div></div><div class="activity-link-preview-excerpt"><p>' + description + '</p></div><a title="Cancel Preview" href="#" id="' + closeId + '"><i class="dashicons dashicons-no-alt"></i></a></div><div class="bp-link-preview-hidden"><input type="hidden" name="' + fieldPrefix + 'url" value="' + url + '" /><input type="hidden" name="' + fieldPrefix + 'title" value="' + title + '" /><input type="hidden" name="' + fieldPrefix + 'image" value="' + image + '" /></div></div>';

		$(attachmentContainer + ' .' + containerClass).remove();
		$(attachmentContainer).append(link_preview);

		// Handle special cases for Twitter and Facebook
		if (url.includes('x.com')) {
			const tweetIdMatch = url.match(/status\/(\d+)/);
			var tweetId = '';
			if (tweetIdMatch && tweetIdMatch[1]) {
				tweetId = tweetIdMatch[1];
			}
			$($(attachmentContainer).find("." + previewClass)[0]).html('<a title="Cancel Preview" href="#" id="' + closeId + '"><i class="dashicons dashicons-no-alt"></i></a>');
			if (tweetId) {
				twttr.widgets.createTweet(
					tweetId,
					$(attachmentContainer).find("." + previewClass)[0],
					{ theme: 'light' }
				);
			}
		}
		
		if (url.includes('facebook.com')) {
			$($(attachmentContainer).find("." + previewClass)[0]).html('<a title="Cancel Preview" href="#" id="' + closeId + '"><i class="dashicons dashicons-no-alt"></i></a><div class="fb-post" data-href="' + url + '" data-width="500" data-height="500"></div>');
			if (typeof FB !== 'undefined') {
				FB.XFBML.parse();
			} else {
				console.error('Facebook SDK not loaded.');
			}
		}
	}

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
		var startIndex = urlText.indexOf(prefix);
		var responseUrl = '';

		if (typeof $($.parseHTML(urlText)).attr('href') !== 'undefined') {
			urlString = $(urlText).attr('href');
		} else {
			for (var i = startIndex; i < urlText.length; i++) {
				if (urlText[i] === ' ' || urlText[i] === '\n') {
					break;
				} else {
					urlString += urlText[i];
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

		var link_preview = '<div class="' + containerClass + '"><div class="activity-link-preview-container"><p class="activity-link-preview-title">' + title + '</p><div id="activity-url-scrapper-img-holder"><div class="activity-link-preview-image"><img src="' + image + '"><a title="Cancel Preview Image" href="#" id="' + imageCloseId + '"><i class="dashicons dashicons-no-alt"></i></a></div><div class="activity-url-thumb-nav"><button type="button" id="' + prevButtonId + '"><span class="dashicons dashicons-arrow-left-alt2"></span></button><button type="button" id="' + nextButtonId + '"><span class="dashicons dashicons-arrow-right-alt2"></span></button><div id="' + imageCountId + '">Image ' + (link_image_index + 1) + '&nbsp;of&nbsp;' + image_count + '</div></div></div><div class="activity-link-preview-excerpt"><p>' + description + '</p></div><a title="Cancel Preview" href="#" id="' + closeId + '"><i class="dashicons dashicons-no-alt"></i></a></div><div class="bp-link-preview-hidden"><input type="hidden" name="' + fieldPrefix + 'url" value="' + url + '" /><input type="hidden" name="' + fieldPrefix + 'title" value="' + title + '" /><input type="hidden" name="' + fieldPrefix + 'description" value="' + escapeHtml(description) + '" /><input type="hidden" name="' + fieldPrefix + 'image" value="' + image + '" /></div></div>';

		$(attachmentContainer + ' .' + containerClass).remove();
		$(attachmentContainer).append(link_preview);
	}

	// Helper function to get input value from textarea or contenteditable div (BuddyBoss compatibility)
	var getInputValue = function ($element) {
		if ($element.is('textarea')) {
			return $element.val();
		} else if ($element.attr('contenteditable') === 'true') {
			// BuddyBoss uses contenteditable div instead of textarea
			return $element.text();
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