import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            // Admin panel only; the public website is the Next.js app in frontend/.
            input: ['resources/css/admin.css', 'resources/js/admin.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    build: {
        // The admin bundle carries the Jodit editor.
        chunkSizeWarningLimit: 900,
    },
});
