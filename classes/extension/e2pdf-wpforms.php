<?php

/**
 * E2Pdf WPForms Extension
 * @copyright  Copyright 2017 https://e2pdf.com
 * @license    GPL v2
 * @version    1
 * @link       https://e2pdf.com
 * @since      1.23.00
 */
if (!defined('ABSPATH')) {
    die('Access denied.');
}

class Extension_E2pdf_Wpforms extends Model_E2pdf_Model {

    private $options;
    private $info = array(
        'key' => 'wpforms',
        'title' => 'WPForms',
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
        if (defined('E2PDF_WPFORMS_EXTENSION') || $this->helper->load('extension')->is_plugin_active('wpforms-lite/wpforms.php') || $this->helper->load('extension')->is_plugin_active('wpforms/wpforms.php')) {
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
            case 'item':
                $this->set('cached_form', false);
                if ($this->get('item')) {
                    $this->set('cached_form', get_post($this->get('item')));
                }
                break;
            case 'dataset':
                $this->set('cached_entry', false);
                if ($this->get('dataset')) {
                    if (substr($this->get('dataset'), 0, 4) === 'tmp_') {
                        $cached_entry = new stdClass();
                        $cached_entry->entry_id = '';
                        $cached_entry->form_id = $this->get('item');
                        $cached_entry->fields = json_encode(get_transient('e2pdf_' . $this->get('dataset')));  // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
                        $this->set('cached_entry', $cached_entry);
                    } else {
                        if ($this->get('dataset') && function_exists('wpforms') && wpforms()->entry) {
                            $this->set('cached_entry', wpforms()->entry->get($this->get('dataset')));
                        }
                    }
                }
                break;
            default:
                break;
        }
        return true;
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
        if (function_exists('wpforms')) {
            $forms = wpforms()->form->get();
            if (!empty($forms)) {
                foreach ($forms as $form) {
                    $items[] = $this->item($form->ID);
                }
            }
        }
        return $items;
    }

    /**
     * Get item
     * @param int $item - Item ID
     * @return object - Item
     */
    public function item($item_id = false) {
        $item_id = (int) $item_id;
        if (!$item_id && $this->get('item')) {
            $item_id = $this->get('item');
        }
        $form = get_post($item_id);
        $item = new stdClass();
        if ($form) {
            $item->id = (string) $item_id;
            $item->url = $this->helper->get_url(
                    array(
                        'page' => 'wpforms-builder',
                        'view' => 'fields',
                        'form_id' => $item_id,
                    )
            );
            $item->name = $form->post_title;
        } else {
            $item->id = '';
            $item->url = 'javascript:void(0);';
            $item->name = '';
        }
        return $item;
    }

    /**
     * Get entries for export
     * @param int $item - Form ID
     * @param string $name - Entries names
     * @return array() - Entries list
     */
    public function datasets($item_id = false, $name = false) {
        $item_id = (int) $item_id;
        $datasets = array();
        $entries_args = array(
            'form_id' => absint($item_id),
            'number' => 9999999,
        );

        if (isset(wpforms()->entry)) {
            $entries = wpforms()->entry->get_entries($entries_args);
            if ($entries) {
                $this->set('item', $item_id);
                foreach ($entries as $key => $entry) {
                    $this->set('dataset', $entry->entry_id);
                    $entry_title = $this->render($name);
                    if (!$entry_title) {
                        $entry_title = $entry->entry_id;
                    }
                    $datasets[] = array(
                        'key' => $entry->entry_id,
                        'value' => $entry_title,
                    );
                }
            }
        }
        return $datasets;
    }

    /**
     * Get Dataset Actions
     * @param int $dataset - Dataset ID
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
                    'page' => 'wpforms-entries',
                    'view' => 'details',
                    'entry_id' => $dataset_id,
                )
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
     * Load actions for this extension
     */
    public function load_actions() {
        $options = get_option('wpforms_settings', false);
        $async = is_array($options) && !empty($options['email-async']) ? wp_unslash($options['email-async']) : false;
        if ($async) {
            add_action('action_scheduler_begin_execute', array($this, 'action_scheduler_begin_execute'), 10, 2);
            add_action('action_scheduler_after_execute', array($this, 'action_scheduler_after_execute'), 10, 3);
        } else {
            add_action('wpforms_email_send_after', array($this, 'action_wpforms_email_send_after'));
        }

        /* Hooks */
        add_action('wpforms_entry_details_sidebar', array($this, 'hook_wpforms_entry_view'));
        add_action('wpforms_pro_admin_entries_edit_sidebar', array($this, 'hook_wpforms_entry_edit'));
    }

    public function action_wpforms_email_send_after() {
        $files = $this->helper->get('wpforms_attachments');
        if (is_array($files) && !empty($files)) {
            foreach ($files as $key => $file) {
                $this->helper->delete_dir(dirname($file) . '/');
            }
            $this->helper->deset('wpforms_attachments');
        }
    }

    public function action_scheduler_begin_execute($action_id, $context) {
        if (class_exists('ActionScheduler_Store')) {
            $store = ActionScheduler_Store::instance();
            if ($store) {
                $action = $store->fetch_action($action_id);
                if ($action->get_hook() == 'wpforms_process_entry_emails') {
                    add_filter('wp_mail', array($this, 'filter_wp_mail'), 30);
                }
            }
        }
    }

    public function action_scheduler_after_execute($action_id, $action, $context) {
        $files = $this->helper->get('wpforms_attachments');
        if (is_array($files) && !empty($files)) {
            foreach ($files as $key => $file) {
                $this->helper->delete_dir(dirname($file) . '/');
            }
            $this->helper->deset('wpforms_attachments');
        }
    }

