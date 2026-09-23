import { defineConfig } from 'astro/config';
import mdx from '@astrojs/mdx';
import sitemap from '@astrojs/sitemap';
import icon from 'astro-icon';
import tailwindcss from '@tailwindcss/vite';

const EXCLUDED_FROM_SITEMAP = ['/thank-you/', '/styleguide/'];

const siteUrl = (process.env.SITE_URL || 'https://example.com').replace(/\/$/, '');
// Indexing is opt-in. This launch ships to production before the site should be found.
const allowIndexing = process.env.ALLOW_INDEXING === 'true';
process.env.PUBLIC_ALLOW_INDEXING = allowIndexing ? 'true' : 'false';

// https://astro.build/config
export default defineConfig({
  site: siteUrl,
  output: 'static',
  trailingSlash: 'always',
  build: { format: 'directory' },
  integrations: [
    mdx(),
    icon(),
    ...(allowIndexing
      ? [
          sitemap({
            filter: (page) => {
              const path = new URL(page).pathname;
              return !EXCLUDED_FROM_SITEMAP.some((excluded) => path.startsWith(excluded));
            },
          }),
        ]
      : []),
  ],
  vite: {
    plugins: [tailwindcss()],
  },
});
