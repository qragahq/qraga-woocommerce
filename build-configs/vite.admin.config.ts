import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import tsconfigPaths from 'vite-tsconfig-paths';
import tailwindcss from '@tailwindcss/vite';
import { fileURLToPath, URL } from 'node:url';
import path from 'node:path';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

// https://vitejs.dev/config/
export default defineConfig({
	plugins: [react(), tsconfigPaths(), tailwindcss()],
	root: path.resolve(__dirname, '../includes/admin/backend'),
	build: {
		outDir: path.resolve(__dirname, '../includes/admin/assets/js'),
		emptyOutDir: true,
		rollupOptions: {
			input: path.resolve(__dirname, '../includes/admin/backend/src/main.tsx'),
			output: {
				entryFileNames: `[name].js`, // No need for hash, you have plugin version to deal with caching.
				chunkFileNames: `[name].js`, // No need for hash, you have plugin version to deal with caching.
				assetFileNames: `[name].[ext]`, // No need for hash, you have plugin version to deal with caching.
				manualChunks: undefined, // We want single JS file. By default its split into index and vendor.
				format: 'iife', // We need iife or else globals will clash with WordPress global variables!
			},
		},
	},
	server: {
		port: 5178, // Port running our app, its hardcoded under the class-wp-react-admin-panel-assets.php
	},
	resolve: {
		alias: {
			'@': path.resolve(__dirname, '../includes/admin/backend/src')
		}
	}
}); 