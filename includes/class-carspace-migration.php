<?php
/**
 * WP-CLI Migration Command
 *
 * Migrates data from ACF/CPT storage to custom tables.
 * Usage: wp carspace migrate [--dry-run]
 *
 * @package Carspace_Dashboard
 * @since 5.0.0
 */

defined('ABSPATH') || exit;

if (!defined('WP_CLI') || !WP_CLI) {
    return;
}

/**
 * Carspace data migration commands.
 */
class Carspace_Migration_Command {

    /**
     * Migrate ACF/CPT data to custom tables.
     *
     * ## OPTIONS
     *
     * [--dry-run]
     * : Preview changes without writing to the database.
     *
     * ## EXAMPLES
     *
     *     wp carspace migrate
     *     wp carspace migrate --dry-run
     *
     * @param array $args       Positional arguments.
     * @param array $assoc_args Associative arguments.
     */
    public function migrate($args, $assoc_args) {
        $dry_run = isset($assoc_args['dry-run']);

        if ($dry_run) {
            WP_CLI::log('--- DRY RUN MODE (no data will be written) ---');
        }

        $counts = array(
            'invoices_migrated'      => 0,
            'invoices_skipped'       => 0,
            'invoice_items_migrated' => 0,
            'notifications_migrated' => 0,
            'notifications_skipped'  => 0,
            'port_images_migrated'   => 0,
            'port_images_skipped'    => 0,
            'product_meta_migrated'  => 0,
            'user_meta_migrated'     => 0,
        );

        $this->migrate_invoices($dry_run, $counts);
        $this->migrate_notifications($dry_run, $counts);
        $this->migrate_port_images($dry_run, $counts);
        $this->migrate_product_meta($dry_run, $counts);
        $this->migrate_user_meta($dry_run, $counts);

        WP_CLI::log('');
        WP_CLI::log('=== Migration Summary ===');
        foreach ($counts as $key => $val) {
            WP_CLI::log(sprintf('  %-30s %d', str_replace('_', ' ', $key) . ':', $val));
        }

        if ($dry_run) {
            WP_CLI::success('Dry run complete. No data was written.');
        } else {
            WP_CLI::success('Migration complete.');
        }
    }

