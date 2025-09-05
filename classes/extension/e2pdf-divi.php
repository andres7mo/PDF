<?php

/**
 * E2Pdf Divi Extension
 * @copyright  Copyright 2017 https://e2pdf.com
 * @license    GPLv3
 * @version    1
 * @link       https://e2pdf.com
 * @since      0.00.01
 */
if (!defined('ABSPATH')) {
    die('Access denied.');
}

class Extension_E2pdf_Divi extends Model_E2pdf_Model {

    private $options;
    private $info = array(
        'key' => 'divi',
        'title' => 'Divi Forms',
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
        if (file_exists(get_template_directory() . '/et-pagebuilder/et-pagebuilder.php')) {
            return true;
        } else {
            if (defined('E2PDF_DIVI_EXTENSION') || $this->helper->load('extension')->is_plugin_active('divi-builder/divi-builder.php')) {
                return true;
            }
        }
        return false;
    }

    /**
     * Set option
     * @param string $attr - Key of option
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
                $this->set('cached_entry', array());
                $this->set('cached_meta', array());
                if ($this->get('item') && $this->get('dataset')) {
                    $entry = new Model_E2pdf_Dataset();
                    if ($entry->load($this->get('dataset'), $this->get('item'), 'divi')) {
                        $cached_entry = $entry->get('entry');
                        $this->set('cached_entry', $cached_entry);
                        $processed_fields_values = array();
                        if ($cached_entry && is_array($cached_entry)) {
                            $et_contact_proccess = array_search('et_contact_proccess', $cached_entry);
                            $et_pb_contact_form_num = $et_contact_proccess === false ? 0 : str_replace('et_pb_contactform_submit_', '', $et_contact_proccess);
                            $current_form_fields = isset($cached_entry['et_pb_contact_email_fields_' . $et_pb_contact_form_num]) ? $cached_entry['et_pb_contact_email_fields_' . $et_pb_contact_form_num] : '';
                            if ('' !== $current_form_fields) {
                                $fields_data_json = str_replace('\\', '', $current_form_fields);
                                $fields_data_array = json_decode($fields_data_json, true);
                                if (!empty($fields_data_array)) {
                                    foreach ($fields_data_array as $index => $field_value) {
                                        $processed_fields_values[$field_value['original_id']]['value'] = isset($cached_entry[$field_value['field_id']]) ? $cached_entry[$field_value['field_id']] : '';
                                        $processed_fields_values[$field_value['original_id']]['label'] = $field_value['field_label'];
                                        if (
                                                (
                                                (isset($cached_entry[$field_value['field_id'] . '_is_signature_pad']) && $cached_entry[$field_value['field_id'] . '_is_signature_pad'] == 'yes') ||
                                                (isset($cached_entry[$field_value['field_id'] . '_is_file']) && $cached_entry[$field_value['field_id'] . '_is_file'] == 'yes')
                                                ) && $processed_fields_values[$field_value['original_id']]['value']
                                        ) {
                                            $subdir = isset($cached_entry['_subdir']) ? $cached_entry['_subdir'] : '';
                                            if (isset($cached_entry['_save_files_to_media']) && $cached_entry['_save_files_to_media'] == 'on') {
                                                if ($subdir && !file_exists(path_join(wp_upload_dir()['basedir'] . $subdir, $processed_fields_values[$field_value['original_id']]['value'])) && preg_match('/^\/\d{4}\/\d{2}$/', $subdir)) {
                                                    $tmpsubdir = '/' . date('Y/m', strtotime(str_replace('/', '-', ltrim($subdir, '/')) . " -1 month"));
                                                    if (file_exists(path_join(wp_upload_dir()['basedir'] . $tmpsubdir, $processed_fields_values[$field_value['original_id']]['value']))) {
                                                        $subdir = $tmpsubdir;
                                                    }
                                                }
                                                $processed_fields_values[$field_value['original_id']]['value'] = path_join(wp_upload_dir()['baseurl'] . $subdir, $processed_fields_values[$field_value['original_id']]['value']);
                                            } else {
                                                $contact_form_id = isset($cached_entry['_unique_id']) ? $cached_entry['_unique_id'] : '';
                                                if (function_exists('pwh_dcfh_file_helpers') && $contact_form_id) {
                                                    if ($subdir && !file_exists(pwh_dcfh_file_helpers()::get_form_upload_dir($contact_form_id, $subdir, $processed_fields_values[$field_value['original_id']]['value'])) && preg_match('/^\d{4}\/\d{2}$/', $subdir)) {
                                                        $tmpsubdir = date('Y/m', strtotime(str_replace('/', '-', ltrim($subdir, '/')) . " -1 month"));
                                                        if (file_exists(pwh_dcfh_file_helpers()::get_form_upload_dir($contact_form_id, $tmpsubdir, $processed_fields_values[$field_value['original_id']]['value']))) {
                                                            $subdir = $tmpsubdir;
                                                        }
                                                    }
                                                    $processed_fields_values[$field_value['original_id']]['value'] = pwh_dcfh_file_helpers()::get_form_upload_url($contact_form_id, $subdir, $processed_fields_values[$field_value['original_id']]['value']);
                                                } else {
                                                    $processed_fields_values[$field_value['original_id']]['value'] = '';
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                            if (!isset($processed_fields_values['_wp_http_referer'])) {
                                $processed_fields_values['_wp_http_referer'] = array(
                                    'value' => isset($cached_entry['_wp_http_referer']) ? $cached_entry['_wp_http_referer'] : '',
                                    'label' => '_wp_http_referer',
                                );
                            }

                            if (!isset($processed_fields_values['e2pdf_entry_id'])) {
                                $processed_fields_values['e2pdf_entry_id'] = array(
                                    'value' => (int) $this->get('dataset'),
                                    'label' => 'e2pdf_entry_id',
                                );
                            }
                        }
                        $meta_data = array();
                        foreach ($processed_fields_values as $field_key => $field_value) {
                            $meta_data['%%' . $field_key . '%%'] = $field_value['value'];
                        }
                        $this->set('cached_meta', $meta_data);
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
        if (isset($this->options->$key)) {
            $value = $this->options->$key;
        } else {
            switch ($key) {
                case 'args':
                case 'cached_entry':
                case 'cached_meta':
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
        global $wpdb;
        $condition = array(
            'post_content' => array(
                'condition' => 'LIKE',
                'value' => '%et_pb_contact_form%',
                'type' => '%s',
            ),
            'post_type' => array(
                'condition' => '<>',
                'value' => array(
                    'revision',
                    'et_pb_layout',
                ),
                'type' => '%s',
            ),
        );
        $order_condition = array(
            'orderby' => 'id',
            'order' => 'desc',
        );
        $where = $this->helper->load('db')->prepare_where($condition);
        $orderby = $this->helper->load('db')->prepare_orderby($order_condition);
        $posts = $wpdb->get_results($wpdb->prepare('SELECT * FROM `' . $wpdb->prefix . 'posts`' . $where['sql'] . $orderby . '', $where['filter']));

        $content = array();
        foreach ($posts as $key => $post) {
            foreach ($this->get_forms($post->post_content) as $form_key => $form_value) {
                $content[] = $this->item($form_key);
            }
        }
        return $content;
    }

    /**
     * Parse available forms from pages
     * @param string $content - Page content
     * @return array() - Forms list
     */
    public function get_forms($content) {
        $forms = array();
        if (false !== strpos($content, 'et_pb_contact_form')) {
            $shortcode_tags = array(
                'et_pb_contact_form',
            );
            preg_match_all('@\[([^<>&/\[\]\x00-\x20=]++)@', $content, $matches);
            $tagnames = array_intersect($shortcode_tags, $matches[1]);
            if (!empty($tagnames)) {
                preg_match_all('/' . $this->helper->load('shortcode')->get_shortcode_regex($tagnames) . '/', $content, $shortcodes);
                foreach ($shortcodes[0] as $key => $shortcode_value) {
                    $shortcode = $this->helper->load('shortcode')->get_shortcode($shortcodes, $key);
                    preg_match_all('/admin_label="(.*?)"/', $shortcode[3], $labels);
                    if (isset($labels['1'])) {
                        foreach ($labels['1'] as $label) {
                            $forms[$label] = $label;
                        }
                    }
                }
            }
        }
        return $forms;
    }

