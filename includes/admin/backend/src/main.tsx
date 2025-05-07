/* eslint-disable no-var */
import React from 'react';
import ReactDOM from 'react-dom/client';
import { QueryClient, QueryClientProvider } from 'react-query';
import App from './App.tsx';
import './assets/css/index.css';

// --- Type declaration for qragaData (if not moved to a .d.ts file) ---
declare global {
	interface Window {
		qragaData?: {
			root: string;
			apiNonce: string;
			baseUrl?: string;
		};
	}
}

const queryClient = new QueryClient({
	defaultOptions: {
		queries: {
			staleTime: 5000,
			refetchOnWindowFocus: false,
			retry: 2,
		},
	},
});

// Function to initialize the React app
const initApp = () => {
	const rootElement = document.getElementById('qraga-admin-root');
	if (rootElement) {
		ReactDOM.createRoot(rootElement).render(
			<React.StrictMode>
				<QueryClientProvider client={queryClient}>
					<App />
				</QueryClientProvider>
			</React.StrictMode>
		);
	} else {
		console.error('Qraga: Target root element #qraga-admin-root not found in DOM.');
	}
};

// Wait for the DOM to be fully loaded before initializing the app
if (document.readyState === 'loading') {
	// Loading hasn't finished yet
	document.addEventListener('DOMContentLoaded', initApp);
} else {
	// DOMContentLoaded has already fired
	initApp();
}
