<?php
/**
 * Account Notifications Endpoint
 *
 * Displays all notifications for the current user from carspace_notifications table.
 *
 * @package Carspace_Dashboard
 * @since 5.0.0
 */

defined('ABSPATH') || exit;

// Include base endpoint class if not already included
if (!class_exists('Carspace_Endpoint')) {
    require_once CARSPACE_PATH . 'includes/endpoints/class-carspace-endpoint.php';
}

/**
 * Account Notifications endpoint class
 */
class Carspace_Endpoint_Account_Notifications extends Carspace_Endpoint {
    /**
     * Constructor
     */
    public function __construct() {
        $this->endpoint = 'account-notifications';
        $this->title = __('Notifications', 'carspace-dashboard');
        $this->icon = 'bell';
        $this->position = 80;

        parent::__construct();
    }

    /**
     * Render endpoint content
     */
    public function render_content() {
        echo '<h3>' . esc_html__('Your Notifications', 'carspace-dashboard') . '</h3>';

        if (!is_user_logged_in()) {
            echo '<p>' . esc_html__('Please log in to view your notifications.', 'carspace-dashboard') . '</p>';
            return;
        }

        $user_id  = get_current_user_id();
        $paged    = max(1, absint(get_query_var('paged') ?: (isset($_GET['paged']) ? $_GET['paged'] : 1)));
        $per_page = 10;

        $result = Carspace_Notification::get_for_user($user_id, false, $per_page, $paged);
        $items  = $result['items'];
        $total  = $result['total'];
        $max_num_pages = max(1, ceil($total / $per_page));

        echo '<div class="carspace-notifications container my-4">';

        // "Mark All as Read" button
        echo '<div class="text-end mb-3">';
        echo '<button id="mark-all-read" class="btn btn-outline-secondary btn-sm">';
        echo esc_html__('Mark All as Read', 'carspace-dashboard');
        echo '</button>';
        echo '</div>';

        if (!empty($items)) {
            echo '<ul class="notification-list list-unstyled">';

            foreach ($items as $notif) {
                $status   = !empty($notif->status) ? $notif->status : 'unread';
                $type     = !empty($notif->type) ? $notif->type : 'info';
                $link     = !empty($notif->link) ? $notif->link : '';
                $message  = !empty($notif->message) ? $notif->message : '';

                $is_unread    = ($status === 'unread');
                $status_class = $is_unread ? 'unread' : 'read';
                $type_icon    = $type === 'alert' ? '!' : 'bell';

                $this->render_notification_item($notif, $status_class, $type_icon, $message, $link, $is_unread);
            }

            echo '</ul>';

            // Pagination
            $this->render_pagination($max_num_pages, $paged);
        } else {
            echo '<p>' . esc_html__('You have no notifications.', 'carspace-dashboard') . '</p>';
        }

        echo '</div>';

        // Add current date and user info in a small footer
        echo '<div class="text-muted small mt-4 text-end">';
        printf(
            /* translators: %1$s: current date */
            esc_html__('Last updated: %1$s ', 'carspace-dashboard'),
            date_i18n(get_option('date_format') . ' ' . get_option('time_format'), current_time('timestamp')),
            wp_get_current_user()->user_login
        );
        echo '</div>';
    }

    /**
     * Render a single notification item
     *
     * @param object $notif        Notification row from custom table.
     * @param string $status_class CSS class for notification status.
     * @param string $type_icon    Icon identifier for notification type.
     * @param string $message      Notification message.
     * @param string $link         Optional link for the notification.
     * @param bool   $is_unread    Whether the notification is unread.
     */
    protected function render_notification_item($notif, $status_class, $type_icon, $message, $link, $is_unread) {
        $notification_id = $notif->id;
        $title           = esc_html($notif->title);
        $date_display    = date_i18n(get_option('date_format'), strtotime($notif->created_at));
        $icon_display    = $type_icon === '!' ? '!' : 'bell';
        ?>
        <li class="notification-item <?php echo esc_attr($status_class); ?> mb-4 p-3 border rounded shadow-sm notification-card" data-notification-id="<?php echo esc_attr($notification_id); ?>">
            <div class="notification-content d-flex align-items-start gap-3">
                <span class="notification-icon fs-4"><?php echo esc_html($icon_display); ?></span>
                <div class="notification-text">
                    <strong><?php echo $title; ?></strong><br>
                    <small class="text-muted"><?php echo esc_html($date_display); ?></small>

                    <?php if (!empty($message)) : ?>
                        <div class="notification-message mt-2"><?php echo wp_kses_post($message); ?></div>
                    <?php endif; ?>

                    <?php if (!empty($link)) : ?>
                        <div class="notification-link mt-2">
                            <a href="<?php echo esc_url($link); ?>" class="notification-action btn btn-sm btn-outline-primary">
                                <?php esc_html_e('View', 'carspace-dashboard'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($is_unread) : ?>
                <div class="mark-read-btn mt-2 text-end">
                    <button class="btn btn-sm btn-outline-success mark-notification-read" title="<?php esc_attr_e('Mark as read', 'carspace-dashboard'); ?>" data-notification-id="<?php echo esc_attr($notification_id); ?>">
                        <?php esc_html_e('Mark as Read', 'carspace-dashboard'); ?>
                    </button>
                </div>
            <?php endif; ?>
        </li>
        <?php
    }

    /**
     * Render pagination for notifications
     *
     * @param int $total_pages  Total number of pages.
     * @param int $current_page Current page number.
     */
    protected function render_pagination($total_pages, $current_page) {
        if ($total_pages <= 1) {
            return;
        }

        echo '<nav class="pagination-nav mt-4"><ul class="pagination justify-content-center">';

        // Previous page link
        if ($current_page > 1) {
            echo '<li class="page-item">';
            echo '<a class="page-link" href="' . esc_url(add_query_arg('page', $current_page - 1)) . '">';
            echo '&laquo; ' . esc_html__('Previous', 'carspace-dashboard');
            echo '</a>';
            echo '</li>';
        }

        // Page numbers
        $start_page = max(1, $current_page - 2);
        $end_page = min($total_pages, $current_page + 2);

        if ($start_page > 1) {
            echo '<li class="page-item"><a class="page-link" href="' . esc_url(add_query_arg('page', 1)) . '">1</a></li>';
            if ($start_page > 2) {
                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }

        for ($i = $start_page; $i <= $end_page; $i++) {
            $active = $i === $current_page ? ' active' : '';
            echo '<li class="page-item' . $active . '">';
            echo '<a class="page-link" href="' . esc_url(add_query_arg('page', $i)) . '">' . $i . '</a>';
            echo '</li>';
        }

        if ($end_page < $total_pages) {
            if ($end_page < $total_pages - 1) {
                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
            echo '<li class="page-item"><a class="page-link" href="' . esc_url(add_query_arg('page', $total_pages)) . '">' . $total_pages . '</a></li>';
        }

        // Next page link
        if ($current_page < $total_pages) {
            echo '<li class="page-item">';
            echo '<a class="page-link" href="' . esc_url(add_query_arg('page', $current_page + 1)) . '">';
            echo esc_html__('Next', 'carspace-dashboard') . ' &raquo;';
            echo '</a>';
            echo '</li>';
        }

        echo '</ul></nav>';
    }
}

// Initialize the endpoint
new Carspace_Endpoint_Account_Notifications();
