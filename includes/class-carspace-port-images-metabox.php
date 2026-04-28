<?php
/**
 * Port Images Metabox
 *
 * Adds a "Port Images" panel to the WooCommerce product edit screen,
 * similar to the built-in product gallery. Works as both a WC product
 * data tab and a classic side metabox (fallback).
 *
 * @package Carspace_Dashboard
 */

defined( 'ABSPATH' ) || exit;

class Carspace_Port_Images_Metabox {

    public static function init() {
        // WooCommerce product data tab (appears in the main product data panel)
        add_filter( 'woocommerce_product_data_tabs', array( __CLASS__, 'add_product_data_tab' ) );
        add_action( 'woocommerce_product_data_panels', array( __CLASS__, 'render_product_data_panel' ) );

        // Classic side metabox (fallback / also visible)
        add_action( 'add_meta_boxes', array( __CLASS__, 'register_metabox' ) );

        // Save on product save
        add_action( 'woocommerce_process_product_meta', array( __CLASS__, 'save' ), 20 );
        add_action( 'save_post_product', array( __CLASS__, 'save_metabox' ), 20 );

        // Enqueue assets
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
    }

    /* ------------------------------------------------------------------
     * WooCommerce Product Data Tab
     * ----------------------------------------------------------------*/

    public static function add_product_data_tab( $tabs ) {
        $tabs['carspace_port_images'] = array(
            'label'    => __( 'Port Images', 'carspace-dashboard' ),
            'target'   => 'carspace_port_images_panel',
            'class'    => array(),
            'priority' => 80,
        );
        return $tabs;
    }

    public static function render_product_data_panel() {
        global $post;
        $image_ids = Carspace_Port_Images::get( $post->ID );
        ?>
        <div id="carspace_port_images_panel" class="panel woocommerce_options_panel">
            <div class="options_group" style="padding: 12px;">
                <?php self::render_gallery_ui( $image_ids, 'wc_tab' ); ?>
            </div>
        </div>
        <?php
    }

    /* ------------------------------------------------------------------
     * Classic Side Metabox
     * ----------------------------------------------------------------*/

    public static function register_metabox() {
        add_meta_box(
            'carspace_port_images',
            __( 'Port Images', 'carspace-dashboard' ),
            array( __CLASS__, 'render_metabox' ),
            'product',
            'side',
            'low'
        );
    }

    public static function render_metabox( $post ) {
        $image_ids = Carspace_Port_Images::get( $post->ID );
        self::render_gallery_ui( $image_ids, 'metabox' );
    }

    /* ------------------------------------------------------------------
     * Shared Gallery UI
     * ----------------------------------------------------------------*/

    private static function render_gallery_ui( $image_ids, $context = 'metabox' ) {
        wp_nonce_field( 'carspace_port_images', 'carspace_port_images_nonce' );
        $suffix = $context === 'wc_tab' ? '_tab' : '';
        ?>
        <div class="carspace-port-images-wrap" data-context="<?php echo esc_attr( $context ); ?>">
            <ul class="carspace-port-images-list">
                <?php foreach ( $image_ids as $id ) :
                    $thumb = wp_get_attachment_image_url( $id, 'thumbnail' );
                    if ( ! $thumb ) continue;
                ?>
                    <li class="carspace-port-image" data-attachment-id="<?php echo esc_attr( $id ); ?>">
                        <img src="<?php echo esc_url( $thumb ); ?>" />
                        <a href="#" class="carspace-port-image-remove" title="<?php esc_attr_e( 'Remove image', 'carspace-dashboard' ); ?>">&times;</a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <input type="hidden" class="carspace-port-image-ids" name="carspace_port_image_ids<?php echo $suffix; ?>" value="<?php echo esc_attr( implode( ',', $image_ids ) ); ?>" />
            <p>
                <a href="#" class="carspace-port-images-add button"><?php esc_html_e( 'Add port images', 'carspace-dashboard' ); ?></a>
            </p>
        </div>
        <?php
    }

    /* ------------------------------------------------------------------
     * Save handlers
     * ----------------------------------------------------------------*/

    /**
     * Save from WooCommerce product data tab.
     */
    public static function save( $post_id ) {
        if ( ! isset( $_POST['carspace_port_images_nonce'] ) ) return;
        if ( ! wp_verify_nonce( $_POST['carspace_port_images_nonce'], 'carspace_port_images' ) ) return;

        // Prefer the WC tab input, fallback to metabox input
        $raw = '';
        if ( isset( $_POST['carspace_port_image_ids_tab'] ) ) {
            $raw = sanitize_text_field( $_POST['carspace_port_image_ids_tab'] );
        } elseif ( isset( $_POST['carspace_port_image_ids'] ) ) {
            $raw = sanitize_text_field( $_POST['carspace_port_image_ids'] );
        }

        $ids = array_filter( array_map( 'intval', explode( ',', $raw ) ) );
        Carspace_Port_Images::save( $post_id, $ids );
    }

