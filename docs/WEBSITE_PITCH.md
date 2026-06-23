# KAAL Realtime: The Future of Blade

## Landing Page Copy

**Headline:**
> Make Any Laravel Blade Application Realtime.

**Subheadline:**
> Add realtime updates, AJAX forms, clusters, presence, and live interactions to existing Blade applications without rewriting your frontend.

### Primary Call to Actions
- [Get Started] -> Routes to `/docs/installation`
- [View Examples] -> Routes to `/examples`

### Features Section
1. **Zero Javascript Required.** Add `kaal-submit` to a form. Wrap an element in `@realtime`. You're done.
2. **Server Actions.** Run backend closures directly from button clicks using `kaal-action="archive"`.
3. **Cluster Sync.** Group multiple realtime fragments together. Refresh an entire chat room layout with `Cluster::refresh('chat-room')`.
4. **Presence.** Track who is online instantly using the `@presence` directive.

## Website Architecture & Design System

### Color Palette (TailwindCSS)
- **Primary:** `indigo-500` to `indigo-600`
- **Backgrounds:** `gray-900` (Dark Mode Default), `gray-800` for cards.
- **Typography:** Inter (Headings), Fira Code (Code blocks).

### Interactive Demos to Build (For the /examples page)
1. **Realtime Chat Demo:** Showcasing `@cluster`, `@realtime`, and `@presence` working together.
2. **Realtime Dashboard:** Showcasing `kaal-action` and broadcast-driven table updates.
3. **Complex Form Demo:** Showcasing `kaal-submit`, validation handling, loading states, and toast notifications.

## Execution Plan for the Docs App
Since KAAL is an internal package, the easiest way to launch the documentation site is using **Vitepress** or a dedicated **Laravel Breeze** installation utilizing `spatie/laravel-markdown`. The Markdown files in the `docs/` folder serve as the definitive source of truth.
