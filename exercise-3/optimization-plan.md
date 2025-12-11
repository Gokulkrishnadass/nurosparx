# Exercise 3 – Performance Optimization Plan

## Top 5 Issues (Most Impactful First)

1. **Unoptimized Images (8 large images ~3MB each)**
   - **Problem:** ~24MB of image data is blocking rendering; on mobile this dominates LCP.
   - **Solution:** Compress images, serve responsive sizes, convert to WebP, lazy load non-hero images.
   - **Expected Improvement:** LCP drop from 6.8s → ~2.5–3s, total page size down by ~60–70%.
   - **Difficulty:** Medium (tooling + template changes).

2. **Render-blocking CSS (15 separate files)**
   - **Problem:** Many CSS files block initial render, increasing TTFB→LCP gap.
   - **Solution:** Inline critical CSS, defer or combine non-critical CSS into one minified file.
   - **Expected Improvement:** LCP improvement of 1–2 seconds.
   - **Difficulty:** Medium–Hard (needs auditing and build process).

3. **Blocking JavaScript in `<head>` (12 files, 2.4MB)**
   - **Problem:** Large JS executed before paint adds 1.9s TBT and delays interactivity.
   - **Solution:** Move non-critical JS to footer, defer, or load asynchronously; split bundles.
   - **Expected Improvement:** TBT from 1920ms → <300–400ms.
   - **Difficulty:** Medium.

4. **No Browser Caching**
   - **Problem:** Repeat visitors re-download static assets, wasting bandwidth.
   - **Solution:** Add long-lived cache headers for images, fonts, CSS, JS with cache-busting query strings.
   - **Expected Improvement:** Faster repeat views (50–80% savings) and better Lighthouse scores.
   - **Difficulty:** Easy.

5. **Excessive Database Queries (147 per page)**
   - **Problem:** Slow back-end, especially under load or on shared hosting.
   - **Solution:** Use object caching, remove duplicate WP_Query calls, add DB indexes for hot meta keys.
   - **Expected Improvement:** Backend time reduction by 30–50%.
   - **Difficulty:** Medium.

## Trade-offs

- If the client **insists on keeping all images**:
  - We still compress + convert to WebP + implement lazy loading.
  - Trade-off: Slightly lower quality hero images or delayed loading for below-the-fold images.
  - We clearly explain that visual fidelity vs speed is a business decision affecting conversion.

- For **third-party scripts (Google Fonts, Analytics)**:
  - Self-host a reduced font set (2–3 weights only).
  - Use `display=swap` and preconnect hints.
  - Defer analytics until after first paint or on interaction if possible.

## Measurement Tools

- Google Lighthouse / PageSpeed Insights
- WebPageTest.org for filmstrip view
- Browser DevTools Performance & Coverage tabs
- GTmetrix for before/after waterfall comparison

## Target (Projections)

- LCP: 6.8s → below 2.5–3.0s
- TBT: 1920ms → below 300–400ms
- CLS: from 0.42 → < 0.1 by reserving space for images and fonts
- HTTP requests: 73 → ~40–50 by bundling/minifying
- Page size: 12.4MB → ~2–4MB
