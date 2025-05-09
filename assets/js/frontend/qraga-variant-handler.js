// Simple frontend script to update Qraga widget on variant change

(function ($) {
	// Use jQuery provided by WordPress/WooCommerce for simplicity
	'use strict';

	$(document).ready(function () {
		// Find all variation forms on the page (usually only one on product page)
		const $variationForms = $('.variations_form');

		if (!$variationForms.length) {
			return;
		}

		// Ensure the instance storage exists
		window.qragaWidgetInstances = window.qragaWidgetInstances || {};

		function updateQragaWidgetVariant(productId, variationId) {
			const instance = window.qragaWidgetInstances[productId];

			if (instance && typeof instance.setVariantId === 'function') {
				// const variantIdForWidget = variationId ? 'var-wc-' + variationId : null; // Old prefixing
				const variantIdForWidget = variationId ? String(variationId) : null; // Use raw ID
				// console.log(`Qraga Variant: Setting variant ID for product ${productId} to:`, variantIdForWidget);
				instance.setVariantId(variantIdForWidget);
			} else {
				 // console.warn(`Qraga Variant: Widget instance not found for product ${productId} when trying to set variant.`);
			}
		}

		$variationForms.each(function() {
			const $form = $(this);
			// Try to get the product ID associated with this form.
			// WooCommerce forms often have a data attribute or a hidden input.
            // Adjust selector if needed based on theme/WooCommerce version.
            let productId = $form.data('product_id'); // Common data attribute
            if (!productId) {
                const $productIdInput = $form.closest('.product').find('.qraga-widget-placeholder').first().data('product-id');
                if ($productIdInput) {
                     productId = $productIdInput;
                }
            }
             if (!productId) {
                 const $hiddenInput = $form.find('[name="product_id"]').first();
                 if ($hiddenInput.length) {
                     productId = $hiddenInput.val();
                 }
             }

			if (!productId) {
				// console.warn('Qraga Variant: Could not determine product ID for variation form.', $form);
				return; // Skip this form if product ID is unknown
			}

            // console.log(`Qraga Variant: Setting up listeners for product ${productId}`);

			// Listen for WooCommerce variation changes on this specific form
			$form.on('found_variation.qraga', function (event, variation) {
				if (variation && variation.variation_id) {
					updateQragaWidgetVariant(productId, variation.variation_id);
				} else {
                    // If variation is found but has no ID (shouldn't happen?), maybe reset?
                    updateQragaWidgetVariant(productId, null);
                }
			});

			// Handle reset (clearing selection) on this specific form
			$form.find('.reset_variations').on('click.qraga', function () {
				updateQragaWidgetVariant(productId, null);
			});

			// Trigger initial update for this form if a default variation is pre-selected
			setTimeout(function() {
				const initialVariationId = $form.find('input[name="variation_id"]').val();
				if (initialVariationId && initialVariationId !== '0') {
                    // console.log(`Qraga Variant: Found initial variant ${initialVariationId} for product ${productId}`);
					updateQragaWidgetVariant(productId, initialVariationId);
				}
			}, 150); // Slightly longer delay to ensure init script might have run
        });

	});

})(jQuery); 