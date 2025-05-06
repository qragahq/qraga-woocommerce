const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const WooCommerceDependencyExtractionWebpackPlugin = require('@woocommerce/dependency-extraction-webpack-plugin');
const path = require( 'path' ); // Import path module

// Get the default entry points
const defaultEntry = defaultConfig.entry ? defaultConfig.entry() : {}; // Handle cases where entry might not be a function

module.exports = {
	...defaultConfig,
	// Define multiple entry points
	entry: {
		// index: path.resolve( process.cwd(), 'src', 'index.js' ), // Original admin page entry - Handled by defaultEntry
		...defaultEntry, // Include default entry points (@wordpress/scripts usually handles this implicitly, but be explicit)
		'product-widget': path.resolve( process.cwd(), 'src', 'frontend', 'product-widget.js' ),
		// Add back the block entry point
		'blocks/qraga-widget-placeholder/index': path.resolve( process.cwd(), 'src', 'blocks', 'qraga-widget-placeholder', 'index.js' ),
	},
	// Adjust output filename to handle multiple entry points
	output: {
		...defaultConfig.output,
		filename: '[name].js', // Use the entry point name in the output filename
	},
	plugins: [
		...defaultConfig.plugins.filter(
			(plugin) =>
				plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'
		),
		new WooCommerceDependencyExtractionWebpackPlugin(),
	],
};
