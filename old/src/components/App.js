/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

const App = () => {
	console.log("Qraga: Rendering simplified App.js component...");
	return (
		<div>
			<h1>{__( 'Qraga Minimal App Component Test', 'qraga' )}</h1>
			<p>{__( 'If this renders, the problem is with TabPanel, Router, or child pages.', 'qraga' )}</p>
		</div>
	);
};

export default App; 