    /**
     * Step 1: Migrate invoice CPT posts to carspace_invoices + carspace_invoice_items.
     */
    private function migrate_invoices($dry_run, &$counts) {
        global $wpdb;

        $post_ids = $wpdb->get_col(
            "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'invoice' AND post_status IN ('publish','draft','private') ORDER BY ID ASC"
        );

        if (empty($post_ids)) {
            WP_CLI::log('No invoice posts found.');
            return;
        }

        WP_CLI::log(sprintf('Found %d invoice posts.', count($post_ids)));
        $progress = \WP_CLI\Utils\make_progress_bar('Migrating invoices', count($post_ids));

        foreach ($post_ids as $post_id) {
            $progress->tick();

            // Idempotent: skip if already migrated
            if (Carspace_Invoice::exists($post_id)) {
                $counts['invoices_skipped']++;
                continue;
            }

            // Read ACF repeater items (numbered postmeta format)
            $items = array();
            $i = 0;
            while (true) {
                $vin = get_post_meta($post_id, 'product_info_for_pay_' . $i . '_vin', true);
                if ($vin === '' && $i > 0) {
                    // Also check if the meta key exists at all
                    $exists = metadata_exists('post', $post_id, 'product_info_for_pay_' . $i . '_vin');
                    if (!$exists) {
                        break;
                    }
                }
                if ($vin !== false || $i === 0) {
                    $amount    = get_post_meta($post_id, 'product_info_for_pay_' . $i . '__amount_', true);
                    $sale_date = get_post_meta($post_id, 'product_info_for_pay_' . $i . '_sale_date', true);
                    $make      = get_post_meta($post_id, 'product_info_for_pay_' . $i . '_make', true);
                    $model     = get_post_meta($post_id, 'product_info_for_pay_' . $i . '_model', true);
                    $year      = get_post_meta($post_id, 'product_info_for_pay_' . $i . '_year', true);

                    // Only add if at least VIN or amount exists
                    if ($vin !== '' || $amount !== '') {
                        // Convert date format if needed (DD/MM/YYYY to YYYY-MM-DD)
                        $formatted_date = null;
                        if (!empty($sale_date)) {
                            $formatted_date = $this->convert_date($sale_date);
                        }

                        $items[] = array(
                            'vin'       => $vin ?: '',
                            'amount'    => floatval($amount),
                            'sale_date' => $formatted_date,
                            'make'      => $make ?: '',
                            'model'     => $model ?: '',
                            'year'      => !empty($year) ? intval($year) : null,
                        );
                        $counts['invoice_items_migrated']++;
                    }
                }
                $i++;
                // Safety limit
                if ($i > 200) {
                    break;
                }
            }

            // If no items found via numbered keys, try serialized array
            if (empty($items)) {
                $serialized = get_post_meta($post_id, 'product_info_for_pay', true);
                if (is_array($serialized)) {
                    foreach ($serialized as $row) {
                        $formatted_date = null;
                        if (!empty($row['sale_date'])) {
                            $formatted_date = $this->convert_date($row['sale_date']);
                        }
                        $items[] = array(
                            'vin'       => isset($row['vin']) ? $row['vin'] : '',
                            'amount'    => isset($row['_amount_']) ? floatval($row['_amount_']) : 0,
                            'sale_date' => $formatted_date,
                            'make'      => isset($row['make']) ? $row['make'] : '',
                            'model'     => isset($row['model']) ? $row['model'] : '',
                            'year'      => !empty($row['year']) ? intval($row['year']) : null,
                        );
                        $counts['invoice_items_migrated']++;
                    }
                }
            }

            // Read customer fields
            $customer_type        = get_post_meta($post_id, 'customer_details_customer_type_choose', true);
            $customer_name        = get_post_meta($post_id, 'customer_details_customer_name', true);
            $customer_company     = get_post_meta($post_id, 'customer_details_customer_company_name', true);
            $customer_personal_id = get_post_meta($post_id, 'customer_details_customer_id_or_other_doc', true);
            $company_ident        = get_post_meta($post_id, 'customer_details_company_ident_number', true);

            // Read invoice fields
            $dealer_fee    = get_post_meta($post_id, 'dealer_fee_price', true);
            $dealer_note   = get_post_meta($post_id, 'dealer_fee_note_save', true);
            $commission    = get_post_meta($post_id, 'extra_comission_for_dealer', true);
            $subtotal      = get_post_meta($post_id, 'subtotal_with_dealer_fee', true);
            $invoice_type  = get_post_meta($post_id, 'invoice_type_for_what', true);
            $owner_user_id = get_post_meta($post_id, 'invoice_owner', true);

            // Receipt image
            $receipt_image_id  = get_post_meta($post_id, '_receipt_image_id', true);
            $receipt_image_url = get_post_meta($post_id, '_receipt_image', true);

            // Status from category taxonomy
            $status = 'unpaid';
            $terms  = wp_get_post_terms($post_id, 'category', array('fields' => 'slugs'));
            if (!is_wp_error($terms) && !empty($terms)) {
                foreach ($terms as $slug) {
                    if (in_array($slug, array('paid', 'unpaid', 'partly-paid'), true)) {
                        $status = $slug;
                        break;
                    }
                }
            }

            $data = array(
                'post_id'               => $post_id,
                'invoice_type'          => $invoice_type ?: '',
                'status'                => $status,
                'customer_type'         => $customer_type ?: '',
                'customer_name'         => $customer_name ?: '',
                'customer_company_name' => $customer_company ?: '',
                'customer_personal_id'  => $customer_personal_id ?: '',
                'company_ident_number'  => $company_ident ?: '',
                'dealer_fee'            => floatval($dealer_fee),
                'dealer_fee_note'       => $dealer_note ?: '',
                'commission'            => floatval($commission),
                'subtotal'              => floatval($subtotal),
                'receipt_image_id'      => !empty($receipt_image_id) ? intval($receipt_image_id) : null,
                'receipt_image_url'     => $receipt_image_url ?: '',
                'owner_user_id'         => !empty($owner_user_id) ? intval($owner_user_id) : null,
            );

            if (!$dry_run) {
                Carspace_Invoice::create($data, $items);
            }

            $counts['invoices_migrated']++;
        }

        $progress->finish();
    }

