/**
 * Registers a new block provided a unique name and an object defining its behavior.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-registration/
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * All files containing `style` keyword are bundled together. The code used
 * gets applied both to the front of your site and to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './style.scss';

/**
 * Internal dependencies
 */
import Edit from './edit.js';
import save from './save.js';
import metadata from './block.json';

// Define custom SVG icon
const qragaIcon = (
	<svg width="20" height="20" viewBox="0 0 69.691406 69.589838" preserveAspectRatio="xMidYMid" version="1.0" id="svg17">
		<defs id="defs1">
			<clipPath id="480296ebf4">
				<path d="m 53.707031,125.10156 h 69.749999 v 69.75 H 53.707031 Z m 0,0" clip-rule="nonzero" id="path1" />
			</clipPath>
		</defs>
		<g clip-path="url(#480296ebf4)" id="g2" transform="translate(-53.707031,-125.10547)">
			<path fill="#ffbd59" d="m 88.601562,159.89844 v 23.92187 c -0.03125,0 -0.0625,0.004 -0.09766,0.004 -13.191406,0 -23.921875,-10.73438 -23.921875,-23.92578 0,-13.1875 10.730469,-23.92188 23.921875,-23.92188 13.191408,0 23.925788,10.73438 23.925788,23.92188 0,4.28906 -1.13672,8.3125 -3.11719,11.79687 l 7.89063,7.89063 c 1.32031,-1.92188 2.44531,-3.97266 3.36328,-6.14063 1.8164,-4.29297 2.73437,-8.85156 2.73437,-13.54687 0,-4.69141 -0.91797,-9.25 -2.73437,-13.54297 -1.75391,-4.14453 -4.26172,-7.86719 -7.45703,-11.0586 -3.19141,-3.19531 -6.91407,-5.70312 -11.0586,-7.45703 -4.292972,-1.8164 -8.851565,-2.73437 -13.546878,-2.73437 -4.695312,0 -9.25,0.91797 -13.542968,2.73437 -4.144532,1.75391 -7.867188,4.26172 -11.0625,7.45703 -3.191407,3.19141 -5.699219,6.91407 -7.453126,11.0586 -1.816406,4.29297 -2.738281,8.85156 -2.738281,13.54297 0,4.69531 0.921875,9.2539 2.738281,13.54687 1.753907,4.14453 4.261719,7.86719 7.453126,11.0586 3.195312,3.19531 6.917968,5.70312 11.0625,7.45703 4.292968,1.8164 8.847656,2.73437 13.542968,2.73437 3.769532,0 7.445313,-0.59375 10.96875,-1.76562 v -6.77735 l 8.546878,8.54297 h 15.37891 L 88.601562,159.89844" fill-opacity="1" fill-rule="nonzero" id="path2" />
		</g>
	</svg>
);

/**
 * Every block starts by registering a new block type definition.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-registration/
 */
registerBlockType(metadata.name, {
	/**
	 * @see ./edit.js
	 */
	edit: Edit,

	/**
	 * @see ./save.js
	 */
	save,

	/**
	 * Override icon with custom SVG
	 */
	icon: qragaIcon,
});