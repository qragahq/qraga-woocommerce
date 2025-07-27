import apiFetch from '@wordpress/api-fetch';

// Define the shape of the settings data exchanged with the API
export interface QragaSettings {
    siteId: string;
    apiKey: string;
    region: string;
    apiVersion: string;
    endpointUrl: string; // Computed from region and apiVersion
    widgetId?: string;
}

// Define the shape of the data used by the settings form (can be same as QragaSettings or different)
// For this case, they are the same, but it's good practice to differentiate if form has transformations.
export interface SettingsFormData {
    siteId: string;
    apiKey: string;
    region: string;
    apiVersion: string;
    widgetId?: string;
}

/**
 * Fetches the current Qraga settings from the WordPress backend.
 */
export const fetchQragaSettings = async (): Promise<QragaSettings> => {
  return apiFetch<QragaSettings>({ path: '/qraga/v1/settings' });
};

/**
 * Saves the Qraga settings to the WordPress backend.
 * @param data The settings data to save.
 */
export const saveQragaSettings = async (data: SettingsFormData): Promise<QragaSettings> => {
  return apiFetch<QragaSettings>({ path: '/qraga/v1/settings', method: 'POST', data });
};

// You can add other Qraga API related functions here in the future,
// e.g., for fetching bulk sync status, or specific product sync status if needed. 