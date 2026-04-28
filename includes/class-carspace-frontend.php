<?php
/**
 * Carspace Frontend Loader
 *
 * Serves the React SPA as a clean HTML document — no theme, no other plugin styles.
 * Uses template_redirect to intercept the page before any theme output.
 *
 * @package Carspace_Dashboard
 */

defined('ABSPATH') || exit;

class Carspace_Frontend {

    private static $manifest = null;

    public static function init() {
        // Register shortcode (used only to detect which page is the SPA page)
        add_shortcode('carspace_app', array(__CLASS__, 'shortcode_placeholder'));
        add_shortcode('carspace_dashboard', array(__CLASS__, 'shortcode_placeholder'));

        // Intercept BEFORE any theme output
        add_action('template_redirect', array(__CLASS__, 'maybe_serve_spa'), 0);
    }

    /**
     * Shortcode placeholder — never actually renders because template_redirect
     * takes over the page before WordPress gets to shortcode processing.
     */
    public static function shortcode_placeholder() {
        return '<div id="carspace-root"></div>';
    }

    /**
     * Check if current page has our shortcode, and if so, serve clean SPA HTML.
     */
    public static function maybe_serve_spa() {
        if (!is_singular()) {
            return;
        }

        global $post;
        if (!$post) {
            return;
        }

        // Check if this page has our shortcode
        $has_shortcode = has_shortcode($post->post_content, 'carspace_app')
                      || has_shortcode($post->post_content, 'carspace_dashboard');

        // Also check configured dashboard page
        if (!$has_shortcode) {
            $spa_page_id = get_option('carspace_dashboard_page_id', 0);
            if ($spa_page_id && (int) $post->ID === (int) $spa_page_id) {
                $has_shortcode = true;
            }
        }

        if (!$has_shortcode) {
            return;
        }

        // Serve SPA page for both logged-in and guest users.
        // Guest users see the SPA login form; authenticated users see the dashboard.
        self::render_spa_page();
        exit;
    }

    /**
     * Output a complete clean HTML page with ONLY the React SPA.
     * No theme, no other plugins, no WordPress styles — just our app.
     */
    private static function render_spa_page() {
        $manifest = self::get_manifest();

        if (empty($manifest) || !isset($manifest['src/main.tsx'])) {
            self::render_error_page();
            return;
        }

        $entry    = $manifest['src/main.tsx'];
        $dist_url = CARSPACE_URL . 'dist/';

        // Base app data (available to both guest and authenticated)
        $app_data = array(
            'ajax_url'   => admin_url('admin-ajax.php'),
            'nonce'      => wp_create_nonce('wp_rest'),
            'plugin_url' => CARSPACE_URL,
            'rest_url'   => esc_url_raw(rest_url('carspace/v1/')),
            'user'       => null,
        );

        // Authenticated user data
        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            $role = 'dealer';
            if (in_array('administrator', (array) $user->roles, true)) {
                $role = 'admin';
            } elseif (in_array('editor', (array) $user->roles, true) || in_array('shop_manager', (array) $user->roles, true)) {
                $role = 'manager';
            }

            $app_data['user'] = array(
                'id'     => $user->ID,
                'name'   => $user->display_name,
                'email'  => $user->user_email,
                'avatar' => get_avatar_url($user->ID, array('size' => 96)),
                'role'   => $role,
            );
        }

        $app_json = wp_json_encode($app_data);
        $js_url   = esc_url($dist_url . $entry['file']);

        // CSS links
        $css_tags = '';
        if (!empty($entry['css'])) {
            foreach ($entry['css'] as $css_file) {
                $css_url = esc_url($dist_url . $css_file);
                $css_tags .= '    <link rel="stylesheet" crossorigin href="' . $css_url . '">' . "\n";
            }
        }

