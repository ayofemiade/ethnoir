import { defineConfig } from 'astro/config';
import sitemap from '@astrojs/sitemap';

export default defineConfig({
  site: 'https://ethnoir.com', // Replace with the actual production domain
  integrations: [sitemap()],
  output: 'static',
  build: {
    format: 'directory',
  },
});
