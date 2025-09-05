<?php

/**
 * E2Pdf WordPress Extension
 * @copyright  Copyright 2017 https://e2pdf.com
 * @license    GPLv3
 * @version    1
 * @link       https://e2pdf.com
 * @since      0.00.01
 */
if (!defined('ABSPATH')) {
    die('Access denied.');
}

class Extension_E2pdf_Wordpress extends Model_E2pdf_Model {

    private $options;
    private $info = array(
        'key' => 'wordpress',
        'title' => 'WordPress',
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
        return true;
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
                    if ($this->get('item') == '-3') {
                        $this->set('cached_post', get_user_by('id', $this->get('dataset')));
                    } else {
                        $this->set('cached_post', get_post($this->get('dataset')));
                    }
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
        if ($key == 'user_id' && $this->get('item') == '-3') {
            $key = 'dataset';
        }
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
        $forms = get_post_types(array(), 'names');
        foreach ($forms as $form) {
            if ($form != 'attachment') {
                $items[] = $this->item($form);
            }
        }
        $items[] = $this->item('-3');
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
        if ($item_id) {
            if ($item_id == '-3') {
                $entries = get_users(
                        array(
                            'fields' => array(
                                'ID', 'user_login',
                            ),
                        )
                );

                if ($entries) {
                    $this->set('item', $item_id);
                    foreach ($entries as $key => $entry) {
                        $this->set('dataset', $entry->ID);
                        $entry_title = $this->render($name);
                        if (!$entry_title) {
                            $entry_title = isset($entry->user_login) && $entry->user_login ? $entry->user_login : $entry->ID;
                        }
                        $datasets[] = array(
                            'key' => $entry->ID,
                            'value' => $entry_title,
                        );
                    }
                }
            } else {
                $entries = get_posts(
                        array(
                            'post_type' => $item_id,
                            'numberposts' => -1,
                            'post_status' => 'any',
                        )
                );
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
        }
        return $datasets;
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
        if ($this->get('item') == '-3') {
            $actions->view = $this->helper->get_url(
                    array(
                        'user_id' => $dataset_id,
                    ), 'user-edit.php?'
            );
        } else {
            $actions->view = $this->helper->get_url(
                    array(
                        'post' => $dataset_id,
                        'action' => 'edit',
                    ), 'post.php?'
            );
        }
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
        if ($item_id == '-3') {
            $item->id = $item_id;
            $item->name = __('Users');
            $item->url = $this->helper->get_url(array(), 'users.php');
        } else {
            $form = get_post_type_object($item_id);
            if ($form) {
                $item->id = $item_id;
                $item->name = $form->label ? $form->label : $item_id;
                $item->url = $this->helper->get_url(array('post_type' => $item_id), 'edit.php?');
            } else {
                $item->id = '';
                $item->name = '';
                $item->url = 'javascript:void(0);';
            }
        }
        return $item;
    }

    public function load_filters() {
        add_filter('the_content', array($this, 'filter_the_content'), 10, 2);
        add_filter('widget_text', array($this, 'filter_content_custom'));
        add_filter('widget_block_content', array($this, 'filter_content_custom'));

        /**
         * Popup Maker – Popup for opt-ins, lead gen, & more
         * https://wordpress.org/plugins/popup-maker/
         */
        add_filter('pum_popup_content', array($this, 'filter_the_content'), 10, 2);

        /**
         * Events Manager
         * https://wordpress.org/plugins/events-manager/
         */
        add_filter('em_event_output_placeholder', array($this, 'filter_content_custom'), 0);
        add_filter('em_event_output', array($this, 'filter_content_custom'));
        add_filter('em_booking_output_placeholder', array($this, 'filter_content_custom'), 0);
        add_filter('em_booking_output', array($this, 'filter_content_custom'));
        add_filter('em_location_output_placeholder', array($this, 'filter_content_custom'), 0);
        add_filter('em_location_output', array($this, 'filter_content_custom'));
        add_filter('em_category_output_placeholder', array($this, 'filter_content_custom'), 0);

        /**
         * Beaver Builder – WordPress Page Builder
         * https://wordpress.org/plugins/beaver-builder-lite-version/
         */
        add_filter('fl_builder_before_render_shortcodes', array($this, 'filter_content_loop'));

        /**
         * WPBakery Page Builder Image Object Link
         */
        add_filter('vc_map_get_attributes', array($this, 'filter_vc_map_get_attributes'), 10, 2);

        /**
         * Flatsome theme global tab content
         */
        add_filter('theme_mod_tab_content', array($this, 'filter_content_custom'));

        /**
         * MemberPress Mail attachments
         * https://memberpress.com/
         */
        add_filter('mepr_email_send_attachments', array($this, 'filter_mepr_email_send_attachments'), 10, 4);
        add_filter('the_content', array($this, 'filter_mepr_the_content'), 99);
        add_filter('mepr_custom_thankyou_message', array($this, 'filter_mepr_custom_thankyou_message'));

        /**
         * Thrive Theme Builder dynamic shortcode support
         */
        add_filter('thrive_theme_template_content', array($this, 'filter_thrive_theme_template_content'));

        /**
         * WPBakery Page Builder Grid Item
         * [e2pdf-download id="1" dataset="{{ post_data:ID }}"]
         */
        add_filter('vc_basic_grid_items_list', array($this, 'filter_vc_basic_grid_items_list'));

        /**
         * Themify Builder dynamic shortcode support
         * https://wordpress.org/plugins/themify-builder/
         */
        add_filter('themify_builder_module_content', array($this, 'filter_themify_builder_module_content'));

        /**
         * Impreza theme by Up Solutions
         * https://themeforest.net/item/impreza-retina-responsive-wordpress-theme/6434280
         */
        add_filter('us_content_template_the_content', array($this, 'filter_content_custom'));

        /**
         * Cornerstone Builder
         * https://theme.co/cornerstone/
         */
        add_filter('cs_element_pre_render', array($this, 'filter_cs_element_pre_render'));

        /**
         * Divi Theme Builder
         * https://www.elegantthemes.com/
         */
        add_filter('et_pb_module_content', array($this, 'filter_et_pb_module_content'), 30, 6);

        /**
         * User Registration & Membership – Custom Registration Form, Login Form, and User Profile
         * https://wordpress.org/plugins/user-registration/
         */
        add_filter('user_registration_smart_tag_values', array($this, 'filter_user_registration_smart_tag_values'), 0);

        /* Hooks */
        add_filter('post_row_actions', array($this, 'hook_wordpress_row_actions'), 10, 2);
        add_filter('page_row_actions', array($this, 'hook_wordpress_row_actions'), 10, 2);
        add_filter('user_row_actions', array($this, 'hook_wordpress_row_actions'), 10, 2);
    }

    public function load_actions() {
        /**
         * Elementor Website Builder – More than Just a Page Builder
         * https://wordpress.org/plugins/elementor/
         */
        add_action('elementor/widget/before_render_content', array($this, 'action_elementor_widget_before_render_content'));
        add_action('elementor/frontend/widget/before_render', array($this, 'action_elementor_widget_before_render_content'), 5);

        /**
         * JetEngine: WordPress Plugin for Elementor
         * https://crocoblock.com
         */
        add_action('jet-engine/listing/grid/before-render', array($this, 'action_jet_engine_listing_grid_before_render'));
        add_action('jet-engine/listing/grid/after-render', array($this, 'action_jet_engine_listing_grid_after_render'));

        /**
         * Happy Addons for Elementor
         * https://wordpress.org/plugins/happy-elementor-addons/ compatibility fix
         */
        add_action('elementor/frontend/before_render', array($this, 'action_elementor_widget_before_render_content'), 0);

        /**
         * MemberPress Mail attachments remove
         * https://memberpress.com/
         */
        add_action('mepr_email_sent', array($this, 'action_mepr_email_sent'), 10, 3);

        /**
         * User Registration & Membership – Custom Registration Form, Login Form, and User Profile
         * https://wordpress.org/plugins/user-registration/
         */
        add_action('user_registration_email_send_before', array($this, 'action_user_registration_email_send_before'));
        add_action('user_registration_email_send_after', array($this, 'action_user_registration_email_send_after'));

        /* Hooks */
        add_action('add_meta_boxes', array($this, 'hook_wordpress_page_edit'));
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
            /**
             * Set current Post for Toolset Views
             * https://toolset.com/
             */
            do_action('wpv_action_wpv_set_top_current_post', $this->get('cached_post'));

            $wordpress_shortcodes = array(
                'id',
                'post_author',
                'post_date',
                'post_date_gmt',
                'post_content',
                'post_title',
                'post_excerpt',
                'post_status',
                'comment_status',
                'ping_status',
                'post_password',
                'post_name',
                'to_ping',
                'pinged',
                'post_modified',
                'post_modified_gmt',
                'post_content_filtered',
                'post_parent',
                'guid',
                'menu_order',
                'post_type',
                'post_mime_type',
                'comment_count',
                'filter',
                'post_thumbnail',
                'get_the_post_thumbnail',
                'get_the_post_thumbnail_url',
                'get_permalink',
                'get_post_permalink',
            );

            if (false !== strpos($value, '[')) {
                $value = $this->helper->load('field')->pre_shortcodes($value, $this, $field);
                $value = $this->helper->load('field')->inner_shortcodes($value, $this, $field);
                if ($this->get('item') == '-3') {
                    $shortcode_tags = array(
                        'acf',
                    );
                } else {
                    $shortcode_tags = array(
                        'meta',
                        'terms',
                        'e2pdf-wp',
                        'e2pdf-wp-term',
                        'e2pdf-content',
                        'acf',
                    );
                    $shortcode_tags = array_merge($shortcode_tags, $wordpress_shortcodes);
                }
                preg_match_all('@\[([^<>&/\[\]\x00-\x20=]++)@', $value, $matches);
                $tagnames = array_intersect($shortcode_tags, $matches[1]);
                if (!empty($tagnames)) {
                    preg_match_all('/' . $this->helper->load('shortcode')->get_shortcode_regex($tagnames) . '/', $value, $shortcodes);
                    foreach ($shortcodes[0] as $key => $shortcode_value) {
                        $shortcode = $this->helper->load('shortcode')->get_shortcode($shortcodes, $key);
                        $atts = shortcode_parse_atts($shortcode[3]);
                        if ($shortcode[2] === 'e2pdf-content') {
                            if (!isset($atts['id']) && isset($this->get('cached_post')->ID) && $this->get('cached_post')->ID) {
                                $shortcode[3] .= ' id=' . $this->get('cached_post')->ID . '';
                                $value = str_replace($shortcode_value, '[' . $shortcode[2] . $shortcode[3] . ']', $value);
                            }
                        } elseif ($shortcode[2] === 'meta' || $shortcode[2] === 'terms' || $shortcode[2] == 'e2pdf-wp') {
                            if (!isset($atts['id']) && isset($this->get('cached_post')->ID) && $this->get('cached_post')->ID) {
                                $shortcode[3] .= ' id=' . $this->get('cached_post')->ID . '';
                            }
                            if ($shortcode[2] === 'meta') {
                                $shortcode[3] .= ' meta=true';
                            }
                            if ($shortcode[2] === 'terms') {
                                $shortcode[3] .= ' terms=true';
                            }
                            if (substr($shortcode_value, -11) === '[/e2pdf-wp]' || substr($shortcode_value, -8) === '[/terms]' || substr($shortcode_value, -7) === '[/meta]') {
                                if ($shortcode[5]) {
                                    $shortcode[5] = $this->render($shortcode[5], array(), false);
                                }
                                $value = str_replace($shortcode_value, '[e2pdf-wp' . $shortcode[3] . ']' . $shortcode[5] . '[/e2pdf-wp]', $value);
                            } else {
                                $value = str_replace($shortcode_value, '[e2pdf-wp' . $shortcode[3] . ']', $value);
                            }
                        } elseif ($shortcode[2] === 'e2pdf-wp-term') {
                            if (substr($shortcode_value, -16) === '[/e2pdf-wp-term]') {
                                if ($shortcode[5]) {
                                    $shortcode[5] = $this->render($shortcode[5], array(), false);
                                }
                                $value = str_replace($shortcode_value, '[e2pdf-wp-term' . $shortcode[3] . ']' . $shortcode[5] . '[/e2pdf-wp-term]', $value);
                            }
                        } elseif (in_array($shortcode[2], $wordpress_shortcodes)) {
                            if (!isset($atts['id']) && isset($this->get('cached_post')->ID) && $this->get('cached_post')->ID) {
                                $shortcode[3] .= ' id=' . $this->get('cached_post')->ID . '';
                            }
                            $shortcode[3] .= ' key=' . $shortcode[2] . '';
                            $value = str_replace($shortcode_value, '[e2pdf-wp' . $shortcode[3] . ']', $value);
                        } elseif ($shortcode[2] === 'acf') {
                            if (!isset($atts['post_id']) && isset($this->get('cached_post')->ID) && $this->get('cached_post')->ID) {
                                if ($this->get('item') == '-3') {
                                    $shortcode[3] .= ' post_id=user_' . $this->get('cached_post')->ID . '';
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

            add_filter('frm_filter_view', array($this, 'filter_frm_filter_view'));
            $value = $this->helper->load('field')->do_shortcodes($value, $this, $field);
            $value = $this->helper->load('field')->render(
                    apply_filters('e2pdf_extension_render_shortcodes_pre_value', $value, $element_id, $this->get('template_id'), $this->get('item'), $this->get('dataset'), false, false),
                    $this,
                    $field
            );
            remove_filter('frm_filter_view', array($this, 'filter_frm_filter_view'));
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
     * Convert shortcodes inside value string
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

        if ($this->get('item') == '-3') {
            $elements[] = array(
                'type' => 'e2pdf-html',
                'block' => true,
                'properties' => array(
                    'top' => '20',
                    'left' => '20',
                    'right' => '20',
                    'width' => '100%',
                    'height' => 'auto',
                    'value' => '<h1>[e2pdf-user key="user_login"]</h1>',
                ),
            );

            $elements[] = array(
                'type' => 'e2pdf-image',
                'block' => true,
                'properties' => array(
                    'top' => '20',
                    'left' => '20',
                    'right' => '20',
                    'width' => '100%',
                    'height' => '100',
                    'vertical' => 'top',
                    'value' => '[e2pdf-user key="get_avatar_url"]',
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
                    'height' => 'auto',
                    'value' => 'ID: [e2pdf-user key="ID"]',
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
                    'height' => 'auto',
                    'value' => 'First Name: [e2pdf-user key="user_firstname"]',
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
                    'height' => 'auto',
                    'value' => 'Last Name: [e2pdf-user key="user_lastname"]',
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
                    'height' => 'auto',
                    'value' => 'Email: [e2pdf-user key="user_email"]',
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
                    'height' => 'auto',
                    'value' => 'Registered: [e2pdf-user key="user_registered"]',
                ),
            );
        } else {
            $elements[] = array(
                'type' => 'e2pdf-html',
                'block' => true,
                'properties' => array(
                    'top' => '20',
                    'left' => '20',
                    'right' => '20',
                    'width' => '100%',
                    'height' => 'auto',
                    'value' => '<h1>[e2pdf-wp key="post_title"]</h1>',
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
                    'height' => 'auto',
                    'value' => 'Post name: [e2pdf-wp key="post_name"]',
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
                    'height' => 'auto',
                    'value' => 'Post type: [e2pdf-wp key="post_type"]',
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
                    'height' => 'auto',
                    'value' => 'ID: [e2pdf-wp key="id"]',
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
                    'height' => 'auto',
                    'value' => 'Author: [e2pdf-wp key="post_author"]',
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
                    'height' => '300',
                    'value' => '[e2pdf-wp key="post_content"]',
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
                    'height' => 'auto',
                    'value' => 'Created: [e2pdf-wp key="post_date"]',
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
                    'height' => 'auto',
                    'value' => 'Modified: [e2pdf-wp key="post_modified"]',
                ),
            );
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
                    $widget->set_settings('shortcode', $this->filter_content($content, false, $wp_reset_postdata));
                } elseif ($widget->get_name() == 'text-editor' && !$wp_reset_postdata) {
                    $widget->set_settings('editor', $this->filter_content($content, false, $wp_reset_postdata));
                }
            }
        }
    }

    public function action_jet_engine_listing_grid_before_render($listing_grid) {
        $this->set('queried_object', true);
    }

    public function action_jet_engine_listing_grid_after_render($listing_grid) {
        $this->set('queried_object', false);
    }

    /**
     * Delete attachments that were sent by MemberPress email
     * https://memberpress.com/
     */
    public function action_mepr_email_sent($email, $values, $attachments) {
        $files = $this->helper->get('wordpress_attachments_mepr');
        if (is_array($files) && !empty($files)) {
            foreach ($files as $key => $file) {
                $this->helper->delete_dir(dirname($file) . '/');
            }
            $this->helper->deset('wordpress_attachments_mepr');
        }
    }

    public function action_user_registration_email_send_before() {
        add_filter('wp_mail', array($this, 'filter_wp_mail'), 11);
    }

    public function action_user_registration_email_send_after() {
        remove_filter('wp_mail', array($this, 'filter_wp_mail'), 11);
        $files = $this->helper->get('wordpress_attachments');
        if (is_array($files) && !empty($files)) {
            foreach ($files as $key => $file) {
                $this->helper->delete_dir(dirname($file) . '/');
            }
            $this->helper->deset('wordpress_attachments');
        }
    }

    /**
     * Search and update shortcodes for this extension inside content
     * Auto set of dataset id
     * @param string $content - Content
     * @param string $form_id - Custom Post ID
     * @return string - Content with updated shortcodes
     */
    public function filter_content($content, $post_id = false, $wp_reset_postdata = true) {
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
                        if ($template->get('extension') === 'woocommerce' || $template->get('extension') === 'jetformbuilder') {
                            continue;
                        } elseif ($template->get('extension') === 'wordpress') { // phpcs:ignore WordPress.WP.CapitalPDangit.Misspelled
                            if (!isset($atts['dataset'])) {
                                if ($template->get('item') == '-3') {
                                    $dataset = get_current_user_id();
                                    $atts['dataset'] = $dataset;
                                    $shortcode[3] .= ' dataset="' . $dataset . '"';
                                } else {
                                    if ($this->get('queried_object') && !$post_id) {
                                        $queried_object = get_queried_object();
                                        $post_id = $queried_object && isset($queried_object->ID) ? $queried_object->ID : false;
                                    }
                                    if ($post_id || isset($post->ID)) {
                                        $dataset = $post_id ? $post_id : $post->ID;
                                        $atts['dataset'] = $dataset;
                                        $shortcode[3] .= ' dataset="' . $dataset . '"';
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
                    $content = str_replace($shortcode_value, do_shortcode_tag($shortcode), $content);
                }
            }
        }
        return $content;
    }

    /**
     * [e2pdf-exclude] support inside Formidable Forms View beforeContent and afterContent
     */
    public function filter_frm_filter_view($view) {
        if (isset($view->frm_before_content) && $view->frm_before_content) {
            $view->frm_before_content = str_replace('[e2pdf-exclude]', '[e2pdf-exclude apply="true"]', $view->frm_before_content);
        }
        if (isset($view->post_content) && $view->post_content) {
            $view->post_content = str_replace('[e2pdf-exclude]', '[e2pdf-exclude apply="true"]', $view->post_content);
        }
        if (isset($view->frm_after_content) && $view->frm_after_content) {
            $view->frm_after_content = str_replace('[e2pdf-exclude]', '[e2pdf-exclude apply="true"]', $view->frm_after_content);
        }
        return $view;
    }

    public function filter_vc_map_get_attributes($atts, $tag) {
        if ($tag == 'vc_single_image' && ((isset($atts['onclick']) && $atts['onclick'] == 'custom_link') || (empty($atts['onclick']) && (!isset($atts['img_link_large']) || 'yes' !== $atts['img_link_large']))) && (!empty($atts['link']) || !empty($atts['img_link']))) {

            if (!empty($atts['link'])) {
                $atts['link'] = $this->filter_content($atts['link']);
            }

            if (!empty($atts['img_link'])) {
                $atts['img_link'] = $this->filter_content($atts['img_link']);
            }
        }
        return $atts;
    }

    public function filter_cs_element_pre_render($data) {
        if (!empty($data['_type']) && $data['_type'] == 'raw-content' && !empty($data['raw_content']) && !empty($data['_p'])) {
            $data['raw_content'] = $this->filter_content($data['raw_content'], $data['_p']);
        }
        return $data;
    }

    public function filter_themify_builder_module_content($content) {
        if ($content) {
            $content = $this->filter_content($content);
        }
        return $content;
    }

    public function filter_vc_basic_grid_items_list($items) {
        if ($items) {
            $items = $this->filter_content($items);
        }
        return $items;
    }

    public function filter_the_content($content, $post_id = false) {
        $content = $this->filter_content($content, $post_id);
        return $content;
    }

    public function filter_content_custom($content) {
        $content = $this->filter_content($content);
        return $content;
    }

    public function filter_content_loop($content) {
        $content = $this->filter_content($content, false, false);
        return $content;
    }

    /**
     * Memberpress Mail attachments filter
     * https://memberpress.com/
     */
    public function filter_mepr_email_send_attachments($attachments, $mail, $body, $values) {
        $message = $body ? $body : $mail->body();
        if ($message && false !== strpos($message, '[')) {
            $message = $mail->replace_variables($message, $values);
            $shortcode_tags = array(
                'e2pdf-attachment',
                'e2pdf-save',
            );
            preg_match_all('@\[([^<>&/\[\]\x00-\x20=]++)@', $message, $matches);
            $tagnames = array_intersect($shortcode_tags, $matches[1]);
            if (!empty($tagnames)) {
                preg_match_all('/' . $this->helper->load('shortcode')->get_shortcode_regex($tagnames) . '/', $message, $shortcodes);
                foreach ($shortcodes[0] as $key => $shortcode_value) {
                    $shortcode = $this->helper->load('shortcode')->get_shortcode($shortcodes, $key);
                    $atts = shortcode_parse_atts($shortcode[3]);
                    $file = false;
                    if (isset($atts['id']) && isset($atts['dataset'])) {
                        if (!isset($atts['apply'])) {
                            $shortcode[3] .= ' apply="true"';
                        }
                        if (!isset($atts['filter'])) {
                            $shortcode[3] .= ' filter="true"';
                        }
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
                                        $this->helper->add('wordpress_attachments_mepr', $file);
                                    }
                                } else {
                                    $this->helper->add('wordpress_attachments_mepr', $file);
                                }
                                $attachments[] = $file;
                            }
                        }
                    }
                }
            }
        }
        return $attachments;
    }

    public function filter_mepr_the_content($content) {
        if (
                class_exists('MeprUtils') && method_exists('MeprUtils', 'get_current_post') &&
                class_exists('MeprOptions') && method_exists('MeprOptions', 'fetch')
        ) {
            $current_post = MeprUtils::get_current_post();
            if ($current_post !== false) {
                $mepr_options = MeprOptions::fetch();
                if ($current_post->ID == $mepr_options->thankyou_page_id) {
                    remove_shortcode('e2pdf-download');
                    remove_shortcode('e2pdf-view');
                    remove_shortcode('e2pdf-save');
                    remove_shortcode('e2pdf-zapier');
                    remove_shortcode('e2pdf-adobesign');
                }
            }
        }
        return $content;
    }

    public function filter_mepr_custom_thankyou_message($content) {
        add_shortcode('e2pdf-download', array(new Model_E2pdf_Shortcode(), 'e2pdf_download'));
        add_shortcode('e2pdf-view', array(new Model_E2pdf_Shortcode(), 'e2pdf_view'));
        add_shortcode('e2pdf-save', array(new Model_E2pdf_Shortcode(), 'e2pdf_save'));
        add_shortcode('e2pdf-zapier', array(new Model_E2pdf_Shortcode(), 'e2pdf_zapier'));
        add_shortcode('e2pdf-adobesign', array(new Model_E2pdf_Shortcode(), 'e2pdf_adobesign'));
        if (isset($_REQUEST['trans_num']) && class_exists('MeprTransaction') && method_exists('MeprTransaction', 'get_one_by_trans_num')) {
            $txn = new MeprTransaction();
            $data = MeprTransaction::get_one_by_trans_num($_REQUEST['trans_num']);
            if (method_exists($txn, 'load_data')) {
                $txn->load_data($data);
                if (false !== strpos($content, '[')) {
                    $shortcode_tags = array(
                        'e2pdf-download',
                        'e2pdf-view',
                        'e2pdf-save',
                        'e2pdf-zapier',
                        'e2pdf-adobesign',
                    );
                    preg_match_all('@\[([^<>&/\[\]\x00-\x20=]++)@', $content, $matches);
                    $tagnames = array_intersect($shortcode_tags, $matches[1]);
                    if (!empty($tagnames)) {
                        preg_match_all('/' . $this->helper->load('shortcode')->get_shortcode_regex($tagnames) . '/', $content, $shortcodes);
                        foreach ($shortcodes[0] as $key => $shortcode_value) {
                            wp_reset_postdata();
                            $shortcode = $this->helper->load('shortcode')->get_shortcode($shortcodes, $key);
                            $atts = shortcode_parse_atts($shortcode[3]);
                            if ($this->helper->load('shortcode')->is_attachment($shortcode, $atts)) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
                            } elseif (isset($atts['id'])) {
                                $user_id = !empty($txn->user_id) ? $txn->user_id : get_current_user_id();
                                $template = new Model_E2pdf_Template();
                                $template->load($atts['id']);
                                if ($template->get('extension') === 'wordpress' && $template->get('item') == '-3') { // phpcs:ignore WordPress.WP.CapitalPDangit.Misspelled
                                    if (!isset($atts['dataset'])) {
                                        $atts['dataset'] = $user_id;
                                        $shortcode[3] .= ' dataset="' . $user_id . '"';
                                    }
                                } else {
                                    if (!isset($atts['user_id'])) {
                                        $atts['user_id'] = $user_id;
                                        $shortcode[3] .= ' user_id="' . $user_id . '"';
                                    }
                                }
                            }
                            if (!isset($atts['apply'])) {
                                $shortcode[3] .= ' apply="true"';
                            }
                            if (!isset($atts['filter'])) {
                                $shortcode[3] .= ' filter="true"';
                            }
                            $content = str_replace($shortcode_value, do_shortcode_tag($shortcode), $content);
                        }
                    }
                }
            }
        }
        return $content;
    }

    public function filter_user_registration_smart_tag_values($values) {
        foreach ($values as $key => $value) {
            if (is_string($value) && false !== strpos($value, '[e2pdf-')) {
                $values[$key] = str_replace('[e2pdf-', '&#91;e2pdf-', $value);
            }
        }
        return $values;
    }

    public function filter_wp_mail($args) {
        if (isset($args['message'])) {
            if (false !== strpos($args['message'], '[')) {
                $shortcode_tags = array(
                    'e2pdf-download',
                    'e2pdf-save',
                    'e2pdf-attachment',
                    'e2pdf-adobesign',
                    'e2pdf-zapier',
                );
                preg_match_all('@\[([^<>&/\[\]\x00-\x20=]++)@', $args['message'], $matches);
                $tagnames = array_intersect($shortcode_tags, $matches[1]);
                if (!empty($tagnames)) {
                    $args['attachments'] = $this->helper->load('convert')->to_array($args['attachments']);
                    preg_match_all('/' . $this->helper->load('shortcode')->get_shortcode_regex($tagnames) . '/', $args['message'], $shortcodes);
                    foreach ($shortcodes[0] as $key => $shortcode_value) {
                        $shortcode = $this->helper->load('shortcode')->get_shortcode($shortcodes, $key);
                        $atts = shortcode_parse_atts($shortcode[3]);
                        if (!isset($atts['apply'])) {
                            $shortcode[3] .= ' apply="true"';
                        }
                        if (!isset($atts['filter'])) {
                            $shortcode[3] .= ' filter="true"';
                        }
                        $file = false;
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
                                        $this->helper->add('wordpress_attachments', $file);
                                    }
                                } else {
                                    $this->helper->add('wordpress_attachments', $file);
                                }
                                $args['attachments'][] = $file;
                            }
                            $args['message'] = str_replace($shortcode_value, '', $args['message']);
                        } else {
                            $args['message'] = str_replace($shortcode_value, do_shortcode_tag($shortcode), $args['message']);
                        }
                    }
                }
            }
        }
        $wp_mail = array(
            'to' => $args['to'],
            'subject' => $args['subject'],
            'message' => $args['message'],
            'headers' => $args['headers'],
            'attachments' => $args['attachments'],
        );
        return $wp_mail;
    }

    /**
     * Thrive Theme Builder dynamic shortcode support
     */
    public function filter_thrive_theme_template_content($html) {
        add_filter('e2pdf_model_shortcode_e2pdf_download_atts', array($this, 'filter_global_post_id'));
        add_filter('e2pdf_model_shortcode_e2pdf_view_atts', array($this, 'filter_global_post_id'));
        add_filter('e2pdf_model_shortcode_e2pdf_save_atts', array($this, 'filter_global_post_id'));
        add_filter('e2pdf_model_shortcode_e2pdf_zapier_atts', array($this, 'filter_global_post_id'));
        return $html;
    }

    /**
     * Dynamic Post ID support
     */
    public function filter_global_post_id($atts) {
        if (!isset($atts['dataset']) && isset($atts['id'])) {
            $template_id = isset($atts['id']) ? (int) $atts['id'] : 0;
            $template = new Model_E2pdf_Template();
            if ($template->load($template_id, false)) {
                if ($template->get('extension') === 'wordpress') { // phpcs:ignore WordPress.WP.CapitalPDangit.Misspelled
                    global $post;
                    if (isset($post->ID)) {
                        $atts['dataset'] = (string) $post->ID;
                        if (!isset($atts['apply'])) {
                            $atts['apply'] = 'true';
                        }
                        if (!isset($atts['filter'])) {
                            $atts['filter'] = 'true';
                        }
                    }
                }
            }
        }
        return $atts;
    }

    public function filter_et_pb_module_content($content, $props, $attrs, $render_slug, $_address, $global_content) {
        if (($render_slug == 'et_pb_code' || $render_slug == 'et_pb_text') &&
                false !== strpos($content, '[') &&
                function_exists('et_core_is_builder_used_on_current_request') &&
                !et_core_is_builder_used_on_current_request()
        ) {
            global $post;
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
                    wp_reset_postdata();
                    $shortcode = $this->helper->load('shortcode')->get_shortcode($shortcodes, $key);
                    $atts = shortcode_parse_atts($shortcode[3]);
                    if ($this->helper->load('shortcode')->is_attachment($shortcode, $atts)) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
                    } else {
                        if (!isset($atts['dataset']) && isset($atts['id']) && (isset($post->ID))) {
                            $dataset = $post->ID;
                            $atts['dataset'] = $dataset;
                            $shortcode[3] .= ' dataset="' . $dataset . '"';
                        }
                    }
                    if (!isset($atts['apply'])) {
                        $shortcode[3] .= ' apply="true"';
                    }

                    if (!isset($atts['filter'])) {
                        $shortcode[3] .= ' filter="true"';
                    }
                    $new_shortcode = '[' . $shortcode[2] . $shortcode[3] . ']';
                    $content = str_replace($shortcode_value, $new_shortcode, $content);
                }
            }
        }
        return $content;
    }

    /**
     * Verify if item and dataset exists
     * @return bool - item and dataset exists
     */
    public function verify() {
        if ($this->get('item') && $this->get('cached_post') && (($this->get('item') == get_post_type($this->get('cached_post'))) || ($this->get('item') == '-3' && $this->get('cached_post')))) {
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

        if ($this->get('item') != '-3') {
            $vc .= '<h3 class="e2pdf-plr5">Common</h3>';
            $vc .= '<div class="e2pdf-grid">';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('ID', 'e2pdf-wp key="id"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Author', 'e2pdf-wp key="post_author"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Author ID', 'e2pdf-wp key="post_author_id"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Date', 'e2pdf-wp key="post_date"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Date (GMT)', 'e2pdf-wp key="post_date_gmt"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Content', 'e2pdf-wp key="post_content"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Title', 'e2pdf-wp key="post_title"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Excerpt', 'e2pdf-wp key="post_excerpt"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Status', 'e2pdf-wp key="post_status"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Comment Status', 'e2pdf-wp key="comment_status"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Ping Status', 'e2pdf-wp key="ping_status"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Password', 'e2pdf-wp key="post_password"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Name', 'e2pdf-wp key="post_name"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('To Ping', 'e2pdf-wp key="to_ping"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Ping', 'e2pdf-wp key="pinged"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Modified Date', 'e2pdf-wp key="post_modified"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Modified Date (GMT)', 'e2pdf-wp key="post_modified_gmt"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Filtered Content', 'e2pdf-wp key="post_content_filtered"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Parent ID', 'e2pdf-wp key="post_parent"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('GUID', 'e2pdf-wp key="guid"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Menu Order', 'e2pdf-wp key="menu_order"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Type', 'e2pdf-wp key="post_type"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Mime Type', 'e2pdf-wp key="post_mime_type"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Comments Count', 'e2pdf-wp key="comment_count"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Filter', 'e2pdf-wp key="filter"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Post Thumbnail', 'e2pdf-wp key="get_the_post_thumbnail"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Post Thumbnail URL', 'e2pdf-wp key="get_the_post_thumbnail_url"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Permalink', 'e2pdf-wp key="get_permalink"') . '</div>';
            $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element('Post Permalink', 'e2pdf-wp key="get_post_permalink"') . '</div>';
            $vc .= '</div>';

            $meta_keys = $this->get_post_meta_keys();
            if (!empty($meta_keys)) {
                $vc .= '<h3 class="e2pdf-plr5">Meta Keys</h3>';
                $vc .= "<div class='e2pdf-grid'>";
                foreach ($meta_keys as $meta_key) {
                    $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element($meta_key, 'e2pdf-wp key="' . $meta_key . '" meta="true"') . '</div>';
                }
                $vc .= '</div>';
            }

            $meta_keys = $this->get_post_taxonomy_keys();
            if (!empty($meta_keys)) {
                $vc .= '<h3 class="e2pdf-plr5">Taxonomy</h3>';
                $vc .= "<div class='e2pdf-grid'>";
                foreach ($meta_keys as $meta_key) {
                    $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element($meta_key, 'e2pdf-wp key="' . $meta_key . '" terms="true" names="true"') . '</div>';
                }
                $vc .= '</div>';
            }
        }

        $vc .= '<h3 class="e2pdf-plr5">User</h3>';
        $vc .= "<div class='e2pdf-grid'>";
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
            $vc .= "<div class='e2pdf-grid'>";
            foreach ($meta_keys as $meta_key) {
                $vc .= '<div class="e2pdf-ib e2pdf-w50 e2pdf-vm-item">' . $this->get_vm_element($meta_key, 'e2pdf-user key="' . $meta_key . '" meta="true"') . '</div>';
            }
            $vc .= '</div>';
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
            if ($this->get('item') == '-3') {
                $groups = acf_get_field_groups(
                        array(
                            'user_id' => 'new',
                            'user_form' => 'all',
                        )
                );
            } else {
                $groups = acf_get_field_groups(array('post_type' => $this->get('item')));
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

    public function get_post_meta_keys() {
        global $wpdb;
        $meta_keys = array();
        if ($this->get('item')) {
            $condition = array(
                'p.post_type' => array(
                    'condition' => '=',
                    'value' => $this->get('item'),
                    'type' => '%s',
                ),
            );
            $order_condition = array(
                'orderby' => 'meta_key',
                'order' => 'desc',
            );
            $where = $this->helper->load('db')->prepare_where($condition);
            $orderby = $this->helper->load('db')->prepare_orderby($order_condition);
            $meta_keys = $wpdb->get_col($wpdb->prepare('SELECT DISTINCT `meta_key` FROM `' . $wpdb->postmeta . '` `pm` LEFT JOIN ' . $wpdb->posts . ' `p` ON (`p`.`ID` = `pm`.`post_ID`) ' . $where['sql'] . $orderby . '', $where['filter']));
        }
        return $meta_keys;
    }

    public function get_user_meta_keys() {
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

    public function get_post_taxonomy_keys() {
        global $wpdb;
        $meta_keys = array();
        if ($this->get('item')) {
            $order_condition = array(
                'orderby' => 'taxonomy',
                'order' => 'desc',
            );
            $orderby = $this->helper->load('db')->prepare_orderby($order_condition);
            $meta_keys = $wpdb->get_col($wpdb->prepare('SELECT DISTINCT `taxonomy` FROM `' . $wpdb->term_taxonomy . '` `t` ' . $orderby . ''));
        }
        return $meta_keys;
    }

    public function get_vm_element($name, $id) {
        $element = '<div>';
        $element .= '<label>' . $name . ':</label>';
        $element .= '<input type="text" name=\'[' . $id . ']\' value=\'[' . $id . ']\' class="e2pdf-w100">';
        $element .= '</div>';
        return $element;
    }

    public function hook_wordpress_page_edit() {
        $items = $this->helper->load('hooks')->get_items('wordpress', 'hook_wordpress_page_edit');
        if (!empty($items)) {
            add_meta_box(
                    'e2pdf',
                    apply_filters('e2pdf_hook_section_title', __('E2Pdf Actions', 'e2pdf'), 'hook_wordpress_page_edit'),
                    array($this, 'hook_wordpress_page_edit_callback'),
                    $items,
                    'side',
                    'default'
            );
        }
    }

    public function hook_wordpress_page_edit_callback($post) {
        if (!empty($post->post_type)) {
            $hooks = $this->helper->load('hooks')->get('wordpress', 'hook_wordpress_page_edit', $post->post_type);
            if (!empty($hooks)) {
                foreach ($hooks as $hook) {
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
                            ), 'hook_wordpress_page_edit', $hook, $post->ID
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

    public function hook_wordpress_row_actions($actions, $post) {
        if ($post && is_a($post, 'WP_User')) {
            if (!empty($post->ID)) {
                $hooks = $this->helper->load('hooks')->get('wordpress', 'hook_wordpress_row_actions', '-3');
                foreach ($hooks as $hook) {
                    $action = apply_filters('e2pdf_hook_action_button',
                            array(
                                'html' => '<a class="e2pdf-download-hook" target="_blank" href="%s">%s</a>',
                                'url' => $this->helper->get_url(
                                        array(
                                            'page' => 'e2pdf',
                                            'action' => 'export',
                                            'id' => $hook,
                                            'dataset' => $post->ID,
                                        ), 'admin.php?'
                                ),
                                'title' => 'PDF #' . $hook
                            ), 'hook_wordpress_row_actions', $hook, $post->ID
                    );
                    if (!empty($action)) {
                        $actions['e2pdf_' . $hook] = sprintf(
                                $action['html'], $action['url'], $action['title']
                        );
                    }
                }
            }
        } else {
            if (!empty($post->post_type) && !empty($post->ID)) {
                $hooks = $this->helper->load('hooks')->get('wordpress', 'hook_wordpress_row_actions', $post->post_type);
                foreach ($hooks as $hook) {
                    $action = apply_filters('e2pdf_hook_action_button',
                            array(
                                'html' => '<a class="e2pdf-download-hook" target="_blank" href="%s">%s</a>',
                                'url' => $this->helper->get_url(
                                        array(
                                            'page' => 'e2pdf',
                                            'action' => 'export',
                                            'id' => $hook,
                                            'dataset' => $post->ID,
                                        ), 'admin.php?'
                                ),
                                'title' => 'PDF #' . $hook
                            ), 'hook_wordpress_row_actions', $hook, $post->ID
                    );
                    if (!empty($action)) {
                        $actions['e2pdf_' . $hook] = sprintf(
                                $action['html'], $action['url'], $action['title']
                        );
                    }
                }
            }
        }
        return $actions;
    }
}
