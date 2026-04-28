<?php
/**
 * Carspace Admin Page
 *
 * Adds a wp-admin menu page for plugin settings and shortcode instructions.
 *
 * @package Carspace_Dashboard
 */

defined('ABSPATH') || exit;

class Carspace_Admin {

    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'register_menu'));
        add_action('admin_init', array(__CLASS__, 'register_settings'));
    }

    public static function register_menu() {
        add_menu_page(
            __('Carspace Dashboard', 'carspace-dashboard'),
            __('Carspace', 'carspace-dashboard'),
            'manage_options',
            'carspace-dashboard',
            array(__CLASS__, 'render_page'),
            'dashicons-car',
            30
        );

        add_submenu_page(
            'carspace-dashboard',
            __('Settings', 'carspace-dashboard'),
            __('Settings', 'carspace-dashboard'),
            'manage_options',
            'carspace-settings',
            array(__CLASS__, 'render_settings_page')
        );
    }

    public static function register_settings() {
        register_setting('carspace_settings', 'carspace_dashboard_page_id', array(
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'default'           => 0,
        ));
    }

    public static function render_page() {
        $page_id = get_option('carspace_dashboard_page_id', 0);
        $page_url = $page_id ? get_permalink($page_id) : '';
        $tables_ok = self::check_tables();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Carspace Dashboard', 'carspace-dashboard'); ?></h1>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px; max-width: 900px;">

                <!-- Status Card -->
                <div class="card" style="padding: 20px; margin: 0;">
                    <h2 style="margin-top: 0;"><?php esc_html_e('Plugin Status', 'carspace-dashboard'); ?></h2>
                    <table class="widefat striped" style="border: none;">
                        <tbody>
                            <tr>
                                <td><strong><?php esc_html_e('Version', 'carspace-dashboard'); ?></strong></td>
                                <td><?php echo esc_html(CARSPACE_VERSION); ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php esc_html_e('DB Version', 'carspace-dashboard'); ?></strong></td>
                                <td><?php echo esc_html(get_option('carspace_db_version', 'Not installed')); ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php esc_html_e('Custom Tables', 'carspace-dashboard'); ?></strong></td>
                                <td>
                                    <?php if ($tables_ok['all_ok']) : ?>
                                        <span style="color: green;">&#10003; <?php esc_html_e('All tables created', 'carspace-dashboard'); ?></span>
                                    <?php else : ?>
                                        <span style="color: red;">&#10007; <?php esc_html_e('Missing tables:', 'carspace-dashboard'); ?>
                                            <?php echo esc_html(implode(', ', $tables_ok['missing'])); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong><?php esc_html_e('React SPA', 'carspace-dashboard'); ?></strong></td>
                                <td>
                                    <?php if (file_exists(CARSPACE_PATH . 'dist/.vite/manifest.json')) : ?>
                                        <span style="color: green;">&#10003; <?php esc_html_e('Built & ready', 'carspace-dashboard'); ?></span>
                                    <?php else : ?>
                                        <span style="color: red;">&#10007; <?php esc_html_e('Not built — run npm run build', 'carspace-dashboard'); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong><?php esc_html_e('Dashboard Page', 'carspace-dashboard'); ?></strong></td>
                                <td>
                                    <?php if ($page_id && get_post($page_id)) : ?>
                                        <span style="color: green;">&#10003;
                                            <a href="<?php echo esc_url($page_url); ?>" target="_blank">
                                                <?php echo esc_html(get_the_title($page_id)); ?>
                                            </a>
                                        </span>
                                    <?php else : ?>
                                        <span style="color: orange;">&#9888; <?php esc_html_e('Not configured — set in Settings', 'carspace-dashboard'); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Shortcode Card -->
                <div class="card" style="padding: 20px; margin: 0;">
                    <h2 style="margin-top: 0;"><?php esc_html_e('How to Use', 'carspace-dashboard'); ?></h2>
                    <p><?php esc_html_e('Add the following shortcode to any WordPress page to display the dashboard:', 'carspace-dashboard'); ?></p>
                    <p>
                        <code style="display: inline-block; padding: 8px 16px; background: #f0f0f1; font-size: 14px; border-radius: 4px;">[carspace_app]</code>
                    </p>
                    <p class="description"><?php esc_html_e('The shortcode renders a full-screen React SPA. Only logged-in users will see the dashboard. The legacy shortcode [carspace_dashboard] also works as an alias.', 'carspace-dashboard'); ?></p>

                    <hr style="margin: 15px 0;">
                    <h3 style="margin-top: 0;"><?php esc_html_e('Quick Setup', 'carspace-dashboard'); ?></h3>
                    <ol style="margin-left: 20px;">
                        <li><?php esc_html_e('Create a new WordPress page (e.g. "Dashboard")', 'carspace-dashboard'); ?></li>
                        <li><?php esc_html_e('Add the shortcode [carspace_app] as the only content', 'carspace-dashboard'); ?></li>
                        <li><?php esc_html_e('Set the page ID in Settings to enable direct links', 'carspace-dashboard'); ?></li>
                        <li><?php esc_html_e('Logged-in users can now access the dashboard', 'carspace-dashboard'); ?></li>
                    </ol>
                </div>

                <!-- Stats Card -->
                <div class="card" style="padding: 20px; margin: 0; grid-column: 1 / -1;">
                    <h2 style="margin-top: 0;"><?php esc_html_e('Data Overview', 'carspace-dashboard'); ?></h2>
                    <div style="display: flex; gap: 30px; flex-wrap: wrap;">
                        <?php
                        global $wpdb;
                        $invoice_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'invoice' AND post_status = 'publish'");
                        $product_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status = 'publish'");
                        $notif_table = $wpdb->prefix . 'carspace_notifications';
                        $notif_count = $wpdb->get_var("SELECT COUNT(*) FROM {$notif_table}");
                        if ($notif_count === null) $notif_count = 0;
                        ?>
                        <div style="text-align: center;">
                            <div style="font-size: 28px; font-weight: 700; color: #2271b1;"><?php echo intval($product_count); ?></div>
                            <div style="font-size: 12px; color: #646970;"><?php esc_html_e('Products', 'carspace-dashboard'); ?></div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 28px; font-weight: 700; color: #2271b1;"><?php echo intval($invoice_count); ?></div>
                            <div style="font-size: 12px; color: #646970;"><?php esc_html_e('Invoices', 'carspace-dashboard'); ?></div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 28px; font-weight: 700; color: #2271b1;"><?php echo intval($notif_count); ?></div>
                            <div style="font-size: 12px; color: #646970;"><?php esc_html_e('Notifications', 'carspace-dashboard'); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public static function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Carspace Settings', 'carspace-dashboard'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('carspace_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="carspace_dashboard_page_id"><?php esc_html_e('Dashboard Page', 'carspace-dashboard'); ?></label>
                        </th>
                        <td>
                            <?php
                            wp_dropdown_pages(array(
                                'name'             => 'carspace_dashboard_page_id',
                                'id'               => 'carspace_dashboard_page_id',
                                'selected'         => get_option('carspace_dashboard_page_id', 0),
                                'show_option_none' => __('— Select a page —', 'carspace-dashboard'),
                                'option_none_value' => 0,
                            ));
                            ?>
                            <p class="description">
                                <?php esc_html_e('Select the page where [carspace_app] shortcode is placed. This enables direct links from notifications and emails.', 'carspace-dashboard'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    private static function check_tables() {
        global $wpdb;
        $required = array(
            $wpdb->prefix . 'carspace_invoices',
            $wpdb->prefix . 'carspace_invoice_items',
            $wpdb->prefix . 'carspace_notifications',
            $wpdb->prefix . 'carspace_port_images',
        );

        $missing = array();
        foreach ($required as $table) {
            $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
            if (!$exists) {
                $missing[] = str_replace($wpdb->prefix, '', $table);
            }
        }

        return array(
            'all_ok'  => empty($missing),
            'missing' => $missing,
        );
    }
}
