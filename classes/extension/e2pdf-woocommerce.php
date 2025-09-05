<?php

/**
 * E2Pdf WooCommerce Extension
 * @copyright  Copyright 2017 https://e2pdf.com
 * @license    GPLv3
 * @version    1
 * @link       https://e2pdf.com
 * @since      1.09.07
 */
if (!defined('ABSPATH')) {
    die('Access denied.');
}

class Extension_E2pdf_Woocommerce extends Model_E2pdf_Model {

    private $options;
    private $info = array(
        'key' => 'woocommerce',
        'title' => 'WooCommerce',
    );

    /**
     * Get info about extension
     * @param string $key - Key to get assigned extension info value
     * @return array|string - Extension Key and Title or Assigned extension info value
     */
    public function info($key = false) {
        if ($key && isset($this->info[$key])) {
            return $this->info[$key];
        } else {
            return array(
                $this->info['key'] => $this->info['title'],
            );
        }
    }

    /**
     * Check if needed plugin active
     * @return bool - Activated/Not Activated plugin
     */
    public function active() {
        if (defined('E2PDF_WOOCOMMERCE_EXTENSION') || $this->helper->load('extension')->is_plugin_active('woocommerce/woocommerce.php')) {
            return true;
        }
        return false;
    }

    /**
     * Set option
     * @param string $key - Key of option
     * @param string $value - Value of option
     * @return bool - Status of setting option
     */
    public function set($key, $value) {
        if (!isset($this->options)) {
            $this->options = new stdClass();
        }
        $this->options->$key = $value;
        switch ($key) {
            case 'dataset':
                $this->set('cached_post', false);
                if ($this->get('dataset')) {
                    $this->set('cached_post', get_post($this->get('dataset')));
                }
                break;
            default:
                break;
        }
    }

    /**
     * Get option by key
     * @param string $key - Key to get assigned option value
     * @return mixed
     */
    public function get($key) {
        if (isset($this->options->$key)) {
            $value = $this->options->$key;
        } else {
            switch ($key) {
                case 'args':
                    $value = array();
                    break;
                default:
                    $value = false;
                    break;
            }
        }
        return $value;
    }

    /**
     * Get items to work with
     * @return array() - List of available items
     */
    public function items() {
        $items = array();
        $forms = array(
            'product',
            'product_variation',
            'shop_order',
            'cart',
        );
        foreach ($forms as $form) {
            $items[] = $this->item($form);
        }
        return $items;
    }

    /**
     * Get entries for export
     * @param string $item_id - Item ID
     * @param string $name - Entries names
     * @return array() - Entries list
     */
    public function datasets($item_id = false, $name = false) {
        $datasets = array();
        $entries = array();
        if ($item_id) {
            if ($item_id == 'cart') {
                if (function_exists('wc_get_page_id') && wc_get_page_id('cart')) {
                    $entries = get_posts(
                            array(
                                'post_type' => 'any',
                                'post__in' => array(wc_get_page_id('cart')),
                                'numberposts' => -1,
                                'post_status' => 'any',
                            )
                    );
                }
            } elseif ($item_id == 'shop_order' && get_option('woocommerce_custom_orders_table_enabled') === 'yes' && get_option('woocommerce_custom_orders_table_data_sync_enabled') !== 'yes') {
                $entries = get_posts(
                        array(
                            'post_type' => 'shop_order_placehold',
                            'numberposts' => -1,
                            'post_status' => 'any',
                        )
                );
            } else {
                $entries = get_posts(
                        array(
                            'post_type' => $item_id,
                            'numberposts' => -1,
                            'post_status' => 'any',
                        )
                );
            }
            if ($entries) {
                $this->set('item', $item_id);
                foreach ($entries as $key => $entry) {
                    $this->set('dataset', $entry->ID);
                    $entry_title = $this->render($name);
                    if (!$entry_title) {
                        $entry_title = isset($entry->post_title) && $entry->post_title ? $entry->post_title : $entry->ID;
                    }
                    $datasets[] = array(
                        'key' => $entry->ID,
                        'value' => $entry_title,
                    );
                }
            }
        }
        return $datasets;
    }

    /**
     * Get item
     * @param string $item_id - Item ID
     * @return object - Item
     */
    public function item($item_id = false) {
        if (!$item_id && $this->get('item')) {
            $item_id = $this->get('item');
        }
        $item = new stdClass();
        $form = get_post_type_object($item_id);
        if ($form) {
            $item->id = $item_id;
            $item->name = $form->label ? $form->label : $item_id;
            $item->url = $this->helper->get_url(array('post_type' => $item_id), 'edit.php?');
        } elseif ($item_id == 'cart' && function_exists('wc_get_page_id')) {
            $item->id = $item_id;
            $item->name = __('Cart / Checkout', 'e2pdf');
            $item->url = $this->helper->get_url(
                    array(
                        'post' => wc_get_page_id('cart'),
                        'action' => 'edit',
                    ), 'post.php?'
            );
        } else {
            $item->id = '';
            $item->name = '';
            $item->url = 'javascript:void(0);';
        }
        return $item;
    }

    /**
     * Get Dataset Actions
     * @param int $dataset_id - Dataset ID
     * @return object
     */
    public function get_dataset_actions($dataset_id = false) {
        $dataset_id = (int) $dataset_id;
        if (!$dataset_id) {
            return false;
        }
        $actions = new stdClass();
        $actions->view = $this->helper->get_url(
                array(
                    'post' => $dataset_id,
                    'action' => 'edit',
                ), 'post.php?'
        );
        $actions->delete = false;
        return $actions;
    }

    /**
     * Get Template Actions
     * @param int $template - Template ID
     * @return object
     */
    public function get_template_actions($template = false) {
        $template = (int) $template;
        if (!$template) {
            return;
        }
        $actions = new stdClass();
        $actions->delete = false;
        return $actions;
    }

    public function load_actions() {
        $email_actions = apply_filters(
                'woocommerce_email_actions',
                array(
                    'woocommerce_low_stock',
                    'woocommerce_no_stock',
                    'woocommerce_product_on_backorder',
                    'woocommerce_order_status_pending_to_processing',
                    'woocommerce_order_status_pending_to_completed',
                    'woocommerce_order_status_processing_to_cancelled',
                    'woocommerce_order_status_pending_to_failed',
                    'woocommerce_order_status_pending_to_on-hold',
                    'woocommerce_order_status_failed_to_processing',
                    'woocommerce_order_status_failed_to_completed',
                    'woocommerce_order_status_failed_to_on-hold',
                    'woocommerce_order_status_cancelled_to_processing',
                    'woocommerce_order_status_cancelled_to_completed',
                    'woocommerce_order_status_cancelled_to_on-hold',
                    'woocommerce_order_status_on-hold_to_processing',
                    'woocommerce_order_status_on-hold_to_cancelled',
                    'woocommerce_order_status_on-hold_to_failed',
                    'woocommerce_order_status_completed',
                    'woocommerce_order_fully_refunded',
                    'woocommerce_order_partially_refunded',
                    'woocommerce_new_customer_note',
                    'woocommerce_created_customer',
                    'ywgc_email_send_gift_card',
                )
        );

        foreach ($email_actions as $email_action) {
            add_action($email_action . '_notification', array($this, 'action_after_email'), 99, 2);
        }
        add_action('woocommerce_after_resend_order_email', array($this, 'action_after_email'), 99, 2);

        if (get_option('e2pdf_wc_cart_template_id', '0')) {
            add_action('woocommerce_proceed_to_checkout', array($this, 'action_e2pdf_wc_cart_template_id'), (int) get_option('e2pdf_wc_cart_template_id_priority', '10'));
        }

        if (get_option('e2pdf_wc_checkout_template_id', '0') && get_option('e2pdf_wc_checkout_template_id_hook', 'woocommerce_review_order_before_submit') &&
                in_array(
                        get_option('e2pdf_wc_checkout_template_id_hook', 'woocommerce_review_order_before_submit'),
                        array(
                            'woocommerce_review_order_before_submit',
                            'woocommerce_review_order_after_submit',
                        )
                )) {
            add_action(get_option('e2pdf_wc_checkout_template_id_hook', 'woocommerce_review_order_before_submit'), array($this, 'action_e2pdf_wc_checkout_template_id'), (int) get_option('e2pdf_wc_checkout_template_id_priority', '10'));
        }

        if (get_option('e2pdf_wc_order_details_template_id', '0') && get_option('e2pdf_wc_order_details_template_id_hook', 'woocommerce_order_details_before_order_table') &&
                in_array(
                        get_option('e2pdf_wc_order_details_template_id_hook', 'woocommerce_order_details_before_order_table'),
                        array(
                            'woocommerce_order_details_before_order_table',
                            'woocommerce_order_details_before_order_table_items',
                            'woocommerce_order_details_after_order_table_items',
                            'woocommerce_order_details_after_order_table',
                            'woocommerce_after_order_details',
                        )
                )) {
            add_action(get_option('e2pdf_wc_order_details_template_id_hook', 'woocommerce_order_details_before_order_table'), array($this, 'action_e2pdf_wc_order_details_template_id'), (int) get_option('e2pdf_wc_order_details_template_id_priority', '10'));
        }

        if (get_option('e2pdf_wc_admin_order_actions_template_id', '0') && get_option('e2pdf_wc_admin_order_actions_template_id_hook', 'woocommerce_admin_order_actions_end') &&
                in_array(
                        get_option('e2pdf_wc_admin_order_actions_template_id_hook', 'woocommerce_admin_order_actions_end'),
                        array(
                            'woocommerce_admin_order_actions_start',
                            'woocommerce_admin_order_actions_end',
                        )
                )) {
            add_action(get_option('e2pdf_wc_admin_order_actions_template_id_hook', 'woocommerce_admin_order_actions_end'), array($this, 'action_e2pdf_wc_admin_order_actions_template_id'), (int) get_option('e2pdf_wc_admin_order_actions_template_id_priority', '10'));
        }

        if (get_option('e2pdf_wc_admin_order_details_template_id', '0')) {
            add_action('add_meta_boxes', array($this, 'action_add_meta_boxes'));
        }

        /* Compatibility fix with Enforcing Rules enabled */
        add_action('woocommerce_settings_products', array($this, 'action_enforcing_rules_enabled_check'), -10);
        add_action('woocommerce_admin_process_product_object', array($this, 'action_enforcing_rules_enabled_check'));

        /* https://wordpress.org/plugins/elementor/ */
        add_action('elementor/widget/before_render_content', array($this, 'action_elementor_widget_before_render_content'));
        add_action('elementor/frontend/widget/before_render', array($this, 'action_elementor_widget_before_render_content'), 5);

        /* https://wordpress.org/plugins/happy-elementor-addons/ compatibility fix */
        add_action('elementor/frontend/before_render', array($this, 'action_elementor_widget_before_render_content'), 0);

        /* Temporary solution for File Downloads in WC Admin Order page */
        add_action('add_meta_boxes_woocommerce_page_wc-orders', array($this, 'action_add_meta_boxes_woocommerce_page_wc_orders'));
        add_action('add_meta_boxes_shop_order', array($this, 'action_add_meta_boxes_woocommerce_page_wc_orders'));

        /* Hooks */
        add_action('add_meta_boxes', array($this, 'hook_woocommerce_order_edit'));
        add_action('woocommerce_admin_order_actions_end', array($this, 'hook_woocommerce_order_row_actions'));
    }

    public function load_filters() {
        add_filter('woocommerce_product_file_download_path', array($this, 'filter_woocommerce_product_file_download_path'), 10, 3);
        add_filter('woocommerce_short_description', array($this, 'filter_content_custom'));
        add_filter('the_content', array($this, 'filter_the_content'), 10, 2);
        add_filter('woocommerce_email_attachments', array($this, 'filter_woocommerce_email_attachments'), 10, 4);
        add_filter('woocommerce_mail_content', array($this, 'filter_woocommerce_mail_content'), 99);
        add_filter('woocommerce_display_product_attributes', array($this, 'filter_woocommerce_display_product_attributes'), 10, 2);
        add_filter('e2pdf_model_shortcode_wc_product_get_attribute_value', array($this, 'filter_e2pdf_model_shortcode_wc_product_get_attribute_value'), 10, 2);
        add_filter('e2pdf_model_options_get_options_options', array($this, 'filter_e2pdf_model_options_get_options_options'));
        add_filter('woocommerce_customer_available_downloads', array($this, 'filter_download_urls'));
        add_filter('woocommerce_order_get_downloadable_items', array($this, 'filter_download_urls'));

        if (get_option('e2pdf_wc_my_orders_actions_template_id', '0')) {
            add_filter('woocommerce_my_account_my_orders_actions', array($this, 'filter_woocommerce_my_account_my_orders_actions'), (int) get_option('e2pdf_wc_checkout_template_id_priority', '10'), 2);
        }

        /* Flatsome theme global tab content */
        add_filter('theme_mod_tab_content', array($this, 'filter_content_custom'));

        /* Variable Product Short Description Dynamic ID */
        add_filter('woocommerce_available_variation', array($this, 'filter_woocommerce_available_variation'), 10, 3);

        /* PDF download link with E2Pdf shortcodes */
        add_filter('e2pdf_model_shortcode_e2pdf_wc_product_description', array($this, 'filter_the_content'), 10, 2);

        /* Compatibility fix with Enforcing Rules enabled */
        add_filter('woocommerce_product_downloads_approved_directory_validation_for_shortcodes', array($this, 'filter_woocommerce_product_downloads_approved_directory_validation_for_shortcodes'), 99);

        /**
         * Cornerstone Builder
         * https://theme.co/cornerstone/
         */
        add_filter('cs_element_pre_render', array($this, 'filter_cs_element_pre_render'));

        /* Hooks */
        add_filter('manage_edit-shop_order_columns', array($this, 'hook_woocommerce_order_row_column'));
    }

    /**
     * Delete attachments that were sent by email
     */
    public function action_after_email($order_id, $order = false) {
        $files = $this->helper->get('woocommerce_attachments');
        if (is_array($files) && !empty($files)) {
            foreach ($files as $key => $file) {
                $this->helper->delete_dir(dirname($file) . '/');
            }
            $this->helper->deset('woocommerce_attachments');
        }
    }

    public function action_e2pdf_wc_cart_template_id() {
        if (!is_cart() || WC()->cart->is_empty()) {
            return;
        }
        echo apply_filters(
                'e2pdf_wc_action_e2pdf_wc_cart_template_id',
                do_shortcode('[e2pdf-download id="' . get_option('e2pdf_wc_cart_template_id', '0') . '" dataset="' . wc_get_page_id('cart') . '" class="button e2pdf-wc-download-button e2pdf-wc-download-cart-button"]')
        );
    }

    public function action_e2pdf_wc_checkout_template_id() {
        if (!is_checkout() || WC()->cart->is_empty()) {
            return;
        }
        echo apply_filters(
                'e2pdf_wc_action_e2pdf_wc_checkout_template_id',
                do_shortcode('[e2pdf-download id="' . get_option('e2pdf_wc_checkout_template_id', '0') . '" dataset="' . wc_get_page_id('cart') . '" class="button e2pdf-wc-download-button e2pdf-wc-download-checkout-button"]')
        );
    }

    public function action_e2pdf_wc_order_details_template_id($order) {
        $statuses = get_option('e2pdf_wc_order_details_template_id_status', array('any'));
        if (is_array($statuses) && (in_array($order->get_status(), $statuses) || in_array('wc-' . $order->get_status(), $statuses) || in_array('any', $statuses))) {
            echo apply_filters(
                    'e2pdf_wc_action_e2pdf_wc_order_details_template_id',
                    do_shortcode('[e2pdf-download id="' . get_option('e2pdf_wc_order_details_template_id', '0') . '" dataset="' . $order->get_id() . '" class="button e2pdf-wc-download-button e2pdf-wc-download-order-details-button"]'),
                    $order
            );
        }
    }

    public function action_e2pdf_wc_admin_order_actions_template_id($order) {
        $statuses = get_option('e2pdf_wc_admin_order_actions_template_id_status', array('any'));
        if (is_array($statuses) && (in_array($order->get_status(), $statuses) || in_array('wc-' . $order->get_status(), $statuses) || in_array('any', $statuses))) {
            echo apply_filters(
                    'e2pdf_wc_action_e2pdf_wc_admin_order_actions_template_id',
                    do_shortcode('[e2pdf-download id="' . get_option('e2pdf_wc_admin_order_actions_template_id', '0') . '" dataset="' . $order->get_id() . '" user_id="' . $order->get_user_id() . '" class="button e2pdf-wc-download-button e2pdf-wc-download-order-details-button"]'),
                    $order
            );
        }
    }

    public function action_e2pdf_wc_admin_order_details_template_id() {
        global $post;
        if (function_exists('wc_get_order') && isset($post->ID)) {
            $order = wc_get_order($post->ID);
            if ($order) {
                $statuses = get_option('e2pdf_wc_admin_order_details_template_id_status', array('any'));
                if (is_array($statuses) && (in_array($order->get_status(), $statuses) || in_array('wc-' . $order->get_status(), $statuses) || in_array('any', $statuses))) {
                    echo apply_filters(
                            'e2pdf_wc_action_e2pdf_wc_admin_order_details_template_id',
                            do_shortcode('[e2pdf-download id="' . get_option('e2pdf_wc_admin_order_details_template_id', '0') . '" dataset="' . $order->get_id() . '" user_id="' . $order->get_user_id() . '" class="button e2pdf-wc-download-button e2pdf-wc-download-order-details-button"]'),
                            $order
                    );
                }
            }
        }
    }

    public function action_add_meta_boxes() {
        add_meta_box(
                'woocommerce-order-my-custom',
                'E2Pdf',
                array($this, 'action_e2pdf_wc_admin_order_details_template_id'),
                'shop_order',
                'side',
                'high'
        );
    }

