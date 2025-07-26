const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');

module.exports = {
	...defaultConfig,
	context: path.resolve(__dirname, '../includes/admin/blocks/qraga-product-widget'),
	entry: {
		index: './src/index.js',
		view: './src/view.js',
	},
	output: {
		...defaultConfig.output,
		path: path.resolve(__dirname, '../includes/admin/blocks/qraga-product-widget/build'),
	}
}; 