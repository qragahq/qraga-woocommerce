/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { PanelBody, TextControl, Button, Spinner } from '@wordpress/components';
import { Fragment, useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

const SettingsPage = () => {
	// Reuse state logic from original component
	const [ siteId, setSiteId ] = useState( '' );
	const [ apiKey, setApiKey ] = useState( '' );
	const [ endpointUrl, setEndpointUrl ] = useState( '' );
	const [ isLoadingSettings, setIsLoadingSettings ] = useState( true );
	const [ isSavingSettings, setIsSavingSettings ] = useState( false );
	const [ settingsNotice, setSettingsNotice ] = useState( { type: '', message: '' } );

	useEffect( () => {
		setIsLoadingSettings( true );
		setSettingsNotice({ type: '', message: '' });
		apiFetch( { path: '/qraga/v1/settings' } )
			.then( ( settings ) => {
				setSiteId( settings.siteId || '' );
				setApiKey( settings.apiKey || '' );
				setEndpointUrl( settings.endpointUrl || '' );
				setIsLoadingSettings( false );
			} )
			.catch( ( error ) => {
				console.error( 'Error fetching settings:', error );
				setSettingsNotice({ type: 'error', message: __( 'Error fetching settings.', 'qraga' ) + ` (${error.message})` });
				setIsLoadingSettings( false );
			} );
	}, [] );

	const handleSaveSettings = () => {
		setSettingsNotice({ type: '', message: '' }); // Clear previous notices
		if ( ! siteId || ! apiKey || ! endpointUrl ) {
			setSettingsNotice({ type: 'error', message: __( 'Site ID, API Key, and Endpoint URL cannot be empty.', 'qraga' ) });
			return;
		}
		setIsSavingSettings( true );
		apiFetch( {
			path: '/qraga/v1/settings',
			method: 'POST',
			data: { siteId, apiKey, endpointUrl },
		} )
			.then( ( response ) => {
				if ( response.success ) {
					setSettingsNotice({ type: 'success', message: __( 'Settings saved successfully.', 'qraga' ) });
				} else {
					setSettingsNotice({ type: 'error', message: __( 'Error saving settings:', 'qraga' ) + ` ${response.message || 'Unknown error'}` });
				}
				setIsSavingSettings( false );
			} )
			.catch( ( error ) => {
				console.error( 'Error saving settings:', error );
				const message = error.message || __( 'An unknown error occurred.', 'qraga' );
				setSettingsNotice({ type: 'error', message: __( 'Error saving settings.', 'qraga' ) + ` (${message})` });
				setIsSavingSettings( false );
			} );
	};

	// Add Export logic here too, or maybe move Export to a dedicated section/page?
	const [ isExporting, setIsExporting ] = useState( false );
	const [ exportNotice, setExportNotice ] = useState( { type: '', message: '' } );
	const canSync = siteId && apiKey && endpointUrl;

	const handleStartExport = () => {
		setExportNotice({ type: '', message: '' });
		if ( !canSync ) {
			setExportNotice({ type: 'error', message: __( 'Site ID, API Key, and Endpoint URL must be configured and saved before starting sync.', 'qraga' ) });
			return;
		}
		setIsExporting( true );
		apiFetch( {
			path: '/qraga/v1/export',
			method: 'POST',
		} )
			.then( ( response ) => {
				const noticeType = response && typeof response.success === 'boolean' ? (response.success ? 'success' : 'error') : 'error';
				const noticeMessage = JSON.stringify( response, null, 2 );
				setExportNotice({ type: noticeType, message: noticeMessage });
				setIsExporting( false );
			} )
			.catch( ( error ) => {
				console.error( 'Error starting sync:', error );
				const errorMessage = error instanceof Error ? error.message : JSON.stringify(error, null, 2);
				setExportNotice({ type: 'error', message: __( 'Error during API call:', 'qraga' ) + ` ${errorMessage}` });
				setIsExporting( false );
			} );
	};

	return (
		<PanelBody title={__( 'Settings & Manual Sync', 'qraga' )}>
			{ isLoadingSettings ? (
				<Spinner />
			) : (
				<Fragment>
					<h3>{__( 'Connection Settings', 'qraga' )}</h3>
					{/* Add Logto connection button/status here */}
					<p>{__( 'Connect your Qraga account using Logto...' )}</p>
					<hr />
					<h3>{__( 'API Configuration (Fallback/Manual)', 'qraga' )}</h3>
					{ settingsNotice.message && (
						<div className={`notice notice-${ settingsNotice.type } is-dismissible`} style={{marginBottom: '1em'}}>
							<p>{ settingsNotice.message }</p>
						</div>
					) }
					<TextControl
						label={ __( 'Site ID', 'qraga' ) }
						help={ __( 'A unique identifier for this WooCommerce site.', 'qraga' ) }
						value={ siteId }
						onChange={ setSiteId }
					/>
					<TextControl
						label={ __( 'API Key/Token', 'qraga' ) }
						help={ __( 'Your API key/token from qraga.com.', 'qraga' ) }
						value={ apiKey }
						type="password"
						onChange={ setApiKey }
					/>
					<TextControl
						label={ __( 'Endpoint URL', 'qraga' ) }
						help={ __( 'The URL of the external API (e.g., https://api.qraga.com).', 'qraga' ) }
						value={ endpointUrl }
						type="url"
						onChange={ setEndpointUrl }
					/>
					<Button
						isPrimary
						isBusy={ isSavingSettings }
						disabled={ isSavingSettings }
						onClick={ handleSaveSettings }
					>
						{ isSavingSettings ? __( 'Saving...', 'qraga' ) : __( 'Save Settings', 'qraga' ) }
					</Button>

					<hr style={{margin: '2em 0'}} />

					<h3>{__( 'Manual Product Sync', 'qraga' )}</h3>
					<p>{__('Manually sync all published products to the configured external endpoint.', 'qraga')}</p>
					{ exportNotice.message && (
						<div className={`notice notice-${ exportNotice.type } is-dismissible`} style={{marginBottom: '1em'}}>
							{/* Display raw JSON in a pre tag for readability */}
							<pre style={{ whiteSpace: 'pre-wrap', wordBreak: 'break-all' }}>{ exportNotice.message }</pre>
						</div>
					) }
					<Button
						isSecondary
						isBusy={ isExporting }
						disabled={ isExporting || !canSync || isLoadingSettings }
						onClick={ handleStartExport }
					>
						{ isExporting ? __( 'Syncing Products...', 'qraga' ) : __( 'Sync Products Now', 'qraga' ) }
					</Button>
					{ !canSync && !isLoadingSettings && <p style={{color: 'red', marginTop: '0.5em'}}>{__('API Configuration must be saved before syncing.', 'qraga')}</p> }
				</Fragment>
			) }
		</PanelBody>
	);
};

export default SettingsPage; 