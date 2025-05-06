// Simple frontend script to update Qraga widget on variant change

(function ($) {
	// Use jQuery provided by WordPress/WooCommerce for simplicity
	'use strict';

	$(document).ready(function () {
		// Only run on variable product pages
		var $variationForm = $('.variations_form');
		if (!$variationForm.length) {
			return;
		}

		// Function to update the Qraga widget
		function updateQragaWidgetVariant(variationId) {
			// Check if the global widget instance exists (set by inline script in PHP)
			if (window.qragaWidget && typeof window.qragaWidget.setVariantId === 'function') {
				var variantIdForWidget = variationId ? 'var-wc-' + variationId : null;
				console.log('Qraga: Setting variant ID:', variantIdForWidget);
				window.qragaWidget.setVariantId(variantIdForWidget);
			} else {
				// Optional: Wait or retry if widget might not be initialized yet
				// console.warn('Qraga widget instance (window.qragaWidget) not found yet.');
			}
		}

		// Listen for WooCommerce variation changes
		$variationForm.on('found_variation', function (event, variation) {
			// 'variation' object contains details, including variation.variation_id
			if (variation && variation.variation_id) {
				updateQragaWidgetVariant(variation.variation_id);
			}
		});

		// Handle reset (clearing selection)
		$('.reset_variations').on('click', function () {
			updateQragaWidgetVariant(null);
		});

		// Trigger initial update in case a default variation is pre-selected
		// Need a slight delay for form initialization potentially
		setTimeout(function() {
			var initialVariationId = $variationForm.find('input[name="variation_id"]').val();
			if (initialVariationId && initialVariationId !== '0') {
				updateQragaWidgetVariant(initialVariationId);
			}
		}, 100); // Adjust delay if needed

	});

})(jQuery); 