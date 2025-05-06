/**
 * External dependencies
 */
import domReady from '@wordpress/dom-ready';
import { createRoot, render as wpRender } from '@wordpress/element';
import { HashRouter } from 'react-router-dom';

/**
 * Internal dependencies
 */
import App from './components/App';
import './index.scss';

// --- Mount the App when DOM is ready ---
domReady( function () {
	console.log("Qraga: DOM is ready. Attempting to mount SIMPLIFIED React app...");
	const rootElement = document.getElementById( 'qraga-admin-root' );

	if ( rootElement ) {
		console.log("Qraga: Found root element #qraga-admin-root (simplified app test).");
		try {
			// Prefer createRoot if available (React 18+ style)
			if (createRoot) {
				console.log("Qraga: Using createRoot to render simplified App.");
				const root = createRoot( rootElement );
				root.render(
					<HashRouter>
						<App />
					</HashRouter>
				);
			} else {
				console.log("Qraga: Using wpRender (fallback) to render simplified App.");
				wpRender(
					<HashRouter>
						<App />
					</HashRouter>,
					rootElement
				);
			}
			console.log("Qraga: Simplified React app render function called via domReady.");
		} catch (error) {
			console.error("Qraga Error during simplified app render:", error);
		}
	} else {
		console.error("Qraga Error: Could not find root element #qraga-admin-root (simplified app test).");
	}
});