        // Font preloads
        $font_preloads = '';
        if (!empty($entry['assets'])) {
            foreach ($entry['assets'] as $asset) {
                if (strpos($asset, '.woff2') !== false) {
                    $font_url = esc_url($dist_url . $asset);
                    $font_preloads .= '    <link rel="preload" href="' . $font_url . '" as="font" type="font/woff2" crossorigin />' . "\n";
                }
            }
        }

        // Data-chunk modulepreloads — each src/data/*.ts compiles to its own
        // dynamically-imported chunk. They're always loaded on first nav, so
        // hint the browser to fetch them in parallel with main.js instead of
        // waiting for main.js to parse and request them.
        $module_preloads = '';
        foreach ($manifest as $entry_data) {
            if (!is_array($entry_data) || empty($entry_data['file']) || empty($entry_data['isDynamicEntry'])) {
                continue;
            }
            $chunk_url = esc_url($dist_url . $entry_data['file']);
            $module_preloads .= '    <link rel="modulepreload" href="' . $chunk_url . '" crossorigin />' . "\n";
        }

        // Site title for <title> tag
        $site_name = get_bloginfo('name');

        // Send headers
        status_header(200);
        header('Content-Type: text/html; charset=utf-8');
        header('X-Robots-Tag: noindex, nofollow');

        ?><!DOCTYPE html>
<html lang="<?php echo esc_attr(get_locale()); ?>">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo esc_html($site_name); ?> — Dashboard</title>
    <script type="module" crossorigin src="<?php echo $js_url; ?>"></script>
<?php echo $module_preloads; ?>
<?php echo $font_preloads; ?>
<?php echo $css_tags; ?>
</head>
<body>
    <script>window.carspaceApp = <?php echo $app_json; ?>;</script>
    <div id="carspace-root"></div>
</body>
</html><?php
    }

    /**
     * Render a helpful error page when the build is missing.
     */
    private static function render_error_page() {
        $manifest_path = CARSPACE_PATH . 'dist/.vite/manifest.json';
        status_header(200);
        header('Content-Type: text/html; charset=utf-8');
        ?><!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Carspace Dashboard</title></head>
<body style="padding:40px;font-family:system-ui,sans-serif;background:#fff;color:#333;">
    <h2>Carspace Dashboard v<?php echo esc_html(CARSPACE_VERSION); ?></h2>
    <p style="color:red;font-weight:bold;">React build not found.</p>
    <p>Looking for manifest at:<br><code><?php echo esc_html($manifest_path); ?></code></p>
    <p>File exists: <strong><?php echo file_exists($manifest_path) ? 'YES' : 'NO'; ?></strong></p>
    <p>CARSPACE_PATH: <code><?php echo esc_html(CARSPACE_PATH); ?></code></p>
    <p>CARSPACE_URL: <code><?php echo esc_html(CARSPACE_URL); ?></code></p>
    <p>Run <code>npm run build</code> in the plugin directory to generate the production build.</p>
</body>
</html><?php
    }

    /**
     * Read and cache the Vite manifest.
     *
     * Three-tier cache: in-process static var → object cache (Redis if
     * available, request-scoped otherwise) → file_get_contents fallback.
     * Cache group is version-stamped so a plugin upgrade orphans the old
     * entry automatically — no manual flush needed.
     */
    private static function get_manifest() {
        if (self::$manifest !== null) {
            return self::$manifest;
        }

        $cache_group = 'carspace_v' . CARSPACE_VERSION;
        $cached = wp_cache_get('manifest', $cache_group);
        if (is_array($cached)) {
            self::$manifest = $cached;
            return $cached;
        }

        $manifest_path = CARSPACE_PATH . 'dist/.vite/manifest.json';

        if (!file_exists($manifest_path)) {
            self::$manifest = array();
            return self::$manifest;
        }

        $contents = file_get_contents($manifest_path);
        $decoded  = json_decode($contents, true);

        self::$manifest = is_array($decoded) ? $decoded : array();

        wp_cache_set('manifest', self::$manifest, $cache_group, HOUR_IN_SECONDS);

        return self::$manifest;
    }
}
