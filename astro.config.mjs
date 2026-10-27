import { defineConfig } from 'astro/config';
import sitemap from '@astrojs/sitemap';

export default defineConfig({
  site: 'https://ethnoirconsulting.com',
  integrations: [sitemap()],
  output: 'static',
  build: {
    format: 'directory',
    assets: 'assets',
  },
});
