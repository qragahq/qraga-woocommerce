// vite.config.ts
import { defineConfig } from "file:///Users/ferhat/Development/qraga/qraga-woocommerce/includes/admin/backend/node_modules/.pnpm/vite@5.4.19_lightningcss@1.29.2/node_modules/vite/dist/node/index.js";
import react from "file:///Users/ferhat/Development/qraga/qraga-woocommerce/includes/admin/backend/node_modules/.pnpm/@vitejs+plugin-react@4.4.1_vite@5.4.19_lightningcss@1.29.2_/node_modules/@vitejs/plugin-react/dist/index.mjs";
import tsconfigPaths from "file:///Users/ferhat/Development/qraga/qraga-woocommerce/includes/admin/backend/node_modules/.pnpm/vite-tsconfig-paths@4.3.2_typescript@5.8.3_vite@5.4.19_lightningcss@1.29.2_/node_modules/vite-tsconfig-paths/dist/index.mjs";
import tailwindcss from "file:///Users/ferhat/Development/qraga/qraga-woocommerce/includes/admin/backend/node_modules/.pnpm/@tailwindcss+vite@4.1.5_vite@5.4.19_lightningcss@1.29.2_/node_modules/@tailwindcss/vite/dist/index.mjs";
var vite_config_default = defineConfig({
  plugins: [react(), tsconfigPaths(), tailwindcss()],
  build: {
    rollupOptions: {
      output: {
        entryFileNames: `[name].js`,
        // No need for hash, you have plugin version to deal with caching.
        chunkFileNames: `[name].js`,
        // No need for hash, you have plugin version to deal with caching.
        assetFileNames: `[name].[ext]`,
        // No need for hash, you have plugin version to deal with caching.
        dir: `../assets/js`,
        // This is assets folder one directory below backend folder.
        manualChunks: void 0,
        // We want single JS file. By default its split into index and vendor.
        format: "iife"
        // We need iife or else globals will clash with WordPress global variables!
      }
    }
  },
  server: {
    port: 5178
    // Port running our app, its hardcoded under the class-wp-react-admin-panel-assets.php
  }
});
export {
  vite_config_default as default
};
//# sourceMappingURL=data:application/json;base64,ewogICJ2ZXJzaW9uIjogMywKICAic291cmNlcyI6IFsidml0ZS5jb25maWcudHMiXSwKICAic291cmNlc0NvbnRlbnQiOiBbImNvbnN0IF9fdml0ZV9pbmplY3RlZF9vcmlnaW5hbF9kaXJuYW1lID0gXCIvVXNlcnMvZmVyaGF0L0RldmVsb3BtZW50L3FyYWdhL3FyYWdhLXdvb2NvbW1lcmNlL2luY2x1ZGVzL2FkbWluL2JhY2tlbmRcIjtjb25zdCBfX3ZpdGVfaW5qZWN0ZWRfb3JpZ2luYWxfZmlsZW5hbWUgPSBcIi9Vc2Vycy9mZXJoYXQvRGV2ZWxvcG1lbnQvcXJhZ2EvcXJhZ2Etd29vY29tbWVyY2UvaW5jbHVkZXMvYWRtaW4vYmFja2VuZC92aXRlLmNvbmZpZy50c1wiO2NvbnN0IF9fdml0ZV9pbmplY3RlZF9vcmlnaW5hbF9pbXBvcnRfbWV0YV91cmwgPSBcImZpbGU6Ly8vVXNlcnMvZmVyaGF0L0RldmVsb3BtZW50L3FyYWdhL3FyYWdhLXdvb2NvbW1lcmNlL2luY2x1ZGVzL2FkbWluL2JhY2tlbmQvdml0ZS5jb25maWcudHNcIjtpbXBvcnQgeyBkZWZpbmVDb25maWcgfSBmcm9tICd2aXRlJztcbmltcG9ydCByZWFjdCBmcm9tICdAdml0ZWpzL3BsdWdpbi1yZWFjdCc7XG5pbXBvcnQgdHNjb25maWdQYXRocyBmcm9tICd2aXRlLXRzY29uZmlnLXBhdGhzJztcbmltcG9ydCB0YWlsd2luZGNzcyBmcm9tICdAdGFpbHdpbmRjc3Mvdml0ZSc7XG5cbi8vIGh0dHBzOi8vdml0ZWpzLmRldi9jb25maWcvXG5leHBvcnQgZGVmYXVsdCBkZWZpbmVDb25maWcoe1xuXHRwbHVnaW5zOiBbcmVhY3QoKSwgdHNjb25maWdQYXRocygpLCB0YWlsd2luZGNzcygpXSxcblx0YnVpbGQ6IHtcblx0XHRyb2xsdXBPcHRpb25zOiB7XG5cdFx0XHRvdXRwdXQ6IHtcblx0XHRcdFx0ZW50cnlGaWxlTmFtZXM6IGBbbmFtZV0uanNgLCAvLyBObyBuZWVkIGZvciBoYXNoLCB5b3UgaGF2ZSBwbHVnaW4gdmVyc2lvbiB0byBkZWFsIHdpdGggY2FjaGluZy5cblx0XHRcdFx0Y2h1bmtGaWxlTmFtZXM6IGBbbmFtZV0uanNgLCAvLyBObyBuZWVkIGZvciBoYXNoLCB5b3UgaGF2ZSBwbHVnaW4gdmVyc2lvbiB0byBkZWFsIHdpdGggY2FjaGluZy5cblx0XHRcdFx0YXNzZXRGaWxlTmFtZXM6IGBbbmFtZV0uW2V4dF1gLCAvLyBObyBuZWVkIGZvciBoYXNoLCB5b3UgaGF2ZSBwbHVnaW4gdmVyc2lvbiB0byBkZWFsIHdpdGggY2FjaGluZy5cblx0XHRcdFx0ZGlyOiBgLi4vYXNzZXRzL2pzYCwgLy8gVGhpcyBpcyBhc3NldHMgZm9sZGVyIG9uZSBkaXJlY3RvcnkgYmVsb3cgYmFja2VuZCBmb2xkZXIuXG5cdFx0XHRcdG1hbnVhbENodW5rczogdW5kZWZpbmVkLCAvLyBXZSB3YW50IHNpbmdsZSBKUyBmaWxlLiBCeSBkZWZhdWx0IGl0cyBzcGxpdCBpbnRvIGluZGV4IGFuZCB2ZW5kb3IuXG5cdFx0XHRcdGZvcm1hdDogJ2lpZmUnLCAvLyBXZSBuZWVkIGlpZmUgb3IgZWxzZSBnbG9iYWxzIHdpbGwgY2xhc2ggd2l0aCBXb3JkUHJlc3MgZ2xvYmFsIHZhcmlhYmxlcyFcblx0XHRcdH0sXG5cdFx0fSxcblx0fSxcblx0c2VydmVyOiB7XG5cdFx0cG9ydDogNTE3OCwgLy8gUG9ydCBydW5uaW5nIG91ciBhcHAsIGl0cyBoYXJkY29kZWQgdW5kZXIgdGhlIGNsYXNzLXdwLXJlYWN0LWFkbWluLXBhbmVsLWFzc2V0cy5waHBcblx0fSxcbn0pO1xuIl0sCiAgIm1hcHBpbmdzIjogIjtBQUEwWSxTQUFTLG9CQUFvQjtBQUN2YSxPQUFPLFdBQVc7QUFDbEIsT0FBTyxtQkFBbUI7QUFDMUIsT0FBTyxpQkFBaUI7QUFHeEIsSUFBTyxzQkFBUSxhQUFhO0FBQUEsRUFDM0IsU0FBUyxDQUFDLE1BQU0sR0FBRyxjQUFjLEdBQUcsWUFBWSxDQUFDO0FBQUEsRUFDakQsT0FBTztBQUFBLElBQ04sZUFBZTtBQUFBLE1BQ2QsUUFBUTtBQUFBLFFBQ1AsZ0JBQWdCO0FBQUE7QUFBQSxRQUNoQixnQkFBZ0I7QUFBQTtBQUFBLFFBQ2hCLGdCQUFnQjtBQUFBO0FBQUEsUUFDaEIsS0FBSztBQUFBO0FBQUEsUUFDTCxjQUFjO0FBQUE7QUFBQSxRQUNkLFFBQVE7QUFBQTtBQUFBLE1BQ1Q7QUFBQSxJQUNEO0FBQUEsRUFDRDtBQUFBLEVBQ0EsUUFBUTtBQUFBLElBQ1AsTUFBTTtBQUFBO0FBQUEsRUFDUDtBQUNELENBQUM7IiwKICAibmFtZXMiOiBbXQp9Cg==
