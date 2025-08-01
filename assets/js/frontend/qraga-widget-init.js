// File: assets/js/frontend/qraga-widget-init.js

(function() {
    'use strict';

    function initializeQragaWidget() {
        const placeholder = document.getElementById('qraga-widget-container');
        
        if (!placeholder) {
            return;
        }

        if (typeof window.Qraga === 'undefined') {
            // console.error('Qraga Init Error: window.Qraga object not found. Ensure CDN script loaded correctly.');
            return;
        }

        // Initialize storage for widget instances if it doesn't exist
        window.qragaWidgetInstances = window.qragaWidgetInstances || {};

        const widgetId = placeholder.dataset.widgetId;
        const productId = placeholder.dataset.productId; 
        const isDev = placeholder.dataset.dev === 'true';

        // Note: siteId is no longer expected/used here based on previous PHP changes
        if (!widgetId || !productId) {
            // console.error('Qraga Init Error: Placeholder #qraga-widget-container missing required data-widget-id or data-product-id attributes.', placeholder);
            return; 
        }

        // Check if already initialized for this product ID (safeguard against multiple calls on same page view)
        if (window.qragaWidgetInstances[productId]) {
            return; 
        }

        // console.log(`Qraga Init: Initializing widget for product ${productId} in container #${placeholder.id}`);

        try {
            const config = {
                widgetId: widgetId,
                container: `#${placeholder.id}`, // This will be '#qraga-widget-container'
                product: {
                    id: productId,
                    variantId: null 
                }
            };
            
            // Add environment setting if dev mode is enabled
            if (isDev) {
                config.env = 'development';
            }

            const qragaInstance = new window.Qraga(config);
            qragaInstance.init(); 

            window.qragaWidgetInstances[productId] = qragaInstance;
            // console.log(`Qraga Init: Instance stored for product ${productId}`);
            
        } catch (error) {
            // console.error(`Qraga Init Error: Failed to initialize widget for product ${productId} in #${placeholder.id}`, error);
            if (placeholder && placeholder.innerHTML !== undefined) { // defensive check
                placeholder.innerHTML = '<!-- Qraga Widget Failed to Load -->';
            }
        }
    }

    if (document.readyState === 'loading') { 
        document.addEventListener('DOMContentLoaded', initializeQragaWidget);
    } else { 
        initializeQragaWidget();
    }

})(); 