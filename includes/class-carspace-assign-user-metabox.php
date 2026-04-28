<?php
/**
 * Car Assignment Metabox + Quick Edit
 *
 * Adds an "Assigned User" dropdown to the WooCommerce product edit screen
 * and to the Quick Edit panel on the products list.
 * Stores the user ID in postmeta key `assigned_user`.
 *
 * @package Carspace_Dashboard
 */

defined( 'ABSPATH' ) || exit;

class Carspace_Assign_User_Metabox {

    private static $users_cache = null;

    public static function init() {
        // Metabox on product edit screen
        add_action( 'add_meta_boxes', array( __CLASS__, 'register_metabox' ) );
        add_action( 'save_post_product', array( __CLASS__, 'save' ), 20 );

        // Quick Edit on products list
        add_filter( 'manage_product_posts_columns', array( __CLASS__, 'add_column' ) );
        add_action( 'manage_product_posts_custom_column', array( __CLASS__, 'render_column' ), 10, 2 );
        add_action( 'quick_edit_custom_box', array( __CLASS__, 'render_quick_edit' ), 10, 2 );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_quick_edit_js' ) );

        // AJAX handler for "Update Price" button
        add_action( 'wp_ajax_carspace_update_transport_price', array( __CLASS__, 'ajax_update_price' ) );
    }

    private static function get_users_list() {
        if ( self::$users_cache !== null ) {
            return self::$users_cache;
        }
        self::$users_cache = get_users( array(
            'role__in' => array( 'administrator', 'editor', 'shop_manager', 'subscriber', 'customer' ),
            'orderby'  => 'display_name',
            'order'    => 'ASC',
            'number'   => 500,
        ) );
        return self::$users_cache;
    }

    /* ------------------------------------------------------------------
     * Metabox (product edit screen)
     * ----------------------------------------------------------------*/

    public static function register_metabox() {
        add_meta_box(
            'carspace_assigned_user',
            __( 'Car Assignment', 'carspace-dashboard' ),
            array( __CLASS__, 'render' ),
            'product',
            'side',
            'high'
        );
    }

