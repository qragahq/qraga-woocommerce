/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { PanelBody } from '@wordpress/components';

const OverviewPage = () => {
	return (
		<PanelBody title={__( 'Overview', 'qraga' )}>
			<p>{ __( 'Overview content goes here. Perhaps an iframe?' ) }</p>
			{/* Example iframe placeholder */}
			{/* <iframe src="https://dashboard.qraga.com/embedded-overview" width="100%" height="600px" style={{ border: 'none' }}></iframe> */}
		</PanelBody>
	);
};

export default OverviewPage; 