    public function filter_wp_mail($args = array()) {
        $message = preg_replace('/(\{\{)((e2pdf-save|e2pdf-attachment)[^\}]*?)(\}\})/', '[$2]', $args['message']);
        if (false !== strpos($message, '[')) {
            $shortcode_tags = array(
                'e2pdf-save',
                'e2pdf-attachment',
            );
            preg_match_all('@\[([^<>&/\[\]\x00-\x20=]++)@', $message, $matches);
            $tagnames = array_intersect($shortcode_tags, $matches[1]);
            if (!empty($tagnames)) {
                /* Shortcodes do not work when user is not logged-in in ASYNC mode */
                if (!shortcode_exists('e2pdf-save')) {
                    add_shortcode('e2pdf-save', array(new Model_E2pdf_Shortcode(), 'e2pdf_save'));
                }
                if (!shortcode_exists('e2pdf-attachment')) {
                    add_shortcode('e2pdf-attachment', array(new Model_E2pdf_Shortcode(), 'e2pdf_attachment'));
                }
                preg_match_all('/' . $this->helper->load('shortcode')->get_shortcode_regex($tagnames) . '/', $message, $shortcodes);
                foreach ($shortcodes[0] as $key => $shortcode_value) {
                    $file = false;
                    $shortcode = $this->helper->load('shortcode')->get_shortcode($shortcodes, $key);
                    $atts = shortcode_parse_atts($shortcode[3]);
                    if ($this->helper->load('shortcode')->is_attachment($shortcode, $atts)) {
                        $transient = isset($atts['dataset']) && substr($atts['dataset'], 0, 4) === 'tmp_' ? 'e2pdf_' . $atts['dataset'] : false;
                        $file = do_shortcode_tag($shortcode);
                        if ($file) {
                            $tmp = false;
                            if (substr($file, 0, 4) === 'tmp:') {
                                $file = substr($file, 4);
                                $tmp = true;
                            }
                            if ($shortcode[2] === 'e2pdf-save' || isset($atts['pdf'])) {
                                if ($tmp) {
                                    $this->helper->add('wpforms_attachments', $file);
                                }
                            } else {
                                $this->helper->add('wpforms_attachments', $file);
                            }
                            $args['attachments'][] = $file;
                        }
                        $message = str_replace($shortcode_value, '', $message);
                        if ($transient) {
                            delete_transient($transient);
                        }
                    }
                }
                $args['message'] = $message;
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
     * Load filters for this extension
     */
    public function load_filters() {
        add_filter('wpforms_frontend_confirmation_message', array($this, 'filter_wpforms_frontend_confirmation_message'), 10, 4);
        add_filter('wpforms_emails_send_email_data', array($this, 'filter_wpforms_emails_send_email_data'), 11, 2);
        add_filter('wpforms_process_smart_tags', array(new Model_E2pdf_Filter(), 'pre_filter'), 0);
        add_filter('wpforms_process_smart_tags', array(new Model_E2pdf_Filter(), 'filter'), 999);

        /* Hooks */
        add_filter('wpforms_entry_table_actions', array($this, 'hook_wpforms_row_actions'), 10, 2);
    }

    public function filter_wpforms_frontend_confirmation_message($message, $form_data, $fields, $dataset) {
        if (false !== strpos($message, '[')) {
            $shortcode_tags = array(
                'e2pdf-download',
                'e2pdf-save',
                'e2pdf-view',
                'e2pdf-adobesign',
                'e2pdf-zapier',
            );
            preg_match_all('@\[([^<>&/\[\]\x00-\x20=]++)@', $message, $matches);
            $tagnames = array_intersect($shortcode_tags, $matches[1]);
            if (!empty($tagnames)) {
                preg_match_all('/' . $this->helper->load('shortcode')->get_shortcode_regex($tagnames) . '/', $message, $shortcodes);
                foreach ($shortcodes[0] as $key => $shortcode_value) {
                    $shortcode = $this->helper->load('shortcode')->get_shortcode($shortcodes, $key);
                    $atts = shortcode_parse_atts($shortcode[3]);
                    if ($this->helper->load('shortcode')->is_attachment($shortcode, $atts)) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
                    } else {
                        if (!isset($atts['dataset']) && isset($atts['id'])) {
                            $template = new Model_E2pdf_Template();
                            $template->load($atts['id']);
                            if ($template->get('extension') === 'wpforms') {
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
                        $message = str_replace($shortcode_value, do_shortcode_tag($shortcode), $message);
                    }
                }
            }
        }
        return $message;
    }

    public function filter_wpforms_emails_send_email_data($mail, $data) {
        if ($mail && isset($mail['message'])) {
            if (false !== strpos($mail['message'], '[')) {
                $shortcode_tags = array(
                    'e2pdf-download',
                    'e2pdf-save',
                    'e2pdf-attachment',
                    'e2pdf-adobesign',
                    'e2pdf-zapier',
                );
                preg_match_all('@\[([^<>&/\[\]\x00-\x20=]++)@', $mail['message'], $matches);
                $tagnames = array_intersect($shortcode_tags, $matches[1]);
                if (!empty($tagnames)) {
                    $options = get_option('wpforms_settings', false);
                    $async = is_array($options) && !empty($options['email-async']) ? wp_unslash($options['email-async']) : false;
                    preg_match_all('/' . $this->helper->load('shortcode')->get_shortcode_regex($tagnames) . '/', $mail['message'], $shortcodes);
                    foreach ($shortcodes[0] as $key => $shortcode_value) {
                        $file = false;
                        $transient = false;
                        $shortcode = $this->helper->load('shortcode')->get_shortcode($shortcodes, $key);
                        $atts = shortcode_parse_atts($shortcode[3]);
                        if (!isset($atts['dataset']) && isset($atts['id'])) {
                            $template = new Model_E2pdf_Template();
                            $template->load($atts['id']);
                            if ($template->get('extension') === 'wpforms') {
                                if (isset($data->entry_id) && $data->entry_id) {
                                    $atts['dataset'] = $data->entry_id;
                                    $shortcode[3] .= ' dataset="' . $data->entry_id . '"';
                                } elseif ($this->helper->load('shortcode')->is_attachment($shortcode, $atts)) {
                                    $dataset = 'tmp_' . $this->helper->load('encryption')->random_md5();
                                    $transient = 'e2pdf_' . $dataset;
                                    set_transient($transient, $data->fields, 1800);
                                    $atts['dataset'] = $dataset;
                                    $shortcode[3] .= ' dataset="' . $dataset . '"';
                                }
                            }
                        }
                        if (!isset($atts['apply'])) {
                            $shortcode[3] .= ' apply="true"';
                        }
                        if (!isset($atts['filter'])) {
                            $shortcode[3] .= ' filter="true"';
                        }
                        if ($this->helper->load('shortcode')->is_attachment($shortcode, $atts)) {
                            if ($async) {
                                $mail['message'] = str_replace($shortcode_value, '{{' . $shortcode[2] . $shortcode[3] . '}}', $mail['message']);
                            } else {
                                $file = do_shortcode_tag($shortcode);
                                if ($file) {
                                    $tmp = false;
                                    if (substr($file, 0, 4) === 'tmp:') {
                                        $file = substr($file, 4);
                                        $tmp = true;
                                    }
                                    if ($shortcode[2] === 'e2pdf-save' || isset($atts['pdf'])) {
                                        if ($tmp) {
                                            $this->helper->add('wpforms_attachments', $file);
                                        }
                                    } else {
                                        $this->helper->add('wpforms_attachments', $file);
                                    }
                                    $mail['attachments'][] = $file;
                                }
                                $mail['message'] = str_replace($shortcode_value, '', $mail['message']);
                                if ($transient) {
                                    delete_transient($transient);
                                }
                            }
                        } else {
                            $mail['message'] = str_replace($shortcode_value, do_shortcode_tag($shortcode), $mail['message']);
                            if ($transient) {
                                delete_transient($transient);
                            }
                        }
                    }
                }
            }
        }
        return $mail;
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
            if (function_exists('wpforms_process_smart_tags')) {
                add_filter('wpforms_smart_tags_formatted_field_value', array($this, 'filter_wpforms_smart_tags_formatted_field_value'), 99, 4);
                add_filter('wpforms_smarttags_process_value', array($this, 'filter_wpforms_smarttags_process_value'), 10, 6);
                $value = wpforms_process_smart_tags($value, wpforms_decode($this->get('cached_form')->post_content), wpforms_decode($this->get('cached_entry')->fields), $this->get('cached_entry')->entry_id);
                remove_filter('wpforms_smarttags_process_value', array($this, 'filter_wpforms_smarttags_process_value'), 10);
                remove_filter('wpforms_smart_tags_formatted_field_value', array($this, 'filter_wpforms_smart_tags_formatted_field_value'), 99);
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
     * WPForms checkboxes, multi-selects, and payment checkboxes in email PDF attachments
     */
    public function filter_wpforms_smart_tags_formatted_field_value($value, $field_id, $fields, $field_key) {
        if (function_exists('wpforms_get_multi_fields')) {
            $field_type = $fields[$field_id]['type'] ?? '';
            if (in_array($field_type, wpforms_get_multi_fields(), true)) {
                return $fields[$field_id][$field_key] ?? '';
            }
        }
        return $value;
    }

    public function filter_wpforms_smarttags_process_value($value, $tag_name, $form_data, $fields, $entry_id, $smart_tag_object) {

        $field_id = $smart_tag_object->get_attributes()['field_id'] ?? 0;
        $field_id = explode('|', $field_id)[0];

        if (false !== strpos($field_id, '_')) {
            $repeater = array_map('intval', explode('_', $field_id, 2));
            if (wpforms_is_repeated_field($repeater[0], $fields)) {
                $prefix = $repeater[1] == '1' ? $repeater[0] : $repeater[0] . '_' . $repeater[1];
                if ($tag_name == 'field_value_id') {
                    $field_key = isset($fields[$prefix]['value_raw']) && !is_array($fields[$prefix]['value_raw']) && (string) $fields[$prefix]['value_raw'] !== '' ? 'value_raw' : 'value';
                } else {
                    $field_key = 'value';
                }
                $value = isset($fields[$prefix][$field_key]) ? $fields[$prefix][$field_key] : '';
            }
        }

        if (false === strpos($field_id, '_') && apply_filters('e2pdf_for_do_shortcode_data_process', false) && wpforms_is_repeated_field((int) $field_id, $fields)) {
            $values = array();
            $prefix = (int) $field_id . '_';
            foreach ($fields as $key => $field) {
                if (strpos($key, $prefix) === 0 || $key == (int) $field_id) {
                    $values[] = $field;
                }
            }
            $value = serialize($values); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
        } else {
            if (
                    $this->get_field_property('type', $smart_tag_object, $form_data, $tag_name) == 'checkbox' ||
                    ($this->get_field_property('type', $smart_tag_object, $form_data, $tag_name) == 'select' && $this->get_field_property('multiple', $smart_tag_object, $form_data, $tag_name) == '1')
            ) {
                $value = implode(', ', explode("\n", $value));
            } elseif ($this->get_field_property('type', $smart_tag_object, $form_data, $tag_name) == 'payment-checkbox') {
                if ($tag_name == 'field_value_id') {
                    $value = implode(', ', explode(',', $value));
                } else {
                    $value = implode(', ', explode("\r\n", $value));
                }
            }
            $entity_decode = array(
                'payment-multiple',
                'payment-checkbox',
                'payment-total',
                'payment-single',
                'payment-select',
            );
            if (in_array($this->get_field_property('type', $smart_tag_object, $form_data, $tag_name), $entity_decode)) {
                $value = html_entity_decode($value);
            }
        }
        return $value;
    }

    public function get_field_property($attr, $smart_tag_object, $form_data, $tag_name) {
        if (isset($smart_tag_object->get_attributes()[$tag_name])) {
            $field_id = $smart_tag_object->get_attributes()['field_id'] ?? 0;
            $field_id = (int) explode('|', $field_id)[0];
            if (isset($form_data['fields'][$field_id][$attr])) {
                return $form_data['fields'][$field_id][$attr];
            }
        }
        return false;
    }

    /**
     * Strip unused shortcodes
     * @param string $value - Content
     * @return string - Value with removed unused shortcodes
     */
    public function strip_shortcodes($value) {
        $value = preg_replace('~(?:\[/?)[^/\]]+/?\]~s', '', $value);
        $value = preg_replace('~a\:\d+\:{[^}]*}(*SKIP)(*FAIL)|{[^}]*}~', '', $value);
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
                $search = array('&#91;', '&#93;', '&#091;', '&#093;');
                $replace = array('[', ']', '[', ']');
                $value = str_replace($search, $replace, $value);
                if (!$html) {
                    $value = wp_specialchars_decode($value, ENT_QUOTES);
                }
            } else {
                $search = array('[', ']', '&#091;', '&#093;');
                $replace = array('&#91;', '&#93;', '&#91;', '&#93;');
                $value = str_replace($search, $replace, $value);
            }
        }
        return $value;
    }

    /**
     * Auto Generate of Template for this extension
     * @return array - List of elements
     */
    public function auto() {

        $elements = array();
        if ($this->get('cached_form') && function_exists('wpforms_get_form_fields')) {
            $form = wpforms_decode($this->get('cached_form')->post_content);
            $form_fields = isset($form['fields']) ? $form['fields'] : array();
            $sizes = array();
            $repeaters = array();
            $new_form_fields = array();

            foreach ($form_fields as $field) {
                if ($field['type'] == 'layout' || $field['type'] == 'repeater') {
                    foreach ($field['columns'] as $column) {
                        foreach ($column['fields'] as $sub_field) {
                            $sizes[$sub_field] = $column['width_preset'];
                            if ($field['type'] == 'repeater') {
                                $repeaters[] = $sub_field;
                            }
                        }
                    }
                }
            }

            foreach ($form_fields as $field) {
                if ($field['type'] == 'layout' || $field['type'] == 'repeater') {
                    foreach ($field['columns'] as $column) {
                        foreach ($column['fields'] as $sub_field) {
                            $found = $this->inner_field($form_fields, $sub_field);
                            if ($found !== false) {
                                $new_form_fields[] = $form_fields[$found];
                            }
                        }
                    }
                } else {
                    $field_id = isset($field['id']) ? $field['id'] : '0';
                    if (!in_array($field_id, $repeaters) && !array_key_exists($field_id, $sizes)) {
                        $new_form_fields[] = $field;
                    }
                }
            }

            foreach ($new_form_fields as $field) {
                $width = '100';
                $field_id = isset($field['id']) ? $field['id'] : '0';
                $field_value = in_array($field_id, $repeaters) ? '{field_id="' . $field_id . '_1"}' : '{field_id="' . $field_id . '"}';
                if (isset($sizes[$field_id])) {
                    $width = $sizes[$field_id];
                }
                switch ($field['type']) {
                    case 'divider':
                        $elements[] = $this->auto_field(
                                $field,
                                array(
                                    'type' => 'e2pdf-html',
                                    'block' => true,
                                    'float' => true,
                                    'properties' => array(
                                        'top' => '20',
                                        'left' => '20',
                                        'right' => '20',
                                        'width' => $width . '%',
                                        'height' => 'auto',
                                        'value' => isset($field['label']) ? $field['label'] : '',
                                    ),
                                )
                        );
                        if ($field['description']) {
                            $elements[] = $this->auto_field(
                                    $field,
                                    array(
                                        'type' => 'e2pdf-html',
                                        'properties' => array(
                                            'top' => '20',
                                            'left' => '20',
                                            'right' => '20',
                                            'width' => $width . '%',
                                            'height' => 'auto',
                                            'value' => $field['description'],
                                        ),
                                    )
                            );
                        }
                        break;
                    case 'payment-total':
                    case 'payment-single':
                        $elements[] = $this->auto_field(
                                $field,
                                array(
                                    'type' => 'e2pdf-html',
                                    'block' => true,
                                    'float' => true,
                                    'properties' => array(
                                        'top' => '20',
                                        'left' => '20',
                                        'right' => '20',
                                        'width' => $width . '%',
                                        'height' => 'auto',
                                        'value' => isset($field['label']) && $field['label'] ? $field['label'] . ': ' . $field_value : $field_value,
                                    ),
                                )
                        );
                        break;
                    case 'html':
                    case 'content':
                        $label = isset($field['label']) && $field['label'] ? $field['label'] : '';
                        if ($label) {
                            $elements[] = $this->auto_field(
                                    $field,
                                    array(
                                        'type' => 'e2pdf-html',
                                        'block' => true,
                                        'float' => true,
                                        'properties' => array(
                                            'top' => '20',
                                            'left' => '20',
                                            'right' => '20',
                                            'width' => $width . '%',
                                            'height' => 'auto',
                                            'value' => isset($field['label']) ? $field['label'] : '',
                                        ),
                                    )
                            );
                        }
                        if ($field['type'] == 'code') {
                            $content = isset($field['code']) && $field['code'] ? $field['code'] : '';
                        } else {
                            $content = isset($field['content']) && $field['content'] ? $field['content'] : '';
                        }
                        if ($content) {
                            $elements[] = $this->auto_field(
                                    $field,
                                    array(
                                        'type' => 'e2pdf-html',
                                        'block' => true,
                                        'float' => true,
                                        'properties' => array(
                                            'top' => '20',
                                            'left' => '20',
                                            'right' => '20',
                                            'width' => $width . '%',
                                            'height' => 'auto',
                                            'value' => $content,
                                        ),
                                    )
                            );
                        }
                        break;
                    case 'name':
                    case 'text':
                    case 'number':
                    case 'email':
                    case 'number-slider':
                    case 'phone':
                    case 'date-time':
                    case 'url':
                    case 'rating':
                    case 'hidden':
                    case 'payment-coupon':
                        $elements[] = $this->auto_field(
                                $field,
                                array(
                                    'type' => 'e2pdf-html',
                                    'block' => true,
                                    'float' => true,
                                    'properties' => array(
                                        'top' => '20',
                                        'left' => '20',
                                        'right' => '20',
                                        'width' => $width . '%',
                                        'height' => 'auto',
                                        'value' => isset($field['label']) ? $field['label'] : '',
                                    ),
                                )
                        );

                        $elements[] = $this->auto_field(
                                $field,
                                array(
                                    'type' => 'e2pdf-input',
                                    'properties' => array(
                                        'top' => '5',
                                        'width' => '100%',
                                        'height' => 'auto',
                                        'value' => $field_value,
                                    ),
                                )
                        );
                        break;
                    case 'select':
                    case 'payment-select':
                        $elements[] = $this->auto_field(
                                $field,
                                array(
                                    'type' => 'e2pdf-html',
                                    'block' => true,
                                    'float' => true,
                                    'properties' => array(
                                        'top' => '20',
                                        'left' => '20',
                                        'right' => '20',
                                        'width' => $width . '%',
                                        'height' => 'auto',
                                        'value' => isset($field['label']) ? $field['label'] : '',
                                    ),
                                )
                        );
                        $field_options = array();
                        foreach ($field['choices'] as $option) {
                            $option_value = isset($option['label']) ? $option['label'] : '';
                            if ($field['type'] == 'payment-select') {
                                $option_value .= isset($option['value']) ? ' - ' . html_entity_decode(wpforms_format_amount(wpforms_sanitize_amount($option['value']), true)) : '';
                            }
                            $field_options[] = $option_value;
                        }
                        if (isset($field['multiple']) && $field['multiple']) {

                            $elements[] = $this->auto_field(
                                    $field,
                                    array(
                                        'type' => 'e2pdf-select',
                                        'properties' => array(
                                            'top' => '5',
                                            'width' => '100%',
                                            'height' => '44',
                                            'multiline' => '1',
                                            'options' => implode("\n", $field_options),
                                            'value' => $field_value,
                                        ),
                                    )
                            );
                        } else {
                            $elements[] = $this->auto_field(
                                    $field,
                                    array(
                                        'type' => 'e2pdf-select',
                                        'properties' => array(
                                            'top' => '5',
                                            'width' => '100%',
                                            'height' => 'auto',
                                            'options' => implode("\n", $field_options),
                                            'value' => $field_value,
                                        ),
                                    )
                            );
                        }
                        break;
                    case 'textarea':
                    case 'address':
                    case 'file-upload':
                        $elements[] = $this->auto_field(
                                $field,
                                array(
                                    'type' => 'e2pdf-html',
                                    'block' => true,
                                    'float' => true,
                                    'properties' => array(
                                        'top' => '20',
                                        'left' => '20',
                                        'right' => '20',
                                        'width' => $width . '%',
                                        'height' => 'auto',
                                        'value' => isset($field['label']) ? $field['label'] : '',
                                    ),
                                )
                        );

                        $elements[] = $this->auto_field(
                                $field,
                                array(
                                    'type' => 'e2pdf-textarea',
                                    'properties' => array(
                                        'top' => '5',
                                        'width' => '100%',
                                        'height' => 'auto',
                                        'value' => $field_value,
                                    ),
                                )
                        );
                        break;
                    case 'richtext':
                        $elements[] = $this->auto_field(
                                $field,
                                array(
                                    'type' => 'e2pdf-html',
                                    'block' => true,
                                    'float' => true,
                                    'properties' => array(
                                        'top' => '20',
                                        'left' => '20',
                                        'right' => '20',
                                        'width' => $width . '%',
                                        'height' => 'auto',
                                        'value' => isset($field['label']) ? $field['label'] : '',
                                    ),
                                )
                        );
                        $elements[] = $this->auto_field(
                                $field,
                                array(
                                    'type' => 'e2pdf-html',
                                    'properties' => array(
                                        'top' => '5',
                                        'width' => '100%',
                                        'height' => '150',
                                        'value' => $field_value,
                                    ),
                                )
                        );
                        break;
                    case 'radio':
                    case 'payment-multiple':
                        $elements[] = $this->auto_field(
                                $field,
                                array(
                                    'type' => 'e2pdf-html',
                                    'block' => true,
                                    'float' => true,
                                    'properties' => array(
                                        'top' => '20',
                                        'left' => '20',
                                        'right' => '20',
                                        'width' => $width . '%',
                                        'height' => 'auto',
                                        'value' => isset($field['label']) ? $field['label'] : '',
                                    ),
                                )
                        );

                        foreach ($field['choices'] as $opt_key => $option) {
                            $option_value = isset($option['label']) ? $option['label'] : '';
                            if ($field['type'] == 'payment-multiple') {
                                $option_value .= isset($option['value']) ? ' - ' . html_entity_decode(wpforms_format_amount(wpforms_sanitize_amount($option['value']), true)) : '';
                            }
                            $elements[] = $this->auto_field(
                                    $field,
                                    array(
                                        'type' => 'e2pdf-radio',
                                        'properties' => array(
                                            'top' => '5',
                                            'width' => 'auto',
                                            'height' => 'auto',
                                            'value' => $field_value,
                                            'option' => $option_value,
                                            'group' => $field_value,
                                        ),
                                    )
                            );
                            $elements[] = $this->auto_field(
                                    $field,
                                    array(
                                        'type' => 'e2pdf-html',
                                        'float' => true,
                                        'properties' => array(
                                            'left' => '5',
                                            'width' => '100%',
                                            'height' => 'auto',
                                            'value' => !empty($field['show_price_after_labels']) ? $option_value : $option['label'],
                                        ),
                                    )
                            );
                        }
                        break;
                    case 'checkbox':
                    case 'payment-checkbox':
                        $elements[] = $this->auto_field(
                                $field,
                                array(
                                    'type' => 'e2pdf-html',
                                    'block' => true,
                                    'float' => true,
                                    'properties' => array(
                                        'top' => '20',
                                        'left' => '20',
                                        'right' => '20',
                                        'width' => $width . '%',
                                        'height' => 'auto',
                                        'value' => isset($field['label']) ? $field['label'] : '',
                                    ),
                                )
                        );
                        foreach ($field['choices'] as $opt_key => $option) {
                            $option_value = isset($option['label']) ? $option['label'] : '';
                            if ($field['type'] == 'payment-checkbox') {
                                $option_value .= isset($option['value']) ? ' - ' . html_entity_decode(wpforms_format_amount(wpforms_sanitize_amount($option['value']), true)) : '';
                            }

                            $elements[] = $this->auto_field(
                                    $field,
                                    array(
                                        'type' => 'e2pdf-checkbox',
                                        'properties' => array(
                                            'top' => '5',
                                            'width' => 'auto',
                                            'height' => 'auto',
                                            'value' => $field_value,
                                            'option' => $option_value,
                                        ),
                                    )
                            );
                            $elements[] = $this->auto_field(
                                    $field,
                                    array(
                                        'type' => 'e2pdf-html',
                                        'float' => true,
                                        'properties' => array(
                                            'left' => '5',
                                            'width' => '100%',
                                            'height' => 'auto',
                                            'value' => !empty($field['show_price_after_labels']) ? $option_value : $option['label'],
                                        ),
                                    )
                            );
                        }
                        break;
                    case 'signature':
                        $elements[] = $this->auto_field(
                                $field,
                                array(
                                    'type' => 'e2pdf-html',
                                    'block' => true,
                                    'float' => true,
                                    'properties' => array(
                                        'top' => '20',
                                        'left' => '20',
                                        'right' => '20',
                                        'width' => $width . '%',
                                        'height' => 'auto',
                                        'value' => isset($field['label']) ? $field['label'] : '',
                                    ),
                                )
                        );
                        $elements[] = $this->auto_field(
                                $field,
                                array(
                                    'type' => 'e2pdf-signature',
                                    'properties' => array(
                                        'top' => '5',
                                        'width' => '100%',
                                        'height' => '150',
                                        'dimension' => '1',
                                        'block_dimension' => '1',
                                        'value' => $field_value,
                                    ),
                                )
                        );
                        break;
                    default:
                        break;
                }
            }
        }
        $response = array();
        $response['page'] = array(
            'bottom' => '20',
            'top' => '20',
            'left' => '20',
            'right' => '20',
        );

        $response['elements'] = $elements;
        return $response;
    }

    public function inner_field($array, $id) {
        foreach ($array as $index => $item) {
            if (is_array($item)) {
                if (isset($item['id']) && $item['id'] == $id) {
                    return $index;
                }
            }
        }
        return false;
    }

    public function auto_field($field = false, $element = array()) {
        if (!$field) {
            return false;
        }
        return $element;
    }

    /**
     * Verify if item and dataset exists
     * @return bool - item and dataset exists
     */
    public function verify() {
        if ($this->get('cached_form') && $this->get('cached_entry') && $this->get('cached_form')->ID == $this->get('cached_entry')->form_id) {
            return true;
        }
        return false;
    }

    /**
     * Init Visual Mapper data
     * @return bool|string - HTML data source for Visual Mapper
     */
    public function visual_mapper() {
        $html = '';
        $source = '';
        if ($this->get('item')) {
            ob_start();
            add_filter('wpforms_forms_anti_spam_v3_is_honeypot_enabled', '__return_false');
            echo do_shortcode('[wpforms id="' . $this->get('item') . '"]');
            add_filter('wpforms_forms_anti_spam_v3_is_honeypot_enabled', '__return_true');
            $source = ob_get_clean();
            if ($source) {
                libxml_use_internal_errors(true);
                $dom = new DOMDocument();
                if (function_exists('mb_convert_encoding')) {
                    if (defined('LIBXML_HTML_NOIMPLIED') && defined('LIBXML_HTML_NODEFDTD') && defined('LIBXML_SCHEMA_CREATE')) {
                        $html = $dom->loadHTML(mb_convert_encoding('<html>' . $source . '</html>', 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_SCHEMA_CREATE);
                    } else {
                        $html = $dom->loadHTML(mb_convert_encoding($source, 'HTML-ENTITIES', 'UTF-8'));
                    }
                } else {
                    if (defined('LIBXML_HTML_NOIMPLIED') && defined('LIBXML_HTML_NODEFDTD') && defined('LIBXML_SCHEMA_CREATE')) {
                        $html = $dom->loadHTML('<?xml encoding="UTF-8"><html>' . $source . '</html>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_SCHEMA_CREATE);
                    } else {
                        $html = $dom->loadHTML('<?xml encoding="UTF-8">' . $source);
                    }
                }
                libxml_clear_errors();
            }

            if (ob_get_length() > 0) {
                while (@ob_end_clean()); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
            }
            if (!$source) {
                return '<div class="e2pdf-vm-error">' . __("The form source is empty or doesn't exist", 'e2pdf') . '</div>';
            } elseif (!$html) {
                return '<div class="e2pdf-vm-error">' . __('The form could not be parsed due the incorrect HTML', 'e2pdf') . '</div>';
            } else {
                $form_handler = new WPForms_Form_Handler();
                $xml = new Helper_E2pdf_Xml();
                $xml->set('dom', $dom);
                $xpath = new DomXPath($dom);
                $remove_by_tag = array(
                    'script',
                    'noscript',
                );
                foreach ($remove_by_tag as $key => $tag) {
                    $elements = $xpath->query('//' . $tag);
                    foreach ($elements as $element) {
                        $element->parentNode->removeChild($element);
                    }
                }

                $remove_by_class = array(
                    'wpforms-page-button',
                    'wpforms-field-pagebreak',
                    'wpforms-signature-wrap',
                    'dz-message',
                    'wp-editor-tabs',
                    'wpforms-page-indicator progress',
                    'quicktags-toolbar',
                    'wpforms-field-entry-preview',
                    'wpforms-field-payment-coupon-button',
                    'wpforms-single-item-price',
                );
                foreach ($remove_by_class as $key => $class) {
                    $elements = $xpath->query("//*[contains(@class, '{$class}')]");
                    foreach ($elements as $element) {
                        $element->parentNode->removeChild($element);
                    }
                }
                $remove_styles = array(
                    'wpforms-page',
                    'dropzone-input',
                    'wpforms-conditional-field',
                );
                foreach ($remove_styles as $key => $class) {
                    $elements = $xpath->query("//*[contains(@class, '{$class}')]");
                    foreach ($elements as $element) {
                        $xml->set_node_value($element, 'style', '');
                    }
                }

                $elements = $xpath->query("//*[contains(@class, 'wpforms-field-payment-checkbox') or contains(@class, 'wpforms-field-payment-multiple') or contains(@class, 'wpforms-field-payment-select')]");
                foreach ($elements as $element) {
                    $field_id = $xml->get_node_value($element, 'data-field-id');
                    $field = $form_handler->get_field($this->get('item'), $field_id);
                    $sub_elements = $xpath->query('.//input|.//option', $element);
                    foreach ($sub_elements as $sub_element) {
                        $field_value = $xml->get_node_value($sub_element, 'value');
                        $option_value = '';
                        if ($field_value) {
                            if (isset($field['choices'][$field_value])) {
                                $option = $field['choices'][$field_value];
                                $option_value = isset($option['label']) ? $option['label'] : '';
                                $option_value .= isset($option['value']) ? ' - ' . html_entity_decode(wpforms_format_amount(wpforms_sanitize_amount($option['value']), true)) : '';
                            }
                        }
                        $xml->set_node_value($sub_element, 'value', $option_value);
                    }
                }
                $elements = $xpath->query("//*[contains(@class, 'wpforms-field-payment-single') or contains(@class, 'wpforms-field-payment-total')]");
                foreach ($elements as $element) {
                    $sub_elements = $xpath->query('.//input', $element);
                    foreach ($sub_elements as $sub_element) {
                        $xml->set_node_value($sub_element, 'type', 'text');
                        $xml->set_node_value($sub_element, 'class', '');
                    }
                }
                $remove_by_class = array(
                    'wpforms-payment-total',
                );
                foreach ($remove_by_class as $key => $class) {
                    $elements = $xpath->query("//*[contains(@class, '{$class}')]");
                    foreach ($elements as $element) {
                        $element->parentNode->removeChild($element);
                    }
                }

                $elements = $xpath->query("//*[contains(@class, 'wpforms-field-repeater-display-rows')]");
                foreach ($elements as $element) {
                    $sub_elements = $xpath->query('.//input|.//textarea|.//select', $element);
                    foreach ($sub_elements as $sub_element) {
                        $xml->set_node_value($sub_element, 'name', preg_replace('/(wpforms\[fields\]\[)(\d+)(\].*)/', '$1$2_1$3', $xml->get_node_value($sub_element, 'name')));
                    }
                }

                $elements = $xpath->query("//*[contains(@class, 'dropzone-input')]");
                foreach ($elements as $element) {
                    $xml->set_node_value($element, 'name', preg_replace('/wpforms_\d+_(\d+)/', 'wpforms[fields][$1]', $xml->get_node_value($element, 'name')));
                }
                $elements = $xpath->query('//input|//textarea|//select');
                foreach ($elements as $element) {
                    $xml->set_node_value($element, 'name', '{field_id="' . preg_replace('/wpforms\[fields\]\[(\d+|\d+_\d+)\].*/', '$1', $xml->get_node_value($element, 'name')) . '"}');
                }
                $submit_buttons = $xpath->query("//input[@type='submit']|//button[@type='submit']");
                foreach ($submit_buttons as $element) {
                    $element->parentNode->removeChild($element);
                }

                if (defined('LIBXML_HTML_NOIMPLIED') && defined('LIBXML_HTML_NODEFDTD')) {
                    return str_replace(array('<html>', '</html>'), '', $dom->saveHTML());
                } else {
                    return $dom->saveHTML();
                }
            }
        }
        return false;
    }

    /**
     * Load additional shortcodes for this extension
     */
    public function load_shortcodes() { // phpcs:ignore Squiz.WhiteSpace.SuperfluousWhitespace.EndLine
    }

    /**
     * Get styles for generating Map Field function
     * @return array - List of css files to load
     */
    public function styles($item_id = false) {
        $styles = array();
        if (defined('WPFORMS_PLUGIN_URL')) {
            if (defined('WPFORMS_PLUGIN_SLUG') && WPFORMS_PLUGIN_SLUG == 'wpforms') {
                $styles[] = WPFORMS_PLUGIN_URL . 'assets/pro/css/fields/content/frontend.css';
                $styles[] = WPFORMS_PLUGIN_URL . 'assets/pro/css/fields/layout.css';
                $styles[] = WPFORMS_PLUGIN_URL . 'assets/pro/css/fields/repeater.css';
            }
            $styles[] = WPFORMS_PLUGIN_URL . 'assets/css/frontend/modern/wpforms-full.css';
        }
        $styles[] = plugins_url('css/extension/wpforms.css?v=' . time(), $this->helper->get('plugin_file_path'));
        return $styles;
    }

    public function hook_wpforms_entry_view($entry) {
        if (!empty($entry->form_id)) {
            $hooks = $this->helper->load('hooks')->get('wpforms', 'hook_wpforms_entry_view', $entry->form_id);
            if (!empty($hooks)) {
                echo '<div id="wpforms-entry-actions" class="postbox">';
                echo '<div class="postbox-header"><h2 class="hndle"><span>' . apply_filters('e2pdf_hook_section_title', __('E2Pdf Actions', 'e2pdf'), 'hook_wpforms_entry_view') . '</span></h2></div>';
                echo '<div class="inside">';
                echo '<div class="wpforms-entry-actions-meta">';
                foreach ($hooks as $hook) {
                    $action = apply_filters('e2pdf_hook_action_button',
                            array(
                                'html' => '<p style="padding: 6px 12px 6.5px 42px"><a class="e2pdf-download-hook" target="_blank" href="%s"><span class="dashicons dashicons-pdf"></span> %s</a></p>',
                                'url' => $this->helper->get_url(
                                        array(
                                            'page' => 'e2pdf',
                                            'action' => 'export',
                                            'id' => $hook,
                                            'dataset' => $entry->entry_id,
                                        ), 'admin.php?'
                                ),
                                'title' => 'PDF #' . $hook
                            ), 'hook_wpforms_entry_view', $hook, $entry->entry_id
                    );
                    if (!empty($action)) {
                        echo sprintf(
                                $action['html'], $action['url'], $action['title']
                        );
                    }
                }
                echo '</div>';
                echo '</div>';
                echo '</div>';
            }
        }
    }

    public function hook_wpforms_entry_edit($entry) {
        if (!empty($entry->form_id)) {
            $hooks = $this->helper->load('hooks')->get('wpforms', 'hook_wpforms_entry_edit', $entry->form_id);
            if (!empty($hooks)) {
                echo '<div id="wpforms-entry-actions" class="postbox">';
                echo '<div class="postbox-header"><h2 class="hndle"><span>' . apply_filters('e2pdf_hook_section_title', __('E2Pdf Actions', 'e2pdf'), 'hook_wpforms_entry_edit') . '</span></h2></div>';
                echo '<div class="inside">';
                echo '<div class="wpforms-entry-actions-meta">';
                foreach ($hooks as $hook) {
                    $action = apply_filters('e2pdf_hook_action_button',
                            array(
                                'html' => '<p style="padding: 6px 12px 6.5px 42px"><a class="e2pdf-download-hook" target="_blank" href="%s"><span class="dashicons dashicons-pdf"></span> %s</a></p>',
                                'url' => $this->helper->get_url(
                                        array(
                                            'page' => 'e2pdf',
                                            'action' => 'export',
                                            'id' => $hook,
                                            'dataset' => $entry->entry_id,
                                        ), 'admin.php?'
                                ),
                                'title' => 'PDF #' . $hook
                            ), 'hook_wpforms_entry_edit', $hook, $entry->entry_id
                    );
                    if (!empty($action)) {
                        echo sprintf(
                                $action['html'], $action['url'], $action['title']
                        );
                    }
                }
                echo '</div>';
                echo '</div>';
                echo '</div>';
            }
        }
    }

    public function hook_wpforms_row_actions($actions, $entry) {
        if (!empty($entry->form_id)) {
            $hooks = $this->helper->load('hooks')->get('wpforms', 'hook_wpforms_row_actions', $entry->form_id);
            foreach ($hooks as $hook) {
                $action = apply_filters('e2pdf_hook_action_button',
                        array(
                            'html' => '<a class="e2pdf-download-hook" target="_blank" href="%s">%s</a>',
                            'url' => $this->helper->get_url(
                                    array(
                                        'page' => 'e2pdf',
                                        'action' => 'export',
                                        'id' => $hook,
                                        'dataset' => $entry->entry_id,
                                    ), 'admin.php?'
                            ),
                            'title' => 'PDF #' . $hook
                        ), 'hook_wpforms_row_actions', $hook, $entry->entry_id
                );
                if (!empty($action)) {
                    $actions['e2pdf_' . $hook] = sprintf(
                            $action['html'], $action['url'], $action['title']
                    );
                }
            }
        }
        return $actions;
    }
}
