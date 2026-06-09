/**
 * CodeSoup Options - Tabbed UI JavaScript
 *
 * @package CodeSoup\Options
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		/**
		 * Mobile Tab Navigation - Select Dropdown
		 */
		$('#codesoup-mobile-tab-select').on('change', function() {
			const selectedTab = $(this).val();
			const pageSlug = $(this).data('page-slug');
			const currentUrl = new URL(window.location.href);

			// Update the tab parameter
			currentUrl.searchParams.set('tab', selectedTab);

			// Navigate to the new URL
			window.location.href = currentUrl.toString();
		});

		/**
		 * Form change tracking
		 */
		const $form = $('.codesoup-options-tab-content form');
		let formChanged = false;

		if (!$form.length) {
			return;
		}

		$form.on('change', 'input, textarea, select', function() {
			formChanged = true;
		});

		$(window).on('beforeunload', function(e) {
			if (formChanged) {
				const message = 'You have unsaved changes. Are you sure you want to leave?';
				e.returnValue = message;
				return message;
			}
		});

		$form.on('submit', function() {
			formChanged = false;
		});

		$('.nav-tab, .codesoup-options-tab-item a').on('click', function(e) {
			if (formChanged) {
				const confirmed = confirm('You have unsaved changes. Are you sure you want to switch tabs?');
				if (!confirmed) {
					e.preventDefault();
					return false;
				}
			}
		});

		$('.codesoup-options-tab-item a').on('keydown', function(e) {
			const $items = $('.codesoup-options-tab-item a');
			const currentIndex = $items.index(this);

			if (e.key === 'ArrowDown' || e.key === 'ArrowRight') {
				e.preventDefault();
				const nextIndex = (currentIndex + 1) % $items.length;
				$items.eq(nextIndex).focus();
			} else if (e.key === 'ArrowUp' || e.key === 'ArrowLeft') {
				e.preventDefault();
				const prevIndex = (currentIndex - 1 + $items.length) % $items.length;
				$items.eq(prevIndex).focus();
			} else if (e.key === 'Home') {
				e.preventDefault();
				$items.first().focus();
			} else if (e.key === 'End') {
				e.preventDefault();
				$items.last().focus();
			}
		});

		$('.nav-tab').on('keydown', function(e) {
			const $tabs = $('.nav-tab');
			const currentIndex = $tabs.index(this);

			if (e.key === 'ArrowLeft') {
				e.preventDefault();
				const prevIndex = (currentIndex - 1 + $tabs.length) % $tabs.length;
				$tabs.eq(prevIndex).focus();
			} else if (e.key === 'ArrowRight') {
				e.preventDefault();
				const nextIndex = (currentIndex + 1) % $tabs.length;
				$tabs.eq(nextIndex).focus();
			} else if (e.key === 'Home') {
				e.preventDefault();
				$tabs.first().focus();
			} else if (e.key === 'End') {
				e.preventDefault();
				$tabs.last().focus();
			}
		});
	});

})(jQuery);

