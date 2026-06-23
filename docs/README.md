# KAAL Realtime Documentation

Welcome to KAAL Realtime! 

KAAL is a framework-quality platform for Laravel that transforms existing Blade applications into highly interactive, realtime experiences **without writing Javascript** or restructuring your entire frontend architecture into an SPA.

## Core Philosophy

*"Keep existing Blade applications. Make them realtime."*

We believe Laravel Blade is fantastic. KAAL is NOT a Livewire clone. It does not try to manage component state, nor does it require Alpine, Vue, React, or HTMX. It simply binds Blade fragments to backend Eloquent events or logical clusters, intercepting DOM changes to surgically update your interface.

## Documentation Index

- **Getting Started**
  - [Installation & Quick Start](./installation.md)
  - [Architecture & Philosophy](./architecture.md)
- **Core Concepts**
  - [The `@realtime` Directive](./realtime.md)
  - [Smart Refresh Strategies](./realtime.md#smart-refresh-strategies)
  - [Preserving & Ignoring DOM State](./realtime.md#preserving-state)
- **Clusters**
  - [Cluster System Overview](./clusters.md)
  - [Dynamic & Nested Clusters](./clusters.md#dynamic-clusters)
- **Interactions**
  - [AJAX Forms & Validation](./forms.md)
  - [Loading States & Toasts](./forms.md#loading-states)
  - [Server Actions](./actions.md)
- **Advanced Features**
  - [Presence & Offline Detection](./advanced.md)
  - [Infinite Scroll & Pagination](./advanced.md)
- **Server API**
  - [Artisan Commands & Debugging](./debugging.md)
  - [Deployment & Scaling Guide](./deployment.md)

## Building the Documentation Website

The KAAL repository includes a massive markdown payload designed to be statically generated or served via a dedicated Laravel documentation website. 

To achieve the premium, Algolia-searchable, dark-mode experience requested:
1. We recommend spinning up a lightweight Laravel app using **Vite** and **TailwindCSS**.
2. Install a markdown parser like `spatie/laravel-markdown` configured with `Shiki` for exact syntax highlighting.
3. Map routes to read these markdown files from the `docs/` folder.
