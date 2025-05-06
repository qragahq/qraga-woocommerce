/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { PanelBody, TextControl, Button, Spinner } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

// Access localized data passed from PHP
const isBlockTheme = window.qragaAdminData?.isBlockTheme || false;

const WidgetConfigPage = () => {
	const [ widgetId, setWidgetId ] = useState( '' );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ isSaving, setIsSaving ] = useState( false );
	const [ notice, setNotice ] = useState( { type: '', message: '' } );

	// Fetch existing widget ID on load
	useEffect( () => {
		setIsLoading( true );
		setNotice( { type: '', message: '' } );
		apiFetch( { path: '/qraga/v1/settings' } )
			.then( ( settings ) => {
				setWidgetId( settings.widgetId || '' ); // Use widgetId from settings
				setIsLoading( false );
			} )
			.catch( ( error ) => {
				console.error( 'Error fetching settings:', error );
				setNotice( { type: 'error', message: __( 'Error fetching widget setting.', 'qraga' ) + ` (${error.message})` } );
				setIsLoading( false );
			} );
	}, [] );

	const handleSaveWidgetId = () => {
		setIsSaving( true );
		setNotice( { type: '', message: '' } );
		apiFetch( {
			path: '/qraga/v1/settings', // Use the same endpoint
			method: 'POST',
			// Send *only* widgetId, assuming endpoint handles partial update or merges
			data: { widgetId: widgetId },
		} )
			.then( ( response ) => {
				if ( response.success ) {
					setNotice( { type: 'success', message: __( 'Widget ID saved.', 'qraga' ) } );
				} else {
					setNotice( { type: 'error', message: __( 'Error saving Widget ID:', 'qraga' ) + ` ${response.message || 'Unknown error'}` } );
				}
				setIsSaving( false );
			} )
			.catch( ( error ) => {
				console.error( 'Error saving widget ID:', error );
				const message = error.message || __( 'An unknown error occurred.', 'qraga' );
				setNotice( { type: 'error', message: __( 'Error saving Widget ID.', 'qraga' ) + ` (${message})` } );
				setIsSaving( false );
			} );
	};

	return (
		<PanelBody title={__( 'Widget Configuration', 'qraga' )}>
			{ isLoading ? (
				<Spinner />
			) : (
				<>
					<p>{ __( 'Configure the Qraga widget displayed on product pages.', 'qraga' ) }</p>

					<h3>{ __( 'Widget Activation', 'qraga' ) }</h3>
					{ notice.message && (
						<div className={`notice notice-${ notice.type } is-dismissible`} style={{marginBottom: '1em'}}>
							<p>{ notice.message }</p>
						</div>
					) }
					<TextControl
						label={ __( 'Qraga Widget ID', 'qraga' ) }
						help={ __( 'Enter the Widget ID provided by Qraga. Leave empty to disable the widget.', 'qraga' ) }
						value={ widgetId }
						onChange={ setWidgetId }
					/>
					<Button isPrimary onClick={ handleSaveWidgetId } isBusy={ isSaving } disabled={ isSaving }>
						{ isSaving ? __( 'Saving...', 'qraga' ) : __( 'Save Widget ID', 'qraga' ) }
					</Button>

					<hr style={{ margin: '2em 0' }}/>

					<h3>{ __( 'Widget Position', 'qraga' ) }</h3>
					{ isBlockTheme ? (
						<p>
							{ __( 'To change the position of the widget, edit the Single Product template using the Site Editor (Appearance > Editor) and place the \"Qraga Widget Placeholder\" block where you want it.', 'qraga' ) }
						</p>
					) : (
						<p>
							{ __( 'To change the position of the widget on the product page, go to:', 'qraga' ) }{ ' '}
							<strong>{ __( 'Appearance > Customize > Qraga Widget', 'qraga' ) }</strong>.
						</p>
					) }
					{/* Maybe add a direct link if possible? */}
					{/* <Button isSecondary href={ customizeUrl }>Go to Customizer</Button> */}

				</>
			) }
		</PanelBody>
	);
};

export default WidgetConfigPage; 