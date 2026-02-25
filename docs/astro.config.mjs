// @ts-check
import { defineConfig } from 'astro/config';
import starlight from '@astrojs/starlight';

// https://astro.build/config
export default defineConfig({
    site: 'https://frostybee.github.io',
    base: '/swarm-icons',
    integrations: [
        starlight({
            title: 'Swarm Icons',
            favicon: '/favicon.svg',
            customCss: ['./src/styles/custom.css'],
            head: [
                {
                    tag: 'link',
                    attrs: {
                        rel: 'preconnect',
                        href: 'https://fonts.googleapis.com',
                    },
                },
                {
                    tag: 'link',
                    attrs: {
                        rel: 'preconnect',
                        href: 'https://fonts.gstatic.com',
                        crossorigin: true,
                    },
                },
                {
                    tag: 'link',
                    attrs: {
                        rel: 'stylesheet',
                        href: 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600;700&display=swap',
                    },
                },
                {
                    tag: 'script',
                    content: `
                        (function() {
                            if (!localStorage.getItem('starlight-theme')) {
                                localStorage.setItem('starlight-theme', 'dark');
                            }
                        })();
                    `,
                },
            ],
            description: 'Framework-Agnostic PHP Library for Rendering SVG Icons',
            social: [
                {
                    icon: 'github',
                    label: 'GitHub',
                    href: 'https://github.com/frostybee/swarm-icons',
                },
            ],
            sidebar: [
                {
                    label: 'Getting Started',
                    items: [
                        { label: 'Installation',  slug: 'getting-started/installation' },
                        { label: 'Quick Start',   slug: 'getting-started/quick-start' },
                        { label: 'Core Concepts', slug: 'getting-started/core-concepts' },
                    ],
                },
                {
                    label: 'Providers',
                    items: [
                        { label: 'Overview',               slug: 'providers/overview' },
                        { label: 'Local SVG',              slug: 'providers/local-svg' },
                        { label: 'JSON Collections',       slug: 'providers/json-collections' },
                        { label: 'Iconify API (Runtime)',   slug: 'providers/iconify-api' },
                        { label: 'Hybrid (ChainProvider)', slug: 'providers/hybrid' },
                    ],
                },
                {
                    label: 'Guides',
                    items: [
                        { label: 'CLI: json:download',  slug: 'guides/json-download' },
                        { label: 'Twig',                slug: 'guides/twig' },
                        { label: 'Using with Laravel',  slug: 'guides/laravel-blade' },
                        { label: 'CommonMark',          slug: 'guides/commonmark' },
                        { label: 'Accessibility',       slug: 'guides/accessibility' },
                    ],
                },
                {
                    label: 'Reference',
                    items: [
                        { label: 'Configuration', slug: 'reference/configuration' },
                        { label: 'Fluent API',    slug: 'reference/fluent-api' },
                        { label: 'CLI Commands',  slug: 'reference/cli-commands' },
                    ],
                },
                {
                    label: 'Development',
                    items: [
                        { label: 'Commands',     slug: 'development/commands' },
                        { label: 'Contributing', slug: 'development/contributing' },
                    ],
                },
            ],
        }),
    ],
});
