const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');

module.exports = {
	...defaultConfig,
	entry: {
		index: path.resolve(__dirname, '../includes/admin/blocks/qraga-product-widget/src/index.js'),
		view: path.resolve(__dirname, '../includes/admin/blocks/qraga-product-widget/src/view.js'),
	},
	output: {
		...defaultConfig.output,
		path: path.resolve(__dirname, '../includes/admin/blocks/qraga-product-widget/build'),
	},
	resolve: {
		...defaultConfig.resolve,
		modules: [
			path.resolve(__dirname, '../includes/admin/blocks/qraga-product-widget/src'),
			'node_modules'
		]
	}
}; 