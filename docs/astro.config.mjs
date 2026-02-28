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
            expressiveCode: {},
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
                        { label: 'Why Swarm Icons?', slug: 'getting-started/why-swarm-icons' },
                        { label: 'Installation',     slug: 'getting-started/installation' },
                        { label: 'Quick Start',      slug: 'getting-started/quick-start' },
                        { label: 'Core Concepts',    slug: 'getting-started/core-concepts' },
                    ],
                },
                {
                    label: 'Providers',
                    items: [
                        { label: 'Using Local SVG Files',  slug: 'providers/local-svg' },
                        { label: 'Using JSON Collections', slug: 'providers/json-collections' },
                        { label: 'Using the Iconify API',  slug: 'providers/iconify-api' },
                        { label: 'Using Hybrid Provider',  slug: 'providers/hybrid' },
                    ],
                },
                {
                    label: 'Configuration',
                    items: [
                        { label: 'Configuration Builder',  slug: 'configuration/builder' },
                        { label: 'Defaults & Rendering',   slug: 'configuration/defaults-and-rendering' },
                        { label: 'Advanced Options',        slug: 'configuration/advanced-options' },
                    ],
                },
                {
                    label: 'Guides',
                    items: [
                        { label: 'Twig Integration', slug: 'guides/twig' },
                        { label: 'Laravel Blade',    slug: 'guides/laravel-blade' },
                        { label: 'Slim Framework',   slug: 'guides/slim' },
                        { label: 'CommonMark',       slug: 'guides/commonmark' },
                        { label: 'Accessibility',    slug: 'guides/accessibility' },
                        { label: 'SVG Sprites',    slug: 'guides/sprites' },
                        { label: 'Icon Stacking',  slug: 'guides/stacking' },
                    ],
                },
                {
                    label: 'CLI Reference',
                    items: [
                        { label: 'Overview',          slug: 'cli/overview' },
                        { label: 'Icon Set Commands', slug: 'cli/icon-sets' },
                        { label: 'Updating Sets',     slug: 'cli/updating-sets' },
                        { label: 'Exporting Icons',   slug: 'cli/exporting-icons' },
                        { label: 'Cache & Utilities', slug: 'cli/cache-and-utilities' },
                    ],
                },
                {
                    label: 'Reference',
                    items: [
                        { label: 'Fluent API',     slug: 'reference/fluent-api' },
                    ],
                },
            ],
        }),
    ],
});
