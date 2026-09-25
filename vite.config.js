import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

// Note: the build previously fetched "Instrument Sans" from fonts.bunny.net at
// build time (laravel-vite-plugin/fonts). That remote fetch fails in restricted-
// egress environments (e.g. the cloud loop) and broke `npm run build`. The font is
// no longer fetched at build time; the app uses its CSS fallback stack. To restore
// the exact typeface, self-host the woff2 files with a local @font-face rather than
// reintroducing a build-time network dependency.
export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/css/dashboard-prototype.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