    /**
     * Step 2: Migrate notification CPT posts to carspace_notifications table.
     */
    private function migrate_notifications($dry_run, &$counts) {
        global $wpdb;

        $post_ids = $wpdb->get_col(
            "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'notification' AND post_status IN ('publish','draft','private') ORDER BY ID ASC"
        );

        if (empty($post_ids)) {
            WP_CLI::log('No notification posts found.');
            return;
        }

        WP_CLI::log(sprintf('Found %d notification posts.', count($post_ids)));
        $progress = \WP_CLI\Utils\make_progress_bar('Migrating notifications', count($post_ids));

        $table = $wpdb->prefix . 'carspace_notifications';

        foreach ($post_ids as $post_id) {
            $progress->tick();

            $post = get_post($post_id);
            if (!$post) {
                continue;
            }

            // Get all recipient user IDs (multiple meta rows)
            $recipients = get_post_meta($post_id, '_notification_recipient');
            if (empty($recipients)) {
                // Try the old ACF field
                $old = get_post_meta($post_id, '_recipient_user', true);
                if (is_array($old)) {
                    $recipients = $old;
                } elseif (is_numeric($old)) {
                    $recipients = array($old);
                } else {
                    $recipients = array();
                }
            }

            if (empty($recipients)) {
                $counts['notifications_skipped']++;
                continue;
            }

            // Read notification metadata
            $status        = get_post_meta($post_id, 'status', true) ?: 'unread';
            $type          = get_post_meta($post_id, 'type', true) ?: 'info';
            $link          = get_post_meta($post_id, 'link', true) ?: '';
            $visible_until = get_post_meta($post_id, 'visible_until', true) ?: null;
            $message       = $post->post_content;
            $title         = $post->post_title;
            $created       = $post->post_date;

            foreach ($recipients as $uid) {
                $uid = intval($uid);
                if (!$uid) {
                    continue;
                }

                // Idempotent: check if row already exists for this user + title + created_at combo
                $exists = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(1) FROM {$table} WHERE user_id = %d AND title = %s AND created_at = %s",
                    $uid, $title, $created
                ));

                if ($exists) {
                    $counts['notifications_skipped']++;
                    continue;
                }

                if (!$dry_run) {
                    $wpdb->insert(
                        $table,
                        array(
                            'user_id'       => $uid,
                            'title'         => $title,
                            'message'       => $message,
                            'type'          => $type,
                            'status'        => $status,
                            'link'          => $link,
                            'visible_until' => $visible_until,
                            'created_at'    => $created,
                        ),
                        array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
                    );
                }

                $counts['notifications_migrated']++;
            }
        }

        $progress->finish();
    }

    /**
     * Step 3: Migrate port_images postmeta to carspace_port_images table.
     */
    private function migrate_port_images($dry_run, &$counts) {
        global $wpdb;

        $rows = $wpdb->get_results(
            "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = 'port_images' AND meta_value != '' ORDER BY post_id ASC"
        );

        if (empty($rows)) {
            WP_CLI::log('No port_images meta found.');
            return;
        }

        WP_CLI::log(sprintf('Found %d products with port_images meta.', count($rows)));
        $progress = \WP_CLI\Utils\make_progress_bar('Migrating port images', count($rows));

        foreach ($rows as $row) {
            $progress->tick();

            $product_id = intval($row->post_id);

            // Idempotent: skip if already has images in custom table
            if (Carspace_Port_Images::has_images($product_id)) {
                $counts['port_images_skipped']++;
                continue;
            }

            $attachment_ids = maybe_unserialize($row->meta_value);

            if (!is_array($attachment_ids)) {
                // Could be a single ID
                if (is_numeric($attachment_ids)) {
                    $attachment_ids = array(intval($attachment_ids));
                } else {
                    $counts['port_images_skipped']++;
                    continue;
                }
            }

            // Filter to valid integer IDs
            $attachment_ids = array_filter(array_map('intval', $attachment_ids));

            if (empty($attachment_ids)) {
                $counts['port_images_skipped']++;
                continue;
            }

            if (!$dry_run) {
                Carspace_Port_Images::save($product_id, $attachment_ids);
            }

            $counts['port_images_migrated']++;
        }

        $progress->finish();
    }

    /**
     * Step 4: Migrate product meta keys to new naming convention.
     */
    private function migrate_product_meta($dry_run, &$counts) {
        global $wpdb;

        $mappings = array(
            'purchase_date'           => '_purchase_date',
            'mimgebi_piri'            => '_receiver_name',
            'mimgebis_piradi_nomeri'  => '_receiver_personal_id',
        );

        $product_ids = $wpdb->get_col(
            "SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key IN ('purchase_date','mimgebi_piri','mimgebis_piradi_nomeri') ORDER BY post_id ASC"
        );

        if (empty($product_ids)) {
            WP_CLI::log('No product meta to migrate.');
            return;
        }

        WP_CLI::log(sprintf('Found %d products with meta to migrate.', count($product_ids)));
        $progress = \WP_CLI\Utils\make_progress_bar('Migrating product meta', count($product_ids));

        foreach ($product_ids as $product_id) {
            $progress->tick();

            foreach ($mappings as $old_key => $new_key) {
                $old_value = get_post_meta($product_id, $old_key, true);
                if ($old_value === '' || $old_value === false) {
                    continue;
                }

                // Only write if new key doesn't exist yet
                $existing = metadata_exists('post', $product_id, $new_key);
                if ($existing) {
                    continue;
                }

                $value = $old_value;

                // Convert purchase_date from DD/MM/YYYY to YYYY-MM-DD
                if ($old_key === 'purchase_date') {
                    $value = $this->convert_date($value);
                    if (!$value) {
                        continue;
                    }
                }

                if (!$dry_run) {
                    update_post_meta($product_id, $new_key, $value);
                }

                $counts['product_meta_migrated']++;
            }
        }

        $progress->finish();
    }

    /**
     * Step 5: Migrate user commission type meta.
     */
    private function migrate_user_meta($dry_run, &$counts) {
        global $wpdb;

        $rows = $wpdb->get_results(
            "SELECT user_id, meta_value FROM {$wpdb->usermeta} WHERE meta_key = 'user_commission_type' AND meta_value != '' ORDER BY user_id ASC"
        );

        if (empty($rows)) {
            WP_CLI::log('No user commission meta to migrate.');
            return;
        }

        WP_CLI::log(sprintf('Found %d users with commission_type meta.', count($rows)));
        $progress = \WP_CLI\Utils\make_progress_bar('Migrating user meta', count($rows));

        foreach ($rows as $row) {
            $progress->tick();

            $user_id = intval($row->user_id);

            // Only write if _commission_type doesn't exist yet
            $existing = get_user_meta($user_id, '_commission_type', true);
            if ($existing !== '' && $existing !== false) {
                continue;
            }

            if (!$dry_run) {
                update_user_meta($user_id, '_commission_type', $row->meta_value);
            }

            $counts['user_meta_migrated']++;
        }

        $progress->finish();
    }

    /**
     * Convert DD/MM/YYYY date to YYYY-MM-DD.
     * Also handles YYYY-MM-DD passthrough and other common formats.
     *
     * @param string $date_str
     * @return string|null YYYY-MM-DD format or null on failure.
     */
    private function convert_date($date_str) {
        if (empty($date_str)) {
            return null;
        }

        $date_str = trim($date_str);

        // Already in YYYY-MM-DD format
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_str)) {
            return $date_str;
        }

        // DD/MM/YYYY
        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $date_str, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }

        // DD.MM.YYYY
        if (preg_match('#^(\d{1,2})\.(\d{1,2})\.(\d{4})$#', $date_str, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }

        // Try PHP strtotime as fallback
        $ts = strtotime($date_str);
        if ($ts) {
            return date('Y-m-d', $ts);
        }

        return null;
    }
}

WP_CLI::add_command('carspace', 'Carspace_Migration_Command');