    /**
     * Save from classic metabox (save_post_product hook).
     */
    public static function save_metabox( $post_id ) {
        // Only run if WC hook didn't already handle it
        if ( did_action( 'woocommerce_process_product_meta' ) ) return;
        if ( ! isset( $_POST['carspace_port_images_nonce'] ) ) return;
        if ( ! wp_verify_nonce( $_POST['carspace_port_images_nonce'], 'carspace_port_images' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        $raw = isset( $_POST['carspace_port_image_ids'] ) ? sanitize_text_field( $_POST['carspace_port_image_ids'] ) : '';
        $ids = array_filter( array_map( 'intval', explode( ',', $raw ) ) );
        Carspace_Port_Images::save( $post_id, $ids );
    }

    /* ------------------------------------------------------------------
     * Assets
     * ----------------------------------------------------------------*/

    public static function enqueue_assets( $hook ) {
        if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) return;

        $screen = get_current_screen();
        if ( ! $screen || $screen->post_type !== 'product' ) return;

        wp_enqueue_media();
        wp_enqueue_script( 'jquery-ui-sortable' );

        // CSS
        $css = '
            .carspace-port-images-list {
                display: flex;
                flex-wrap: wrap;
                gap: 6px;
                list-style: none;
                margin: 0 0 10px;
                padding: 0;
            }
            .carspace-port-image {
                position: relative;
                width: 72px;
                height: 72px;
                border: 1px solid #ddd;
                border-radius: 4px;
                overflow: hidden;
                cursor: move;
            }
            .carspace-port-image img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                display: block;
            }
            .carspace-port-image-remove {
                position: absolute;
                top: 0;
                right: 0;
                background: rgba(0,0,0,.6);
                color: #fff;
                width: 20px;
                height: 20px;
                line-height: 18px;
                text-align: center;
                font-size: 14px;
                text-decoration: none;
                border-radius: 0 0 0 4px;
                display: none;
            }
            .carspace-port-image:hover .carspace-port-image-remove {
                display: block;
            }
            /* WC tab icon */
            #woocommerce-product-data ul.wc-tabs li.carspace_port_images_options a::before {
                content: "\f128";
                font-family: dashicons;
            }
        ';
        wp_add_inline_style( 'woocommerce_admin_styles', $css );

        // JS
        $js = <<<'JS'
jQuery(function($) {
    // Init each gallery wrapper (WC tab + side metabox)
    $('.carspace-port-images-wrap').each(function() {
        var $wrap  = $(this);
        var $list  = $wrap.find('.carspace-port-images-list');
        var $input = $wrap.find('.carspace-port-image-ids');

        function syncInput() {
            var ids = [];
            $list.find('.carspace-port-image').each(function() {
                ids.push($(this).data('attachment-id'));
            });
            $input.val(ids.join(','));
            // Sync the other instance too
            $('.carspace-port-image-ids').val(ids.join(','));
        }

        $list.sortable({
            items: '.carspace-port-image',
            cursor: 'move',
            tolerance: 'pointer',
            update: syncInput
        });

        $wrap.on('click', '.carspace-port-images-add', function(e) {
            e.preventDefault();
            var frame = wp.media({
                title: 'Port Images',
                button: { text: 'Add to port images' },
                library: { type: 'image' },
                multiple: true
            });
            frame.on('select', function() {
                frame.state().get('selection').each(function(att) {
                    var thumb = att.attributes.sizes && att.attributes.sizes.thumbnail
                        ? att.attributes.sizes.thumbnail.url
                        : att.attributes.url;
                    $list.append(
                        '<li class="carspace-port-image" data-attachment-id="' + att.id + '">' +
                            '<img src="' + thumb + '" />' +
                            '<a href="#" class="carspace-port-image-remove" title="Remove">&times;</a>' +
                        '</li>'
                    );
                });
                syncInput();
            });
            frame.open();
        });

        $wrap.on('click', '.carspace-port-image-remove', function(e) {
            e.preventDefault();
            $(this).closest('.carspace-port-image').remove();
            syncInput();
        });
    });
});
JS;

        wp_add_inline_script( 'jquery-ui-sortable', $js );
    }
}