    public static function render( $post ) {
        $assigned = get_post_meta( $post->ID, 'assigned_user', true );
        $users    = self::get_users_list();

        wp_nonce_field( 'carspace_assign_user', 'carspace_assign_user_nonce' );
        ?>
        <p>
            <label for="carspace_assigned_user_select">
                <strong><?php esc_html_e( 'Assigned User', 'carspace-dashboard' ); ?></strong>
            </label>
        </p>
        <select
            id="carspace_assigned_user_select"
            name="carspace_assigned_user"
            style="width: 100%; max-width: 100%;"
        >
            <option value=""><?php esc_html_e( '&mdash; Select User &mdash;', 'carspace-dashboard' ); ?></option>
            <?php foreach ( $users as $user ) : ?>
                <option
                    value="<?php echo esc_attr( $user->ID ); ?>"
                    <?php selected( (int) $assigned, $user->ID ); ?>
                >
                    <?php echo esc_html( $user->display_name ); ?>
                    (<?php echo esc_html( $user->user_email ); ?>)
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    /* ------------------------------------------------------------------
     * Save (shared by metabox + quick edit)
     * ----------------------------------------------------------------*/

    public static function save( $post_id ) {
        // Quick edit sends its own nonce
        $has_metabox_nonce    = isset( $_POST['carspace_assign_user_nonce'] ) && wp_verify_nonce( $_POST['carspace_assign_user_nonce'], 'carspace_assign_user' );
        $has_quick_edit_nonce = isset( $_POST['carspace_assign_user_qe_nonce'] ) && wp_verify_nonce( $_POST['carspace_assign_user_qe_nonce'], 'carspace_assign_user_qe' );

        if ( ! $has_metabox_nonce && ! $has_quick_edit_nonce ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        if ( ! isset( $_POST['carspace_assigned_user'] ) ) return;

        $user_id = intval( $_POST['carspace_assigned_user'] );

        if ( $user_id > 0 ) {
            update_post_meta( $post_id, 'assigned_user', $user_id );
        } else {
            delete_post_meta( $post_id, 'assigned_user' );
        }
    }

    /* ------------------------------------------------------------------
     * Products list column (shows assigned user + stores value for JS)
     * ----------------------------------------------------------------*/

    public static function add_column( $columns ) {
        $new = array();
        foreach ( $columns as $key => $label ) {
            $new[ $key ] = $label;
            if ( $key === 'sku' || $key === 'name' ) {
                $new['assigned_user'] = __( 'Assigned User', 'carspace-dashboard' );
            }
        }
        if ( ! isset( $new['assigned_user'] ) ) {
            $new['assigned_user'] = __( 'Assigned User', 'carspace-dashboard' );
        }
        return $new;
    }

    public static function render_column( $column, $post_id ) {
        if ( $column !== 'assigned_user' ) return;

        $user_id = get_post_meta( $post_id, 'assigned_user', true );
        $name    = '';

        if ( $user_id ) {
            $user = get_userdata( (int) $user_id );
            $name = $user ? $user->display_name : '';
        }

        echo '<span class="carspace-assigned-user-value" data-user-id="' . esc_attr( $user_id ) . '">';
        echo esc_html( $name ?: '—' );
        echo '</span>';

        // "Update Price" button (visible when a user is assigned)
        if ( $user_id && class_exists( 'Carspace_Transport_Price' ) ) {
            $price = get_post_meta( $post_id, '_regular_price', true );
            echo '<br><button type="button" class="button button-small carspace-update-price" '
               . 'data-product-id="' . esc_attr( $post_id ) . '" '
               . 'style="margin-top:4px;font-size:11px;">'
               . esc_html__( '$ Update Price', 'carspace-dashboard' )
               . '</button>';
            if ( $price !== '' && $price !== null ) {
                echo ' <span class="carspace-current-price" style="color:#666;font-size:11px;">$' . esc_html( number_format( (float) $price, 2 ) ) . '</span>';
            }
        }
    }

    /* ------------------------------------------------------------------
     * Quick Edit field
     * ----------------------------------------------------------------*/

    public static function render_quick_edit( $column_name, $post_type ) {
        if ( $column_name !== 'assigned_user' || $post_type !== 'product' ) return;

        $users = self::get_users_list();
        ?>
        <fieldset class="inline-edit-col-right" style="clear: both;">
            <div class="inline-edit-col">
                <label class="inline-edit-group">
                    <span class="title"><?php esc_html_e( 'Assigned User', 'carspace-dashboard' ); ?></span>
                    <span class="input-text-wrap">
                        <?php wp_nonce_field( 'carspace_assign_user_qe', 'carspace_assign_user_qe_nonce' ); ?>
                        <select name="carspace_assigned_user" class="carspace-qe-assigned-user">
                            <option value=""><?php esc_html_e( '&mdash; No User &mdash;', 'carspace-dashboard' ); ?></option>
                            <?php foreach ( $users as $user ) : ?>
                                <option value="<?php echo esc_attr( $user->ID ); ?>">
                                    <?php echo esc_html( $user->display_name ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </span>
                </label>
            </div>
        </fieldset>
        <?php
    }

    /* ------------------------------------------------------------------
     * AJAX: Update transport price for a product
     * ----------------------------------------------------------------*/

    public static function ajax_update_price() {
        check_ajax_referer( 'carspace_update_price', 'nonce' );

        if ( ! current_user_can( 'edit_products' ) ) {
            wp_send_json_error( __( 'Permission denied.', 'carspace-dashboard' ) );
        }

        $product_id = intval( $_POST['product_id'] ?? 0 );
        if ( ! $product_id ) {
            wp_send_json_error( __( 'Invalid product.', 'carspace-dashboard' ) );
        }

        $user_id = (int) get_post_meta( $product_id, 'assigned_user', true );
        if ( ! $user_id ) {
            wp_send_json_error( __( 'No user assigned to this product.', 'carspace-dashboard' ) );
        }

        Carspace_Transport_Price::auto_set_transport_price( $product_id, $user_id );

        $new_price = get_post_meta( $product_id, '_regular_price', true );

        wp_send_json_success( array(
            'price'     => $new_price !== '' ? (float) $new_price : null,
            'formatted' => $new_price !== '' ? '$' . number_format( (float) $new_price, 2 ) : '—',
            'message'   => $new_price !== ''
                ? sprintf( __( 'Price updated: $%s', 'carspace-dashboard' ), number_format( (float) $new_price, 2 ) )
                : __( 'No price found for this location/tier combination.', 'carspace-dashboard' ),
        ) );
    }

    /* ------------------------------------------------------------------
     * JS: Quick edit populate + Update Price button
     * ----------------------------------------------------------------*/

    public static function enqueue_quick_edit_js( $hook ) {
        if ( $hook !== 'edit.php' ) return;

        $screen = get_current_screen();
        if ( ! $screen || $screen->post_type !== 'product' ) return;

        $js = <<<'JS'
jQuery(function($) {
    // Quick Edit: populate assigned user dropdown
    var origInlineEdit = inlineEditPost.edit;
    inlineEditPost.edit = function(id) {
        origInlineEdit.apply(this, arguments);

        if (typeof id === 'object') {
            id = this.getId(id);
        }
        if (!id) return;

        var $row = $('#post-' + id);
        var userId = $row.find('.carspace-assigned-user-value').data('user-id') || '';
        var $editRow = $('#edit-' + id);
        $editRow.find('.carspace-qe-assigned-user').val(String(userId));
    };

    // Update Price button
    $(document).on('click', '.carspace-update-price', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var productId = $btn.data('product-id');

        $btn.prop('disabled', true).text('Updating…');

        $.post(ajaxurl, {
            action: 'carspace_update_transport_price',
            nonce: carspaceUpdatePrice.nonce,
            product_id: productId
        }, function(res) {
            if (res.success) {
                $btn.text('✓ Done').css('color', 'green');
                var $price = $btn.next('.carspace-current-price');
                if ($price.length) {
                    $price.text(res.data.formatted);
                } else {
                    $btn.after(' <span class="carspace-current-price" style="color:#666;font-size:11px;">' + res.data.formatted + '</span>');
                }
                setTimeout(function() {
                    $btn.text('$ Update Price').css('color', '').prop('disabled', false);
                }, 2000);
            } else {
                $btn.text('✗ ' + (res.data || 'Error')).css('color', 'red');
                setTimeout(function() {
                    $btn.text('$ Update Price').css('color', '').prop('disabled', false);
                }, 3000);
            }
        }).fail(function() {
            $btn.text('✗ Request failed').css('color', 'red');
            setTimeout(function() {
                $btn.text('$ Update Price').css('color', '').prop('disabled', false);
            }, 3000);
        });
    });
});
JS;
        wp_add_inline_script( 'inline-edit-post', $js );

        // Pass nonce to JS
        wp_localize_script( 'inline-edit-post', 'carspaceUpdatePrice', array(
            'nonce' => wp_create_nonce( 'carspace_update_price' ),
        ) );
    }
}
