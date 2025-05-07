import React from 'react';
import { HashRouter, Routes, Route, Link } from 'react-router-dom';
import apiFetch from '@wordpress/api-fetch';

// Import shadcn/ui components that we'll use
// import { Button } from '@/components/ui/button'; // No longer directly using Button for these links
import { buttonVariants } from "@/components/ui/button"; // Import buttonVariants
import { Toaster } from "@/components/ui/sonner";
// Assuming the Sidebar component is more complex and might be a default export or named export
// For now, let's assume a simple structure. If Sidebar is a default export from shadcn:
// import Sidebar from '@/components/ui/sidebar'; 
// Or if it's a collection of components, you'd import specific parts.
// For this example, we'll create a very simple sidebar structure directly.

// Import our pages
import OverviewPage from './pages/OverviewPage'; // New Overview page
import SettingsPage from './pages/SettingsPage';
import SyncPage from './pages/SyncPage';

// --- Type declaration for qragaData on the window object ---
declare global {
	interface Window {
		qragaData?: {
			root: string;       // REST API root URL
			apiNonce: string;   // Nonce for REST API
			baseUrl?: string;    // Plugin base URL (optional for frontend, but good to have)
			// Add other properties if you pass more data via wp_localize_script
		};
	}
}
// --- End Type declaration ---

// Configure @wordpress/api-fetch to use our global qragaData
if (window.qragaData && window.qragaData.root && window.qragaData.apiNonce) {
	apiFetch.use(apiFetch.createRootURLMiddleware(window.qragaData.root));
	apiFetch.use(apiFetch.createNonceMiddleware(window.qragaData.apiNonce));
} else {
	console.warn(
		'Qraga: window.qragaData or its required properties (root, apiNonce) not found for apiFetch setup.'
	);
}

const App: React.FC = () => {
	return (
		<HashRouter>
			<div className="flex h-screen bg-muted/40">
				{/* Simple Sidebar Area */}
				<aside className="w-64 border-r bg-background p-4 flex flex-col space-y-2">
					<h2 className="text-lg font-semibold mb-4">Qraga Menu</h2>
					
					<Link 
						to="/"
						className={buttonVariants({ variant: "ghost", className: "w-full justify-start" })}
					>
						Overview
					</Link>

					<Link 
						to="/settings"
						className={buttonVariants({ variant: "ghost", className: "w-full justify-start" })}
					>
						Settings
					</Link>

					<Link 
						to="/sync"
						className={buttonVariants({ variant: "ghost", className: "w-full justify-start" })}
					>
						Bulk Sync
					</Link>
					{/* Add more sidebar links here as needed */}
				</aside>

				{/* Main Content Area */}
				<main className="flex-1 p-6 overflow-auto">
					<Routes>
						<Route path="/" element={<OverviewPage />} /> {/* Changed from PostsPage to OverviewPage */}
						<Route path="/settings" element={<SettingsPage />} />
						<Route path="/sync" element={<SyncPage />} />
						{/* Add more routes here for other pages */}
					</Routes>
				</main>
				
				<Toaster richColors position="top-right" />
			</div>
		</HashRouter>
	);
};

export default App;
