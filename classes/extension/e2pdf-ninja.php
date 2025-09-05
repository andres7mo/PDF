<?php

/**
 * E2Pdf Fluent Forms Extension
 * @copyright  Copyright 2017 https://e2pdf.com
 * @license    GPL v2
 * @version    1
 * @link       https://e2pdf.com
 * @since      1.07.04
 */
if (!defined('ABSPATH')) {
    die('Access denied.');
}

class Extension_E2pdf_Ninja extends Model_E2pdf_Model {

    private $options;
    private $info = array(
        'key' => 'ninja',
        'title' => 'Ninja Forms',
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
        if (defined('E2PDF_NINJA_EXTENSION') || $this->helper->load('extension')->is_plugin_active('ninja-forms/ninja-forms.php')) {
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
                if ($this->get('item') && function_exists('Ninja_Forms')) {
                    $form = Ninja_Forms()->form($this->get('item'))->get();
                    if ($form->get_setting('objectType')) {
                        $this->set('cached_form', $form);
                    }
                }
                break;
            case 'dataset':
                $this->set('cached_entry', false);
                if ($this->get('cached_form') && $this->get('dataset')) {
                    $entry = Ninja_Forms()->form()->get_sub($this->get('dataset'));
                    if ($entry->get_form_id() == $this->get('cached_form')->get_id()) {
                        $this->set('cached_entry', $entry);

                        $fields_merge_tag_object = Ninja_Forms()->merge_tags['fields'];
                        $fields_merge_tag_object->set_form_id($this->get('item'));
                        $fields = Ninja_Forms()->form($this->get('item'))->get_fields();

                        if (class_exists('NF_Adapters_SubmissionsSubmission')) {
                            $fields = new NF_Adapters_SubmissionsSubmission($fields, $this->get('item'), $this->get('cached_entry'));
                            foreach ($fields as $field) {
                                // Fix PHP warnings
                                if (isset($field['type']) && isset($field['value'])) {
                                    switch ($field['type']) {
                                        case 'checkbox':
                                            if (!isset($field['settings'])) {
                                                $field['settings'] = array();
                                            }
                                            if (!isset($field['settings']['unchecked_value'])) {
                                                $field['settings']['unchecked_value'] = '';
                                            }
                                            if (!isset($field['unchecked_calc_value'])) {
                                                $field['unchecked_calc_value'] = '';
                                            }
                                            break;
                                        case 'file_upload':
                                            $_GET['sub_id'] = '';
                                            break;
                                        default:
                                            break;
                                    }
                                }
                                $fields_merge_tag_object->add_field($field);
                                if (isset($field['type']) && isset($field['value'])) {
                                    switch ($field['type']) {
                                        case 'repeater':
                                            $form_field = Ninja_Forms()->form($this->get('item'))->get_field($field['id']);
                                            $list_fields_types = array('listcheckbox', 'listmultiselect', 'listradio', 'listselect');
                                            $field['value_label'] = $field['value'];
                                            foreach ($form_field->get_setting('fields') as $sub_field) {
                                                if (in_array($sub_field['type'], $list_fields_types)) {
                                                    foreach ($field['value_label'] as $sub_value_key => $sub_value) {
                                                        if (0 === strpos($sub_value['id'], $sub_field['id'] . '_')) {
                                                            $sub_field['value'] = $sub_value['value'];
                                                            $field['value_label'][$sub_value_key]['value'] = $fields_merge_tag_object->get_list_labels($sub_field);
                                                        }
                                                    }
                                                }
                                            }

                                            $repeater_data_labels = Ninja_Forms()->fieldsetRepeater->extractSubmissions($field['id'], $field['value_label'], $form_field->get_settings());
                                            $rows = array();
                                            foreach ($repeater_data_labels as $repeater_key => $repeater) {
                                                $row = array();
                                                foreach ($repeater as $sub_repeater_key => $sub_repeater) {
                                                    $sub_value = is_array($sub_repeater['value']) ? implode(', ', $sub_repeater['value']) : $sub_repeater['value'];
                                                    $fields_merge_tag_object->add('field_' . $field['key'] . '_' . $repeater_key . '_' . $sub_repeater_key . '_label', $field['key'], '{field:' . $field['key'] . ':' . $repeater_key . '_' . $sub_repeater_key . ':label}', $sub_value);
                                                    $row[] = $sub_repeater['label'] . ': ' . $sub_value;
                                                }
                                                if (!empty($row)) {
                                                    $rows[] = implode(', ', $row);
                                                }
                                            }
                                            $fields_merge_tag_object->add('field_' . $field['key'] . '_raw_label', $field['key'], '{field:' . $field['key'] . ':raw:label}', str_replace(array('{', '}'), array('&#123;', '&#125;'), serialize($repeater_data_labels))); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
                                            $fields_merge_tag_object->add('field_' . $field['key'] . '_label', $field['key'], '{field:' . $field['key'] . ':label}', implode("\r\n", $rows));

                                            $repeater_data = Ninja_Forms()->fieldsetRepeater->extractSubmissions($field['id'], $field['value'], $form_field->get_settings());
                                            $rows = array();
                                            foreach ($repeater_data as $repeater_key => $repeater) {
                                                $row = array();
                                                foreach ($repeater as $sub_repeater_key => $sub_repeater) {
                                                    $sub_value = is_array($sub_repeater['value']) ? implode(', ', $sub_repeater['value']) : $sub_repeater['value'];
                                                    $fields_merge_tag_object->add('field_' . $field['key'] . '_' . $repeater_key . '_' . $sub_repeater_key, $field['key'], '{field:' . $field['key'] . ':' . $repeater_key . '_' . $sub_repeater_key . '}', $sub_value);
                                                    $row[] = $sub_repeater['label'] . ': ' . $sub_value;
                                                }
                                                if (!empty($row)) {
                                                    $rows[] = implode(', ', $row);
                                                }
                                            }
                                            $fields_merge_tag_object->add('field_' . $field['key'] . '_raw', $field['key'], '{field:' . $field['key'] . ':raw}', str_replace(array('{', '}'), array('&#123;', '&#125;'), serialize($repeater_data))); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
                                            $fields_merge_tag_object->add('field_' . $field['key'], $field['key'], '{field:' . $field['key'] . '}', implode("\r\n", $rows));
                                            break;
                                        case 'listcountry':
                                        case 'liststate':
                                            $form_field = Ninja_Forms()->form($this->get('item'))->get_field($field['id']);
                                            $options = apply_filters('ninja_forms_render_options', $form_field->get_setting('options'), $form_field->get_settings());
                                            $options = apply_filters('ninja_forms_render_options_' . $form_field->get_setting('type'), $options, $form_field->get_settings());
                                            if (isset($field['value']) && $field['value']) {
                                                $key = array_search(
                                                        $field['value'], array_map(
                                                                function ($v) {
                                                                    return $v['value'];
                                                                }, $options
                                                        )
                                                );

                                                $label = isset($options[$key]['label']) ? $options[$key]['label'] : '';
                                                $fields_merge_tag_object->add('field_' . $field['key'] . '_label', $field['key'], '{field:' . $field['key'] . ':label}', $label);
                                            }
                                            break;
                                        case 'checkbox':
                                            $form_field = Ninja_Forms()->form($this->get('item'))->get_field($field['id']);
                                            $checked = '';
                                            if (null == $form_field->get_setting('checked_value') && (1 == $field['value'] || 'on' == $field['value'])) {
                                                $checked = esc_html__('Checked', 'ninja-forms');
                                            } elseif (null == $form_field->get_setting('unchecked_value') && 0 == $field['value']) {
                                                $checked = esc_html__('Unchecked', 'ninja-forms');
                                            }
                                            if ($checked == '') {
                                                if (1 == $field['value'] || 'on' == $field['value']) {
                                                    $checked = $form_field->get_setting('checked_value');
                                                } elseif ($form_field->get_setting('checked_value') != $field['value']) {
                                                    $checked = $form_field->get_setting('unchecked_value');
                                                }
                                            }
                                            $fields_merge_tag_object->add('field_' . $field['key'] . '_label', $field['key'], '{field:' . $field['key'] . ':label}', $checked);
                                            break;
                                        case 'file_upload':
                                            if (is_array($field['value'])) {
                                                $fields_merge_tag_object->add('field_' . $field['key'], $field['key'], '{field:' . $field['key'] . '}', implode(', ', $field['value']));
                                            }
                                            break;
                                        default:
                                            break;
                                    }
                                }
                            }
                            $fields_merge_tag_object->include_all_fields_merge_tags();
                            foreach ($fields as $field) {
                                if (isset($field['type']) && ($field['type'] === 'repeater' || $field['type'] === 'file_upload')) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
                                } else {
                                    // Fix PHP warnings
                                    if (isset($field['type']) && isset($field['value'])) {
                                        switch ($field['type']) {
                                            case 'checkbox':
                                                if (!isset($field['settings'])) {
                                                    $field['settings'] = array();
                                                }
                                                if (!isset($field['settings']['unchecked_value'])) {
                                                    $field['settings']['unchecked_value'] = '';
                                                }
                                                if (!isset($field['unchecked_calc_value'])) {
                                                    $field['unchecked_calc_value'] = '';
                                                }
                                                break;
                                            default:
                                                break;
                                        }
                                    }
                                    $fields_merge_tag_object->add_field($field); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
                                }
                            }
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
        if (function_exists('Ninja_Forms')) {
            $forms = Ninja_Forms()->form()->get_forms();
            if (!empty($forms)) {
                foreach ($forms as $form) {
                    $items[] = $this->item($form->get_id());
                }
            }
        }
        return $items;
    }

    /**
     * Get item
     * @param int $item_id - Item ID
     * @return object - Item
     */
    public function item($item_id = false) {
        $item_id = (int) $item_id;
        if (!$item_id && $this->get('item')) {
            $item_id = $this->get('item');
        }
        $form = false;
        if (function_exists('Ninja_Forms')) {
            $form = Ninja_Forms()->form($item_id)->get();
        }
        $item = new stdClass();
        if ($form) {
            $item->id = (string) $item_id;
            $item->url = $this->helper->get_url(
                    array(
                        'page' => 'ninja-forms',
                        'form_id' => $item_id,
                    )
            );
            $item->name = $form->get_setting('title');
        } else {
            $item->id = '';
            $item->url = 'javascript:void(0);';
            $item->name = '';
        }
        return $item;
    }

    /**
     * Get entries for export
     * @param int $item_id - Form ID
     * @param string $name - Entries names
     * @return array() - Entries list
     */
    public function datasets($item_id = false, $name = false) {
        $item_id = (int) $item_id;
        $datasets = array();
        if (function_exists('Ninja_Forms')) {
            $entries = Ninja_Forms()->form($item_id)->get_subs();
            if ($entries) {
                $this->set('item', $item_id);
                foreach ($entries as $key => $entry) {
                    $this->set('dataset', $entry->get_id());
                    $entry_title = $this->render($name);
                    if (!$entry_title) {
                        $entry_title = $entry->get_id();
                    }
                    $datasets[] = array(
                        'key' => $entry->get_id(),
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
            return false;
        }
        $actions = new stdClass();
        $actions->view = $this->helper->get_url(
                array(
                    'post' => $dataset_id,
                    'action' => 'edit',
                ),
                'post.php?'
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
    public function load_actions() { // phpcs:ignore Squiz.WhiteSpace.SuperfluousWhitespace.EndLine
    }

    /**
     * Load filters for this extension
     */
    public function load_filters() {
        add_filter('ninja_forms_run_action_settings', array($this, 'filter_ninja_forms_run_action_settings'), 10, 4);
        add_filter('ninja_forms_post_run_action_type_save', array($this, 'filter_ninja_forms_post_run_action_type_save'));
        add_filter('ninja_forms_post_run_action_type_email', array($this, 'filter_ninja_forms_post_run_action_type_email'));
        add_filter('ninja_forms_action_email_attachments', array($this, 'filter_ninja_forms_action_email_attachments'), 10, 3);
        add_filter('ninja_forms_action_email_message', array($this, 'filter_ninja_forms_action_email_message'), 10, 3);
        /* 1.25.14 - Trigger Email Action Fix */
        add_filter('rest_dispatch_request', array($this, 'filter_rest_dispatch_request'), 10, 3);
    }

    public function filter_ninja_forms_run_action_settings($settings, $form_id, $action_id, $form_data) {
        $type = isset($settings['type']) ? $settings['type'] : false;
        if ($type == 'successmessage' && isset($settings['success_msg'])) {
            $settings['success_msg'] = $this->filter_content($settings['success_msg'], $this->get('dataset'));
        }
        return $settings;
    }

    public function filter_ninja_forms_post_run_action_type_save($data) {
        $dataset_id = isset($data['actions']['save']['sub_id']) ? $data['actions']['save']['sub_id'] : false;
        if ($dataset_id) {
            $this->set('dataset', $dataset_id);
        }
        return $data;
    }

    public function filter_ninja_forms_post_run_action_type_email($data) {
        $files = $this->helper->get('ninja_attachments');
        if (is_array($files) && !empty($files)) {
            foreach ($files as $key => $file) {
                $this->helper->delete_dir(dirname($file) . '/');
            }
            $this->helper->deset('ninja_attachments');
        }
        return $data;
    }

    public function filter_ninja_forms_action_email_attachments($attachments, $data, $action_settings) {
        $message = isset($action_settings['email_message']) ? $action_settings['email_message'] : '';
        if (false !== strpos($message, '[')) {
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
                    if (!isset($atts['dataset']) && isset($atts['id'])) {
                        $template = new Model_E2pdf_Template();
                        $template->load($atts['id']);
                        if ($template->get('extension') === 'ninja') {
                            $dataset_id = isset($data['actions']['save']['sub_id']) ? $data['actions']['save']['sub_id'] : false;
                            if (!$dataset_id) {
                                $dataset_id = isset($action_settings['sub_id']) ? $action_settings['sub_id'] : false;
                            }
                            if ($dataset_id) {
                                $atts['dataset'] = $dataset_id;
                                $shortcode[3] .= ' dataset="' . $dataset_id . '"';
                            }
                        }
                    }
                    if (!isset($atts['apply'])) {
                        $shortcode[3] .= ' apply="true"';
                    }
                    if (!isset($atts['filter'])) {
                        $shortcode[3] .= ' filter="true"';
                    }
                    if ($this->helper->load('shortcode')->is_attachment($shortcode, $atts)) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
                        $file = do_shortcode_tag($shortcode);
                        if ($file) {
                            $tmp = false;
                            if (substr($file, 0, 4) === 'tmp:') {
                                $file = substr($file, 4);
                                $tmp = true;
                            }
                            if ($shortcode[2] === 'e2pdf-save' || isset($atts['pdf'])) {
                                if ($tmp) {
                                    $this->helper->add('ninja_attachments', $file);
                                }
                            } else {
                                $this->helper->add('ninja_attachments', $file);
                            }
                            $attachments[] = $file;
                        }
                    }
                }
            }
        }
        return $attachments;
    }

    public function filter_ninja_forms_action_email_message($message, $data, $action_settings) {
        $dataset_id = isset($data['actions']['save']['sub_id']) ? $data['actions']['save']['sub_id'] : false;
        if (!$dataset_id) {
            $dataset_id = isset($action_settings['sub_id']) ? $action_settings['sub_id'] : false;
        }
        $message = $this->filter_content($message, $dataset_id);
        return $message;
    }

    public function filter_rest_dispatch_request($result, $request, $route) {
        if ($route && false !== strpos($route, '/ninja-forms-submissions/email-action')) {
            $data = json_decode($request->get_body());
            if (!empty($data->submission) && !empty($data->action_settings)) {
                $data->action_settings->sub_id = $data->submission;
                $request->set_body(json_encode($data)); // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
            }
        }
        return $result;
    }

    public function filter_content($message, $dataset_id) {
        if (false !== strpos($message, '[')) {
            $shortcode_tags = array(
                'e2pdf-download',
                'e2pdf-save',
                'e2pdf-attachment',
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
                    if (!isset($atts['dataset']) && isset($atts['id'])) {
                        $template = new Model_E2pdf_Template();
                        $template->load($atts['id']);
                        if ($template->get('extension') === 'ninja') {
                            $atts['dataset'] = $dataset_id;
                            $shortcode[3] .= ' dataset="' . $dataset_id . '"';
                        }
                    }
                    if (!isset($atts['apply'])) {
                        $shortcode[3] .= ' apply="true"';
                    }
                    if (!isset($atts['filter'])) {
                        $shortcode[3] .= ' filter="true"';
                    }
                    if ($this->helper->load('shortcode')->is_attachment($shortcode, $atts)) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
                        $message = str_replace($shortcode_value, '', $message);
                    } else {
                        $message = str_replace($shortcode_value, do_shortcode_tag($shortcode), $message);
                    }
                }
            }
        }
        return $message;
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
            $value = apply_filters('ninja_forms_merge_tags', $value);
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
                $search = array('&#91;', '&#93;', '&#091;', '&#093;', '&#123;', '&#125;');
                $replace = array('[', ']', '[', ']', '{', '}');
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
        if ($this->get('cached_form')) {
            $form_fields = Ninja_Forms()->form($this->get('item'))->get_fields();
            $width = '100';
            foreach ($form_fields as $form_field) {
                $field = $form_field->get_settings();
                switch ($field['type']) {
                    case 'repeater':
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
                                        'value' => $field['label'],
                                    ),
                                )
                        );

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
                                        'value' => '<hr>',
                                    ),
                                )
                        );

                        foreach ($field['fields'] as $sub_field) {
                            list($field_id, $sub_field_id) = explode('.', $sub_field['id']);
                            $sub_field['key'] = $field['key'] . ':0_' . $sub_field_id;
                            $elements = $this->auto_fields($elements, $sub_field);
                        }

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
                                        'value' => '<hr>',
                                    ),
                                )
                        );
                        break;
                    default:
                        $elements = $this->auto_fields($elements, $field);
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

    public function auto_fields($elements, $field) {
        $width = '100';
        switch ($field['type']) {
            case 'textbox':
            case 'number':
            case 'date':
            case 'city':
            case 'email':
            case 'firstname':
            case 'lastname':
            case 'phone':
            case 'zip':
            case 'hidden':
            case 'starrating':
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
                                'value' => $field['label'],
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
                                'value' => '{field:' . $field['key'] . '}',
                            ),
                        )
                );
                break;
            case 'listselect':
            case 'listmultiselect':
            case 'listcountry':
            case 'liststate':
                if ($field['type'] == 'listcountry' || $field['type'] == 'liststate') {
                    $options = apply_filters('ninja_forms_render_options', $field['options'], $field);
                    $options = apply_filters('ninja_forms_render_options_' . $field['type'], $options, $field);
                } else {
                    $options = $field['options'];
                }

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
                                'value' => $field['label'],
                            ),
                        )
                );
                $options_tmp = array();
                foreach ($options as $option) {
                    $options_tmp[] = $option['label'];
                }

                if ($field['type'] == 'listmultiselect') {
                    $elements[] = $this->auto_field(
                            $field,
                            array(
                                'type' => 'e2pdf-select',
                                'properties' => array(
                                    'top' => '5',
                                    'width' => '100%',
                                    'height' => '44',
                                    'multiline' => '1',
                                    'options' => implode("\n", $options_tmp),
                                    'value' => '{field:' . $field['key'] . ':label}',
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
                                    'options' => implode("\n", $options_tmp),
                                    'value' => '{field:' . $field['key'] . ':label}',
                                ),
                            )
                    );
                }
                break;
            case 'textarea':
            case 'address':
            case 'listimage':
            case 'file_upload':
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
                                'value' => $field['label'],
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
                                'value' => '{field:' . $field['key'] . '}',
                            ),
                        )
                );
                break;
            case 'html':
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
                                'value' => $field['label'],
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
                                'height' => 'auto',
                                'value' => $field['default'],
                            ),
                        )
                );
                break;
            case 'listradio':
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
                                'value' => $field['label'],
                            ),
                        )
                );

                foreach ($field['options'] as $opt_key => $option) {
                    $elements[] = $this->auto_field(
                            $field,
                            array(
                                'type' => 'e2pdf-radio',
                                'properties' => array(
                                    'top' => '5',
                                    'width' => 'auto',
                                    'height' => 'auto',
                                    'value' => '{field:' . $field['key'] . ':label}',
                                    'option' => $option['label'],
                                    'group' => '{field:' . $field['key'] . '}',
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
                                    'value' => $option['label'],
                                ),
                            )
                    );
                }
                break;
            case 'listcheckbox':
            case 'checkbox':
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
                                'value' => $field['label'],
                            ),
                        )
                );

                if ($field['type'] == 'checkbox') {
                    $elements[] = $this->auto_field(
                            $field,
                            array(
                                'type' => 'e2pdf-checkbox',
                                'properties' => array(
                                    'top' => '5',
                                    'width' => 'auto',
                                    'height' => 'auto',
                                    'value' => '{field:' . $field['key'] . ':label}',
                                    'option' => !isset($field['checked_value']) ? esc_html__('Checked', 'ninja-forms') : $field['checked_value'],
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
                                    'value' => $field['label'],
                                ),
                            )
                    );
                } else {
                    foreach ($field['options'] as $opt_key => $option) {
                        $elements[] = $this->auto_field(
                                $field,
                                array(
                                    'type' => 'e2pdf-checkbox',
                                    'properties' => array(
                                        'top' => '5',
                                        'width' => 'auto',
                                        'height' => 'auto',
                                        'value' => '{field:' . $field['key'] . ':label}',
                                        'option' => $option['label'],
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
                                        'value' => $option['label'],
                                    ),
                                )
                        );
                    }
                }
                break;
            case 'asignature':
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
                                'value' => $field['label'],
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
                                'value' => '{field:' . $field['key'] . '}',
                            ),
                        )
                );
                break;
            default:
                break;
        }

        return $elements;
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
        if ($this->get('cached_form') && $this->get('cached_entry')) {
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
        if ($this->get('item')) {
            $form = $this->auto();
            $html = '<div class="ninja-form">';
            $float = false;
            foreach ($form['elements'] as $element) {
                switch ($element['type']) {
                    case 'e2pdf-html':
                        $html .= '<div>' . $element['properties']['value'] . '</div>';
                        if ($float) {
                            $html .= '<div class="clearfix"></div>';
                            $float = false;
                        }
                        break;
                    case 'e2pdf-input':
                        $html .= '<div><input type="text" name="' . $element['properties']['value'] . '"></div>';
                        break;
                    case 'e2pdf-image':
                    case 'e2pdf-signature':
                    case 'e2pdf-textarea':
                        $html .= '<div><textarea name="' . $element['properties']['value'] . '"></textarea></div>';
                        break;
                    case 'e2pdf-select':
                        $html .= '<div>';
                        $html .= '<select name="' . $element['properties']['value'] . '">';
                        $options = explode("\n", $element['properties']['options']);
                        foreach ($options as $option) {
                            $html .= '<option>' . $option . '</option>';
                        }
                        $html .= '</select>';
                        $html .= '</div>';
                        break;
                    case 'e2pdf-checkbox':
                        $html .= '<div class="ninja-checkbox"><input type="checkbox" name="' . $element['properties']['value'] . '" value="' . $element['properties']['option'] . '"></div>';
                        $float = true;
                        break;
                    case 'e2pdf-radio':
                        $html .= '<div class="ninja-radio"><input type="radio" name="' . $element['properties']['value'] . '" value="' . $element['properties']['option'] . '"></div>';
                        $float = true;
                        break;
                    default:
                        break;
                }
            }
            $html .= '</div>';
            return $html;
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
        $styles[] = plugins_url('css/extension/ninja.css?v=' . time(), $this->helper->get('plugin_file_path'));
        return $styles;
    }
}