    /**
     * Get entries for export
     * @param string $item_id - Item ID
     * @param string $name - Entries names
     * @return array() - Entries list
     */
    public function datasets($item_id = false, $name = false) {
        global $wpdb;
        $datasets = array();
        if ($item_id) {
            $condition = array(
                'extension' => array(
                    'condition' => '=',
                    'value' => 'divi',
                    'type' => '%s',
                ),
                'item' => array(
                    'condition' => '=',
                    'value' => $item_id,
                    'type' => '%s',
                ),
            );
            $order_condition = array(
                'orderby' => 'ID',
                'order' => 'desc',
            );
            $where = $this->helper->load('db')->prepare_where($condition);
            $orderby = $this->helper->load('db')->prepare_orderby($order_condition);
            $entries = $wpdb->get_results($wpdb->prepare('SELECT * FROM `' . $wpdb->prefix . 'e2pdf_datasets`' . $where['sql'] . $orderby . '', $where['filter']));

            if ($entries) {
                $this->set('item', $item_id);
                foreach ($entries as $key => $entry) {
                    $this->set('dataset', $entry->ID);
                    $entry_title = $this->render($name);
                    if (!$entry_title) {
                        $entry_title = $entry->ID;
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
     * Get Dataset Actions
     * @param int $dataset_id - Dataset ID
     * @return object
     */
    public function get_dataset_actions($dataset_id = false) {
        $dataset_id = (int) $dataset_id;
        if (!$dataset_id) {
            return;
        }
        $actions = new stdClass();
        $actions->view = false;
        $actions->delete = true;
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
        $actions->delete = true;
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
        if ($item_id) {
            $item->id = (string) $item_id;
            $item->name = $item_id;
            $form = $this->get_form($item_id);
            if (isset($form->ID)) {
                $item->url = $this->helper->get_url(
                        array(
                            'post' => $form->ID,
                            'action' => 'edit',
                        ), 'post.php?'
                );
            } else {
                $item->url = 'javascript:void(0);';
            }
        } else {
            $item->id = '';
            $item->name = '';
            $item->url = 'javascript:void(0);';
        }
        return $item;
    }

    /**
     * Get post
     * @param string $item_id - Item ID
     * @return object - Post
     */
    public function get_form($item_id = false) {
        global $wpdb;
        $item_post = false;
        $condition = array(
            'post_content' => array(
                'condition' => 'LIKE',
                'value' => '%admin_label="' . $item_id . '"%',
                'type' => '%s',
            ),
            'post_type' => array(
                'condition' => '<>',
                'value' => array(
                    'revision',
                    'et_pb_layout',
                ),
                'type' => '%s',
            ),
        );

        $order_condition = array(
            'orderby' => 'id',
            'order' => 'desc',
        );

        $where = $this->helper->load('db')->prepare_where($condition);
        $orderby = $this->helper->load('db')->prepare_orderby($order_condition);

        $posts = $wpdb->get_results($wpdb->prepare('SELECT * FROM `' . $wpdb->prefix . 'posts`' . $where['sql'] . $orderby . '', $where['filter']));
        foreach ($posts as $key => $post) {
            if (in_array($item_id, $this->get_forms($post->post_content))) {
                $item_post = $post;
                break;
            }
        }
        return $item_post;
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
                $value = $this->helper->load('field')->wrapper_shortcodes($value, $this, $field);
            }
            $value = $this->helper->load('field')->do_shortcodes($value, $this, $field);
            if (!empty($this->get('cached_meta'))) {
                $value = $this->helper->load('convert')->stritr($value, $this->get('cached_meta'));
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
        $value = preg_replace('~%%[^%%]*%%~', '', $value);
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
                $value = stripslashes_deep($value);
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

        if ($this->get('item')) {
            $post = $this->get_form($this->get('item'));
            if ($post && isset($post->post_content)) {
                $content = $post->post_content;
                if (false !== strpos($content, '[')) {
                    $shortcode_tags = array(
                        'et_pb_contact_form',
                    );
                    preg_match_all('@\[([^<>&/\[\]\x00-\x20=]++)@', $content, $matches);
                    $tagnames = array_intersect($shortcode_tags, $matches[1]);
                    if (!empty($tagnames)) {
                        preg_match_all('/' . $this->helper->load('shortcode')->get_shortcode_regex($tagnames) . '/', $content, $shortcodes);
                        foreach ($shortcodes[0] as $key => $shortcode_value) {
                            $shortcode = $this->helper->load('shortcode')->get_shortcode($shortcodes, $key);
                            $atts = shortcode_parse_atts($shortcode[3]);
                            if (isset($atts['admin_label']) && $atts['admin_label'] == $this->get('item') && defined('ET_BUILDER_DIR')) {

                                require_once ET_BUILDER_DIR . 'class-et-builder-element.php';
                                require_once ET_BUILDER_DIR . 'functions.php';
                                require_once ET_BUILDER_DIR . 'ab-testing.php';
                                require_once ET_BUILDER_DIR . 'class-et-global-settings.php';
                                if (file_exists(ET_BUILDER_DIR . 'module/type/WithSpamProtection.php')) {
                                    require_once ET_BUILDER_DIR . 'module/type/WithSpamProtection.php';
                                }
                                require_once ET_BUILDER_DIR . 'module/ContactForm.php';
                                require_once ET_BUILDER_DIR . 'module/ContactFormItem.php';

                                new ET_Builder_Module_Contact_Form();
                                new ET_Builder_Module_Contact_Form_Item();

                                $source = do_shortcode($shortcode_value);

                                libxml_use_internal_errors(true);
                                $dom = new DOMDocument();
                                if (function_exists('mb_convert_encoding')) {
                                    if (defined('LIBXML_HTML_NOIMPLIED') && defined('LIBXML_HTML_NODEFDTD')) {
                                        $html = $dom->loadHTML(mb_convert_encoding('<html>' . $source . '</html>', 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
                                    } else {
                                        $html = $dom->loadHTML(mb_convert_encoding($source, 'HTML-ENTITIES', 'UTF-8'));
                                    }
                                } else {
                                    if (defined('LIBXML_HTML_NOIMPLIED') && defined('LIBXML_HTML_NODEFDTD')) {
                                        $html = $dom->loadHTML('<?xml encoding="UTF-8"><html>' . $source . '</html>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
                                    } else {
                                        $html = $dom->loadHTML('<?xml encoding="UTF-8">' . $source);
                                    }
                                }
                                libxml_clear_errors();

                                if ($html) {

                                    $xml = $this->helper->load('xml');
                                    $xml->set('dom', $dom);
                                    $xpath = new DomXPath($dom);

                                    $blocks = $xpath->query("//*[contains(@class, 'et_pb_contact_field')]");
                                    foreach ($blocks as $element) {

                                        if ($xml->get_node_value($element, 'data-type') == 'radio') {

                                            $label = $xpath->query('.//label', $element)->item(0);
                                            $check_handler = $xpath->query(".//input[contains(@class, 'et_pb_checkbox_handle')]", $element)->item(0);

                                            $name = '';
                                            if ($check_handler) {
                                                $name = '%%' . $xml->get_node_value($check_handler, 'data-original_id') . '%%';
                                            }

                                            if ($label) {
                                                $elements[] = $this->auto_field(
                                                        $element,
                                                        array(
                                                            'type' => 'e2pdf-html',
                                                            'block' => true,
                                                            'class' => $xml->get_node_value($element, 'class'),
                                                            'properties' => array(
                                                                'top' => '20',
                                                                'left' => '20',
                                                                'right' => '20',
                                                                'width' => '100%',
                                                                'height' => 'auto',
                                                                'value' => $label->nodeValue,
                                                            ),
                                                        )
                                                );
                                            }

                                            $fields = $xpath->query("//*[contains(@class, 'et_pb_contact_field_radio')]", $element);
                                            foreach ($fields as $field) {
                                                $radio_label = $xpath->query('.//label', $field)->item(0);
                                                $radio = $xpath->query(".//input[@type='radio']", $field)->item(0);

                                                if (
                                                        $radio->attributes->getNamedItem('data-original_id') &&
                                                        $radio->attributes->getNamedItem('value')
                                                ) {
                                                    $elements[] = $this->auto_field(
                                                            $field,
                                                            array(
                                                                'type' => 'e2pdf-radio',
                                                                'class' => $xml->get_node_value($field, 'class'),
                                                                'properties' => array(
                                                                    'top' => '5',
                                                                    'width' => 'auto',
                                                                    'height' => 'auto',
                                                                    'value' => '%%' . $xml->get_node_value($radio, 'data-original_id') . '%%',
                                                                    'option' => $xml->get_node_value($radio, 'value'),
                                                                    'group' => '%%' . $xml->get_node_value($radio, 'data-original_id') . '%%',
                                                                ),
                                                            )
                                                    );
                                                }

                                                if ($radio_label) {
                                                    $elements[] = $this->auto_field(
                                                            $radio,
                                                            array(
                                                                'type' => 'e2pdf-html',
                                                                'float' => true,
                                                                'class' => $xml->get_node_value($radio, 'class'),
                                                                'properties' => array(
                                                                    'left' => '5',
                                                                    'width' => '100%',
                                                                    'height' => 'auto',
                                                                    'value' => $radio_label->nodeValue,
                                                                ),
                                                            )
                                                    );
                                                }
                                            }
                                        } elseif ($xml->get_node_value($element, 'data-type') == 'checkbox') {

                                            $label = $xpath->query('.//label', $element)->item(0);
                                            $check_handler = $xpath->query(".//input[contains(@class, 'et_pb_checkbox_handle')]", $element)->item(0);

                                            $name = '';
                                            if ($check_handler) {
                                                $name = '%%' . $xml->get_node_value($check_handler, 'data-original_id') . '%%';
                                            }

                                            if ($label) {
                                                $elements[] = $this->auto_field(
                                                        $element,
                                                        array(
                                                            'type' => 'e2pdf-html',
                                                            'block' => true,
                                                            'class' => $xml->get_node_value($element, 'class'),
                                                            'properties' => array(
                                                                'top' => '20',
                                                                'left' => '20',
                                                                'right' => '20',
                                                                'width' => '100%',
                                                                'height' => 'auto',
                                                                'value' => $label->nodeValue,
                                                            ),
                                                        )
                                                );
                                            }

                                            $fields = $xpath->query("//*[contains(@class, 'et_pb_contact_field_checkbox')]", $element);
                                            foreach ($fields as $field) {
                                                $checkbox_label = $xpath->query('.//label', $field)->item(0);
                                                $checkbox = $xpath->query(".//input[@type='checkbox']", $field)->item(0);

                                                $elements[] = $this->auto_field(
                                                        $field,
                                                        array(
                                                            'type' => 'e2pdf-checkbox',
                                                            'class' => $xml->get_node_value($field, 'class'),
                                                            'properties' => array(
                                                                'top' => '5',
                                                                'width' => 'auto',
                                                                'height' => 'auto',
                                                                'value' => $name,
                                                                'option' => $xml->get_node_value($checkbox, 'value'),
                                                            ),
                                                        )
                                                );

                                                if ($checkbox_label) {
                                                    $elements[] = $this->auto_field(
                                                            $checkbox,
                                                            array(
                                                                'type' => 'e2pdf-html',
                                                                'float' => true,
                                                                'class' => $xml->get_node_value($checkbox, 'class'),
                                                                'properties' => array(
                                                                    'left' => '5',
                                                                    'width' => '100%',
                                                                    'height' => 'auto',
                                                                    'value' => $checkbox_label->nodeValue,
                                                                ),
                                                            )
                                                    );
                                                }
                                            }
                                        } else {

                                            $label = $xpath->query('.//label', $element)->item(0);
                                            $input_text = $xpath->query(".//input[@type='text']", $element)->item(0);
                                            $select = $xpath->query('.//select', $element)->item(0);
                                            $textarea = $xpath->query('.//textarea', $element)->item(0);

                                            if ($label && ($input_text || $select || $textarea)) {
                                                $elements[] = $this->auto_field(
                                                        $element,
                                                        array(
                                                            'type' => 'e2pdf-html',
                                                            'block' => true,
                                                            'class' => $xml->get_node_value($element, 'class'),
                                                            'properties' => array(
                                                                'top' => '20',
                                                                'left' => '20',
                                                                'right' => '20',
                                                                'width' => '100%',
                                                                'height' => 'auto',
                                                                'value' => $label->nodeValue,
                                                            ),
                                                        )
                                                );
                                            }

                                            if ($input_text) {
                                                $elements[] = $this->auto_field(
                                                        $input_text,
                                                        array(
                                                            'type' => 'e2pdf-input',
                                                            'class' => $xml->get_node_value($input_text, 'class'),
                                                            'properties' => array(
                                                                'top' => '5',
                                                                'width' => '100%',
                                                                'height' => 'auto',
                                                                'value' => '%%' . $xml->get_node_value($input_text, 'data-original_id') . '%%',
                                                            ),
                                                        )
                                                );
                                            } elseif ($select) {
                                                $options_tmp = array();
                                                $options = $xpath->query('.//option', $select);
                                                foreach ($options as $option) {
                                                    $options_tmp[] = $xml->get_node_value($option, 'value');
                                                }

                                                $elements[] = $this->auto_field(
                                                        $select,
                                                        array(
                                                            'type' => 'e2pdf-select',
                                                            'class' => $xml->get_node_value($select, 'class'),
                                                            'properties' => array(
                                                                'top' => '5',
                                                                'width' => '100%',
                                                                'height' => 'auto',
                                                                'options' => implode("\n", $options_tmp),
                                                                'value' => '%%' . $xml->get_node_value($select, 'data-original_id') . '%%',
                                                            ),
                                                        )
                                                );
                                            } elseif ($textarea) {
                                                $elements[] = $this->auto_field(
                                                        $textarea,
                                                        array(
                                                            'type' => 'e2pdf-textarea',
                                                            'class' => $xml->get_node_value($textarea, 'class'),
                                                            'properties' => array(
                                                                'top' => '5',
                                                                'width' => '100%',
                                                                'height' => '150',
                                                                'value' => '%%' . $xml->get_node_value($textarea, 'data-original_id') . '%%',
                                                            ),
                                                        )
                                                );
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        $response['page'] = array(
            'bottom' => '20',
            'top' => '20',
            'right' => '20',
            'left' => '20',
        );
        $response['elements'] = $elements;

        return $response;
    }

    /**
     * Convert Field name to Value
     * @since 0.01.34
     * @param string $name - Field name
     * @return bool|string - Converted value or false
     */
    public function auto_map($name = false) {
        $item = $this->get('item');
        if ($item) {
            $post = $this->get_form($item);
            if ($post && isset($post->post_content)) {
                $content = $post->post_content;

                if (false !== strpos($content, '[')) {
                    $shortcode_tags = array(
                        'et_pb_contact_form',
                    );
                    preg_match_all('@\[([^<>&/\[\]\x00-\x20=]++)@', $content, $matches);
                    $tagnames = array_intersect($shortcode_tags, $matches[1]);
                    if (!empty($tagnames)) {
                        preg_match_all('/' . $this->helper->load('shortcode')->get_shortcode_regex($tagnames) . '/', $content, $shortcodes);
                        foreach ($shortcodes[0] as $key => $shortcode_value) {
                            $shortcode = $this->helper->load('shortcode')->get_shortcode($shortcodes, $key);
                            $atts = shortcode_parse_atts($shortcode[3]);
                            if (isset($atts['admin_label']) && $atts['admin_label'] == $this->get('item')) {
                                $field_content = $shortcode_value;
                                $field_shortcode_tags = array(
                                    'et_pb_contact_field',
                                );
                                preg_match_all('@\[([^<>&/\[\]\x00-\x20=]++)@', $field_content, $field_matches);
                                $field_tagnames = array_intersect($field_shortcode_tags, $field_matches[1]);
                                if (!empty($field_tagnames)) {
                                    preg_match_all('/' . $this->helper->load('shortcode')->get_shortcode_regex($field_tagnames) . '/', $field_content, $field_shortcodes);
                                    foreach ($field_shortcodes[0] as $field_key => $field_shortcode_value) {
                                        $field_shortcode = array();
                                        $field_shortcode[3] = $field_shortcodes[3][$field_key];
                                        $field_atts = shortcode_parse_atts($field_shortcode[3]);
                                        if (isset($field_atts['field_title']) && isset($field_atts['field_id'])) {
                                            if ($field_atts['field_title'] == $name || $field_atts['field_id'] == $name) {
                                                return '%%' . strtolower($field_atts['field_id']) . '%%';
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * Generate field for Auto PDF
     * @param object $field - Formidable field object
     * @param string $type - Field type
     * @param array $options - Field additional options
     * @return array - Prepared auto field
     */
    public function auto_field($field = false, $element = array()) {

        if (!$field) {
            return false;
        }

        if (!isset($element['block'])) {
            $element['block'] = false;
        }

        if (!isset($element['float'])) {
            $element['float'] = false;
        }

        $classes = array();
        if (isset($element['class']) && $element['class']) {
            $classes = explode(' ', $element['class']);
            unset($element['class']);
        }

        $float_classes = array(
            'et_pb_contact_field_half',
        );
        $array_intersect = array_intersect($classes, $float_classes);

        if (!empty($array_intersect) && $element['block']) {
            $element['float'] = true;
        };

        $primary_class = false;
        if (!empty($array_intersect)) {
            $primary_class = end($array_intersect);
        }

        if ($element['block']) {
            switch ($primary_class) {
                case 'et_pb_contact_field_half':
                    $element['width'] = '50%';
                    break;
                default:
                    break;
            }
        }

        return $element;
    }

    /**
     * Verify if item and dataset exists
     * @return bool - item and dataset exists
     */
    public function verify() {
        if ($this->get('cached_entry')) {
            return true;
        }
        return false;
    }

    /**
     * Init Visual Mapper data
     * @return bool|string - HTML data source for Visual Mapper
     */
    public function visual_mapper() {

        $source = '';
        $html = '';

        if ($this->get('item')) {
            $post = $this->get_form($this->get('item'));
            if ($post && isset($post->post_content)) {

                $content = $post->post_content;

                if (false !== strpos($content, '[')) {
                    $shortcode_tags = array(
                        'et_pb_contact_form',
                    );
                    preg_match_all('@\[([^<>&/\[\]\x00-\x20=]++)@', $content, $matches);
                    $tagnames = array_intersect($shortcode_tags, $matches[1]);
                    if (!empty($tagnames)) {
                        preg_match_all('/' . $this->helper->load('shortcode')->get_shortcode_regex($tagnames) . '/', $content, $shortcodes);
                        foreach ($shortcodes[0] as $key => $shortcode_value) {
                            $shortcode = $this->helper->load('shortcode')->get_shortcode($shortcodes, $key);
                            $atts = shortcode_parse_atts($shortcode[3]);
                            if (isset($atts['admin_label']) && $atts['admin_label'] == $this->get('item')) {
                                require_once ET_BUILDER_DIR . 'class-et-builder-element.php';
                                require_once ET_BUILDER_DIR . 'functions.php';
                                require_once ET_BUILDER_DIR . 'ab-testing.php';
                                require_once ET_BUILDER_DIR . 'class-et-global-settings.php';
                                if (file_exists(ET_BUILDER_DIR . 'module/type/WithSpamProtection.php')) {
                                    require_once ET_BUILDER_DIR . 'module/type/WithSpamProtection.php';
                                }
                                require_once ET_BUILDER_DIR . 'module/ContactForm.php';
                                require_once ET_BUILDER_DIR . 'module/ContactFormItem.php';
                                new ET_Builder_Module_Contact_Form();
                                new ET_Builder_Module_Contact_Form_Item();
                                $source = do_shortcode($shortcode_value);
                                if ($source) {
                                    libxml_use_internal_errors(true);
                                    $dom = new DOMDocument();
                                    if (function_exists('mb_convert_encoding')) {
                                        $html = $dom->loadHTML(mb_convert_encoding($source, 'HTML-ENTITIES', 'UTF-8'));
                                    } else {
                                        $html = $dom->loadHTML('<?xml encoding="UTF-8">' . $source);
                                    }
                                    libxml_clear_errors();
                                }
                                if (!$source) {
                                    return '<div class="e2pdf-vm-error">' . __("The form source is empty or doesn't exist", 'e2pdf') . '</div>';
                                } elseif (!$html) {
                                    return '<div class="e2pdf-vm-error">' . __('The form could not be parsed due the incorrect HTML', 'e2pdf') . '</div>';
                                } else {
                                    $xml = $this->helper->load('xml');
                                    $xml->set('dom', $dom);
                                    $xpath = new DomXPath($dom);
                                    /* Replace names */
                                    $fields = $xpath->query("//*[contains(@name, 'et_pb_contact_')]");
                                    foreach ($fields as $element) {
                                        $xml->set_node_value($element, 'name', '%%' . $xml->get_node_value($element, 'data-original_id') . '%%');
                                    }
                                    $checkboxes = $xpath->query("//*[contains(@class, 'et_pb_contact_field') and @data-type='checkbox']");
                                    foreach ($checkboxes as $element) {
                                        $check_handler = $xpath->query(".//input[contains(@class, 'et_pb_checkbox_handle')]", $element)->item(0);

                                        $name = '';
                                        if ($check_handler) {
                                            $name = '%%' . $xml->get_node_value($check_handler, 'data-original_id') . '%%';
                                        }

                                        $checks = $xpath->query(".//input[@type='checkbox']", $element);
                                        foreach ($checks as $check) {
                                            $xml->set_node_value($check, 'name', $name);
                                        }
                                    }
                                    $remove_by_class = array(
                                        'et_pb_contact_submit',
                                        'et_pb_contactform_validate_field',
                                    );
                                    foreach ($remove_by_class as $key => $class) {
                                        $elements = $xpath->query("//*[contains(@class, '{$class}')]");
                                        foreach ($elements as $element) {
                                            $element->parentNode->removeChild($element);
                                        }
                                    }
                                    $remove_parent_by_class = array(
                                        'et_pb_contact_captcha_question',
                                    );
                                    foreach ($remove_parent_by_class as $key => $class) {
                                        $elements = $xpath->query("//*[contains(@class, '{$class}')]/parent::*");
                                        foreach ($elements as $element) {
                                            $element->parentNode->removeChild($element);
                                        }
                                    }
                                }

                                if (defined('LIBXML_HTML_NOIMPLIED') && defined('LIBXML_HTML_NODEFDTD')) {
                                    return str_replace(array('<html>', '</html>'), '', $dom->saveHTML());
                                } else {
                                    return $dom->saveHTML();
                                }
                            }
                        }
                    }
                }
            }
        }

        return false;
    }

    public function filter_wp_mail_pwh_dcfh($args) {
        if (isset($args['message']) && $args['message']) {
            $args['message'] = preg_replace('/(\{\{)((e2pdf-download|e2pdf-view|e2pdf-save|e2pdf-attachment|e2pdf-adobesign|e2pdf-zapier)[^\}]*?)(\}\})/', '[$2]', $args['message']);
        }
        return $this->filter_wp_mail($args);
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
                                        $this->helper->add('divi_attachments', $file);
                                    }
                                } else {
                                    $this->helper->add('divi_attachments', $file);
                                }
                                $args['attachments'][] = $file;
                            }
                            $args['message'] = str_replace($shortcode_value, '', $args['message']);
                        } else {
                            if (is_array($args['headers']) && !in_array('Content-Type: text/html; charset=UTF-8', $args['headers'])) {
                                $args['headers'][] = 'Content-Type: text/html; charset=UTF-8';
                            }
                            $args['message'] = str_replace($shortcode_value, do_shortcode_tag($shortcode), $args['message']);
                            $args['message'] = str_replace("\r\n", '<br/>', $args['message']);
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
     * Load actions for this extension
     */
    public function load_actions() { // phpcs:ignore Squiz.WhiteSpace.SuperfluousWhitespace.EndLine
    }

    /**
     * Load filters for this extension
     */
    public function load_filters() {
        /* Divi Contact Form Helper Confirmation Email compatibility fix */
        if (defined('PWH_DCFH_PLUGIN_FILE')) {
            add_filter('et_pb_module_shortcode_attributes', array($this, 'filter_et_pb_module_shortcode_attributes'), 9, 5);
        } else {
            add_filter('et_pb_module_shortcode_attributes', array($this, 'filter_et_pb_module_shortcode_attributes'), 30, 5);
        }
        add_filter('et_module_shortcode_output', array($this, 'filter_et_module_shortcode_output'), 30, 3);
    }

    public function filter_et_pb_module_shortcode_attributes($props, $attrs, $render_slug, $address, $content) {
        if ($render_slug && $render_slug == 'et_pb_contact_form' && !empty($_POST) && $et_contact_proccess = array_search('et_contact_proccess', $_POST)) {
            $e2pdf_shortcodes = false;
            $success_message = isset($props['success_message']) ? str_replace(array('&#91;', '&#93;', '&quot;'), array('[', ']', '"'), $props['success_message']) : '';
            if (false !== strpos($success_message, '[')) {
                $shortcode_tags = array(
                    'e2pdf-download',
                    'e2pdf-save',
                    'e2pdf-view',
                    'e2pdf-adobesign',
                    'e2pdf-zapier',
                );
                preg_match_all('@\[([^<>&/\[\]\x00-\x20=]++)@', $success_message, $matches);
                $tagnames = array_intersect($shortcode_tags, $matches[1]);
                if (!empty($tagnames)) {
                    $e2pdf_shortcodes = true;
                } else {
                    $success_message = '';
                }
            }

            $use_custom_message_richtext = isset($props['use_custom_message_richtext']) && $props['use_custom_message_richtext'] == 'on' && isset($props['custom_message_richtext']) ? true : false;
            if ($use_custom_message_richtext) {
                $custom_message = str_replace(array('&#91;', '&#93;'), array('[', ']'), $props['custom_message_richtext']);
            } else {
                $custom_message = isset($props['custom_message']) ? str_replace(array('&#91;', '&#93;'), array('[', ']'), $props['custom_message']) : '';
            }
            if (false !== strpos($custom_message, '[')) {
                $shortcode_tags = array(
                    'e2pdf-download',
                    'e2pdf-save',
                    'e2pdf-attachment',
                    'e2pdf-adobesign',
                    'e2pdf-zapier',
                );

                preg_match_all('@\[([^<>&/\[\]\x00-\x20=]++)@', $custom_message, $matches);
                $tagnames = array_intersect($shortcode_tags, $matches[1]);
                if (!empty($tagnames)) {
                    $e2pdf_shortcodes = true;
                } else {
                    $custom_message = '';
                }
            }

            /* Divi Contact Form Helper Confirmation Email and Confirmation Email RichText */
            $use_confirmation_message_richtext = isset($props['use_confirmation_email']) && $props['use_confirmation_email'] == 'on' && isset($props['use_confirmation_message_richtext']) && $props['use_confirmation_message_richtext'] == 'on' && isset($props['confirmation_message_richtext']) ? true : false;
            if ($use_confirmation_message_richtext) {
                $confirmation_email_message = str_replace(array('&#91;', '&#93;'), array('[', ']'), $props['confirmation_message_richtext']);
            } else {
                $confirmation_email_message = isset($props['use_confirmation_email']) && $props['use_confirmation_email'] == 'on' && isset($props['confirmation_email_message']) ? str_replace(array('&#91;', '&#93;'), array('[', ']'), $props['confirmation_email_message']) : '';
            }
            if (false !== strpos($confirmation_email_message, '[')) {
                $shortcode_tags = array(
                    'e2pdf-download',
                    'e2pdf-save',
                    'e2pdf-attachment',
                    'e2pdf-adobesign',
                    'e2pdf-zapier',
                );
                preg_match_all('@\[([^<>&/\[\]\x00-\x20=]++)@', $confirmation_email_message, $matches);
                $tagnames = array_intersect($shortcode_tags, $matches[1]);
                if (!empty($tagnames)) {
                    $e2pdf_shortcodes = true;
                } else {
                    $confirmation_email_message = '';
                }
            }

            /* Check if E2Pdf shortcodes exist in messages */
            if ($e2pdf_shortcodes) {
                $data = $_POST;
                if (isset($props['_unique_id'])) {
                    $data['_unique_id'] = $props['_unique_id'];
                }
                if (isset($props['save_files_to_media']) && $props['save_files_to_media'] == 'on') {
                    $data['_save_files_to_media'] = $props['save_files_to_media'];
                    $data['_subdir'] = wp_upload_dir()['subdir'];
                } elseif (function_exists('pwh_dcfh_file_helpers')) {
                    $subdir = pwh_dcfh_file_helpers()::get_subdir();
                    if (is_array($subdir)) {
                        $data['_subdir'] = implode('/', $subdir);
                    }
                }

                $captcha = isset($props['captcha']) ? $props['captcha'] : '';
                $use_spam_service = isset($props['use_spam_service']) ? $props['use_spam_service'] : 'off';
                $et_pb_contact_form_num = str_replace('et_pb_contactform_submit_', '', $et_contact_proccess);
                $et_contact_error = false;
                $current_form_fields = isset($data['et_pb_contact_email_fields_' . $et_pb_contact_form_num]) ? $data['et_pb_contact_email_fields_' . $et_pb_contact_form_num] : '';
                $contact_email = '';
                $nonce_result = isset($data['_wpnonce-et-pb-contact-form-submitted-' . $et_pb_contact_form_num]) && wp_verify_nonce($data['_wpnonce-et-pb-contact-form-submitted-' . $et_pb_contact_form_num], 'et-pb-contact-form-submit') ? true : false;

                if ($nonce_result && isset($data['et_pb_contactform_submit_' . $et_pb_contact_form_num]) && empty($data['et_pb_contact_et_number_' . $et_pb_contact_form_num])) {
                    if ('' !== $current_form_fields) {
                        $fields_data_json = str_replace('\\', '', $current_form_fields);
                        $fields_data_array = json_decode($fields_data_json, true);
                        /* Check whether captcha field is not empty */
                        if ('on' === $captcha && 'off' === $use_spam_service && (!isset($data['et_pb_contact_captcha_' . $et_pb_contact_form_num]) || empty($data['et_pb_contact_captcha_' . $et_pb_contact_form_num]))) {
                            $et_contact_error = true;
                        } elseif ('on' === $use_spam_service) {
                            if (class_exists('ET_Builder_Element')) {
                                $contact_form = ET_Builder_Element::get_module('et_pb_contact_form');
                                if ($contact_form && is_object($contact_form) && method_exists($contact_form, 'is_spam_submission')) {
                                    $contact_form->props = $props;
                                    if ($contact_form->is_spam_submission()) {
                                        if (!empty($_POST['token'])) {
                                            unset($_POST['token']);
                                        }
                                        $et_contact_error = true;
                                    } else {
                                        $props['use_spam_service'] = 'off';
                                        $props['captcha'] = 'off';
                                    }
                                }
                            }
                        }

                        /* Check all fields on current form and generate error message if needed */
                        if (!empty($fields_data_array)) {
                            foreach ($fields_data_array as $index => $value) {
                                if (isset($value['field_id']) && 'et_pb_contact_et_number_' . $et_pb_contact_form_num === $value['field_id']) {
                                    continue;
                                }
                                /* Check all the required fields, generate error message if required field is empty */
                                $field_value = isset($data[$value['field_id']]) ? trim($data[$value['field_id']]) : '';
                                if ('required' === $value['required_mark'] && empty($field_value) && !is_numeric($field_value)) {
                                    $et_contact_error = true;
                                    continue;
                                }
                                /* Additional check for email field */
                                if ('email' === $value['field_type'] && 'required' === $value['required_mark'] && !empty($field_value)) {
                                    $contact_email = isset($data[$value['field_id']]) ? sanitize_email($data[$value['field_id']]) : '';
                                    if (!empty($contact_email) && !is_email($contact_email)) {
                                        $et_contact_error = true;
                                    }
                                }
                            }
                        }
                    } else {
                        $et_contact_error = true;
                    }
                } else {
                    $et_contact_error = true;
                }

                if (!$et_contact_error && $nonce_result) {
                    $dataset = false;
                    if (false !== strpos($success_message, '[')) {
                        $shortcode_tags = array(
                            'e2pdf-download',
                            'e2pdf-save',
                            'e2pdf-view',
                            'e2pdf-adobesign',
                            'e2pdf-zapier',
                        );
                        preg_match_all('@\[([^<>&/\[\]\x00-\x20=]++)@', $success_message, $matches);
                        $tagnames = array_intersect($shortcode_tags, $matches[1]);
                        if (!empty($tagnames)) {
                            preg_match_all('/' . $this->helper->load('shortcode')->get_shortcode_regex($tagnames) . '/', $success_message, $shortcodes);
                            foreach ($shortcodes[0] as $key => $shortcode_value) {
                                $item = false;
                                $shortcode = $this->helper->load('shortcode')->get_shortcode($shortcodes, $key);
                                $atts = shortcode_parse_atts($shortcode[3]);
                                if ($this->helper->load('shortcode')->is_attachment($shortcode, $atts)) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
                                } else {
                                    if (!isset($atts['dataset']) && isset($atts['id'])) {
                                        $template = new Model_E2pdf_Template();
                                        $template->load($atts['id']);
                                        if ($template->get('extension') === 'divi') {
                                            $item = $template->get('item');
                                            if (!$dataset) {
                                                $entry = new Model_E2pdf_Dataset();
                                                $entry->set('extension', 'divi');
                                                $entry->set('item', $item);
                                                $entry->set('entry', $data);
                                                $dataset = $entry->save();
                                            }
                                            $atts['dataset'] = $dataset;
                                            $shortcode[3] .= ' dataset="' . $dataset . '"';
                                        }
                                    }
                                    if (!isset($atts['apply'])) {
                                        $shortcode[3] .= ' apply="true"';
                                    }
                                    if (!isset($atts['iframe_download'])) {
                                        $shortcode[3] .= ' iframe_download="true"';
                                    }
                                    $props['success_message'] = str_replace(str_replace(array('[', ']'), array('&#91;', '&#93;'), $shortcode_value), '[' . $shortcode[2] . $shortcode[3] . ']', $props['success_message']);
                                }
                            }
                        }
                    }

                    if (false !== strpos($custom_message, '[')) {
                        $shortcode_tags = array(
                            'e2pdf-download',
                            'e2pdf-save',
                            'e2pdf-attachment',
                            'e2pdf-adobesign',
                            'e2pdf-zapier',
                        );

                        preg_match_all('@\[([^<>&/\[\]\x00-\x20=]++)@', $custom_message, $matches);
                        $tagnames = array_intersect($shortcode_tags, $matches[1]);
                        if (!empty($tagnames)) {
                            preg_match_all('/' . $this->helper->load('shortcode')->get_shortcode_regex($tagnames) . '/', $custom_message, $shortcodes);
                            add_filter('wp_mail', array($this, 'filter_wp_mail'), 11);
                            foreach ($shortcodes[0] as $key => $shortcode_value) {
                                $item = false;
                                $shortcode = $this->helper->load('shortcode')->get_shortcode($shortcodes, $key);
                                $atts = shortcode_parse_atts($shortcode[3]);
                                if (!isset($atts['dataset']) && isset($atts['id'])) {
                                    $template = new Model_E2pdf_Template();
                                    $template->load($atts['id']);
                                    if ($template->get('extension') === 'divi') {
                                        $item = $template->get('item');
                                        if (!$dataset) {
                                            $entry = new Model_E2pdf_Dataset();
                                            $entry->set('extension', 'divi');
                                            $entry->set('item', $item);
                                            $entry->set('entry', $data);
                                            $dataset = $entry->save();
                                        }
                                        $atts['dataset'] = $dataset;
                                        $shortcode[3] .= ' dataset="' . $dataset . '"';
                                    }
                                }
                                if ($use_custom_message_richtext) {
                                    if (defined('PWH_DCFH_PLUGIN_VERSION') && version_compare(PWH_DCFH_PLUGIN_VERSION, '1.5.1', '>=')) {
                                        $props['custom_message_richtext'] = str_replace(str_replace(array('[', ']'), array('&#91;', '&#93;'), $shortcode_value), '{{' . $shortcode[2] . $shortcode[3] . '}}', $props['custom_message_richtext']);
                                    } else {
                                        $props['custom_message_richtext'] = str_replace(str_replace(array('[', ']'), array('&#91;', '&#93;'), $shortcode_value), '[' . $shortcode[2] . $shortcode[3] . ']', $props['custom_message_richtext']);
                                    }
                                } else {
                                    $props['custom_message'] = str_replace(str_replace(array('[', ']'), array('&#91;', '&#93;'), $shortcode_value), '[' . $shortcode[2] . $shortcode[3] . ']', $props['custom_message']);
                                }
                            }
                        }
                    }

                    if (false !== strpos($confirmation_email_message, '[')) {
                        $shortcode_tags = array(
                            'e2pdf-download',
                            'e2pdf-save',
                            'e2pdf-attachment',
                            'e2pdf-adobesign',
                            'e2pdf-zapier',
                        );
                        preg_match_all('@\[([^<>&/\[\]\x00-\x20=]++)@', $confirmation_email_message, $matches);
                        $tagnames = array_intersect($shortcode_tags, $matches[1]);
                        if (!empty($tagnames)) {
                            preg_match_all('/' . $this->helper->load('shortcode')->get_shortcode_regex($tagnames) . '/', $confirmation_email_message, $shortcodes);
                            add_filter('wp_mail', array($this, 'filter_wp_mail_pwh_dcfh'), 11);
                            foreach ($shortcodes[0] as $key => $shortcode_value) {
                                $item = false;
                                $shortcode = $this->helper->load('shortcode')->get_shortcode($shortcodes, $key);
                                $atts = shortcode_parse_atts($shortcode[3]);
                                if (!isset($atts['dataset']) && isset($atts['id'])) {
                                    $template = new Model_E2pdf_Template();
                                    $template->load($atts['id']);
                                    if ($template->get('extension') === 'divi') {
                                        $item = $template->get('item');
                                        if (!$dataset) {
                                            $entry = new Model_E2pdf_Dataset();
                                            $entry->set('extension', 'divi');
                                            $entry->set('item', $item);
                                            $entry->set('entry', $data);
                                            $dataset = $entry->save();
                                        }
                                        $atts['dataset'] = $dataset;
                                        $shortcode[3] .= ' dataset="' . $dataset . '"';
                                    }
                                }
                                if (defined('PWH_DCFH_PLUGIN_VERSION') && version_compare(PWH_DCFH_PLUGIN_VERSION, '1.5.1', '>=')) {
                                    if ($use_confirmation_message_richtext) {
                                        $props['confirmation_message_richtext'] = str_replace(str_replace(array('[', ']'), array('&#91;', '&#93;'), $shortcode_value), '{{' . $shortcode[2] . $shortcode[3] . '}}', $props['confirmation_message_richtext']);
                                    } else {
                                        $props['confirmation_email_message'] = str_replace(str_replace(array('[', ']'), array('&#91;', '&#93;'), $shortcode_value), '{{' . $shortcode[2] . $shortcode[3] . '}}', $props['confirmation_email_message']);
                                    }
                                } else {
                                    if ($use_confirmation_message_richtext) {
                                        $props['confirmation_message_richtext'] = str_replace(str_replace(array('[', ']'), array('&#91;', '&#93;'), $shortcode_value), '[' . $shortcode[2] . $shortcode[3] . ']', $props['confirmation_message_richtext']);
                                    } else {
                                        $props['confirmation_email_message'] = str_replace(str_replace(array('[', ']'), array('&#91;', '&#93;'), $shortcode_value), '[' . $shortcode[2] . $shortcode[3] . ']', $props['confirmation_email_message']);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return $props;
    }

    public function filter_et_module_shortcode_output($output, $render_slug, $form) {

        $et_contact_proccess = !empty($_POST) && is_array($_POST) ? array_search('et_contact_proccess', $_POST) : false;
        if ($render_slug && $render_slug == 'et_pb_contact_form' && $et_contact_proccess) {

            $et_pb_contact_form_num = str_replace(array('pwh_dcfh_et_pb_contactform_submit_', 'et_pb_contactform_submit_'), array('', ''), $et_contact_proccess);
            $nonce_result = isset($_POST['_wpnonce-et-pb-contact-form-submitted-' . $et_pb_contact_form_num]) && wp_verify_nonce($_POST['_wpnonce-et-pb-contact-form-submitted-' . $et_pb_contact_form_num], 'et-pb-contact-form-submit') ? true : false;

            if ($nonce_result && (
                    (isset($_POST['et_pb_contactform_submit_' . $et_pb_contact_form_num]) && empty($_POST['et_pb_contact_et_number_' . $et_pb_contact_form_num])) ||
                    (isset($_POST['pwh_dcfh_et_pb_contactform_submit_' . $et_pb_contact_form_num]) && $_POST['pwh_dcfh_et_pb_contactform_submit_' . $et_pb_contact_form_num] == 'et_contact_proccess')
                    )
            ) {

                $success_message = isset($form->props['success_message']) ? str_replace(array('&#91;', '&#93;', '&quot;'), array('[', ']', '"'), $form->props['success_message']) : '';
                if (false !== strpos($success_message, '[')) {
                    $shortcode_tags = array(
                        'e2pdf-download',
                        'e2pdf-save',
                        'e2pdf-view',
                        'e2pdf-adobesign',
                        'e2pdf-zapier',
                    );
                    preg_match_all('@\[([^<>&/\[\]\x00-\x20=]++)@', $success_message, $matches);
                    $tagnames = array_intersect($shortcode_tags, $matches[1]);
                    if (!empty($tagnames)) {
                        preg_match_all('/' . $this->helper->load('shortcode')->get_shortcode_regex($tagnames) . '/', $success_message, $shortcodes);
                        foreach ($shortcodes[0] as $key => $shortcode_value) {
                            $shortcode = $this->helper->load('shortcode')->get_shortcode($shortcodes, $key);
                            if (isset($form->props['enable_multistep']) && 'on' === $form->props['enable_multistep']) {
                                $output = str_replace($shortcode_value, do_shortcode_tag($shortcode), $output);
                            } else {
                                $output = str_replace(str_replace(array('"'), array('&quot;'), $shortcode_value), do_shortcode_tag($shortcode), $output);
                            }
                        }
                    }
                }
            }

            remove_filter('wp_mail', array($this, 'filter_wp_mail'), 11);
            remove_filter('wp_mail', array($this, 'filter_wp_mail_pwh_dcfh'), 11);

            $files = $this->helper->get('divi_attachments');
            if (is_array($files) && !empty($files)) {
                foreach ($files as $key => $file) {
                    $this->helper->delete_dir(dirname($file) . '/');
                }
                $this->helper->deset('divi_attachments');
            }
        }

        return $output;
    }

    /**
     * Delete dataset for template
     * @param int $template_id - Template ID
     * @param int $dataset - Dataset ID
     * @return bool - Result of removing items
     */
    public function delete_item($template_id = false, $dataset = false) {
        global $wpdb;
        $template = new Model_E2pdf_Template();
        if ($template_id && $dataset && $template->load($template_id)) {
            if ($template->get('extension') === 'divi' && $template->get('item')) {
                $item = $template->get('item');
                $where = array(
                    'ID' => $dataset,
                    'item' => $item,
                    'extension' => 'divi',
                );
                $wpdb->delete($wpdb->prefix . 'e2pdf_datasets', $where);
                return true;
            }
        }
        return false;
    }

    /**
     * Delete all datasets for Template
     * @param int $template_id - Template ID
     * @return bool - Result of removing items
     */
    public function delete_items($template_id = false) {
        global $wpdb;
        $template = new Model_E2pdf_Template();
        if ($template_id && $template->load($template_id)) {
            if ($template->get('extension') === 'divi' && $template->get('item')) {
                $where = array(
                    'item' => $template->get('item'),
                    'extension' => 'divi',
                );
                $wpdb->delete($wpdb->prefix . 'e2pdf_datasets', $where);
                return true;
            }
        }
        return false;
    }

    /**
     * Get styles for generating Map Field function
     * @return array - List of css files to load
     */
    public function styles($item_id = false) {
        $styles = array(
            plugins_url('css/extension/divi.css?v=' . time(), $this->helper->get('plugin_file_path')),
        );
        return $styles;
    }
}
