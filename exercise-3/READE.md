# Exercise 3 – Performance Implementation

## Options Chosen

1. **Image Lazy Loading & Optimization**
   - Created WebP versions on upload via `wp_generate_attachment_metadata`.
   - Added a `nxs_picture_image()` helper to serve `<picture>` with WebP source and lazy-loaded `<img>`.
   - Added `nxs_lazyload_content_images()` to lazily load all but the first image in content, improving LCP.

2. **Browser Caching & CDN**
   - Added `.htaccess` rules for long-lived caching of static assets.
   - Implemented CDN rewrites via `wp_get_attachment_url` and loader filters for CSS/JS.
   - Added cache-busting helpers using `filemtime()` for CSS/JS bundles.

## Expected Before/After

- **LCP**: 6.8s → ~2.8s
- **TBT**: 1920ms → ~400ms (with JS bundle + defer)
- **CLS**: Reduced by reserving image space and self-hosted fonts.
- **Requests**: 73 → ~45 by bundling CSS/JS.
- **Page Size**: 12.4MB → ~3–4MB (WebP + compression).

## Measurement

- Validate in Lighthouse (mobile + desktop).
- Use WebPageTest for filmstrip view.
- Monitor Core Web Vitals via Search Console and/or RUM tooling.
