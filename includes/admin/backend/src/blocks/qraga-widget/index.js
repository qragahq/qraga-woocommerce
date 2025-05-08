import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

import metadata from './block.json';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {WPElement} Element to render.
 */
function Edit() {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<p>
				{ __( 'Qraga Product Widget Placeholder. The actual widget will be displayed on the frontend.', 'qraga' ) }
			</p>
			<p>
				<em>{ __( 'Ensure Site ID and Widget ID are configured in Qraga settings.', 'qraga' ) }</em>
			</p>
		</div>
	);
}

registerBlockType( metadata.name, {
	/**
	 * @see ./edit.js
	 */
	edit: Edit,
	// Save function is null for dynamic blocks
	save: () => null,
} ); 