    public function action_enforcing_rules_enabled_check() {
        if (get_option('wc_downloads_approved_directories_mode') === 'enabled') {
            if (class_exists('Automattic\WooCommerce\Internal\ProductDownloads\ApprovedDirectories\Register')) {
                try {
                    $download_directories = wc_get_container()->get(Automattic\WooCommerce\Internal\ProductDownloads\ApprovedDirectories\Register::class);
                    $directory_id = $download_directories->add_approved_directory('file://./');
                    if ($directory_id) {
                        $download_directories->enable_by_id($directory_id);
                    }
                } catch (Exception $e) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
                }
            }
        }
    }

    public function filter_cs_element_pre_render($data) {
        if (!empty($data['_type']) && $data['_type'] == 'raw-content' && !empty($data['raw_content']) && !empty($data['_p'])) {
            $data['raw_content'] = $this->filter_content($data['raw_content'], $data['_p']);
        }
        return $data;
    }

    public function action_elementor_widget_before_render_content($widget) {
        if ($widget && ($widget->get_name() == 'shortcode' || $widget->get_name() == 'text-editor')) {
            $content = $widget->get_name() == 'shortcode' ? $widget->get_settings('shortcode') : $widget->get_settings('editor');
            if ($content && false !== strpos($content, '[e2pdf-')) {
                $wp_reset_postdata = true;
                if (class_exists('\ElementorPro\Plugin') && class_exists('\ElementorPro\Modules\LoopBuilder\Documents\Loop')) {
                    $document = \ElementorPro\Plugin::elementor()->documents->get_current();
                    if ($document && $document instanceof \ElementorPro\Modules\LoopBuilder\Documents\Loop) {
                        $wp_reset_postdata = false;
                    }
                }
                if ($widget->get_name() == 'shortcode') {
                    $widget->set_settings('shortcode', $this->filter_content($content, false, false, $wp_reset_postdata));
                } elseif ($widget->get_name() == 'text-editor' && !$wp_reset_postdata) {
                    $widget->set_settings('editor', $this->filter_content($content, false, false, $wp_reset_postdata));
                }
            }
        }
    }

    public function action_add_meta_boxes_woocommerce_page_wc_orders($post) {
        if (class_exists('Automattic\WooCommerce\Utilities\OrderUtil') && method_exists('Automattic\WooCommerce\Utilities\OrderUtil', 'is_order_edit_screen') && class_exists('WC_Data_Store')) {
            if (Automattic\WooCommerce\Utilities\OrderUtil::is_order_edit_screen()) {
                if ($post instanceof WC_Order) {
                    $order_id = $post->get_id();
                } else {
                    $order_id = $post->ID;
                }
                $data_store = WC_Data_Store::load('customer-download');
                $download_permissions = array();
                if (0 !== $order_id) {
                    $download_permissions = $data_store->get_downloads(
                            array(
                                'order_id' => $order_id,
                                'orderby' => 'product_id',
                            )
                    );
                }
                $product = null;
                $items = array();
                $download_links = array();
                if ($download_permissions && count($download_permissions) > 0) {
                    foreach ($download_permissions as $download) {
                        if (!$product || $product->get_id() !== $download->get_product_id()) {
                            $product = wc_get_product($download->get_product_id());
                        }
                        if (!$product || !$product->exists() || !$product->has_file($download->get_download_id())) {
                            continue;
                        }
                        $file = $product->get_file($download->get_download_id());
                        if ($file && $file->get_file() && strpos($file->get_file(), '[e2pdf-download') !== false) {
                            global $wpdb;
                            if (isset($items[$download->get_download_id()])) {
                                $item_ids = $items[$download->get_download_id()];
                            } else {
                                $item_ids = array();
                            }
                            $item_id = $wpdb->get_var($wpdb->prepare('SELECT `items`.`order_item_id` FROM `' . $wpdb->prefix . 'woocommerce_order_items` `items` INNER JOIN `' . $wpdb->prefix . "woocommerce_order_itemmeta` `itemmeta` ON `itemmeta`.`order_item_id` = `items`.`order_item_id` AND (`itemmeta`.`meta_key` = '_product_id' OR `itemmeta`.`meta_key` = '_variation_id' ) AND `itemmeta`.`meta_value` = %d WHERE `items`.`order_id` = %d and `items`.`order_item_type` = 'line_item' and `items`.`order_item_id` NOT IN ( '" . implode("','", $item_ids) . "' ) ORDER BY `items`.`order_item_id` ASC", $download->get_product_id(), $download->get_order_id()));
                            if ($item_id) {
                                $items[$download->get_download_id()][] = $item_id;
                                $download_link = add_query_arg(
                                        array(
                                            'download_file' => $download->get_product_id(),
                                            'order' => $download->get_order_key(),
                                            'email' => urlencode($download->get_user_email()), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.urlencode_urlencode
                                            'key' => $download->get_download_id(),
                                        ),
                                        trailingslashit(home_url())
                                );
                                $download_links[] = array(
                                    's' => $download->get_download_id(),
                                    'r' => add_query_arg(array('item_id' => $item_id), $download_link),
                                );
                            }
                        }
                    }
                }
                if (!empty($download_links)) {
                    add_action(
                            'admin_print_scripts', function () use ($download_links) {
                                echo '<script>const e2pdf_wc_download_links = ' . wp_json_encode($download_links) . ';document.addEventListener("DOMContentLoaded", function(event) {for (var i = 0; i < e2pdf_wc_download_links.length; i++){var a = document.querySelector(\'a[id="copy-download-link"][href$="\'+e2pdf_wc_download_links[i][\'s\']+\'"]\');if (a) {a.setAttribute(\'href\',e2pdf_wc_download_links[i][\'r\']);}}});</script>';
                            }
                    );
                }
            }
        }
    }

    public function filter_woocommerce_available_variation($data, $wc_product_variable, $variation) {
        $description = $variation->get_description();
        if ($description && false !== strpos($description, '[e2pdf-download') || false !== strpos($description, '[e2pdf-save') || false !== strpos($description, '[e2pdf-view') || false !== strpos($description, '[e2pdf-adobesign') || false !== strpos($description, '[e2pdf-zapier')) {
            $description = $this->filter_the_content($description, $variation->get_variation_id());
            $data['variation_description'] = wc_format_content($description);
        }
        return $data;
    }

    public function filter_woocommerce_mail_content($message) {
        if ($message) {
            if (false !== strpos($message, '[e2pdf-attachment') || false !== strpos($message, '[e2pdf-save') || false !== strpos($message, '[e2pdf-zapier')) {
                $message = preg_replace('~(?:\[(e2pdf-attachment|e2pdf-save|e2pdf-zapier)/?)\s[^\]]+/?\]~s', '', $message);
            }
        }
        return $message;
    }

    public function filter_woocommerce_email_attachments($attachments, $wc_email_id, $order, $wc_email) {
        if ($wc_email_id == 'ywgc-email-send-gift-card') {
            $ywgc_gift_post_id = isset($order->ID) ? $order->ID : false;
            $ywgc_order_item_id = get_post_meta($ywgc_gift_post_id, '_ywgc_order_item_id', true);
            $order = wc_get_order(isset($order->order_id) ? $order->order_id : false);
        }
        if ($order && is_object($order) && method_exists($order, 'get_id') && $wc_email && is_object($wc_email) && method_exists($wc_email, 'get_additional_content')) {
            if ($wc_email_id == 'ywgc-email-send-gift-card') {
                $additional_content = isset($wc_email->introductory_text) ? $wc_email->introductory_text : '';
                $additional_content = str_replace(
                        array(
                            '{recipient_email}',
                        ),
                        array(
                            isset($wc_email->recipient) ? $wc_email->recipient : '',
                        ),
                        $additional_content
                );
            } else {
                $additional_content = $wc_email->get_additional_content();
            }
            if (false !== strpos($additional_content, '[')) {
                $shortcode_tags = array(
                    'e2pdf-attachment',
                    'e2pdf-save',
                    'e2pdf-zapier',
                );
                preg_match_all('@\[([^<>&/\[\]\x00-\x20=]++)@', $additional_content, $matches);
                $tagnames = array_intersect($shortcode_tags, $matches[1]);
                if (!empty($tagnames)) {
                    $items = array();
                    $items_variation = array();
                    $items_product = array();
                    if (method_exists($order, 'get_items')) {
                        $order_items = $order->get_items();
                        foreach ($order_items as $item_id => $order_item) {
                            $items[$item_id] = $order_item->get_product_id();
                            if ($order_item->get_variation_id()) {
                                $items_variation[$item_id] = $order_item->get_variation_id();
                            } else {
                                $items_product[$item_id] = $order_item->get_product_id();
                            }
                        }
                    }
                    preg_match_all('/' . $this->helper->load('shortcode')->get_shortcode_regex($tagnames) . '/', $additional_content, $shortcodes);
                    foreach ($shortcodes[0] as $key => $shortcode_value) {
                        $shortcode = $this->helper->load('shortcode')->get_shortcode($shortcodes, $key);
                        $atts = shortcode_parse_atts($shortcode[3]);
                        $file = false;
                        $template = new Model_E2pdf_Template();
                        if (isset($atts['id']) && $atts['id']) {
                            $template->load($atts['id']);
                        }
                        $attributes = array();
                        foreach ($atts as $att_key => $att) {
                            if (strpos($att_key, 'attribute_') === 0) {
                                $attributes[substr($att_key, 10)] = $att;
                            }
                        }
                        if (isset($atts['product_ids']) && $atts['product_ids']) {
                            $atts['products'] = $atts['product_ids'];
                        }
                        if ($template->get('extension') === 'gravity') {
                            if (!isset($atts['dataset'])) {
                                foreach ($items as $item_id => $item) {
                                    $linked_entry_id = false;
                                    if ($linked_data = wc_get_order_item_meta($item_id, '_gravity_forms_history', true)) {
                                        if (!empty($linked_data['_gravity_form_linked_entry_id'])) {
                                            $linked_entry_id = $linked_data['_gravity_form_linked_entry_id'];
                                        }
                                    } elseif ($linked_data = wc_get_order_item_meta($item_id, 'gspc_gf_entry_ids', true)) {
                                        if (!empty($linked_data) && isset($linked_data[0])) {
                                            $linked_entry_id = $linked_data[0];
                                        }
                                    }
                                    if ($linked_entry_id) {
                                        $attachment = true;
                                        if ($attachment) {
                                            if (!empty($attributes)) {
                                                $product = wc_get_product($item);
                                                if ($product) {
                                                    foreach ($attributes as $attribute_key => $attribute) {
                                                        if ($product->get_attribute($attribute_key) != $attribute) {
                                                            $attachment = false;
                                                            break;
                                                        }
                                                    }
                                                } else {
                                                    $attachment = false;
                                                }
                                            }
                                        }
                                        if ($attachment) {
                                            if (isset($atts['products']) && $atts['products']) {
                                                $attachment = false;
                                                $products = explode(',', $atts['products']);
                                                if (in_array($item, $products)) {
                                                    $attachment = true;
                                                } else {
                                                    $product = wc_get_product($item);
                                                    if ($product && method_exists($product, 'get_parent_id')) {
                                                        if (in_array($product->get_parent_id(), $products)) {
                                                            $attachment = true;
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                        if ($attachment) {
                                            if (isset($atts['categories']) && $atts['categories']) {
                                                $attachment = false;
                                                $categories = explode(',', $atts['categories']);
                                                if (has_term($categories, 'product_cat', $item)) {
                                                    $attachment = true;
                                                }
                                            }
                                        }
                                        if ($attachment) {
                                            if (isset($atts['tags']) && $atts['tags']) {
                                                $attachment = false;
                                                $tags = explode(',', $atts['tags']);
                                                if (has_term($tags, 'product_tag', $item)) {
                                                    $attachment = true;
                                                }
                                            }
                                        }
                                        if ($attachment) {
                                            $item_shortcode = $shortcode;
                                            if (!isset($atts['apply'])) {
                                                $item_shortcode[3] .= ' apply="true"';
                                            }
                                            if (!isset($atts['filter'])) {
                                                $item_shortcode[3] .= ' filter="true"';
                                            }
                                            $item_shortcode[3] .= ' dataset="' . $linked_entry_id . '"';
                                            if (($item_shortcode[2] === 'e2pdf-save' && isset($atts['attachment']) && $atts['attachment'] == 'true') || $item_shortcode[2] === 'e2pdf-attachment') {
                                                $file = do_shortcode_tag($item_shortcode);
                                                if ($file) {
                                                    $tmp = false;
                                                    if (substr($file, 0, 4) === 'tmp:') {
                                                        $file = substr($file, 4);
                                                        $tmp = true;
                                                    }
                                                    if ($shortcode[2] === 'e2pdf-save' || isset($atts['pdf'])) {
                                                        if ($tmp) {
                                                            $this->helper->add('woocommerce_attachments', $file);
                                                        }
                                                    } else {
                                                        $this->helper->add('woocommerce_attachments', $file);
                                                    }
                                                    $attachments[] = $file;
                                                }
                                            } elseif ($item_shortcode[2] === 'e2pdf-zapier' || $item_shortcode[2] === 'e2pdf-save') {
                                                do_shortcode_tag($item_shortcode);
                                            }
                                        }
                                    }
                                }
                            }
                        } elseif ($template->get('extension') === 'formidable') {
                            if (!isset($atts['dataset'])) {
                                foreach ($items as $item_id => $item) {
                                    $_formidable_form_data = wc_get_order_item_meta($item_id, '_formidable_form_data', true);
                                    if ($_formidable_form_data) {
                                        $attachment = true;
                                        if ($attachment) {
                                            if (!empty($attributes)) {
                                                $product = wc_get_product($item);
                                                if ($product) {
                                                    foreach ($attributes as $attribute_key => $attribute) {
                                                        if ($product->get_attribute($attribute_key) != $attribute) {
                                                            $attachment = false;
                                                            break;
                                                        }
                                                    }
                                                } else {
                                                    $attachment = false;
                                                }
                                            }
                                        }
                                        if ($attachment) {
                                            if (isset($atts['products']) && $atts['products']) {
                                                $attachment = false;
                                                $products = explode(',', $atts['products']);
                                                if (in_array($item, $products)) {
                                                    $attachment = true;
                                                } else {
                                                    $product = wc_get_product($item);
                                                    if ($product && method_exists($product, 'get_parent_id')) {
                                                        if (in_array($product->get_parent_id(), $products)) {
                                                            $attachment = true;
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                        if ($attachment) {
                                            if (isset($atts['categories']) && $atts['categories']) {
                                                $attachment = false;
                                                $categories = explode(',', $atts['categories']);
                                                if (has_term($categories, 'product_cat', $item)) {
                                                    $attachment = true;
                                                }
                                            }
                                        }
                                        if ($attachment) {
                                            if (isset($atts['tags']) && $atts['tags']) {
                                                $attachment = false;
                                                $tags = explode(',', $atts['tags']);
                                                if (has_term($tags, 'product_tag', $item)) {
                                                    $attachment = true;
                                                }
                                            }
                                        }
                                        if ($attachment) {
                                            $item_shortcode = $shortcode;
                                            if (!isset($atts['apply'])) {
                                                $item_shortcode[3] .= ' apply="true"';
                                            }
                                            if (!isset($atts['filter'])) {
                                                $item_shortcode[3] .= ' filter="true"';
                                            }
                                            $item_shortcode[3] .= ' dataset="' . $_formidable_form_data . '"';
                                            if (($item_shortcode[2] === 'e2pdf-save' && isset($atts['attachment']) && $atts['attachment'] == 'true') || $item_shortcode[2] === 'e2pdf-attachment') {
                                                $file = do_shortcode_tag($item_shortcode);
                                                if ($file) {
                                                    $tmp = false;
                                                    if (substr($file, 0, 4) === 'tmp:') {
                                                        $file = substr($file, 4);
                                                        $tmp = true;
                                                    }
                                                    if ($shortcode[2] === 'e2pdf-save' || isset($atts['pdf'])) {
                                                        if ($tmp) {
                                                            $this->helper->add('woocommerce_attachments', $file);
                                                        }
                                                    } else {
                                                        $this->helper->add('woocommerce_attachments', $file);
                                                    }
                                                    $attachments[] = $file;
                                                }
                                            } elseif ($item_shortcode[2] === 'e2pdf-zapier' || $item_shortcode[2] === 'e2pdf-save') {
                                                do_shortcode_tag($item_shortcode);
                                            }
                                        }
                                    }
                                }
                            }
                        } elseif ($wc_email_id == 'ywgc-email-send-gift-card' && $template->get('extension') === 'wordpress') {
                            if ($template->get('item') == 'gift_card') {
                                if (isset($atts['dataset'])) {
                                    foreach ($items_product as $item_id => $item) {
                                        if ($ywgc_order_item_id != $item_id) {
                                            continue;
                                        }
                                        if ($item == $atts['dataset']) {
                                            $attachment = true;
                                            if ($attachment) {
                                                if (!empty($attributes)) {
                                                    $product = wc_get_product($item);
                                                    if ($product) {
                                                        foreach ($attributes as $attribute_key => $attribute) {
                                                            if ($product->get_attribute($attribute_key) != $attribute) {
                                                                $attachment = false;
                                                                break;
                                                            }
                                                        }
                                                    } else {
                                                        $attachment = false;
                                                    }
                                                }
                                            }
                                            if ($attachment) {
                                                $item_shortcode = $shortcode;
                                                if (!isset($atts['apply'])) {
                                                    $item_shortcode[3] .= ' apply="true"';
                                                }
                                                if (!isset($atts['filter'])) {
                                                    $item_shortcode[3] .= ' filter="true"';
                                                }
                                                $item_shortcode[3] .= ' dataset="' . $ywgc_gift_post_id . '"';
                                                if (($item_shortcode[2] === 'e2pdf-save' && isset($atts['attachment']) && $atts['attachment'] == 'true') || $item_shortcode[2] === 'e2pdf-attachment') {
                                                    $file = do_shortcode_tag($item_shortcode);
                                                    if ($file) {
                                                        $tmp = false;
                                                        if (substr($file, 0, 4) === 'tmp:') {
                                                            $file = substr($file, 4);
                                                            $tmp = true;
                                                        }
                                                        if ($shortcode[2] === 'e2pdf-save' || isset($atts['pdf'])) {
                                                            if ($tmp) {
                                                                $file = substr($file, 4);
                                                                $this->helper->add('woocommerce_attachments', $file);
                                                            }
                                                        } else {
                                                            $this->helper->add('woocommerce_attachments', $file);
                                                        }
                                                        $attachments[] = $file;
                                                    }
                                                } elseif ($item_shortcode[2] === 'e2pdf-zapier' || $item_shortcode[2] === 'e2pdf-save') {
                                                    do_shortcode_tag($item_shortcode);
                                                }
                                            }
                                        }
                                    }
                                } elseif (!isset($atts['dataset'])) {
                                    foreach ($items_product as $item_id => $item) {
                                        if ($ywgc_order_item_id != $item_id) {
                                            continue;
                                        }
                                        $attachment = true;
                                        if ($attachment) {
                                            if (!empty($attributes)) {
                                                $product = wc_get_product($item);
                                                if ($product) {
                                                    foreach ($attributes as $attribute_key => $attribute) {
                                                        if ($product->get_attribute($attribute_key) != $attribute) {
                                                            $attachment = false;
                                                            break;
                                                        }
                                                    }
                                                } else {
                                                    $attachment = false;
                                                }
                                            }
                                        }
                                        if ($attachment) {
                                            if (isset($atts['products']) && $atts['products']) {
                                                $attachment = false;
                                                $products = explode(',', $atts['products']);
                                                if (in_array($item, $products)) {
                                                    $attachment = true;
                                                }
                                            }
                                        }
                                        if ($attachment) {
                                            if (isset($atts['categories']) && $atts['categories']) {
                                                $attachment = false;
                                                $categories = explode(',', $atts['categories']);
                                                if (has_term($categories, 'product_cat', $item)) {
                                                    $attachment = true;
                                                }
                                            }
                                        }
                                        if ($attachment) {
                                            if (isset($atts['tags']) && $atts['tags']) {
                                                $attachment = false;
                                                $tags = explode(',', $atts['tags']);
                                                if (has_term($tags, 'product_tag', $item)) {
                                                    $attachment = true;
                                                }
                                            }
                                        }
                                        if ($attachment) {
                                            $item_shortcode = $shortcode;
                                            if (!isset($atts['apply'])) {
                                                $item_shortcode[3] .= ' apply="true"';
                                            }
                                            if (!isset($atts['filter'])) {
                                                $item_shortcode[3] .= ' filter="true"';
                                            }
                                            $item_shortcode[3] .= ' dataset="' . $ywgc_gift_post_id . '"';
                                            if (($item_shortcode[2] === 'e2pdf-save' && isset($atts['attachment']) && $atts['attachment'] == 'true') || $item_shortcode[2] === 'e2pdf-attachment') {
                                                $file = do_shortcode_tag($item_shortcode);
                                                if ($file) {
                                                    $tmp = false;
                                                    if (substr($file, 0, 4) === 'tmp:') {
                                                        $file = substr($file, 4);
                                                        $tmp = true;
                                                    }
                                                    if ($shortcode[2] === 'e2pdf-save' || isset($atts['pdf'])) {
                                                        if ($tmp) {
                                                            $this->helper->add('woocommerce_attachments', $file);
                                                        }
                                                    } else {
                                                        $this->helper->add('woocommerce_attachments', $file);
                                                    }
                                                    $attachments[] = $file;
                                                }
                                            } elseif ($item_shortcode[2] === 'e2pdf-zapier' || $item_shortcode[2] === 'e2pdf-save') {
                                                do_shortcode_tag($item_shortcode);
                                            }
                                        }
                                    }
                                }
                            }
                        } elseif ($template->get('extension') === 'woocommerce') {
                            if ($template->get('item') == 'product') {
                                if (isset($atts['dataset'])) {
                                    foreach ($items_product as $item_id => $item) {
                                        if ($wc_email_id == 'ywgc-email-send-gift-card' && $ywgc_order_item_id != $item_id) {
                                            continue;
                                        }
                                        if ($item == $atts['dataset']) {
                                            $attachment = true;
                                            if ($attachment) {
                                                if (!empty($attributes)) {
                                                    $product = wc_get_product($item);
                                                    if ($product) {
                                                        foreach ($attributes as $attribute_key => $attribute) {
                                                            if ($product->get_attribute($attribute_key) != $attribute) {
                                                                $attachment = false;
                                                                break;
                                                            }
                                                        }
                                                    } else {
                                                        $attachment = false;
                                                    }
                                                }
                                            }
                                            if ($attachment) {
                                                $item_shortcode = $shortcode;
                                                if (!isset($atts['apply'])) {
                                                    $item_shortcode[3] .= ' apply="true"';
                                                }
                                                if (!isset($atts['filter'])) {
                                                    $item_shortcode[3] .= ' filter="true"';
                                                }
                                                if (!isset($atts['wc_order_id'])) {
                                                    $item_shortcode[3] .= ' wc_order_id="' . $order->get_id() . '"';
                                                }
                                                if (!isset($atts['wc_product_item_id'])) {
                                                    $item_shortcode[3] .= ' wc_product_item_id="' . $item_id . '"';
                                                }

                                                if (($item_shortcode[2] === 'e2pdf-save' && isset($atts['attachment']) && $atts['attachment'] == 'true') || $item_shortcode[2] === 'e2pdf-attachment') {
                                                    $file = do_shortcode_tag($item_shortcode);
                                                    if ($file) {
                                                        $tmp = false;
                                                        if (substr($file, 0, 4) === 'tmp:') {
                                                            $file = substr($file, 4);
                                                            $tmp = true;
                                                        }
                                                        if ($shortcode[2] === 'e2pdf-save' || isset($atts['pdf'])) {
                                                            if ($tmp) {
                                                                $file = substr($file, 4);
                                                                $this->helper->add('woocommerce_attachments', $file);
                                                            }
                                                        } else {
                                                            $this->helper->add('woocommerce_attachments', $file);
                                                        }
                                                        $attachments[] = $file;
                                                    }
                                                } elseif ($item_shortcode[2] === 'e2pdf-zapier' || $item_shortcode[2] === 'e2pdf-save') {
                                                    do_shortcode_tag($item_shortcode);
                                                }
                                            }
                                        }
                                    }
                                } elseif (!isset($atts['dataset'])) {
                                    foreach ($items_product as $item_id => $item) {
                                        if ($wc_email_id == 'ywgc-email-send-gift-card' && $ywgc_order_item_id != $item_id) {
                                            continue;
                                        }
                                        $attachment = true;
                                        if ($attachment) {
                                            if (!empty($attributes)) {
                                                $product = wc_get_product($item);
                                                if ($product) {
                                                    foreach ($attributes as $attribute_key => $attribute) {
                                                        if ($product->get_attribute($attribute_key) != $attribute) {
                                                            $attachment = false;
                                                            break;
                                                        }
                                                    }
                                                } else {
                                                    $attachment = false;
                                                }
                                            }
                                        }
                                        if ($attachment) {
                                            if (isset($atts['products']) && $atts['products']) {
                                                $attachment = false;
                                                $products = explode(',', $atts['products']);
                                                if (in_array($item, $products)) {
                                                    $attachment = true;
                                                }
                                            }
                                        }
                                        if ($attachment) {
                                            if (isset($atts['categories']) && $atts['categories']) {
                                                $attachment = false;
                                                $categories = explode(',', $atts['categories']);
                                                if (has_term($categories, 'product_cat', $item)) {
                                                    $attachment = true;
                                                }
                                            }
                                        }
                                        if ($attachment) {
                                            if (isset($atts['tags']) && $atts['tags']) {
                                                $attachment = false;
                                                $tags = explode(',', $atts['tags']);
                                                if (has_term($tags, 'product_tag', $item)) {
                                                    $attachment = true;
                                                }
                                            }
                                        }
                                        if ($attachment) {
                                            $item_shortcode = $shortcode;
                                            if (!isset($atts['apply'])) {
                                                $item_shortcode[3] .= ' apply="true"';
                                            }
                                            if (!isset($atts['filter'])) {
                                                $item_shortcode[3] .= ' filter="true"';
                                            }
                                            if (!isset($atts['wc_order_id'])) {
                                                $item_shortcode[3] .= ' wc_order_id="' . $order->get_id() . '"';
                                            }
                                            if (!isset($atts['wc_product_item_id'])) {
                                                $item_shortcode[3] .= ' wc_product_item_id="' . $item_id . '"';
                                            }
                                            $item_shortcode[3] .= ' dataset="' . $item . '"';
                                            if (($item_shortcode[2] === 'e2pdf-save' && isset($atts['attachment']) && $atts['attachment'] == 'true') || $item_shortcode[2] === 'e2pdf-attachment') {
                                                $file = do_shortcode_tag($item_shortcode);
                                                if ($file) {
                                                    $tmp = false;
                                                    if (substr($file, 0, 4) === 'tmp:') {
                                                        $file = substr($file, 4);
                                                        $tmp = true;
                                                    }
                                                    if ($shortcode[2] === 'e2pdf-save' || isset($atts['pdf'])) {
                                                        if ($tmp) {
                                                            $this->helper->add('woocommerce_attachments', $file);
                                                        }
                                                    } else {
                                                        $this->helper->add('woocommerce_attachments', $file);
                                                    }
                                                    $attachments[] = $file;
                                                }
                                            } elseif ($item_shortcode[2] === 'e2pdf-zapier' || $item_shortcode[2] === 'e2pdf-save') {
                                                do_shortcode_tag($item_shortcode);
                                            }
                                        }
                                    }
                                }
                            } elseif ($template->get('item') == 'product_variation') {
                                if (isset($atts['dataset'])) {
                                    foreach ($items_variation as $item_id => $item) {
                                        if ($item == $atts['dataset']) {
                                            $attachment = true;
                                            if ($attachment) {
                                                if (!empty($attributes)) {
                                                    $product = wc_get_product($item);
                                                    if ($product) {
                                                        foreach ($attributes as $attribute_key => $attribute) {
                                                            if ($product->get_attribute($attribute_key) != $attribute) {
                                                                $attachment = false;
                                                                break;
                                                            }
                                                        }
                                                    } else {
                                                        $attachment = false;
                                                    }
                                                }
                                            }
                                            if ($attachment) {
                                                $item_shortcode = $shortcode;
                                                if (!isset($atts['apply'])) {
                                                    $item_shortcode[3] .= ' apply="true"';
                                                }
                                                if (!isset($atts['filter'])) {
                                                    $item_shortcode[3] .= ' filter="true"';
                                                }
                                                if (!isset($atts['wc_order_id'])) {
                                                    $item_shortcode[3] .= ' wc_order_id="' . $order->get_id() . '"';
                                                }
                                                if (!isset($atts['wc_product_item_id'])) {
                                                    $item_shortcode[3] .= ' wc_product_item_id="' . $item_id . '"';
                                                }
                                                if (($item_shortcode[2] === 'e2pdf-save' && isset($atts['attachment']) && $atts['attachment'] == 'true') || $item_shortcode[2] === 'e2pdf-attachment') {
                                                    $file = do_shortcode_tag($item_shortcode);
                                                    if ($file) {
                                                        $tmp = false;
                                                        if (substr($file, 0, 4) === 'tmp:') {
                                                            $file = substr($file, 4);
                                                            $tmp = true;
                                                        }
                                                        if ($shortcode[2] === 'e2pdf-save' || isset($atts['pdf'])) {
                                                            if ($tmp) {
                                                                $this->helper->add('woocommerce_attachments', $file);
                                                            }
                                                        } else {
                                                            $this->helper->add('woocommerce_attachments', $file);
                                                        }
                                                        $attachments[] = $file;
                                                    }
                                                } elseif ($item_shortcode[2] === 'e2pdf-zapier' || $item_shortcode[2] === 'e2pdf-save') {
                                                    do_shortcode_tag($item_shortcode);
                                                }
                                            }
                                        }
                                    }
                                } elseif (!isset($atts['dataset'])) {
                                    foreach ($items_variation as $item_id => $item) {
                                        $attachment = true;
                                        if ($attachment) {
                                            if (!empty($attributes)) {
                                                $product = wc_get_product($item);
                                                if ($product) {
                                                    foreach ($attributes as $attribute_key => $attribute) {
                                                        if ($product->get_attribute($attribute_key) != $attribute) {
                                                            $attachment = false;
                                                            break;
                                                        }
                                                    }
                                                } else {
                                                    $attachment = false;
                                                }
                                            }
                                        }
                                        if ($attachment) {
                                            if (isset($atts['products']) && $atts['products']) {
                                                $attachment = false;
                                                $products = explode(',', $atts['products']);
                                                if (in_array($item, $products)) {
                                                    $attachment = true;
                                                } else {
                                                    $product = wc_get_product($item);
                                                    if ($product && method_exists($product, 'get_parent_id')) {
                                                        if (in_array($product->get_parent_id(), $products)) {
                                                            $attachment = true;
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                        if ($attachment) {
                                            if (isset($atts['categories']) && $atts['categories']) {
                                                $attachment = false;
                                                $categories = explode(',', $atts['categories']);
                                                if (has_term($categories, 'product_cat', $item)) {
                                                    $attachment = true;
                                                }
                                            }
                                        }
                                        if ($attachment) {
                                            if (isset($atts['tags']) && $atts['tags']) {
                                                $attachment = false;
                                                $tags = explode(',', $atts['tags']);
                                                if (has_term($tags, 'product_tag', $item)) {
                                                    $attachment = true;
                                                }
                                            }
                                        }
                                        if ($attachment) {
                                            $item_shortcode = $shortcode;
                                            if (!isset($atts['apply'])) {
                                                $item_shortcode[3] .= ' apply="true"';
                                            }
                                            if (!isset($atts['filter'])) {
                                                $item_shortcode[3] .= ' filter="true"';
                                            }
                                            if (!isset($atts['wc_order_id'])) {
                                                $item_shortcode[3] .= ' wc_order_id="' . $order->get_id() . '"';
                                            }
                                            if (!isset($atts['wc_product_item_id'])) {
                                                $item_shortcode[3] .= ' wc_product_item_id="' . $item_id . '"';
                                            }
                                            $item_shortcode[3] .= ' dataset="' . $item . '"';
                                            if (($item_shortcode[2] === 'e2pdf-save' && isset($atts['attachment']) && $atts['attachment'] == 'true') || $item_shortcode[2] === 'e2pdf-attachment') {
                                                $file = do_shortcode_tag($item_shortcode);
                                                if ($file) {
                                                    $tmp = false;
                                                    if (substr($file, 0, 4) === 'tmp:') {
                                                        $file = substr($file, 4);
                                                        $tmp = true;
                                                    }
                                                    if ($shortcode[2] === 'e2pdf-save' || isset($atts['pdf'])) {
                                                        if ($tmp) {
                                                            $this->helper->add('woocommerce_attachments', $file);
                                                        }
                                                    } else {
                                                        $this->helper->add('woocommerce_attachments', $file);
                                                    }
                                                    $attachments[] = $file;
                                                }
                                            } elseif ($item_shortcode[2] === 'e2pdf-zapier' || $item_shortcode[2] === 'e2pdf-save') {
                                                do_shortcode_tag($item_shortcode);
                                            }
                                        }
                                    }
                                }
                            } elseif ($template->get('item') == 'shop_order') {
                                if (!isset($atts['dataset'])) {
                                    $attachment = true;
                                    if ($attachment) {
                                        if (!empty($attributes)) {
                                            $attachment = false;
                                            foreach ($items_product as $item_id => $item) {
                                                $product = wc_get_product($item);
                                                if ($product) {
                                                    foreach ($attributes as $attribute_key => $attribute) {
                                                        if ($product->get_attribute($attribute_key) != $attribute) {
                                                            $attachment = true;
                                                            break;
                                                        }
                                                    }
                                                }
                                            }
                                            if (!$attachment) {
                                                foreach ($items_variation as $item_id => $item) {
                                                    $product = wc_get_product($item);
                                                    if ($product) {
                                                        foreach ($attributes as $attribute_key => $attribute) {
                                                            if ($product->get_attribute($attribute_key) == $attribute) {
                                                                $attachment = true;
                                                                break;
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    if ($attachment) {
                                        if (isset($atts['products']) && $atts['products']) {
                                            $attachment = false;
                                            $products = explode(',', $atts['products']);
                                            foreach ($products as $product_id) {
                                                if (in_array($product_id, $items) || in_array($product_id, $items_variation)) {
                                                    $attachment = true;
                                                    break;
                                                }
                                            }
                                        }
                                    }
                                    if ($attachment) {
                                        if (isset($atts['categories']) && $atts['categories']) {
                                            $attachment = false;
                                            $categories = explode(',', $atts['categories']);
                                            foreach ($items as $item) {
                                                if (has_term($categories, 'product_cat', $item)) {
                                                    $attachment = true;
                                                    break;
                                                }
                                            }
                                        }
                                    }
                                    if ($attachment) {
                                        if (isset($atts['tags']) && $atts['tags']) {
                                            $attachment = false;
                                            $tags = explode(',', $atts['tags']);
                                            foreach ($items as $item) {
                                                if (has_term($tags, 'product_tag', $item)) {
                                                    $attachment = true;
                                                    break;
                                                }
                                            }
                                        }
                                    }
                                    if ($attachment) {
                                        if (!isset($atts['apply'])) {
                                            $shortcode[3] .= ' apply="true"';
                                        }
                                        if (!isset($atts['filter'])) {
                                            $shortcode[3] .= ' filter="true"';
                                        }
                                        $shortcode[3] .= ' dataset="' . $order->get_id() . '"';
                                        if ($this->helper->load('shortcode')->is_attachment($shortcode, $atts)) {
                                            $file = do_shortcode_tag($shortcode);
                                            if ($file) {
                                                $tmp = false;
                                                if (substr($file, 0, 4) === 'tmp:') {
                                                    $file = substr($file, 4);
                                                    $tmp = true;
                                                }
                                                if ($shortcode[2] === 'e2pdf-save' || isset($atts['pdf'])) {
                                                    if ($tmp) {
                                                        $this->helper->add('woocommerce_attachments', $file);
                                                    }
                                                } else {
                                                    $this->helper->add('woocommerce_attachments', $file);
                                                }
                                                $attachments[] = $file;
                                            }
                                        } elseif ($shortcode[2] === 'e2pdf-zapier' || $shortcode[2] === 'e2pdf-save') {
                                            do_shortcode_tag($shortcode);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return $attachments;
    }

    public function filter_gform_merge_tag_filter($value, $merge_tag, $modifier, $field, $raw_value) {

        if ($field && $value) {
            if ($field->type == 'consent') {
                if (false !== strpos($modifier, 'filter') && is_callable('gw_all_fields_template')) {
                    $modifiers = gw_all_fields_template()->parse_modifiers($modifier);
                    if (isset($modifiers['filter']) && $modifiers['filter'] && !is_array($modifiers['filter'])) {
                        $merge_tag = $modifiers['filter'];
                    }
                }
                $mod = explode('.', $merge_tag);
                if (isset($mod[1]) && $mod[1] == '1') {
                    $value = '1';
                }
            } elseif ($field->type == 'list') {
                if (false !== strpos($modifier, 'filter') && is_callable('gw_all_fields_template')) {
                    $modifiers = gw_all_fields_template()->parse_modifiers($modifier);
                    if (isset($modifiers['filter']) && $modifiers['filter'] && !is_array($modifiers['filter'])) {
                        if (false !== strpos($modifiers['filter'], ':')) {
                            $mods = explode(':', $modifiers['filter']);
                            $merge_tag = $mods[0];
                            $modifier = $mods[1];
                        } else {
                            $merge_tag = $modifiers['filter'];
                        }
                    }
                    if ($merge_tag != $field->id) {
                        return false;
                    }
                }
                if ($modifier && $modifier != 'text') {
                    $list_id = false;
                    $field_id = false;
                    if (false !== strpos($modifier, '_')) {
                        $mod = explode('_', $modifier);
                        if (isset($mod[0]) && is_numeric($mod[0])) {
                            $list_id = $mod[0] - 1;
                        }
                        if (isset($mod[1]) && is_numeric($mod[1])) {
                            $field_id = $mod[1] - 1;
                        }
                    } elseif (is_numeric($modifier)) {
                        $list_id = $modifier - 1;
                    }
                    if ($list_id !== false) {
                        $value = '';
                        if (is_serialized($raw_value)) {
                            $list = $this->helper->load('convert')->unserialize(trim($raw_value));
                        } else {
                            $list = $raw_value;
                        }
                        if (is_array($list)) {
                            if (isset($list[$list_id])) {
                                if ($field_id !== false) {
                                    if (is_array($list[$list_id]) && isset(array_values($list[$list_id])[$field_id])) {
                                        $value = array_values($list[$list_id])[$field_id];
                                    }
                                } else {
                                    if (is_array($list[$list_id])) {
                                        $value = implode(',', $list[$list_id]);
                                    } else {
                                        $value = $list[$list_id];
                                    }
                                }
                            }
                        }
                    }
                }
            } elseif ($field->type == 'name' || $field->type == 'address') {
                if (false !== strpos($modifier, 'filter') && is_callable('gw_all_fields_template')) {
                    $modifiers = gw_all_fields_template()->parse_modifiers($modifier);
                    if (isset($modifiers['filter']) && $modifiers['filter'] && !is_array($modifiers['filter'])) {
                        if (is_array($raw_value) && isset($raw_value[$modifiers['filter']])) {
                            return $raw_value[$modifiers['filter']];
                        }
                    }
                }
            }
        }

        return $value;
    }

    public function filter_woocommerce_display_product_attributes($product_attributes, $product) {
        if ($product_attributes) {
            foreach ($product_attributes as $product_attribute_key => $product_attribute) {
                if (isset($product_attribute['value']) && $product_attribute['value'] && false !== strpos($product_attribute['value'], '[e2pdf-download')) {
                    $product_attributes[$product_attribute_key]['value'] = $this->filter_content(htmlspecialchars_decode($product_attribute['value']), $product->get_id());
                }
            }
        }
        return $product_attributes;
    }

    public function filter_e2pdf_model_shortcode_wc_product_get_attribute_value($product_attribute, $product) {
        if ($product_attribute && false !== strpos($product_attribute, '[e2pdf-download') && $product && is_object($product) && method_exists($product, 'get_id')) {
            $product_attribute = $this->filter_content(htmlspecialchars_decode($product_attribute), $product->get_id());
        }
        return $product_attribute;
    }

    public function filter_woocommerce_product_file_download_path($file_path, $product, $download_id) {
        $file_path = $this->filter_content($file_path, $product->get_id(), true);
        return $file_path;
    }

    public function filter_download_urls($downloads) {
        if (!empty($downloads)) {
            $items = array();
            foreach ($downloads as $key => $download) {
                if (isset($download['file']['file']) && $download['file']['file'] && strpos($download['file']['file'], '[e2pdf-download') !== false) {
                    global $wpdb;
                    if (isset($items[$download['download_id']])) {
                        $item_ids = $items[$download['download_id']];
                    } else {
                        $item_ids = array();
                    }
                    $item_id = $wpdb->get_var($wpdb->prepare('SELECT `items`.`order_item_id` FROM `' . $wpdb->prefix . 'woocommerce_order_items` `items` INNER JOIN `' . $wpdb->prefix . "woocommerce_order_itemmeta` `itemmeta` ON `itemmeta`.`order_item_id` = `items`.`order_item_id` AND (`itemmeta`.`meta_key` = '_product_id' OR `itemmeta`.`meta_key` = '_variation_id' ) AND `itemmeta`.`meta_value` = %d WHERE `items`.`order_id` = %d and `items`.`order_item_type` = 'line_item' and `items`.`order_item_id` NOT IN ( '" . implode("','", $item_ids) . "' ) ORDER BY `items`.`order_item_id` ASC", $download['product_id'], $download['order_id']));
                    if ($item_id) {
                        $downloads[$key]['download_url'] = add_query_arg(
                                array('item_id' => $item_id), $download['download_url']
                        );
                        $items[$download['download_id']][] = $item_id;
                    }
                }
            }
        }
        return $downloads;
    }

    /**
     * Render value according to content
     * @param string $value - Content
     * @param string $type - Type of rendering value
     * @param array $field - Field details
     * @return string - Fully rendered value
     */
    public function render($value, $field = array(), $convert_shortcodes = true, $raw = false) {
        $value = $this->render_shortcodes($value, $field);
        if (!$raw) {
            $value = $this->strip_shortcodes($value);
            $value = $this->convert_shortcodes($value, $convert_shortcodes, isset($field['type']) && $field['type'] == 'e2pdf-html' ? true : false);
            $value = $this->helper->load('field')->render_checkbox($value, $this, $field);
        }
        return $value;
    }

    /**
     * Render shortcodes which available in this extension
     * @param string $value - Content
     * @param string $type - Type of rendering value
     * @param array $field - Field details
     * @return string - Value with rendered shortcodes
     */
    public function render_shortcodes($value, $field = array()) {
        $element_id = isset($field['element_id']) ? $field['element_id'] : false;
        if ($this->verify()) {
            if (false !== strpos($value, '[')) {
                $value = $this->helper->load('field')->pre_shortcodes($value, $this, $field);
                $value = $this->helper->load('field')->inner_shortcodes($value, $this, $field);
                $shortcode_tags = array(
                    'e2pdf-wc-product',
                    'e2pdf-wc-order',
                    'e2pdf-wc-cart',
                    'e2pdf-content',
                    'e2pdf-wp',
                    'acf',
                );
                preg_match_all('@\[([^<>&/\[\]\x00-\x20=]++)@', $value, $matches);
                $tagnames = array_intersect($shortcode_tags, $matches[1]);
                foreach ($matches[1] as $key => $shortcode) {
                    if (strpos($shortcode, ':') !== false) {
                        $shortcode_tags[] = $shortcode;
                    }
                }
                if (!empty($tagnames)) {
                    preg_match_all('/' . $this->helper->load('shortcode')->get_shortcode_regex($tagnames) . '/', $value, $shortcodes);
                    foreach ($shortcodes[0] as $key => $shortcode_value) {
                        $shortcode = $this->helper->load('shortcode')->get_shortcode($shortcodes, $key);
                        $atts = shortcode_parse_atts($shortcode[3]);
                        if ($shortcode[2] === 'e2pdf-wp') {
                            if (isset($atts['id']) && $atts['id'] == 'dynamic' && substr($shortcode_value, -11) === '[/e2pdf-wp]') {
                                if ($shortcode[5]) {
                                    $shortcode[5] = $this->render($shortcode[5], array(), false);
                                }
                                $value = str_replace($shortcode_value, '[e2pdf-wp' . $shortcode[3] . ']' . $shortcode[5] . '[/e2pdf-wp]', $value);
                            }
                        } elseif ($shortcode[2] == 'e2pdf-wc-product') {
                            switch ($this->get('item')) {
                                case 'product':
                                case 'product_variation':
                                    if (!isset($atts['id']) && !isset($atts['index']) && isset($this->get('cached_post')->ID) && $this->get('cached_post')->ID) {
                                        $shortcode[3] .= ' id=' . $this->get('cached_post')->ID . '';
                                    }
                                    if (!isset($atts['wc_order_id']) && $this->get('wc_order_id')) {
                                        $shortcode[3] .= ' wc_order_id=' . $this->get('wc_order_id') . '';
                                    }

                                    if (!isset($atts['wc_product_item_id']) && $this->get('wc_product_item_id')) {
                                        $shortcode[3] .= ' wc_product_item_id=' . $this->get('wc_product_item_id') . '';
                                    }
                                    break;
                                case 'shop_order':
                                    if (!isset($atts['wc_order_id']) && isset($this->get('cached_post')->ID) && $this->get('cached_post')->ID) {
                                        $shortcode[3] .= ' wc_order_id=' . $this->get('cached_post')->ID . '';
                                    }
                                    break;
                                case 'cart':
                                    $shortcode[3] .= ' wc_order_id=cart';
                                    break;
                                default:
                                    break;
                            }

                            if (isset($atts['id']) && $atts['id'] == 'dynamic' && substr($shortcode_value, -19) === '[/e2pdf-wc-product]') {
                                if ($shortcode[5]) {
                                    $shortcode[5] = $this->render($shortcode[5], array(), false);
                                }
                                $value = str_replace($shortcode_value, '[e2pdf-wc-product' . $shortcode[3] . ']' . $shortcode[5] . '[/e2pdf-wc-product]', $value);
                            } else {
                                $value = str_replace($shortcode_value, '[e2pdf-wc-product' . $shortcode[3] . ']', $value);
                            }
                        } elseif ($shortcode[2] == 'e2pdf-wc-order') {
                            switch ($this->get('item')) {
                                case 'product':
                                case 'product_variation':
                                    if (!isset($atts['id']) && $this->get('wc_order_id')) {
                                        $shortcode[3] .= ' id=' . $this->get('wc_order_id') . '';
                                    }
                                    break;
                                case 'shop_order':
                                    if (!isset($atts['id']) && isset($this->get('cached_post')->ID) && $this->get('cached_post')->ID) {
                                        $shortcode[3] .= ' id=' . $this->get('cached_post')->ID . '';
                                    }
                                    break;
                                default:
                                    break;
                            }
                            if (isset($atts['id']) && $atts['id'] == 'dynamic' && substr($shortcode_value, -17) === '[/e2pdf-wc-order]') {
                                if ($shortcode[5]) {
                                    $shortcode[5] = $this->render($shortcode[5], array(), false);
                                }
                                $value = str_replace($shortcode_value, '[e2pdf-wc-order' . $shortcode[3] . ']' . $shortcode[5] . '[/e2pdf-wc-order]', $value);
                            } else {
                                $value = str_replace($shortcode_value, '[e2pdf-wc-order' . $shortcode[3] . ']', $value);
                            }
                        } elseif ($shortcode[2] == 'e2pdf-wc-cart') {
                            if ($this->get('item') == 'cart') {
                                if (!isset($atts['id']) && isset($this->get('cached_post')->ID) && $this->get('cached_post')->ID) {
                                    $shortcode[3] .= ' id=' . $this->get('cached_post')->ID . '';
                                }
                                $value = str_replace($shortcode_value, '[e2pdf-wc-cart' . $shortcode[3] . ']', $value);
                            }
                        } elseif ($shortcode[2] === 'acf') {
                            if (!isset($atts['post_id']) && isset($this->get('cached_post')->ID) && $this->get('cached_post')->ID) {
                                if ($this->get('item') == 'product_variation' && isset($this->get('cached_post')->post_parent)) {
                                    $shortcode[3] .= ' post_id=' . $this->get('cached_post')->post_parent . '';
                                } else {
                                    $shortcode[3] .= ' post_id=' . $this->get('cached_post')->ID . '';
                                }
                                $value = str_replace($shortcode_value, '[' . $shortcode[2] . $shortcode[3] . ']', $value);
                            }
                        }
                    }
                }
                $value = $this->helper->load('field')->wrapper_shortcodes($value, $this, $field);
            }
            $value = $this->helper->load('field')->do_shortcodes($value, $this, $field);

            /* Process Gravity Forms Connected Entry */
            if (false !== strpos($value, '{') && class_exists('GFCommon') && class_exists('GFFormsModel')) {
                if (($this->get('item') == 'product' || $this->get('item') == 'product_variation') && $this->get('wc_order_id')) {
                    $order = wc_get_order($this->get('wc_order_id'));
                    if ($order && method_exists($order, 'get_items')) {
                        $order_items = $order->get_items();
                        $linked_entry_id = false;
                        if ($this->get('wc_product_item_id')) {
                            $item_id = $this->get('wc_product_item_id');
                            if (isset($order_items[$item_id])) {
                                if ($linked_data = wc_get_order_item_meta($item_id, '_gravity_forms_history', true)) {
                                    if (!empty($linked_data['_gravity_form_linked_entry_id'])) {
                                        $linked_entry_id = $linked_data['_gravity_form_linked_entry_id'];
                                    }
                                } elseif ($linked_data = wc_get_order_item_meta($item_id, 'gspc_gf_entry_ids', true)) {
                                    if (!empty($linked_data) && isset($linked_data[0])) {
                                        $linked_entry_id = $linked_data[0];
                                    }
                                }
                            }
                        } else {
                            foreach ($order_items as $item_id => $order_item) {
                                if ($order_item->get_product_id() == $this->get('dataset')) {
                                    if ($linked_data = wc_get_order_item_meta($item_id, '_gravity_forms_history', true)) {
                                        if (!empty($linked_data['_gravity_form_linked_entry_id'])) {
                                            $linked_entry_id = $linked_data['_gravity_form_linked_entry_id'];
                                        }
                                    } elseif ($linked_data = wc_get_order_item_meta($item_id, 'gspc_gf_entry_ids', true)) {
                                        if (!empty($linked_data) && isset($linked_data[0])) {
                                            $linked_entry_id = $linked_data[0];
                                        }
                                    }
                                }
                            }
                        }

                        if ($linked_entry_id) {
                            $entry = GFFormsModel::get_entry($linked_entry_id);
                            if ($entry && isset($entry['form_id'])) {
                                $form = GFFormsModel::get_form_meta($entry['form_id']);
                                add_filter('gform_merge_tag_filter', array($this, 'filter_gform_merge_tag_filter'), 30, 5);
                                $value = GFCommon::replace_variables($value, $form, $entry, false, false, false, 'text');
                                remove_filter('gform_merge_tag_filter', array($this, 'filter_gform_merge_tag_filter'), 30, 5);
                            }
                        }
                    }
                }
            }

            $value = $this->helper->load('field')->render(
                    apply_filters('e2pdf_extension_render_shortcodes_pre_value', $value, $element_id, $this->get('template_id'), $this->get('item'), $this->get('dataset'), false, false),
                    $this,
                    $field
            );
        }
        return apply_filters(
                'e2pdf_extension_render_shortcodes_value', $value, $element_id, $this->get('template_id'), $this->get('item'), $this->get('dataset'), false, false
        );
    }

    /**
     * Strip unused shortcodes
     * @param string $value - Content
     * @return string - Value with removed unused shortcodes
     */
    public function strip_shortcodes($value) {
        $value = preg_replace('~(?:\[/?)[^/\]]+/?\]~s', '', $value);
        return $value;
    }

    /**
     * Convert "shortcodes" inside value string
     * @param string $value - Value string
     * @param bool $to - Convert From/To
     * @return string - Converted value
     */
    public function convert_shortcodes($value, $to = false, $html = false) {
        if ($value) {
            if ($to) {
                $value = str_replace('&#91;', '[', $value);
                if (!$html) {
                    $value = wp_specialchars_decode($value, ENT_QUOTES);
                }
            } else {
                $value = str_replace('[', '&#91;', $value);
            }
        }
        return $value;
    }

    public function auto() {
        $response = array();
        $elements = array();

        $line_height = (int) $this->get('line_height');

        if ($this->get('item') == 'cart') {

            $elements[] = array(
                'type' => 'e2pdf-html',
                'block' => true,
                'properties' => array(
                    'top' => '20',
                    'left' => '20',
                    'right' => '20',
                    'width' => '50%',
                    'height' => 'auto',
                    'value' => '<strong>Billing:</strong>',
                ),
            );

            $elements[] = array(
                'type' => 'e2pdf-html',
                'block' => true,
                'float' => true,
                'properties' => array(
                    'top' => '20',
                    'left' => '20',
                    'right' => '20',
                    'width' => '50%',
                    'height' => 'auto',
                    'value' => '<strong>Shipping:</strong>',
                ),
            );

            $elements[] = array(
                'type' => 'e2pdf-html',
                'block' => true,
                'properties' => array(
                    'top' => '5',
                    'left' => '20',
                    'right' => '20',
                    'width' => '50%',
                    'height' => $line_height * 7,
                    'value' => '[e2pdf-wc-customer key="get_formatted_billing_address"]',
                ),
            );

            $elements[] = array(
                'type' => 'e2pdf-html',
                'block' => true,
                'float' => true,
                'properties' => array(
                    'top' => '5',
                    'left' => '20',
                    'right' => '20',
                    'width' => '50%',
                    'height' => $line_height * 7,
                    'value' => '[e2pdf-wc-customer key="get_formatted_shipping_address"]',
                ),
            );

            $elements[] = array(
                'type' => 'e2pdf-html',
                'block' => true,
                'properties' => array(
                    'top' => '10',
                    'left' => '20',
                    'right' => '20',
                    'width' => '50%',
                    'height' => 'auto',
                    'value' => '<strong>E-mail:</strong>',
                ),
            );

            $elements[] = array(
                'type' => 'e2pdf-html',
                'block' => true,
                'float' => true,
                'properties' => array(
                    'top' => '10',
                    'left' => '20',
                    'right' => '20',
                    'width' => '50%',
                    'height' => 'auto',
                    'value' => '<strong>Phone:</strong>',
                ),
            );

            $elements[] = array(
                'type' => 'e2pdf-html',
                'block' => true,
                'properties' => array(
                    'top' => '5',
                    'left' => '20',
                    'right' => '20',
                    'width' => '50%',
                    'height' => 'auto',
                    'value' => '[e2pdf-wc-customer key="get_billing_email"]',
                ),
            );

            $elements[] = array(
                'type' => 'e2pdf-html',
                'block' => true,
                'float' => true,
                'properties' => array(
                    'top' => '5',
                    'left' => '20',
                    'right' => '20',
                    'width' => '50%',
                    'height' => 'auto',
                    'value' => '[e2pdf-wc-customer key="get_billing_phone"]',
                ),
            );

            $elements[] = array(
                'type' => 'e2pdf-html',
                'block' => true,
                'properties' => array(
                    'wysiwyg_disable' => '1',
                    'css' => 'td {' . "\r\n"
                    . 'text-align: center;' . "\r\n"
                    . 'vertical-align: top;' . "\r\n"
                    . '}' . "\r\n"
                    . '.header {' . "\r\n"
                    . 'background: #eeeeee;' . "\r\n"
                    . '}' . "\r\n"
                    . '.item {' . "\r\n"
                    . 'text-align: left;' . "\r\n"
                    . 'width: 40%;' . "\r\n"
                    . '}' . "\r\n"
                    . '.total-label {' . "\r\n"
                    . 'text-align: right;' . "\r\n"
                    . '}' . "\r\n"
                    . '.total-value {' . "\r\n"
                    . 'text-align: right;' . "\r\n"
                    . '}' . "\r\n"
                    . '.total-order {' . "\r\n"
                    . 'font-weight: bold;' . "\r\n"
                    . '}' . "\r\n"
                    . '.formatted-meta-data {' . "\r\n"
                    . 'color: #555555;' . "\r\n"
                    . 'font-size: 8px;' . "\r\n"
                    . '}' . "\r\n"
                    . '.hr {' . "\r\n"
                    . 'background: #eeeeee;' . "\r\n"
                    . '}' . "\r\n"
                    . '.attachment-32x32 {' . "\r\n"
                    . 'margin-top: 5px;' . "\r\n"
                    . '}' . "\r\n"
                    . 'ul {' . "\r\n"
                    . 'padding-left: 10px;' . "\r\n"
                    . '}' . "\r\n"
                    . 'li {' . "\r\n"
                    . 'padding-left: 5px;' . "\r\n"
                    . '}' . "\r\n",
                    'top' => '20',
                    'left' => '20',
                    'right' => '20',
                    'width' => '100%',
                    'height' => 'max',
                    'value' => '<table cellpadding="5">' . "\r\n"
                    . '<tr class="header"><td></td><td class="item">Item</td><td align="center">Quantity</td><td align="center">Unit Price</td><td align="center">Subtotal</td></tr>' . "\r\n"
                    . '[e2pdf-foreach shortcode="e2pdf-wc-cart" key="get_cart"]' . "\r\n"
                    . '<tr>' . "\r\n"
                    . '<td>[e2pdf-wc-product key="get_image" size="32x32" index="[e2pdf-foreach-index]" order="true" wc_filter="true"]' . "\r\n"
                    . '</td>' . "\r\n"
                    . '<td class="item">' . "\r\n"
                    . '[e2pdf-wc-product key="get_name" index="[e2pdf-foreach-index]" order="true" wc_filter="true"]' . "\r\n"
                    . '[e2pdf-foreach-1 shortcode="e2pdf-wc-product" key="get_formatted_meta_data" index="[e2pdf-foreach-index]" order="true"]' . "\r\n"
                    . '<div class="formatted-meta-data">[e2pdf-wc-product key="get_formatted_meta_data" index="[e2pdf-foreach-index]" path="[e2pdf-foreach-key-1].display_key"]: [e2pdf-wc-product key="get_formatted_meta_data" index="[e2pdf-foreach-index]" path="[e2pdf-foreach-key-1].display_value"]</div>' . "\r\n"
                    . '[/e2pdf-foreach-1]' . "\r\n"
                    . '</td>' . "\r\n"
                    . '<td align="center">[e2pdf-wc-product key="get_quantity" index="[e2pdf-foreach-index]" order="true"]</td>' . "\r\n"
                    . '<td align="center">[e2pdf-wc-product key="get_product_price" index="[e2pdf-foreach-index]" order="true" wc_filter="true"]</td>' . "\r\n"
                    . '<td align="center">[e2pdf-wc-product key="get_subtotal" index="[e2pdf-foreach-index]" order="true" wc_filter="true"]</td>' . "\r\n"
                    . '</tr>' . "\r\n"
                    . '[/e2pdf-foreach]' . "\r\n"
                    . '<tr><td colspan="5"><hr class="hr"></td></tr>' . "\r\n"
                    . '[e2pdf-foreach shortcode="e2pdf-wc-cart" key="get_formatted_cart_totals"]' . "\r\n"
                    . '<tr><td colspan="3" class="total-label">[e2pdf-wc-cart key="get_formatted_cart_totals" path="[e2pdf-foreach-key].label"]:</td><td colspan="2" class="total-value">[e2pdf-wc-cart key="get_formatted_cart_totals" path="[e2pdf-foreach-key].value"]</td></tr>' . "\r\n"
                    . '[/e2pdf-foreach]' . "\r\n"
                    . '</table>' . "\r\n",
                ),
            );
        } elseif ($this->get('item') == 'shop_order') {
            $elements[] = array(
                'type' => 'e2pdf-html',
                'block' => true,
                'properties' => array(
                    'top' => '20',
                    'left' => '20',
                    'right' => '20',
                    'width' => '100%',
                    'height' => 'auto',
                    'value' => '<h2>[e2pdf-wc-order key="get_order_number"]</h2>' . "\r\n",
                ),
            );

            $elements[] = array(
                'type' => 'e2pdf-html',
                'block' => true,
                'properties' => array(
                    'top' => '5',
                    'left' => '20',
                    'right' => '20',
                    'width' => '100%',
                    'height' => 'auto',
                    'value' => 'Payment via [e2pdf-wc-order key="get_payment_method_title"]' . "\r\n",
                ),
            );

            $elements[] = array(
                'type' => 'e2pdf-html',
                'block' => true,
                'properties' => array(
                    'top' => '20',
                    'left' => '20',
                    'right' => '20',
                    'width' => '100%',
                    'height' => $line_height * 3,
                    'value' => 'Order ID: [e2pdf-wc-order key="get_id"]<br>'
                    . 'Date Created: [e2pdf-wc-order key="get_date_created"]<br>' . "\r\n"
                    . 'Status: [e2pdf-wc-order key="get_status" wc_get_order_status_name="true"]' . "\r\n",
                ),
            );

            $elements[] = array(
                'type' => 'e2pdf-html',
                'block' => true,
                'properties' => array(
                    'top' => '20',
                    'left' => '20',
                    'right' => '20',
                    'width' => '50%',
                    'height' => 'auto',
                    'value' => '<strong>Billing:</strong>',
                ),
            );

            $elements[] = array(
                'type' => 'e2pdf-html',
                'block' => true,
                'float' => true,
                'properties' => array(
                    'top' => '20',
                    'left' => '20',
                    'right' => '20',
                    'width' => '50%',
                    'height' => 'auto',
                    'value' => '<strong>Shipping:</strong>',
                ),
            );

            $elements[] = array(
                'type' => 'e2pdf-html',
                'block' => true,
                'properties' => array(
                    'top' => '5',
                    'left' => '20',
                    'right' => '20',
                    'width' => '50%',
                    'height' => $line_height * 7,
                    'value' => '[e2pdf-wc-order key="get_formatted_billing_address"]',
                ),
            );

            $elements[] = array(
                'type' => 'e2pdf-html',
                'block' => true,
                'float' => true,
                'properties' => array(
                    'top' => '5',
                    'left' => '20',
                    'right' => '20',
                    'width' => '50%',
                    'height' => $line_height * 7,
                    'value' => '[e2pdf-wc-order key="get_formatted_shipping_address"]',
                ),
            );

            $elements[] = array(
                'type' => 'e2pdf-html',
                'block' => true,
                'properties' => array(
                    'top' => '10',
                    'left' => '20',
                    'right' => '20',
                    'width' => '50%',
                    'height' => 'auto',
                    'value' => '<strong>E-mail:</strong>',
                ),
            );

            $elements[] = array(
                'type' => 'e2pdf-html',
                'block' => true,
                'float' => true,
                'properties' => array(
                    'top' => '10',
                    'left' => '20',
                    'right' => '20',
                    'width' => '50%',
                    'height' => 'auto',
                    'value' => '<strong>Phone:</strong>',
                ),
            );

            $elements[] = array(
                'type' => 'e2pdf-html',
                'block' => true,
                'properties' => array(
                    'top' => '5',
                    'left' => '20',
                    'right' => '20',
                    'width' => '50%',
                    'height' => 'auto',
                    'value' => '[e2pdf-wc-order key="get_billing_email"]',
                ),
            );

            $elements[] = array(
                'type' => 'e2pdf-html',
                'block' => true,
                'float' => true,
                'properties' => array(
                    'top' => '5',
                    'left' => '20',
                    'right' => '20',
                    'width' => '50%',
                    'height' => 'auto',
                    'value' => '[e2pdf-wc-order key="get_billing_phone"]',
                ),
            );

            $elements[] = array(
                'type' => 'e2pdf-html',
                'block' => true,
                'properties' => array(
                    'wysiwyg_disable' => '1',
                    'css' => 'td {' . "\r\n"
                    . 'text-align: center;' . "\r\n"
                    . 'vertical-align: top;' . "\r\n"
                    . '}' . "\r\n"
                    . '.header {' . "\r\n"
                    . 'background: #eeeeee;' . "\r\n"
                    . '}' . "\r\n"
                    . '.item {' . "\r\n"
                    . 'text-align: left;' . "\r\n"
                    . 'width: 40%;' . "\r\n"
                    . '}' . "\r\n"
                    . '.total-label {' . "\r\n"
                    . 'text-align: right;' . "\r\n"
                    . '}' . "\r\n"
                    . '.total-value {' . "\r\n"
                    . 'text-align: right;' . "\r\n"
                    . '}' . "\r\n"
                    . '.total-order {' . "\r\n"
                    . 'font-weight: bold;' . "\r\n"
                    . '}' . "\r\n"
                    . '.formatted-meta-data {' . "\r\n"
                    . 'color: #555555;' . "\r\n"
                    . 'font-size: 8px;' . "\r\n"
                    . '}' . "\r\n"
                    . '.hr {' . "\r\n"
                    . 'background-color: #eeeeee;' . "\r\n"
                    . '}' . "\r\n"
                    . '.attachment-32x32 {' . "\r\n"
                    . 'margin-top: 5px;' . "\r\n"
                    . '}' . "\r\n"
                    . 'ul {' . "\r\n"
                    . 'padding-left: 10px;' . "\r\n"
                    . '}' . "\r\n"
                    . 'li {' . "\r\n"
                    . 'padding-left: 5px;' . "\r\n"
                    . '}' . "\r\n",
                    'top' => '20',
                    'left' => '20',
                    'right' => '20',
                    'width' => '100%',
                    'height' => 'max',
                    'value' => '<table cellpadding="5">' . "\r\n"
                    . '<tr class="header"><td class="item">Item</td><td align="center">Quantity</td><td align="center">Unit Price</td><td align="center">Subtotal</td><td align="center">Total</td></tr>' . "\r\n"
                    . '[e2pdf-foreach shortcode="e2pdf-wc-order" key="get_items"]' . "\r\n"
                    . '<tr>' . "\r\n"
                    . '<td class="item">' . "\r\n"
                    . '[e2pdf-wc-product key="get_name" index="[e2pdf-foreach-index]" order="true"]' . "\r\n"
                    . '[e2pdf-foreach-1 shortcode="e2pdf-wc-product" key="get_formatted_meta_data" index="[e2pdf-foreach-index]"]' . "\r\n"
                    . '<div class="formatted-meta-data">[e2pdf-wc-product key="get_formatted_meta_data" index="[e2pdf-foreach-index]" path="[e2pdf-foreach-key-1].display_key"]: [e2pdf-wc-product key="get_formatted_meta_data" index="[e2pdf-foreach-index]" path="[e2pdf-foreach-key-1].display_value"]</div>' . "\r\n"
                    . '[/e2pdf-foreach-1]' . "\r\n"
                    . '</td>' . "\r\n"
                    . '<td align="center">[e2pdf-wc-product key="get_quantity" index="[e2pdf-foreach-index]" order="true"]</td>' . "\r\n"
                    . '<td align="center">[e2pdf-wc-product key="get_item_subtotal" index="[e2pdf-foreach-index]" order="true" wc_price="true"]</td>' . "\r\n"
                    . '<td align="center">[e2pdf-wc-product key="get_subtotal" index="[e2pdf-foreach-index]" order="true" wc_price="true"]</td>' . "\r\n"
                    . '<td align="center">[e2pdf-wc-product key="get_total" index="[e2pdf-foreach-index]" order="true" wc_price="true"]</td>' . "\r\n"
                    . '</tr>' . "\r\n"
                    . '[/e2pdf-foreach]' . "\r\n"
                    . '<tr><td colspan="5"><hr class="hr"></td></tr>' . "\r\n"
                    . '[e2pdf-foreach shortcode="e2pdf-wc-order" key="get_order_item_totals"]' . "\r\n"
                    . '<tr>' . "\r\n"
                    . '<td colspan="3" class="total-label">[e2pdf-wc-order key="get_order_item_totals" path="[e2pdf-foreach-key].label"]</td>' . "\r\n"
                    . '<td colspan="2" class="total-value">[e2pdf-wc-order key="get_order_item_totals" path="[e2pdf-foreach-key].value"]</td>' . "\r\n"
                    . '</tr>' . "\r\n"
                    . '[/e2pdf-foreach]' . "\r\n"
                    . '</table>' . "\r\n",
                ),
            );
        } elseif ($this->get('item') == 'product' || $this->get('item') == 'product_variation') {

            $elements[] = array(
                'type' => 'e2pdf-html',
                'block' => true,
                'float' => true,
                'properties' => array(
                    'top' => '20',
                    'left' => '20',
                    'right' => '20',
                    'width' => '100%',
                    'height' => 'auto',
                    'value' => '<hr>',
                ),
            );

            $elements[] = array(
                'type' => 'e2pdf-image',
                'properties' => array(
                    'top' => '20',
                    'width' => '110',
                    'height' => '110',
                    'value' => '[e2pdf-wc-product key="get_image"]',
                    'dimension' => '1',
                    'vertical' => 'top',
                    'horizontal' => 'center',
                ),
            );

            if ($this->get('item') == 'product_variation') {

                $elements[] = array(
                    'type' => 'e2pdf-html',
                    'float' => true,
                    'properties' => array(
                        'left' => '20',
                        'right' => '110',
                        'height' => '110',
                        'width' => '100%',
                        'value' => '<h2>[e2pdf-wc-product key="get_name"]</h2><br>' . "\r\n"
                        . '<div>Price: [e2pdf-wc-product key="get_price" wc_price="true"]</div>' . "\r\n",
                    ),
                );

                $elements[] = array(
                    'type' => 'e2pdf-html',
                    'block' => true,
                    'float' => true,
                    'properties' => array(
                        'top' => '20',
                        'left' => '20',
                        'right' => '20',
                        'width' => '100%',
                        'height' => 'max',
                        'wysiwyg_disable' => '1',
                        'value' =>
                        '<table cellpadding="5">' . "\r\n"
                        . '<tr><td colspan="2"><b>Additional Information:</b></td></tr>' . "\r\n"
                        . '[e2pdf-foreach shortcode="e2pdf-wc-product" key="get_attributes" wc_filter="true"]' . "\r\n"
                        . '<tr>' . "\r\n"
                        . '<td>[e2pdf-wc-product key="get_attributes" path="[e2pdf-foreach-key].label" wc_filter="true"]:</td>' . "\r\n"
                        . '<td>[e2pdf-wc-product key="get_attributes" path="[e2pdf-foreach-key].value" wc_filter="true"]</td>' . "\r\n"
                        . '</tr>' . "\r\n"
                        . '[/e2pdf-foreach]' . "\r\n"
                        . '</table>' . "\r\n"
                        . '<table cellpadding="5">' . "\r\n"
                        . '<tr><td><b>Description</b></td></tr>' . "\r\n"
                        . '<tr><td>[e2pdf-wc-product key="get_description" wc_format_content="true"]</td></tr>' . "\r\n"
                        . '</table>' . "\r\n",
                    ),
                );
            } else {

                $elements[] = array(
                    'type' => 'e2pdf-html',
                    'float' => true,
                    'properties' => array(
                        'left' => '20',
                        'right' => '110',
                        'height' => '110',
                        'width' => '100%',
                        'value' => '<h2>[e2pdf-wc-product key="get_name"]</h2><br>' . "\r\n"
                        . '<div>Price: [e2pdf-wc-product key="get_price" wc_price="true"]</div>' . "\r\n",
                    ),
                );

                $elements[] = array(
                    'type' => 'e2pdf-html',
                    'block' => true,
                    'float' => true,
                    'properties' => array(
                        'top' => '20',
                        'left' => '20',
                        'right' => '20',
                        'width' => '100%',
                        'height' => 'max',
                        'wysiwyg_disable' => '1',
                        'value' =>
                        '<table cellpadding="5">' . "\r\n"
                        . '<tr><td colspan="2"><b>Additional Information:</b></td></tr>' . "\r\n"
                        . '[e2pdf-foreach shortcode="e2pdf-wc-product" key="get_attributes" wc_filter="true"]' . "\r\n"
                        . '<tr>' . "\r\n"
                        . '<td>[e2pdf-wc-product key="get_attributes" path="[e2pdf-foreach-key].label" wc_filter="true"]:</td>' . "\r\n"
                        . '<td>[e2pdf-wc-product key="get_attributes" path="[e2pdf-foreach-key].value" wc_filter="true"]</td>' . "\r\n"
                        . '</tr>' . "\r\n"
                        . '[/e2pdf-foreach]' . "\r\n"
                        . '</table>' . "\r\n"
                        . '<table cellpadding="5">' . "\r\n"
                        . '<tr><td><b>Short Description</b></td></tr>' . "\r\n"
                        . '<tr><td>[e2pdf-wc-product key="get_short_description" wc_format_content="true"]</td></tr>' . "\r\n"
                        . '</table>' . "\r\n",
                    ),
                );
            }
        }

        $response['page'] = array(
            'bottom' => '20',
            'top' => '20',
            'left' => '20',
            'right' => '20',
        );

        $response['elements'] = $elements;
        return $response;
    }

    public function filter_content_custom($content) {
        $content = $this->filter_content($content);
        return $content;
    }

    public function filter_the_content($content, $post_id = false) {
        $content = $this->filter_content($content, $post_id);
        return $content;
    }

    /**
     * Search and update shortcodes for this extension inside content
     * Auto set of dataset id
     * @param string $content - Content
     * @param string $post_id - Custom Post ID
     * @return string - Content with updated shortcodes
     */
    public function filter_content($content, $post_id = false, $download = false, $wp_reset_postdata = true) {
        global $post;
        if (!is_string($content) || false === strpos($content, '[')) {
            return $content;
        }
        $shortcode_tags = array(
            'e2pdf-download',
            'e2pdf-save',
            'e2pdf-view',
            'e2pdf-adobesign',
            'e2pdf-zapier',
        );
        preg_match_all('@\[([^<>&/\[\]\x00-\x20=]++)@', $content, $matches);
        $tagnames = array_intersect($shortcode_tags, $matches[1]);
        if (!empty($tagnames)) {
            preg_match_all('/' . $this->helper->load('shortcode')->get_shortcode_regex($tagnames) . '/', $content, $shortcodes);
            foreach ($shortcodes[0] as $key => $shortcode_value) {
                $shortcode = $this->helper->load('shortcode')->get_shortcode($shortcodes, $key);
                $atts = shortcode_parse_atts($shortcode[3]);
                if (isset($atts['wp_reset_postdata'])) {
                    if ($atts['wp_reset_postdata'] == 'true') {
                        $wp_reset_postdata = true;
                    } else {
                        $wp_reset_postdata = false;
                    }
                }
                if ($wp_reset_postdata) {
                    wp_reset_postdata();
                }
                if ($this->helper->load('shortcode')->is_attachment($shortcode, $atts)) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
                } else {
                    if (isset($atts['id'])) {
                        $template = new Model_E2pdf_Template();
                        $template->load($atts['id']);
                        if ($template->get('extension') === 'wordpress' || $template->get('extension') === 'jetformbuilder') { // phpcs:ignore WordPress.WP.CapitalPDangit.Misspelled
                            if (!$download) {
                                continue;
                            }
                        } elseif ($template->get('extension') === 'gravity') {
                            if (!isset($atts['dataset'])) {
                                if (function_exists('wc_get_order') && isset($_GET['order'])) {
                                    $order_id = wc_get_order_id_by_order_key(wc_clean(wp_unslash($_GET['order'])));
                                    if ($order_id) {
                                        $item_id = isset($_GET['item_id']) && $_GET['item_id'] ? wc_clean(wp_unslash($_GET['item_id'])) : false;
                                        if ($item_id) {
                                            $order = wc_get_order($order_id);
                                            if ($order && method_exists($order, 'get_items')) {
                                                $order_items = $order->get_items();
                                                if (isset($order_items[$item_id])) {
                                                    $linked_entry_id = false;
                                                    if ($linked_data = wc_get_order_item_meta($item_id, '_gravity_forms_history', true)) {
                                                        if (!empty($linked_data['_gravity_form_linked_entry_id'])) {
                                                            $linked_entry_id = $linked_data['_gravity_form_linked_entry_id'];
                                                        }
                                                    } elseif ($linked_data = wc_get_order_item_meta($item_id, 'gspc_gf_entry_ids', true)) {
                                                        if (!empty($linked_data) && isset($linked_data[0])) {
                                                            $linked_entry_id = $linked_data[0];
                                                        }
                                                    }
                                                    if ($linked_entry_id) {
                                                        $atts['dataset'] = $linked_entry_id;
                                                        $shortcode[3] .= ' dataset="' . $linked_entry_id . '"';
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        } elseif ($template->get('extension') === 'formidable') {
                            if (!isset($atts['dataset'])) {
                                if (function_exists('wc_get_order') && isset($_GET['order'])) {
                                    $order_id = wc_get_order_id_by_order_key(wc_clean(wp_unslash($_GET['order'])));
                                    if ($order_id) {
                                        $item_id = isset($_GET['item_id']) && $_GET['item_id'] ? wc_clean(wp_unslash($_GET['item_id'])) : false;
                                        if ($item_id) {
                                            $order = wc_get_order($order_id);
                                            if ($order && method_exists($order, 'get_items')) {
                                                $order_items = $order->get_items();
                                                if (isset($order_items[$item_id])) {
                                                    $_formidable_form_data = wc_get_order_item_meta($item_id, '_formidable_form_data', true);
                                                    if ($_formidable_form_data) {
                                                        $atts['dataset'] = $_formidable_form_data;
                                                        $shortcode[3] .= ' dataset="' . $_formidable_form_data . '"';
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        } elseif ($template->get('extension') === 'woocommerce') {
                            if (!isset($atts['dataset']) && $template->get('item') == 'shop_order' && function_exists('wc_get_order') && isset($_GET['order'])) {
                                $order_id = wc_get_order_id_by_order_key(wc_clean(wp_unslash($_GET['order'])));
                                if ($order_id) {
                                    $atts['dataset'] = $order_id;
                                    $shortcode[3] .= ' dataset="' . $order_id . '"';
                                }
                            }
                            if (!isset($atts['dataset']) && ($post_id || isset($post->ID))) {
                                $dataset_id = $post_id ? $post_id : $post->ID;
                                $atts['dataset'] = $dataset_id;
                                $shortcode[3] .= ' dataset="' . $dataset_id . '"';
                            }
                            if (($template->get('item') == 'product' || $template->get('item') == 'product_variation') && function_exists('wc_get_order') && isset($_GET['order'])) {
                                $order_id = wc_get_order_id_by_order_key(wc_clean(wp_unslash($_GET['order'])));
                                if ($order_id) {
                                    $atts['wc_order_id'] = $order_id;
                                    $shortcode[3] .= ' wc_order_id="' . $order_id . '"';

                                    $item_id = isset($_GET['item_id']) && $_GET['item_id'] ? wc_clean(wp_unslash($_GET['item_id'])) : false;
                                    if ($item_id) {
                                        $order = wc_get_order($order_id);
                                        if ($order && method_exists($order, 'get_items')) {
                                            $order_items = $order->get_items();
                                            if (isset($order_items[$item_id])) {
                                                $atts['wc_product_item_id'] = $item_id;
                                                $shortcode[3] .= ' wc_product_item_id="' . $item_id . '"';
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    if (!isset($atts['apply'])) {
                        $shortcode[3] .= ' apply="true"';
                    }
                    if (!isset($atts['filter'])) {
                        $shortcode[3] .= ' filter="true"';
                    }
                    if ($download) {
                        $site_url = str_replace(array('http:', 'https:'), array('', ''), $this->helper->load('translator')->translate_url($this->helper->get_frontend_site_url()));
                        $shortcode[3] .= ' output="url" site_url="' . $site_url . '" esc_url_raw="true" wc_product_download="true"';
                    }
                    $content = str_replace($shortcode_value, do_shortcode_tag($shortcode), $content);
                }
            }
        }
        return $content;
    }

    /**
     * Add options for WooCommerce extension
     * @param array $options - List of options
     * @return array - Updated options list
     */
    public function filter_e2pdf_model_options_get_options_options($options = array()) {
        global $wpdb;

        $model_e2pdf_template = new Model_E2pdf_Template();

        $order_templates = array(
            '0' => __('--- Select ---', 'e2pdf'),
        );
        $templates = $wpdb->get_results($wpdb->prepare('SELECT * FROM `' . $model_e2pdf_template->get_table() . '` WHERE extension = %s AND item = %s AND activated = %s ORDER BY ID ASC', 'woocommerce', 'shop_order', '1'), ARRAY_A);
        if (!empty($templates)) {
            foreach ($templates as $template) {
                $order_templates[$template['ID']] = $template['title'];
            }
        }

        $cart_templates = array(
            '0' => __('--- Select ---', 'e2pdf'),
        );
        $templates = $wpdb->get_results($wpdb->prepare('SELECT * FROM `' . $model_e2pdf_template->get_table() . '` WHERE extension = %s AND item = %s AND activated = %s ORDER BY ID ASC', 'woocommerce', 'cart', '1'), ARRAY_A);

        if (!empty($templates)) {
            foreach ($templates as $template) {
                $cart_templates[$template['ID']] = $template['title'];
            }
        }

        $options['woocommerce_group'] = array(
            'name' => 'WooCommerce',
            'action' => 'extension',
            'group' => 'woocommerce_group',
            'options' => array(
                array(
                    'header' => __('User Order List', 'e2pdf'),
                    'name' => __('Template', 'e2pdf'),
                    'key' => 'e2pdf_wc_my_orders_actions_template_id',
                    'value' => get_option('e2pdf_wc_my_orders_actions_template_id', '0'),
                    'default_value' => '0',
                    'type' => 'select',
                    'options' => $order_templates,
                ),
                array(
                    'name' => __('Order Status', 'e2pdf'),
                    'key' => 'e2pdf_wc_my_orders_actions_template_id_status',
                    'type' => 'checkbox_list',
                    'options' => $this->get_status_options('e2pdf_wc_my_orders_actions_template_id'),
                ),
                array(
                    'name' => __('Priority', 'e2pdf'),
                    'key' => 'e2pdf_wc_my_orders_actions_template_id_priority',
                    'value' => get_option('e2pdf_wc_my_orders_actions_template_id_priority', '10'),
                    'default_value' => '10',
                    'type' => 'text',
                    'class' => 'e2pdf-numbers',
                    'placeholder' => '0',
                ),
                array(
                    'header' => __('User Order Details', 'e2pdf'),
                    'name' => __('Template', 'e2pdf'),
                    'key' => 'e2pdf_wc_order_details_template_id',
                    'value' => get_option('e2pdf_wc_order_details_template_id', '0'),
                    'default_value' => '0',
                    'type' => 'select',
                    'options' => $order_templates,
                ),
                array(
                    'name' => __('Order Status', 'e2pdf'),
                    'key' => 'e2pdf_wc_order_details_template_id_status',
                    'type' => 'checkbox_list',
                    'options' => $this->get_status_options('e2pdf_wc_order_details_template_id'),
                ),
                array(
                    'name' => __('Hook', 'e2pdf'),
                    'key' => 'e2pdf_wc_order_details_template_id_hook',
                    'value' => get_option('e2pdf_wc_order_details_template_id_hook', 'woocommerce_order_details_before_order_table'),
                    'default_value' => 'woocommerce_order_details_before_order_table',
                    'type' => 'select',
                    'options' => array(
                        'woocommerce_order_details_before_order_table' => 'woocommerce_order_details_before_order_table',
                        'woocommerce_order_details_before_order_table_items' => 'woocommerce_order_details_before_order_table_items',
                        'woocommerce_order_details_after_order_table_items' => 'woocommerce_order_details_after_order_table_items',
                        'woocommerce_order_details_after_order_table' => 'woocommerce_order_details_after_order_table',
                        'woocommerce_after_order_details' => 'woocommerce_after_order_details',
                    ),
                ),
                array(
                    'name' => __('Priority', 'e2pdf'),
                    'key' => 'e2pdf_wc_order_details_template_id_priority',
                    'value' => get_option('e2pdf_wc_order_details_template_id_priority', '10'),
                    'default_value' => '10',
                    'type' => 'text',
                    'class' => 'e2pdf-numbers',
                    'placeholder' => '0',
                ),
                array(
                    'header' => __('User Cart', 'e2pdf'),
                    'name' => __('Template', 'e2pdf'),
                    'key' => 'e2pdf_wc_cart_template_id',
                    'value' => get_option('e2pdf_wc_cart_template_id', '0'),
                    'default_value' => '0',
                    'type' => 'select',
                    'options' => $cart_templates,
                ),
                array(
                    'name' => __('Priority', 'e2pdf'),
                    'key' => 'e2pdf_wc_cart_template_id_priority',
                    'value' => get_option('e2pdf_wc_cart_template_id_priority', '10'),
                    'default_value' => '10',
                    'type' => 'text',
                    'class' => 'e2pdf-numbers',
                    'placeholder' => '0',
                ),
                array(
                    'header' => __('User Checkout', 'e2pdf'),
                    'name' => __('Template', 'e2pdf'),
                    'key' => 'e2pdf_wc_checkout_template_id',
                    'value' => get_option('e2pdf_wc_checkout_template_id', '0'),
                    'default_value' => '0',
                    'type' => 'select',
                    'options' => $cart_templates,
                ),
                array(
                    'name' => __('Hook', 'e2pdf'),
                    'key' => 'e2pdf_wc_checkout_template_id_hook',
                    'value' => get_option('e2pdf_wc_checkout_template_id_hook', 'woocommerce_review_order_before_submit'),
                    'default_value' => 'woocommerce_review_order_before_submit',
                    'type' => 'select',
                    'options' => array(
                        'woocommerce_review_order_before_submit' => 'woocommerce_review_order_before_submit',
                        'woocommerce_review_order_after_submit' => 'woocommerce_review_order_after_submit',
                    ),
                ),
                array(
                    'name' => __('Priority', 'e2pdf'),
                    'key' => 'e2pdf_wc_checkout_template_id_priority',
                    'value' => get_option('e2pdf_wc_checkout_template_id_priority', '10'),
                    'default_value' => '10',
                    'type' => 'text',
                    'class' => 'e2pdf-numbers',
                    'placeholder' => '0',
                ),
            ),
        );

        /* Deprecate and move to Hooks */
        //if (get_option('e2pdf_wc_admin_order_actions_template_id', '0')) {
        $options['woocommerce_group']['options'][] = array(
            'header' => __('Admin Order List', 'e2pdf'),
            'name' => __('Template', 'e2pdf'),
            'key' => 'e2pdf_wc_admin_order_actions_template_id',
            'value' => get_option('e2pdf_wc_admin_order_actions_template_id', '0'),
            'default_value' => '0',
            'type' => 'select',
            'options' => $order_templates,
        );
        $options['woocommerce_group']['options'][] = array(
            'name' => __('Hook', 'e2pdf'),
            'key' => 'e2pdf_wc_admin_order_actions_template_id_hook',
            'value' => get_option('e2pdf_wc_admin_order_actions_template_id_hook', 'woocommerce_admin_order_actions_end'),
            'default_value' => 'woocommerce_admin_order_actions_end',
            'type' => 'select',
            'options' => array(
                'woocommerce_admin_order_actions_start' => 'woocommerce_admin_order_actions_start',
                'woocommerce_admin_order_actions_end' => 'woocommerce_admin_order_actions_end',
            ),
        );
        $options['woocommerce_group']['options'][] = array(
            'name' => __('Order Status', 'e2pdf'),
            'key' => 'e2pdf_wc_admin_order_actions_template_id_status',
            'type' => 'checkbox_list',
            'options' => $this->get_status_options('e2pdf_wc_admin_order_actions_template_id'),
        );
        $options['woocommerce_group']['options'][] = array(
            'name' => __('Priority', 'e2pdf'),
            'key' => 'e2pdf_wc_admin_order_actions_template_id_priority',
            'value' => get_option('e2pdf_wc_admin_order_actions_template_id_priority', '10'),
            'default_value' => '10',
            'type' => 'text',
            'class' => 'e2pdf-numbers',
            'placeholder' => '0',
        );
        //}
        //if (get_option('e2pdf_wc_admin_order_details_template_id', '0')) {
        $options['woocommerce_group']['options'][] = array(
            'header' => __('Admin Order Details', 'e2pdf'),
            'name' => __('Template', 'e2pdf'),
            'key' => 'e2pdf_wc_admin_order_details_template_id',
            'value' => get_option('e2pdf_wc_admin_order_details_template_id', '0'),
            'default_value' => '0',
            'type' => 'select',
            'options' => $order_templates,
        );
        $options['woocommerce_group']['options'][] = array(
            'name' => __('Order Status', 'e2pdf'),
            'key' => 'e2pdf_wc_admin_order_details_template_id_status',
            'type' => 'checkbox_list',
            'options' => $this->get_status_options('e2pdf_wc_admin_order_details_template_id'),
        );
        // }

        return $options;
    }

    public function get_status_options($key) {
        $statuses = get_option($key . '_status', array('any'));
        $order_statuses = array();
        if (function_exists('wc_get_order_statuses')) {
            $order_statuses = wc_get_order_statuses();
        }
        $status_options = array();
        foreach ($order_statuses as $order_status_key => $order_status) {
            $status_options[] = array(
                'name' => $order_status,
                'key' => $key . '_status[]',
                'value' => is_array($statuses) && in_array($order_status_key, $statuses) ? $order_status_key : '',
                'checkbox_value' => $order_status_key,
                'placeholder' => $order_status,
                'type' => 'checkbox',
                'default_value' => '',
            );
        }

        $status_options[] = array(
            'name' => 'Any',
            'key' => $key . '_status[]',
            'value' => is_array($statuses) && in_array('any', $statuses) ? 'any' : '',
            'checkbox_value' => 'any',
            'placeholder' => __('Any', 'e2pdf'),
            'type' => 'checkbox',
            'default_value' => '',
        );

        return $status_options;
    }

    public function filter_woocommerce_my_account_my_orders_actions($actions, $order) {
        $statuses = get_option('e2pdf_wc_my_orders_actions_template_id_status', array('any'));
        if (is_array($statuses) && (in_array($order->get_status(), $statuses) || in_array('wc-' . $order->get_status(), $statuses) || in_array('any', $statuses))) {
            $actions['e2pdf_invoice'] = array(
                'url' => do_shortcode('[e2pdf-download id="' . get_option('e2pdf_wc_my_orders_actions_template_id', '0') . '" dataset="' . $order->get_id() . '" output="url"]'),
                'name' => apply_filters('e2pdf_wc_my_account_my_orders_actions_invoice_title', do_shortcode('[e2pdf-download id="' . get_option('e2pdf_wc_my_orders_actions_template_id', '0') . '" dataset="' . $order->get_id() . '" output="button_title"]')),
            );
        }
        return apply_filters('e2pdf_wc_filter_woocommerce_my_account_my_orders_actions', $actions, $order);
    }

    public function filter_woocommerce_product_downloads_approved_directory_validation_for_shortcodes($enabled) {
        if (get_option('wc_downloads_approved_directories_mode') === 'enabled') {
            return false;
        }
        return $enabled;
    }

    /**
     * Verify if item and dataset exists
     * @return bool - item and dataset exists
     */
    public function verify() {
        if (
                $this->get('item') && $this->get('cached_post') &&
                (
                ($this->get('item') == get_post_type($this->get('cached_post'))) ||
                ($this->get('item') == 'cart' && $this->get('dataset') == wc_get_page_id('cart')) ||
                ($this->get('item') == 'shop_order' && get_post_type($this->get('cached_post')) == 'shop_order_placehold')
                )
        ) {
            return true;
        }
        return false;
    }

    /**
     * Init Visual Mapper data
     * @return bool|string - HTML data source for Visual Mapper
     */
    public function visual_mapper() {

        $vc = '';

        if ($this->get('item') == 'cart') {
            $vc .= '<h3 class="e2pdf-plr5">Cart</h3>';
            $vc .= '<div class="e2pdf-grid">';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Cart', 'e2pdf-wc-cart key="cart"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Cart Products', 'e2pdf-wc-cart key="get_cart"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Applied Coupons', 'e2pdf-wc-cart key="get_applied_coupons"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Cart Total', 'e2pdf-wc-cart key="get_cart_total"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Cart Subtotal', 'e2pdf-wc-cart key="get_cart_subtotal"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Cart Tax', 'e2pdf-wc-cart key="get_cart_tax"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Cart Hash', 'e2pdf-wc-cart key="get_cart_hash"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Cart Contents Total', 'e2pdf-wc-cart key="get_cart_contents_total"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Cart Contents Tax', 'e2pdf-wc-cart key="get_cart_contents_tax"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Cart Contents Taxes', 'e2pdf-wc-cart key="get_cart_contents_taxes"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Cart Contents Count', 'e2pdf-wc-cart key="get_cart_contents_count"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Cart Contents Weight', 'e2pdf-wc-cart key="get_cart_contents_weight"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Cart Item Quantities', 'e2pdf-wc-cart key="get_cart_item_quantities"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Cart Item Tax Classes', 'e2pdf-wc-cart key="get_cart_item_tax_classes"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Cart Item Tax Classes For Shipping', 'e2pdf-wc-cart key="get_cart_item_tax_classes_for_shipping"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Cart Shipping Total', 'e2pdf-wc-cart key="get_cart_shipping_total"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Coupon Discount Totals', 'e2pdf-wc-cart key="get_coupon_discount_totals"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Coupon Discount Tax Totals', 'e2pdf-wc-cart key="get_coupon_discount_tax_totals"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Totals', 'e2pdf-wc-cart key="get_totals"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Total', 'e2pdf-wc-cart key="get_total"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Total Tax', 'e2pdf-wc-cart key="get_total_tax"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Total Ex Tax', 'e2pdf-wc-cart key="get_total_ex_tax"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Total Discount', 'e2pdf-wc-cart key="get_total_discount"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Subtotal', 'e2pdf-wc-cart key="get_subtotal"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Subtotal Tax', 'e2pdf-wc-cart key="get_subtotal_tax"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Discount Total', 'e2pdf-wc-cart key="get_discount_total"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Discount Tax', 'e2pdf-wc-cart key="get_discount_tax"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Shipping Total', 'e2pdf-wc-cart key="get_shipping_total"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Shipping Tax', 'e2pdf-wc-cart key="get_shipping_tax"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Shipping Taxes', 'e2pdf-wc-cart key="get_shipping_taxes"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Fees', 'e2pdf-wc-cart key="get_fees"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Fee Total', 'e2pdf-wc-cart key="get_fee_total"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Fee Tax', 'e2pdf-wc-cart key="get_fee_tax"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Fee Taxes', 'e2pdf-wc-cart key="get_fee_taxes"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Displayed Subtotal', 'e2pdf-wc-cart key="get_displayed_subtotal"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Tax Price Display Mode', 'e2pdf-wc-cart key="get_tax_price_display_mode"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Taxes', 'e2pdf-wc-cart key="get_taxes"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Taxes Total', 'e2pdf-wc-cart key="get_taxes_total"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Taxes Total', 'e2pdf-wc-cart key="get_shipping_method_title"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Taxes Total', 'e2pdf-wc-cart key="get_payment_method_title"') . '</div>';
            $vc .= '</div>';

            $vc .= '<h3 class="e2pdf-plr5">Cart Common</h3>';
            $vc .= '<div class="e2pdf-grid">';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('ID', 'e2pdf-wc-cart key="id"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Author', 'e2pdf-wc-cart key="post_author"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Author ID', 'e2pdf-wc-cart key="post_author_id"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Date', 'e2pdf-wc-cart key="post_date"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Date (GMT)', 'e2pdf-wc-cart key="post_date_gmt"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Content', 'e2pdf-wc-cart key="post_content"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Title', 'e2pdf-wc-cart key="post_title"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Excerpt', 'e2pdf-wc-cart key="post_excerpt"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Status', 'e2pdf-wc-cart key="post_status"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Comment Status', 'e2pdf-wc-cart key="comment_status"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Ping Status', 'e2pdf-wc-cart key="ping_status"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Password', 'e2pdf-wc-cart key="post_password"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Name', 'e2pdf-wc-cart key="post_name"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('To Ping', 'e2pdf-wc-cart key="to_ping"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Ping', 'e2pdf-wc-cart key="pinged"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Modified Date', 'e2pdf-wc-cart key="post_modified"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Modified Date (GMT)', 'e2pdf-wc-cart key="post_modified_gmt"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Filtered Content', 'e2pdf-wc-cart key="post_content_filtered"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Parent ID', 'e2pdf-wc-cart key="post_parent"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('GUID', 'e2pdf-wc-cart key="guid"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Menu Order', 'e2pdf-wc-cart key="menu_order"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Type', 'e2pdf-wc-cart key="post_type"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Mime Type', 'e2pdf-wc-cart key="post_mime_type"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Comments Count', 'e2pdf-wc-cart key="comment_count"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Filter', 'e2pdf-wc-cart key="filter"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Post Thumbnail', 'e2pdf-wc-cart key="get_the_post_thumbnail"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Post Thumbnail URL', 'e2pdf-wc-cart key="get_the_post_thumbnail_url"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Permalink', 'e2pdf-wc-cart key="permalink"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Post Permalink', 'e2pdf-wc-cart key="get_post_permalink"') . '</div>';
            $vc .= '</div>';

            $vc .= '<h3 class="e2pdf-plr5">Cart Special Shortcodes</h3>';
            $vc .= '<div class="e2pdf-grid">';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Cart Products', 'e2pdf-foreach shortcode="e2pdf-wc-cart" key="get_cart"][e2pdf-wc-product key="get_name" index="[e2pdf-foreach-index]" order="true" wc_filter="true"]' . "\r\n" . '[/e2pdf-foreach') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Cart Totals', 'e2pdf-foreach shortcode="e2pdf-wc-cart" key="get_formatted_cart_totals"][e2pdf-wc-cart key="get_formatted_cart_totals" path="[e2pdf-foreach-key].label"]:[e2pdf-wc-cart key="get_formatted_cart_totals" path="[e2pdf-foreach-key].value"]' . "\r\n" . '[/e2pdf-foreach') . '</div>';
            $vc .= '</div>';

            if (function_exists('wc_get_page_id')) {
                $meta_keys = $this->get_post_meta_keys(false, wc_get_page_id('cart'));
                if (!empty($meta_keys)) {
                    $vc .= '<h3 class="e2pdf-plr5">Cart Meta Keys</h3>';
                    $vc .= '<div class="e2pdf-grid">';
                    $i = 0;
                    foreach ($meta_keys as $meta_key) {
                        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element($meta_key, 'e2pdf-wc-cart key="' . $meta_key . '" meta="true"') . '</div>';
                        $i++;
                    }
                    $vc .= '</div>';
                }
            }
            $vc .= $this->get_vm_product_order();
            $vc .= $this->get_vm_product();
            $vc .= '<h3 class="e2pdf-plr5">Customer</h3>';
            $vc .= '<div class="e2pdf-grid">';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Taxable Address', 'e2pdf-wc-customer key="get_taxable_address"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Is Vat Exempt', 'e2pdf-wc-customer key="is_vat_exempt"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Is Vat Exempt', 'e2pdf-wc-customer key="get_is_vat_exempt"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Has Calculated Shipping', 'e2pdf-wc-customer key="has_calculated_shipping"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Calculated Shipping', 'e2pdf-wc-customer key="get_calculated_shipping"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Avatar Url', 'e2pdf-wc-customer key="get_avatar_url"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Username', 'e2pdf-wc-customer key="get_username"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('E-mail', 'e2pdf-wc-customer key="get_email"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('First Name', 'e2pdf-wc-customer key="get_first_name"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Last Name', 'e2pdf-wc-customer key="get_last_name"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Display Name', 'e2pdf-wc-customer key="get_display_name"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Role', 'e2pdf-wc-customer key="get_role"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Date Created', 'e2pdf-wc-customer key="get_date_created"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Date Modified', 'e2pdf-wc-customer key="get_date_modified"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Billing', 'e2pdf-wc-customer key="get_billing"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Billing First Name', 'e2pdf-wc-customer key="get_billing_first_name"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Billing Last Name', 'e2pdf-wc-customer key="get_billing_last_name"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Billing Company', 'e2pdf-wc-customer key="get_billing_company"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Billing Address', 'e2pdf-wc-customer key="get_billing_address"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Billing Address 1', 'e2pdf-wc-customer key="get_billing_address_1"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Billing Address 2', 'e2pdf-wc-customer key="get_billing_address_2"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Billing City', 'e2pdf-wc-customer key="get_billing_city"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Billing State', 'e2pdf-wc-customer key="get_billing_state"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Billing Postcode', 'e2pdf-wc-customer key="get_billing_postcode"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Billing E-mail', 'e2pdf-wc-customer key="get_billing_email"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Billing Phone', 'e2pdf-wc-customer key="get_billing_phone"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Shipping', 'e2pdf-wc-customer key="get_shipping"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Shipping First Name', 'e2pdf-wc-customer key="get_shipping_first_name"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Shipping Last Name', 'e2pdf-wc-customer key="get_shipping_last_name"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Shipping Company', 'e2pdf-wc-customer key="get_shipping_company"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Shipping Address', 'e2pdf-wc-customer key="get_shipping_address"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Shipping Address 1', 'e2pdf-wc-customer key="get_shipping_address_1"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Shipping Address 2', 'e2pdf-wc-customer key="get_shipping_address_2"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Shipping City', 'e2pdf-wc-customer key="get_shipping_city"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Shipping State', 'e2pdf-wc-customer key="get_shipping_state"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Shipping Postcode', 'e2pdf-wc-customer key="get_shipping_postcode"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Shipping Country', 'e2pdf-wc-customer key="get_shipping_country"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Is Paying Customer', 'e2pdf-wc-customer key="get_is_paying_customer"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Formatted Shipping Address', 'e2pdf-wc-customer key="get_formatted_shipping_address"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Formatted Billing Address', 'e2pdf-wc-customer key="get_formatted_billing_address"') . '</div>';
            $vc .= '</div>';
        }

        if ($this->get('item') == 'shop_order') {
            $vc .= $this->get_vm_order();
            $vc .= '<h3 class="e2pdf-plr5">Order Common</h3>';
            $vc .= '<div class="e2pdf-grid">';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('ID', 'e2pdf-wc-order key="id"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Author', 'e2pdf-wc-order key="post_author"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Author ID', 'e2pdf-wc-order key="post_author_id"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Date', 'e2pdf-wc-order key="post_date"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Date (GMT)', 'e2pdf-wc-order key="post_date_gmt"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Content', 'e2pdf-wc-order key="post_content"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Title', 'e2pdf-wc-order key="post_title"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Excerpt', 'e2pdf-wc-order key="post_excerpt"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Status', 'e2pdf-wc-order key="post_status"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Comment Status', 'e2pdf-wc-order key="comment_status"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Ping Status', 'e2pdf-wc-order key="ping_status"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Password', 'e2pdf-wc-order key="post_password"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Name', 'e2pdf-wc-order key="post_name"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('To Ping', 'e2pdf-wc-order key="to_ping"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Ping', 'e2pdf-wc-order key="pinged"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Modified Date', 'e2pdf-wc-order key="post_modified"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Modified Date (GMT)', 'e2pdf-wc-order key="post_modified_gmt"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Filtered Content', 'e2pdf-wc-order key="post_content_filtered"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Parent ID', 'e2pdf-wc-order key="post_parent"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('GUID', 'e2pdf-wc-order key="guid"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Menu Order', 'e2pdf-wc-order key="menu_order"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Type', 'e2pdf-wc-order key="post_type"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Mime Type', 'e2pdf-wc-order key="post_mime_type"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Comments Count', 'e2pdf-wc-order key="comment_count"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Filter', 'e2pdf-wc-order key="filter"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Post Thumbnail', 'e2pdf-wc-order key="get_the_post_thumbnail"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Post Thumbnail URL', 'e2pdf-wc-order key="get_the_post_thumbnail_url"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Permalink', 'e2pdf-wc-order key="permalink"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Post Permalink', 'e2pdf-wc-order key="get_post_permalink"') . '</div>';
            $vc .= '</div>';
            $vc .= $this->get_vm_product_order();
            $vc .= $this->get_vm_product_order_item_meta();
            $vc .= $this->get_vm_product();
        }

        if ($this->get('item') == 'product' || $this->get('item') == 'product_variation') {
            $vc .= $this->get_vm_product();
            $vc .= $this->get_vm_product_order();
            $vc .= $this->get_vm_product_order_item_meta();
            $vc .= $this->get_vm_order();
        }

        if (class_exists('ACF') && function_exists('acf_get_field_groups')) {
            $user_groups = acf_get_field_groups(
                    array(
                        'user_id' => 'new',
                        'user_form' => 'all',
                    )
            );
            if (!empty($user_groups)) {
                $user_groups = array_column($user_groups, 'key');
            }
            if ($this->get('item') == 'product' || $this->get('item') == 'product_variation') {
                $groups = acf_get_field_groups(array('post_type' => 'product'));
            } elseif ($this->get('item') == 'shop_order') {
                $groups = acf_get_field_groups(array('post_type' => 'shop_order'));
            } else {
                $groups = array();
            }
            if (!empty($groups)) {
                $vc .= "<h3 class='e2pdf-plr5'>ACF</h3>";
                foreach ($groups as $group_key => $group) {
                    $post_id = '';
                    if (!empty($user_groups)) {
                        if (in_array($group['key'], $user_groups)) {
                            if ($this->get('item') == '-3') {
                                $post_id = ' post_id="user_[e2pdf-dataset]"';
                            } else {
                                $post_id = ' post_id="user_[e2pdf-userid]"';
                            }
                        }
                    }

                    $vc .= '<h3 class="e2pdf-plr5">' . $group['title'] . '</h3>';
                    $vc .= "<div class='e2pdf-grid'>";
                    foreach (acf_get_fields($group['key']) as $field_key => $field) {
                        $vc = $this->get_acf_field($vc, $field, $post_id);
                    }
                    $vc .= '</div>';
                }
            }
        }

        $vc .= $this->get_vm_user();

        return $vc;
    }

    public function get_acf_field($vc, $field, $post_id) {
        if ($field['type'] == 'repeater' && !empty($field['sub_fields'])) {
            $sub_fields = array();
            foreach ($field['sub_fields'] as $sub_field_key => $sub_field) {
                $sub_fields[] = '[acf field="' . $sub_field['name'] . '"' . $post_id . ']';
                $sub_field['label'] = $field['label'] . ' ' . $sub_field['label'];
                $sub_field['name'] = $field['name'] . '_0_' . $sub_field['name'];
                $vc = $this->get_acf_field($vc, $sub_field, $post_id);
            }
            if (!empty($sub_fields)) {
                $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element($field['label'] . ' Iteration', 'e2pdf-acf-repeater field="' . $field['name'] . '"' . $post_id . ']' . implode(' ', $sub_fields) . "\r\n" . '[/e2pdf-acf-repeater') . '</div>';
            }
        } elseif ($field['type'] == 'group' && !empty($field['sub_fields'])) {
            $sub_fields = array();
            foreach ($field['sub_fields'] as $sub_field_key => $sub_field) {
                $sub_field['label'] = $field['label'] . ' ' . $sub_field['label'];
                $sub_field['name'] = $field['name'] . '_' . $sub_field['name'];
                $vc = $this->get_acf_field($vc, $sub_field, $post_id);
            }
        } else {
            if ($field['name']) {
                $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element($field['label'], 'acf field="' . $field['name'] . '"' . $post_id) . '</div>';
            }
        }
        return $vc;
    }

    private function get_item_type_keys_subkeys($item_type = false) {
        global $wpdb;

        $meta_data = $wpdb->get_results('SELECT DISTINCT `meta`.`meta_key`, `item`.`order_item_type` FROM `' . $wpdb->prefix . 'woocommerce_order_itemmeta` `meta` LEFT JOIN `' . $wpdb->prefix . 'woocommerce_order_items` `item` ON (`meta`.`order_item_id` = `item`.`order_item_id`)', ARRAY_A);
        $meta_keys = array();
        if ($meta_data) {
            foreach ($meta_data as $key => $meta) {
                if ($item_type) {
                    if ($meta['order_item_type'] == $item_type) {
                        $meta_keys[] = $meta['meta_key'];
                    }
                } else {
                    $field_key = array_search($meta['order_item_type'], array_column($meta_keys, 'meta_key'));
                    if ($field_key === false) {
                        $meta_keys[] = array(
                            'meta_key' => $meta['order_item_type'],
                            'sub_keys' => array(
                                $meta['meta_key'],
                            ),
                        );
                    } else {
                        $meta_keys[$field_key]['sub_keys'][] = $meta['meta_key'];
                    }
                }
            }
        }

        return $meta_keys;
    }

    private function get_post_taxonomy_keys() {
        global $wpdb;

        $meta_keys = array();
        if ($this->get('item')) {
            $order_condition = array(
                'orderby' => 'taxonomy',
                'order' => 'desc',
            );
            $orderby = $this->helper->load('db')->prepare_orderby($order_condition);
            $meta_keys = $wpdb->get_col($wpdb->prepare('SELECT DISTINCT `taxonomy` FROM `' . $wpdb->term_taxonomy . '` `t` ' . $orderby . '', ''));
        }

        return $meta_keys;
    }

    private function get_post_meta_keys($item = false, $post_id = false) {
        global $wpdb;

        if (!$item) {
            $item = $this->get('item');
        }

        $meta_keys = array();
        if ($item || $post_id) {
            if ($item == 'shop_order' && get_option('woocommerce_custom_orders_table_enabled') === 'yes' && get_option('woocommerce_custom_orders_table_data_sync_enabled') !== 'yes') {
                $order_condition = array(
                    'orderby' => 'meta_key',
                    'order' => 'desc',
                );
                $orderby = $this->helper->load('db')->prepare_orderby($order_condition);
                $meta_keys = $wpdb->get_col($wpdb->prepare('SELECT DISTINCT `meta_key` FROM `' . $wpdb->prefix . 'wc_orders_meta` `pm` ' . $orderby . ''));
                if (class_exists('WC_Order_Data_Store_CPT')) {
                    $internal_meta_keys = (new WC_Order_Data_Store_CPT())->get_internal_meta_keys();
                    if (!empty($internal_meta_keys) && is_array($internal_meta_keys)) {
                        $meta_keys = array_merge($meta_keys, $internal_meta_keys);
                    }
                }
            } else {
                if ($post_id) {
                    $condition = array(
                        'p.ID' => array(
                            'condition' => '=',
                            'value' => $post_id,
                            'type' => '%d',
                        ),
                    );
                } else {
                    $condition = array(
                        'p.post_type' => array(
                            'condition' => '=',
                            'value' => $item,
                            'type' => '%s',
                        ),
                    );
                }
                $order_condition = array(
                    'orderby' => 'meta_key',
                    'order' => 'desc',
                );
                $where = $this->helper->load('db')->prepare_where($condition);
                $orderby = $this->helper->load('db')->prepare_orderby($order_condition);
                $meta_keys = $wpdb->get_col($wpdb->prepare('SELECT DISTINCT `meta_key` FROM `' . $wpdb->postmeta . '` `pm` LEFT JOIN `' . $wpdb->posts . '` `p` ON (`p`.`ID` = `pm`.`post_ID`) ' . $where['sql'] . $orderby . '', $where['filter']));
            }
        }

        return $meta_keys;
    }

    private function get_product_attribute_keys() {
        global $wpdb;

        $meta_keys = array();
        $attributes = wc_get_attribute_taxonomies();
        foreach ($attributes as $attribute) {
            $meta_keys[] = array(
                'id' => 'pa_' . $attribute->attribute_name,
                'name' => $attribute->attribute_label,
            );
        }

        $condition = array(
            'pm.meta_key' => array(
                'condition' => '=',
                'value' => '_product_attributes',
                'type' => '%s',
            ),
        );

        $order_condition = array(
            'orderby' => 'pm.meta_key',
            'order' => 'desc',
        );

        $where = $this->helper->load('db')->prepare_where($condition);
        $orderby = $this->helper->load('db')->prepare_orderby($order_condition);

        $custom_meta_keys = $wpdb->get_col($wpdb->prepare('SELECT DISTINCT `meta_value` FROM `' . $wpdb->postmeta . '` `pm`' . $where['sql'] . $orderby . '', $where['filter']));
        foreach ($custom_meta_keys as $custom_meta_key) {
            $custom_metas = $this->helper->load('convert')->unserialize($custom_meta_key);
            if ($custom_metas && is_array($custom_metas)) {
                foreach ($custom_metas as $custom_metas_key => $custom_metas_value) {
                    if (isset($custom_metas_value['is_taxonomy']) && $custom_metas_value['is_taxonomy']) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
                    } else {
                        $in_array = array_search($custom_metas_key, array_column($meta_keys, 'id'));
                        if ($in_array === false && isset($custom_metas_value['name'])) {
                            $meta_keys[] = array(
                                'id' => $custom_metas_key,
                                'name' => $custom_metas_value['name'],
                            );
                        }
                    }
                }
            }
        }

        return $meta_keys;
    }

    private function get_vm_element($name, $id) {
        $element = '<div>';
        $element .= '<label>' . $name . ':</label>';
        $element .= '<input type="text" name=\'[' . $id . ']\' value=\'[' . $id . ']\' class="e2pdf-w100">';
        $element .= '</div>';
        return $element;
    }

    private function get_vm_product() {

        $index = '';
        if ($this->get('item') == 'shop_order' || $this->get('item') == 'cart') {
            $index = ' index="0"';
        }

        $vc = '';

        $vc .= '<h3 class="e2pdf-plr5">Product</h3>';
        $vc .= '<div class="e2pdf-grid">';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Name', 'e2pdf-wc-product key="get_name"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Type', 'e2pdf-wc-product key="get_type"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Slug', 'e2pdf-wc-product key="get_slug"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Date Created', 'e2pdf-wc-product key="get_date_created"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Date Modified', 'e2pdf-wc-product key="get_date_modified"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Status', 'e2pdf-wc-product key="get_status"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Featured', 'e2pdf-wc-product key="get_featured"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Catalog Visibility', 'e2pdf-wc-product key="get_catalog_visibility"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Description', 'e2pdf-wc-product key="get_description" wc_format_content="true"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Short Description', 'e2pdf-wc-product key="get_short_description" wc_format_content="true"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Sku', 'e2pdf-wc-product key="get_sku"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Price', 'e2pdf-wc-product key="get_price"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Regular Price', 'e2pdf-wc-product key="get_regular_price"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Sale Price', 'e2pdf-wc-product key="get_sale_price"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Date Sale From', 'e2pdf-wc-product key="get_date_on_sale_from"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Date Sale To', 'e2pdf-wc-product key="get_date_on_sale_to"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Total Sales', 'e2pdf-wc-product key="get_total_sales"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Tax Status', 'e2pdf-wc-product key="get_tax_status"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Tax Class', 'e2pdf-wc-product key="get_tax_class"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Manage Stock', 'e2pdf-wc-product key="get_manage_stock"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Stock Quantity', 'e2pdf-wc-product key="get_stock_quantity"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Stock Status', 'e2pdf-wc-product key="get_stock_status"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Backorders', 'e2pdf-wc-product key="get_backorders"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Low Stock Amount', 'e2pdf-wc-product key="get_low_stock_amount"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Sold Individually', 'e2pdf-wc-product key="get_sold_individually"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Weight', 'e2pdf-wc-product key="get_weight"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Length', 'e2pdf-wc-product key="get_length"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Width', 'e2pdf-wc-product key="get_width"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Height', 'e2pdf-wc-product key="get_height"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Dimensions', 'e2pdf-wc-product key="get_dimensions"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Upsell IDs', 'e2pdf-wc-product key="get_upsell_ids"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Cross Sell IDs', 'e2pdf-wc-product key="get_cross_sell_ids"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Parent ID', 'e2pdf-wc-product key="get_parent_id"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Reviews Allowed', 'e2pdf-wc-product key="get_reviews_allowed"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Purchase Note', 'e2pdf-wc-product key="get_purchase_note"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Attributes', 'e2pdf-wc-product key="get_attributes"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Default Attributes', 'e2pdf-wc-product key="get_default_attributes"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Menu Order', 'e2pdf-wc-product key="get_menu_order"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Password', 'e2pdf-wc-product key="get_post_password"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Category IDs', 'e2pdf-wc-product key="get_category_ids"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Tag IDs', 'e2pdf-wc-product key="get_tag_ids"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Virtual', 'e2pdf-wc-product key="get_virtual"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Gallery Image Ids', 'e2pdf-wc-product key="get_gallery_image_ids"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Shipping Class ID', 'e2pdf-wc-product key="get_shipping_class_id"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Downloads', 'e2pdf-wc-product key="get_downloads"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Download Expiry', 'e2pdf-wc-product key="get_download_expiry"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Downloadable', 'e2pdf-wc-product key="get_downloadable"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Download Limit', 'e2pdf-wc-product key="get_download_limit"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Image ID', 'e2pdf-wc-product key="get_image_id"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Rating Counts', 'e2pdf-wc-product key="get_rating_counts"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Average Rating', 'e2pdf-wc-product key="get_average_rating"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Review Count', 'e2pdf-wc-product key="get_review_count"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Title', 'e2pdf-wc-product key="get_title"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Product Permalink', 'e2pdf-wc-product key="get_permalink"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Children', 'e2pdf-wc-product key="get_children"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Stock Managed ID', 'e2pdf-wc-product key="get_stock_managed_by_id"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Price (HTML)', 'e2pdf-wc-product key="get_price_html"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Formatted Name', 'e2pdf-wc-product key="get_formatted_name"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Min Purchase Quantity', 'e2pdf-wc-product key="get_min_purchase_quantity"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Max Purchase Quantity', 'e2pdf-wc-product key="get_max_purchase_quantity"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Image', 'e2pdf-wc-product key="get_image"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Shipping Class', 'e2pdf-wc-product key="get_shipping_class"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Rating Count', 'e2pdf-wc-product key="get_rating_count"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('File', 'e2pdf-wc-product key="get_file"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('File Download Path', 'e2pdf-wc-product key="get_file_download_path"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Price Suffix', 'e2pdf-wc-product key="get_price_suffix"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Availability', 'e2pdf-wc-product key="get_availability"' . $index) . '</div>';
        if ($this->get('item') == 'product_variation') {
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Product Variation Attributes', 'e2pdf-wc-product key="get_variation_attributes"' . $index) . '</div>';
        }
        $vc .= '</div>';

        $vc .= '<h3 class="e2pdf-plr5">Product Common</h3>';
        $vc .= '<div class="e2pdf-grid">';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('ID', 'e2pdf-wc-product key="id"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Author', 'e2pdf-wc-product key="post_author"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Author ID', 'e2pdf-wc-product key="post_author_id"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Date', 'e2pdf-wc-product key="post_date"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Date (GMT)', 'e2pdf-wc-product key="post_date_gmt"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Content', 'e2pdf-wc-product key="post_content"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Title', 'e2pdf-wc-product key="post_title"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Excerpt', 'e2pdf-wc-product key="post_excerpt"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Status', 'e2pdf-wc-product key="post_status"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Comment Status', 'e2pdf-wc-product key="comment_status"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Ping Status', 'e2pdf-wc-product key="ping_status"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Password', 'e2pdf-wc-product key="post_password"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Name', 'e2pdf-wc-product key="post_name"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('To Ping', 'e2pdf-wc-product key="to_ping"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Ping', 'e2pdf-wc-product key="pinged"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Modified Date', 'e2pdf-wc-product key="post_modified"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Modified Date (GMT)', 'e2pdf-wc-product key="post_modified_gmt"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Filtered Content', 'e2pdf-wc-product key="post_content_filtered"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Parent ID', 'e2pdf-wc-product key="post_parent"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('GUID', 'e2pdf-wc-product key="guid"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Menu Order', 'e2pdf-wc-product key="menu_order"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Type', 'e2pdf-wc-product key="post_type"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Mime Type', 'e2pdf-wc-product key="post_mime_type"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Comments Count', 'e2pdf-wc-product key="comment_count"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Filter', 'e2pdf-wc-product key="filter"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Post Thumbnail', 'e2pdf-wc-product key="get_the_post_thumbnail"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Post Thumbnail URL', 'e2pdf-wc-product key="get_the_post_thumbnail_url"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Permalink', 'e2pdf-wc-product key="permalink"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Post Permalink', 'e2pdf-wc-product key="get_post_permalink"' . $index) . '</div>';
        $vc .= '</div>';

        $meta_keys = $this->get_product_attribute_keys();
        $vc .= '<h3 class="e2pdf-plr5">Product Attributes</h3>';
        $vc .= '<div class="e2pdf-grid">';
        $i = 0;
        foreach ($meta_keys as $meta_key) {
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element($meta_key['name'], 'e2pdf-wc-product key="get_attribute" attribute="' . $meta_key['id'] . '" show="value"' . $index) . '</div>';

            $i++;
        }
        $vc .= '</div>';

        $vc .= '<h3 class="e2pdf-plr5">Product Special Shortcodes</h3>';
        $vc .= '<div class="e2pdf-grid">';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Product Attributes', 'e2pdf-foreach shortcode="e2pdf-wc-product" key="get_attributes"' . $index . ' wc_filter="true"][e2pdf-wc-product key="get_attributes" path="[e2pdf-foreach-key].label" wc_filter="true"' . $index . ']: [e2pdf-wc-product key="get_attributes" path="[e2pdf-foreach-key].value" wc_filter="true"' . $index . ']' . "\r\n" . '[/e2pdf-foreach') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Formatted Meta Data', 'e2pdf-foreach shortcode="e2pdf-wc-product" key="get_formatted_meta_data"' . $index . '][e2pdf-wc-product key="get_formatted_meta_data" path="[e2pdf-foreach-key].display_key"' . $index . ']:[e2pdf-wc-product key="get_formatted_meta_data" path="[e2pdf-foreach-key].display_value"' . $index . ']' . "\r\n" . '[/e2pdf-foreach') . '</div>';
        if ($this->get('item') == 'product_variation') {
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Product Variation Attributes', 'e2pdf-foreach shortcode="e2pdf-wc-product" key="get_variation_attributes"' . $index . ' wc_filter="true"][e2pdf-wc-product key="get_variation_attributes" path="[e2pdf-foreach-key].label" wc_filter="true"' . $index . ']: [e2pdf-wc-product key="get_variation_attributes" path="[e2pdf-foreach-key].value" wc_filter="true"' . $index . ']' . "\r\n" . '[/e2pdf-foreach') . '</div>';
        }

        $vc .= '</div>';

        $meta_keys = $this->get_post_meta_keys('product');
        $meta_keys2 = $this->get_post_meta_keys('product_variation');

        if (!empty($meta_keys2)) {
            foreach ($meta_keys2 as $meta_key) {
                if (!in_array($meta_key, $meta_keys)) {
                    $meta_keys[] = $meta_key;
                }
            }
        }

        if (!empty($meta_keys)) {
            $vc .= '<h3 class="e2pdf-plr5">Product Meta Keys</h3>';
            $vc .= '<div class="e2pdf-grid">';
            $i = 0;
            foreach ($meta_keys as $meta_key) {
                $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element($meta_key, 'e2pdf-wc-product key="' . $meta_key . '" meta="true"' . $index) . '</div>';
                $i++;
            }
            $vc .= '</div>';
        }

        $meta_keys = $this->get_post_taxonomy_keys();
        if (!empty($meta_keys)) {
            $vc .= '<h3 class="e2pdf-plr5">Product Taxonomy</h3>';
            $vc .= '<div class="e2pdf-grid">';
            $i = 0;
            foreach ($meta_keys as $meta_key) {
                $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element($meta_key, 'e2pdf-wc-product key="' . $meta_key . '" terms="true" names="true"' . $index) . '</div>';
                $i++;
            }
            $vc .= '</div>';
        }

        return $vc;
    }

    private function get_vm_product_order() {
        $index = '';
        if ($this->get('item') == 'shop_order' || $this->get('item') == 'cart') {
            $index = ' index="0"';
        }

        $vc = '';

        if ($this->get('item') == 'cart') {
            $vc .= '<h3 class="e2pdf-plr5">Cart Product</h3>';
        } else {
            $vc .= '<h3 class="e2pdf-plr5">Order Product</h3>';
        }
        $vc .= '<div class="e2pdf-grid">';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Name', 'e2pdf-wc-product key="get_name" order="true"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Type', 'e2pdf-wc-product key="get_type" order="true"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Quantity', 'e2pdf-wc-product key="get_quantity" order="true"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Tax Status', 'e2pdf-wc-product key="get_tax_status" order="true"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Tax Class', 'e2pdf-wc-product key="get_tax_class" order="true"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Formatted Meta Data', 'e2pdf-wc-product key="get_formatted_meta_data" order="true"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Product ID', 'e2pdf-wc-product key="get_product_id" order="true"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Variation ID', 'e2pdf-wc-product key="get_variation_id" order="true"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Subtotal', 'e2pdf-wc-product key="get_subtotal" order="true"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Subtotal Tax', 'e2pdf-wc-product key="get_subtotal_tax" order="true"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Total', 'e2pdf-wc-product key="get_total" order="true"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Total Tax', 'e2pdf-wc-product key="get_total_tax" order="true"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Taxes', 'e2pdf-wc-product key="get_taxes" order="true"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Item Download URL', 'e2pdf-wc-product key="get_item_download_url" order="true" index="0" download_order="true"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Downloads', 'e2pdf-wc-product key="get_item_downloads" order="true"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Item Subtotal', 'e2pdf-wc-product key="get_item_subtotal" order="true"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Item Total', 'e2pdf-wc-product key="get_item_total" order="true"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Item Tax', 'e2pdf-wc-product key="get_item_tax" order="true"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Line Total', 'e2pdf-wc-product key="get_line_total" order="true"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Line Tax', 'e2pdf-wc-product key="get_line_tax" order="true"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Formatted Line Subtotal', 'e2pdf-wc-product key="get_formatted_line_subtotal" order="true"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Order Item ID', 'e2pdf-wc-product key="get_order_item_id" order="true"' . $index) . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Order Response Hook', 'e2pdf-wc-product key="order_response_hook" order="true"' . $index) . '</div>';

        if ($this->get('item') == 'cart') {
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Item Cart Response Hook', 'e2pdf-wc-product key="item_cart_response_hook"' . $index) . '</div>';
        } else {
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Item Response Hook', 'e2pdf-wc-product key="item_response_hook" order="true"' . $index) . '</div>';
        }

        $vc .= '</div>';

        return $vc;
    }

    private function get_vm_product_order_item_meta() {

        $index = '';
        if ($this->get('item') == 'shop_order' || $this->get('item') == 'cart') {
            $index = ' index="0"';
        }

        $vc = '';
        $meta_keys = $this->get_item_type_keys_subkeys('line_item');
        if (!empty($meta_keys)) {
            $vc .= '<h3 class="e2pdf-plr5">Order Product Item Meta Keys</h3>';
            $vc .= '<div class="e2pdf-grid">';
            $i = 0;
            foreach ($meta_keys as $meta_key) {
                $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element($meta_key, 'e2pdf-wc-product key="' . $meta_key . '" order_item_meta="true"' . $index) . '</div>';
                $i++;
            }
            $vc .= '</div>';
        }

        return $vc;
    }

    private function get_vm_order() {

        $vc = '';
        $vc .= '<h3 class="e2pdf-plr5">Order</h3>';
        $vc .= '<div class="e2pdf-grid">';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Order ID', 'e2pdf-wc-order key="get_id"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Order Key', 'e2pdf-wc-order key="get_order_key"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Order Number', 'e2pdf-wc-order key="get_order_number"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Formatted Order Total', 'e2pdf-wc-order key="get_formatted_order_total"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Cart Tax', 'e2pdf-wc-order key="get_cart_tax"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Currency', 'e2pdf-wc-order key="get_currency"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Discount Tax', 'e2pdf-wc-order key="get_discount_tax"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Discount to Display', 'e2pdf-wc-order key="get_discount_to_display"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Discount Total', 'e2pdf-wc-order key="get_discount_total"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Shipping Tax', 'e2pdf-wc-order key="get_shipping_tax"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Shipping Total', 'e2pdf-wc-order key="get_shipping_total"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Subtotal', 'e2pdf-wc-order key="get_subtotal"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Subtotal to Display', 'e2pdf-wc-order key="get_subtotal_to_display"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Get Total', 'e2pdf-wc-order key="get_total"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Total Discount', 'e2pdf-wc-order key="get_total_discount"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Total Tax', 'e2pdf-wc-order key="get_total_tax"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Total Refunded', 'e2pdf-wc-order key="get_total_refunded"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Total Tax Refunded', 'e2pdf-wc-order key="get_total_tax_refunded"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Total Shipping Refunded', 'e2pdf-wc-order key="get_total_shipping_refunded"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Item Count Refunded', 'e2pdf-wc-order key="get_item_count_refunded"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Total QTY Refunded', 'e2pdf-wc-order key="get_total_qty_refunded"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Remaining Refund Amount', 'e2pdf-wc-order key="get_remaining_refund_amount"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Item Count', 'e2pdf-wc-order key="get_item_count"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Shipping Method', 'e2pdf-wc-order key="get_shipping_method"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Shipping to Display', 'e2pdf-wc-order key="get_shipping_to_display"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Date Created', 'e2pdf-wc-order key="get_date_created"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Date Modified', 'e2pdf-wc-order key="get_date_modified"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Date Completed', 'e2pdf-wc-order key="get_date_completed"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Date Paid', 'e2pdf-wc-order key="get_date_paid"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Custom ID', 'e2pdf-wc-order key="get_customer_id"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('User ID', 'e2pdf-wc-order key="get_user_id"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Customer IP Address', 'e2pdf-wc-order key="get_customer_ip_address"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Customer User Agent', 'e2pdf-wc-order key="get_customer_user_agent"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Created Via', 'e2pdf-wc-order key="get_created_via"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Customer Note', 'e2pdf-wc-order key="get_customer_note"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Billing First Name', 'e2pdf-wc-order key="get_billing_first_name"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Billing Last Name', 'e2pdf-wc-order key="get_billing_last_name"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Billing Company', 'e2pdf-wc-order key="get_billing_company"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Billing Address 1', 'e2pdf-wc-order key="get_billing_address_1"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Billing Address 2', 'e2pdf-wc-order key="get_billing_address_2"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Billing City', 'e2pdf-wc-order key="get_billing_city"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Billing State', 'e2pdf-wc-order key="get_billing_state"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Billing Postcode', 'e2pdf-wc-order key="get_billing_postcode"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Billing Country', 'e2pdf-wc-order key="get_billing_country"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Billing E-mail', 'e2pdf-wc-order key="get_billing_email"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Billing Phone', 'e2pdf-wc-order key="get_billing_phone"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Shopping First Name', 'e2pdf-wc-order key="get_shipping_first_name"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Shipping Last Name', 'e2pdf-wc-order key="get_shipping_last_name"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Shipping Company', 'e2pdf-wc-order key="get_shipping_company"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Shipping Address 1', 'e2pdf-wc-order key="get_shipping_address_1"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Shipping Address 2', 'e2pdf-wc-order key="get_shipping_address_2"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Shipping City', 'e2pdf-wc-order key="get_shipping_city"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Shipping State', 'e2pdf-wc-order key="get_shipping_state"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Shipping Postcode', 'e2pdf-wc-order key="get_shipping_postcode"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Shipping Country', 'e2pdf-wc-order key="get_shipping_country"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Shipping Address Map URL', 'e2pdf-wc-order key="get_shipping_address_map_url"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Formatted Billing Full Name', 'e2pdf-wc-order key="get_formatted_billing_full_name"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Formatted Shipping Full Name', 'e2pdf-wc-order key="get_formatted_shipping_full_name"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Formatted Billing Address', 'e2pdf-wc-order key="get_formatted_billing_address"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Formatted Shipping Address', 'e2pdf-wc-order key="get_formatted_shipping_address"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Payment Method', 'e2pdf-wc-order key="get_payment_method"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Payment Method Title', 'e2pdf-wc-order key="get_payment_method_title"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Transaction ID', 'e2pdf-wc-order key="get_transaction_id"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Checkout Payment URL', 'e2pdf-wc-order key="get_checkout_payment_url"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Checkout Order Received URL', 'e2pdf-wc-order key="get_checkout_order_received_url"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Cancel Order URL', 'e2pdf-wc-order key="get_cancel_order_url"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Cancel Order URL (raw)', 'e2pdf-wc-order key="get_cancel_order_url_raw"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Cancel Endpoint', 'e2pdf-wc-order key="get_cancel_endpoint"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('View Order URL', 'e2pdf-wc-order key="get_view_order_url"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Edit Order URL', 'e2pdf-wc-order key="get_edit_order_url"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Status', 'e2pdf-wc-order key="get_status"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Cart', 'e2pdf-wc-order key="cart"') . '</div>';
        $vc .= '</div>';

        $vc .= '<h3 class="e2pdf-plr5">Order Special Shortcodes</h3>';
        $vc .= '<div class="e2pdf-grid">';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Order Products', 'e2pdf-foreach shortcode="e2pdf-wc-order" key="get_items"][e2pdf-wc-product key="get_name" order="true" index="[e2pdf-foreach-index]"]' . "\r\n" . '[/e2pdf-foreach') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Order Totals', 'e2pdf-foreach shortcode="e2pdf-wc-order" key="get_order_item_totals"][e2pdf-wc-order key="get_order_item_totals" path="[e2pdf-foreach-key].label"][e2pdf-wc-order key="get_order_item_totals" path="[e2pdf-foreach-key].value"]' . "\r\n" . '[/e2pdf-foreach') . '</div>';

        $vc .= '</div>';

        $meta_keys = $this->get_post_meta_keys('shop_order');
        if (!empty($meta_keys)) {
            $vc .= '<h3 class="e2pdf-plr5">Order Meta Keys</h3>';
            $vc .= '<div class="e2pdf-grid">';
            $i = 0;
            foreach ($meta_keys as $meta_key) {
                $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element($meta_key, 'e2pdf-wc-order key="' . $meta_key . '" meta="true"') . '</div>';
                $i++;
            }
            $vc .= '</div>';
        }

        /*
         * Checkout Field Editor (Checkout Manager) for WooCommerce
         * https://wordpress.org/plugins/woo-checkout-field-editor-pro/
         */
        if (class_exists('THWCFD_Utils')) {
            $meta_keys = array_merge(THWCFD_Utils::get_fields('billing'), THWCFD_Utils::get_fields('shipping'), THWCFD_Utils::get_fields('additional'));
            if (!empty($meta_keys)) {
                $vc .= '<h3 class="e2pdf-plr5">Checkout Field Editor for WooCommerce</h3>';
                $vc .= '<div class="e2pdf-grid">';
                $i = 0;
                foreach ($meta_keys as $meta_key => $meta_value) {
                    $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element($meta_key, 'e2pdf-wc-order key="' . $meta_key . '" meta="true" checkout_field_editor="true"') . '</div>';
                    $i++;
                }
                $vc .= '</div>';
            }
        }

        $meta_keys = $this->get_post_taxonomy_keys();
        if (!empty($meta_keys)) {
            $vc .= '<h3 class="e2pdf-plr5">Order Taxonomy</h3>';
            $vc .= '<div class="e2pdf-grid">';
            $i = 0;
            foreach ($meta_keys as $meta_key) {
                $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element($meta_key, 'e2pdf-wc-order key="' . $meta_key . '" terms="true" names="true"') . '</div>';
                $i++;
            }
            $vc .= '</div>';
        }

        $meta_keys = $this->get_item_type_keys_subkeys();
        if (!empty($meta_keys)) {
            $vc .= '<h3 class="e2pdf-plr5">Order Item Meta Keys</h3>';
            $vc .= '<div class="e2pdf-grid">';

            $i = 0;
            foreach ($meta_keys as $meta_key) {
                foreach ($meta_key['sub_keys'] as $sub_key) {
                    $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element($sub_key . ' (' . $meta_key['meta_key'] . ')', 'e2pdf-wc-order key="' . $meta_key['meta_key'] . '" subkey="' . $sub_key . '" index="0" order_item_meta="true"') . '</div>';

                    $i++;
                }
            }
            $vc .= '</div>';
        }

        return $vc;
    }

    public function get_vm_user() {

        $vc = '<h3 class="e2pdf-plr5">User</h3>';
        $vc .= '<div class="e2pdf-grid">';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('ID', 'e2pdf-user key="ID"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Description', 'e2pdf-user key="user_description"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('First Name', 'e2pdf-user key="user_firstname"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Last Name', 'e2pdf-user key="user_lastname"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Login', 'e2pdf-user key="user_login"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Nicename', 'e2pdf-user key="user_nicename"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('E-mail', 'e2pdf-user key="user_email"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Url', 'e2pdf-user key="user_url"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Registered', 'e2pdf-user key="user_registered"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('User Status', 'e2pdf-user key="user_status"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('User Level', 'e2pdf-user key="user_level"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Display Name', 'e2pdf-user key="display_name"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Spam', 'e2pdf-user key="spam"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Deleted', 'e2pdf-user key="deleted"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Locale', 'e2pdf-user key="locale"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Rich Editing', 'e2pdf-user key="rich_editing"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Syntax Highlighting', 'e2pdf-user key="syntax_highlighting"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Use SSL', 'e2pdf-user key="use_ssl"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Roles', 'e2pdf-user key="roles"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Avatar', 'e2pdf-user key="get_avatar"') . '</div>';
        $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Avatar Url', 'e2pdf-user key="get_avatar_url"') . '</div>';
        $vc .= '</div>';

        $meta_keys = $this->get_user_meta_keys();
        if (!empty($meta_keys)) {
            $vc .= '<h3 class="e2pdf-plr5">User Meta Keys</h3>';
            $vc .= '<div class="e2pdf-grid">';
            $i = 0;
            foreach ($meta_keys as $meta_key) {
                $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element($meta_key, 'e2pdf-user key="' . $meta_key . '" meta="true"') . '</div>';
                $i++;
            }
            $vc .= '</div>';
        }

        return $vc;
    }

    private function get_user_meta_keys() {
        global $wpdb;
        $meta_keys = array();
        if ($this->get('item')) {
            $order_condition = array(
                'orderby' => 'meta_key',
                'order' => 'desc',
            );
            $orderby = $this->helper->load('db')->prepare_orderby($order_condition);
            $meta_keys = $wpdb->get_col($wpdb->prepare('SELECT DISTINCT `meta_key` FROM `' . $wpdb->usermeta . '` ' . $orderby . ''));
        }
        return $meta_keys;
    }

    public function hook_woocommerce_order_edit() {
        $hooks = $this->helper->load('hooks')->get('woocommerce', 'hook_woocommerce_order_edit', 'shop_order');
        if (!empty($hooks)) {
            add_meta_box(
                    'e2pdf',
                    apply_filters('e2pdf_hook_section_title', __('E2Pdf Actions', 'e2pdf'), 'hook_woocommerce_order_edit'),
                    array($this, 'hook_woocommerce_order_edit_callback'),
                    'shop_order',
                    'side',
                    'default',
                    array('hooks' => $hooks)
            );
        }
    }

    public function hook_woocommerce_order_edit_callback($post, $metabox) {
        foreach ($metabox['args']['hooks'] as $hook) {
            $action = apply_filters('e2pdf_hook_action_button',
                    array(
                        'html' => '<p><a class="e2pdf-download-hook" target="_blank" title="%2$s" href="%1$s"><span class="dashicons dashicons-pdf"></span> %2$s</a></p>',
                        'url' => $this->helper->get_url(
                                array(
                                    'page' => 'e2pdf',
                                    'action' => 'export',
                                    'id' => $hook,
                                    'dataset' => $post->ID,
                                ), 'admin.php?'
                        ),
                        'title' => 'PDF #' . $hook
                    ), 'hook_woocommerce_order_edit', $hook, $post->ID
            );
            if (!empty($action)) {
                echo sprintf(
                        $action['html'], $action['url'], $action['title']
                );
            }
        }
    }

    public function hook_woocommerce_order_row_actions($order) {
        if (!empty($order->id)) {
            $hooks = $this->helper->load('hooks')->get('woocommerce', 'hook_woocommerce_order_row_actions', 'shop_order');
            foreach ($hooks as $hook) {
                $action = apply_filters('e2pdf_hook_action_button',
                        array(
                            'html' => '<a class="button e2pdf-download-hook e2pdf-download-hook-icon-button" target="_blank" title="%2$s" href="%1$s">%2$s</a> ',
                            'url' => $this->helper->get_url(
                                    array(
                                        'page' => 'e2pdf',
                                        'action' => 'export',
                                        'id' => $hook,
                                        'dataset' => $order->id,
                                    ), 'admin.php?'
                            ),
                            'title' => 'PDF #' . $hook
                        ), 'hook_woocommerce_order_row_actions', $hook, $order->id
                );
                if (!empty($action)) {
                    echo sprintf(
                            $action['html'], $action['url'], $action['title']
                    );
                }
            }
        }
    }

    public function hook_woocommerce_order_row_column($columns) {
        $hooks = $this->helper->load('hooks')->get('woocommerce', 'hook_woocommerce_order_row_column', 'shop_order');
        if (!empty($hooks)) {
            $columns['e2pdf_hook_woocommerce_order_row_column'] = apply_filters('e2pdf_hook_section_title', __('E2Pdf Actions', 'e2pdf'), 'hook_woocommerce_order_row_column');
            add_action('manage_shop_order_posts_custom_column', array($this, 'hook_woocommerce_order_row_column_callback'), 10, 2);
        }
        return $columns;
    }

    public function hook_woocommerce_order_row_column_callback($column, $post_id) {
        if ($column == 'e2pdf_hook_woocommerce_order_row_column') {
            $hooks = $this->helper->load('hooks')->get('woocommerce', 'hook_woocommerce_order_row_column', 'shop_order');
            foreach ($hooks as $hook) {
                $action = apply_filters('e2pdf_hook_action_button',
                        array(
                            'html' => '<a class="button e2pdf-download-hook e2pdf-download-hook-icon-button" target="_blank" title="%2$s" href="%1$s">%2$s</a> ',
                            'url' => $this->helper->get_url(
                                    array(
                                        'page' => 'e2pdf',
                                        'action' => 'export',
                                        'id' => $hook,
                                        'dataset' => $post_id,
                                    ), 'admin.php?'
                            ),
                            'title' => 'PDF #' . $hook
                        ), 'hook_woocommerce_order_row_column', $hook, $post_id
                );
                if (!empty($action)) {
                    echo sprintf(
                            $action['html'], $action['url'], $action['title']
                    );
                }
            }
        }
    }
}
