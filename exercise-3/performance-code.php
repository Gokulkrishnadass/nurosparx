<?php
/**
 * Exercise 3 – Performance Code
 * Option 1: Image Lazy Loading + WebP
 * Option 4: Browser Caching + CDN
 */

/**
 * Generate WebP versions when images are uploaded.
 */
add_filter('wp_generate_attachment_metadata', 'nxs_generate_webp_version', 10, 2);
function nxs_generate_webp_version($metadata, $attachment_id) {
    $file = get_attached_file($attachment_id);
    $info = pathinfo($file);
    $ext  = strtolower($info['extension']);

    // Only process common raster types.
    if (!in_array($ext, array('jpg', 'jpeg', 'png'), true)) {
        return $metadata;
    }

    if (!function_exists('wp_get_image_editor')) {
        return $metadata;
    }

    $editor = wp_get_image_editor($file);
    if (is_wp_error($editor)) {
        return $metadata;
    }

    $webp_file = $info['dirname'] . '/' . $info['filename'] . '.webp';

    $result = $editor->save($webp_file, 'image/webp');
    if (!is_wp_error($result)) {
        // Save reference in attachment meta for later use in templates.
        update_post_meta($attachment_id, '_nxs_webp_file', wp_basename($webp_file));
    }

    return $metadata;
}

/**
 * Helper to output responsive image with WebP fallback + lazy loading.
 */
function nxs_picture_image($attachment_id, $size = 'large', $attrs = array()) {
    $src       = wp_get_attachment_image_src($attachment_id, $size);
    if (!$src) {
        return;
    }

    $uploads   = wp_upload_dir();
    $base_url  = trailingslashit($uploads['baseurl']);
    $base_dir  = trailingslashit($uploads['basedir']);

    $webp_meta = get_post_meta($attachment_id, '_nxs_webp_file', true);
    $webp_url  = '';
    if ($webp_meta) {
        // We assume the WebP is in same folder as original.
        $relative_path = str_replace($base_dir, '', get_attached_file($attachment_id));
        $relative_dir  = trailingslashit(dirname($relative_path));
        $webp_url      = $base_url . $relative_dir . $webp_meta;
    }

    $default_attrs = array(
        'class'   => 'nxs-lazy-image',
        'loading' => 'lazy',
        'decoding'=> 'async',
    );
    $attrs = array_merge($default_attrs, $attrs);

    $attr_str = '';
    foreach ($attrs as $k => $v) {
        $attr_str .= sprintf(' %s="%s"', esc_attr($k), esc_attr($v));
    }

    ?>
    <picture>
        <?php if ($webp_url): ?>
            <source srcset="<?php echo esc_url($webp_url); ?>" type="image/webp">
        <?php endif; ?>
        <img src="<?php echo esc_url($src[0]); ?>"
             width="<?php echo esc_attr($src[1]); ?>"
             height="<?php echo esc_attr($src[2]); ?>"<?php echo $attr_str; ?> />
    </picture>
    <?php
}

/**
 * Lazy-load images inside post content (non-hero).
 * We skip the first image so as not to hurt LCP hero image.
 */
add_filter('the_content', 'nxs_lazyload_content_images', 20);
function nxs_lazyload_content_images($content) {
    if (is_admin()) {
        return $content;
    }

    $count = 0;

    $content = preg_replace_callback(
        '/<img([^>]+)>/i',
        function ($matches) use (&$count) {
            $count++;

            $img = $matches[0];

            // Skip first image to preserve potential LCP hero.
            if ($count === 1) {
                return $img;
            }

            // If loading attr already exists, leave it.
            if (strpos($img, 'loading=') !== false) {
                return $img;
            }

            // Insert loading="lazy" and decoding="async".
            $replacement = '<img loading="lazy" decoding="async" ' . trim($matches[1]) . '>';
            return $replacement;
        },
        $content
    );

    return $content;
}

/**
 * Option 4: CDN Rewrite + cache-busting asset versions.
 */

// Define CDN domain (change to real one).
if (!defined('NXS_CDN_DOMAIN')) {
    define('NXS_CDN_DOMAIN', 'https://cdn.example.com');
}

/**
 * Rewrite attachment URLs to CDN.
 */
add_filter('wp_get_attachment_url', 'nxs_rewrite_to_cdn');
function nxs_rewrite_to_cdn($url) {
    if (!NXS_CDN_DOMAIN) {
        return $url;
    }
    $site_url = home_url();
    return str_replace($site_url, NXS_CDN_DOMAIN, $url);
}

/**
 * Rewrite enqueued CSS/JS to CDN.
 */
add_filter('style_loader_src', 'nxs_rewrite_to_cdn');
add_filter('script_loader_src', 'nxs_rewrite_to_cdn');

/**
 * Cache-busting helper using filemtime as version.
 */
function nxs_enqueue_versioned_style($handle, $relative_path, $deps = array()) {
    $theme_dir  = get_stylesheet_directory();
    $theme_uri  = get_stylesheet_directory_uri();
    $file       = $theme_dir . '/' . ltrim($relative_path, '/');
    $version    = file_exists($file) ? filemtime($file) : null;

    wp_enqueue_style(
        $handle,
        $theme_uri . '/' . ltrim($relative_path, '/'),
        $deps,
        $version
    );
}

function nxs_enqueue_versioned_script($handle, $relative_path, $deps = array(), $in_footer = true) {
    $theme_dir  = get_stylesheet_directory();
    $theme_uri  = get_stylesheet_directory_uri();
    $file       = $theme_dir . '/' . ltrim($relative_path, '/');
    $version    = file_exists($file) ? filemtime($file) : null;

    wp_enqueue_script(
        $handle,
        $theme_uri . '/' . ltrim($relative_path, '/'),
        $deps,
        $version,
        $in_footer
    );
}

/**
 * Example usage replacing multiple CSS/JS files by single bundles.
 */
add_action('wp_enqueue_scripts', 'nxs_enqueue_performance_assets', 20);
function nxs_enqueue_performance_assets() {
    if (!is_front_page()) {
        return;
    }

    // Dequeue theme's default multiple styles/scripts if needed.
    // wp_dequeue_style('theme-style-1'); etc.

    // Enqueue minified bundles with cache-busting versions.
    nxs_enqueue_versioned_style('nxs-theme-bundle', 'assets/css/theme-bundle.min.css');
    nxs_enqueue_versioned_script('nxs-theme-bundle', 'assets/js/theme-bundle.min.js', array('jquery'));
}
