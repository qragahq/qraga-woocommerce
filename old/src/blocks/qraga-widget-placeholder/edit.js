/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @return {WPElement} Element to render.
 */
export default function Edit() {
	const blockProps = useBlockProps({
		style: {
			padding: '1em',
			border: '1px dashed #ccc',
			textAlign: 'center',
			color: '#777',
		},
	});

	return (
		<div { ...blockProps }>
			<em>{ __( 'Qraga Widget Placeholder', 'qraga' ) }</em>
		</div>
	);
} 