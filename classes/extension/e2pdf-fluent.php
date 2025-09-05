<?php

/**
 * E2Pdf Fluent Forms Extension
 * @copyright  Copyright 2017 https://e2pdf.com
 * @license    GPL v2
 * @version    1
 * @link       https://e2pdf.com
 * @since      1.22.10
 */
if (!defined('ABSPATH')) {
    die('Access denied.');
}

class Extension_E2pdf_Fluent extends Model_E2pdf_Model {

    private $options;
    private $info = array(
        'key' => 'fluent',
        'title' => 'Fluent Forms',
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
        if (defined('E2PDF_FLUENT_EXTENSION') || $this->helper->load('extension')->is_plugin_active('fluentform/fluentform.php')) {
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
                if ($this->get('item') && function_exists('fluentFormApi')) {
                    $this->set('cached_form', fluentFormApi('forms')->find($this->get('item')));
                }
                break;
            case 'dataset':
                $this->set('cached_entry', false);
                if ($this->get('dataset') && $this->get('cached_form') && function_exists('fluentFormApi')) {
                    $entry = fluentFormApi('submissions')->find($this->get('dataset'));
                    $data = false;
                    if ($entry && isset($entry->response)) {
                        $data = @json_decode(json_encode($entry->response), true); // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode,WordPress.PHP.NoSilencedErrors.Discouraged
                    }
                    if (is_array($data)) {
                        // Fix for Address Country
                        foreach ($data as $data_key => $data_value) {
                            if (is_array($data_value) && isset($data_value['country'])) {
                                $data[$data_key . '.country'] = $data_value['country'];
                            }
                        }
                        $entry->data = $data;
                    } else {
                        $entry->data = array();
                    }
                    $this->set('cached_entry', $entry);
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
        if (class_exists('FluentForm\App\Helpers\Helper')) {
            $forms = FluentForm\App\Helpers\Helper::getForms();
            if (isset($forms[0])) {
                unset($forms[0]);
            }
            if (!empty($forms)) {
                foreach ($forms as $key => $form) {
                    $items[] = $this->item($key);
                }
            }
        }
        return $items;
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

        if ($item_id && function_exists('fluentFormApi')) {
            $args = array(
                'per_page' => 99999,
                'page' => 1,
                'search' => '',
                'form_ids' => array($item_id),
                'sort_type' => 'DESC',
                'entry_type' => 'all',
                'user_id' => false,
            );
            $entries = fluentFormApi('submissions')->get($args);
            if ($entries['data']) {
                $this->set('item', $item_id);
                foreach ($entries['data'] as $key => $entry) {
                    $this->set('dataset', $entry->id);
                    $entry_title = $this->render($name);
                    if (!$entry_title) {
                        $entry_title = $entry->id;
                    }
                    $datasets[] = array(
                        'key' => $entry['id'],
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

        $form_id = false;
        if (function_exists('fluentFormApi')) {
            $entry = fluentFormApi('submissions')->find($dataset_id);
            if ($entry) {
                $form_id = $entry->form_id;
            }
        }
        $actions = new stdClass();
        if ($form_id) {
            $actions->view = $this->helper->get_url(
                            array(
                                'page' => 'fluent_forms',
                                'route' => 'entries',
                                'form_id' => $form_id,
                            )
                    ) . '#/entries/' . $dataset_id;
        } else {
            $actions->view = false;
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
     * @param int $item_id - Item ID
     * @return object - Item
     */
    public function item($item_id = false) {
        $item_id = (int) $item_id;
        if (!$item_id && $this->get('item')) {
            $item_id = $this->get('item');
        }
        $form = false;
        if (function_exists('fluentFormApi')) {
            $form = fluentFormApi('forms')->find($item_id);
        }
        $item = new stdClass();
        if ($form) {
            $item->id = (string) $item_id;
            $item->url = $this->helper->get_url(
                    array(
                        'page' => 'fluent_forms',
                        'route' => 'editor',
                        'form_id' => $item_id,
                    )
            );
            $item->name = $form->title;
        } else {
            $item->id = '';
            $item->url = 'javascript:void(0);';
            $item->name = '';
        }
        return $item;
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
        add_action('fluentform/integration_notify_notifications', array($this, 'action_integration_notify_notifications'), 99, 0);
        add_action('fluentform/notify_on_form_submit', array($this, 'action_fluentform_before_form_actions_processing'), 99, 3);
    }

    public function action_fluentform_before_form_actions_processing($insertId, $formData, $form) {
        if ($insertId) {
            $entry = wpFluent()->table('fluentform_submissions')
                    ->where('id', $insertId)
                    ->first();
            if ($entry && $form && !empty($form->form_fields)) {
                $shortcode_tags = array(
                    'e2pdf-download',
                    'e2pdf-save',
                    'e2pdf-attachment',
                    'e2pdf-view',
                    'e2pdf-adobesign',
                    'e2pdf-zapier',
                );
                preg_match_all('@\[([^<>&/\[\]\x00-\x20=]++)@', $form->form_fields, $matches);
                $tagnames = array_intersect($shortcode_tags, $matches[1]);
                if (!empty($tagnames)) {
                    $data = json_decode($form->form_fields, true);
                    $form_fields = isset($data['fields']) ? $data['fields'] : array();
                    $response = array();
                    foreach ($form_fields as $field) {
                        $field_type = !empty($field['attributes']['type']) ? $field['attributes']['type'] : '';
                        $field_value = !empty($field['attributes']['value']) ? $field['attributes']['value'] : '';
                        if ($field_type == 'hidden' && false !== strpos($field_value, '[')) {
                            $value = $this->filter_submission_message_parse($field_value, $insertId);
                            if ($value != $field_value) {
                                $field_name = !empty($field['attributes']['name']) ? $field['attributes']['name'] : '';
                                if ($field_name) {
                                    $response[$field_name] = $value;
                                }
                            }
                        }
                    }
                    if (!empty($response)) {
                        $origianlResponse = json_decode($entry->response, true);
                        foreach ($response as $resKey => $resvalue) {
                            if (!isset($origianlResponse[$resKey]) || $origianlResponse[$resKey] != $resvalue) {
                                $origianlResponse[$resKey] = $resvalue;
                            }
                        }
                        wpFluent()->table('fluentform_submissions')
                                ->where('id', $insertId)
                                ->update([
                                    'response' => json_encode($origianlResponse, JSON_UNESCAPED_UNICODE),
                        ]);
                    }
                }
            }
        }
    }

    public function action_integration_notify_notifications() {
        $files = $this->helper->get('fluent_attachments');
        if (is_array($files) && !empty($files)) {
            foreach ($files as $key => $file) {
                $this->helper->delete_dir(dirname($file) . '/');
            }
            $this->helper->deset('fluent_attachments');
        }
    }

    /**
     * Load filters for this extension
     */
    public function load_filters() {
        add_filter('fluentform/submission_message_parse', array($this, 'filter_submission_message_parse'), 10, 2);
        add_filter('fluentform/filter_email_attachments', array($this, 'filter_email_attachments'), 10, 4);
        add_filter('fluentform/integration_data_trello', array($this, 'filter_integration_data_trello'), 10, 3);
        add_filter('fluentform/insert_response_data', array($this, 'filter_fluentform_insert_response_data'), 99, 3);
    }

    public function filter_fluentform_insert_response_data($formData, $formId, $inputConfigs) {
        if (!empty($formData) && is_array($formData)) {
            $jsonData = json_encode($formData, JSON_UNESCAPED_UNICODE);
            $shortcode_tags = array(
                'e2pdf-download',
                'e2pdf-save',
                'e2pdf-attachment',
                'e2pdf-view',
                'e2pdf-adobesign',
                'e2pdf-zapier',
            );
            preg_match_all('@\[([^<>&/\[\]\x00-\x20=]++)@', $jsonData, $matches);
            $tagnames = array_intersect($shortcode_tags, $matches[1]);
            if (!empty($tagnames)) {
                preg_match_all('/' . $this->helper->load('shortcode')->get_shortcode_regex($tagnames) . '/', $jsonData, $shortcodes);
                foreach ($shortcodes[0] as $key => $shortcode_value) {
                    $jsonData = str_replace($shortcode_value, '', $jsonData);
                }
                $formData = json_decode($jsonData, true);
            }
        }
        return $formData;
    }

    public function filter_submission_message_parse($message, $dataset) {
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
                        if ($template->get('extension') === 'fluent') {
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
                                    $this->helper->add('fluent_attachments', $file);
                                }
                            } else {
                                $this->helper->add('fluent_attachments', $file);
                            }
                        }
                        $message = str_replace($shortcode_value, '', $message);
                    } else {
                        // Iframe onload attribute bug
                        if ($shortcode[2] === 'e2pdf-view') {
                            $message = str_replace($shortcode_value, '[' . $shortcode[2] . $shortcode[3] . ']', $message);
                        } else {
                            $message = str_replace($shortcode_value, do_shortcode_tag($shortcode), $message);
                        }
                    }
                }
            }
        }

        return $message;
    }

    public function filter_integration_data_trello($data, $feed, $entry) {
        if (!empty($data['desc']) && false !== strpos($data['desc'], '[')) {

            $dataset = isset($entry->id) ? $entry->id : 0;
            $message = $data['desc'];

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
                        if ($template->get('extension') === 'fluent') {
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

                    if ($this->helper->load('shortcode')->is_attachment($shortcode, $atts)) {
                        $message = str_replace($shortcode_value, '', $message);
                    } else {
                        $message = str_replace($shortcode_value, do_shortcode_tag($shortcode), $message);
                    }
                }
                $data['desc'] = $message;
            }
        }
        return $data;
    }

    public function filter_email_attachments($emailAttachments, $notification, $form, $submittedDat) {
        $files = $this->helper->get('fluent_attachments');
        if (is_array($files) && !empty($files)) {
            foreach ($files as $file) {
                $emailAttachments[] = $file;
            }
        }
        return $emailAttachments;
    }

    public function filter_fields() {
        $fields = array(
            'select',
            'select_country',
            'input_checkbox',
            'chained_select',
            'repeater_field',
        );
        foreach ($fields as $field) {
            add_filter('fluentform/response_render_' . $field, array($this, 'filter_response_pre_render'), 0, 4);
            add_filter('fluentform/response_render_' . $field, array($this, 'filter_response_after_render'), 99, 4);
        }
    }

    public function unfilter_fields() {
        $fields = array(
            'select',
            'select_country',
            'input_checkbox',
            'chained_select',
            'repeater_field',
        );
        foreach ($fields as $field) {
            remove_filter('fluentform/response_render_' . $field, array($this, 'filter_response_pre_render'), 0);
            remove_filter('fluentform/response_render_' . $field, array($this, 'filter_response_after_render'), 99);
        }
    }

    public function filter_response_pre_render($values, $field, $form_id, $is_html) {
        $cached = $this->get('cached');
        if (!$cached) {
            $cached = array();
        }
        $field_key = isset($field['attributes']['name']) ? $field['attributes']['name'] : '';
        if ($field_key) {
            switch ($field['element']) {
                case 'select_country':
                    if (!$is_html) {
                        if (function_exists('getFluentFormCountryList')) {
                            $cached[$field_key] = $values;
                        } else {
                            $cached[$field_key] = '';
                        }
                    }
                    break;
                case 'select':
                case 'input_checkbox':
                case 'chained_select':
                    if ($is_html) {
                        if ($field['element'] == 'select' && (!isset($field['attributes']['multiple']) || (isset($field['attributes']['multiple']) && !$field['attributes']['multiple']))) {
                            
                        } else {
                            if (is_array($values)) {
                                if (!isset($field['options'])) {
                                    $field['options'] = [];
                                    foreach (\FluentForm\Framework\Helpers\ArrayHelper::get($field, 'raw.settings.advanced_options', []) as $option) {
                                        $field['options'][$option['value']] = $option['label'];
                                    }
                                }
                                $labels = array();
                                foreach ($values as $value) {
                                    if ($label = \FluentForm\Framework\Helpers\ArrayHelper::get($field, 'options.' . $value)) {
                                        $labels[] = $label;
                                    } else {
                                        $labels[] = $value;
                                    }
                                }
                                $cached[$field_key] = implode(', ', $labels);
                            }
                        }
                    }
                    break;
                case 'repeater_field':
                    if (apply_filters('e2pdf_for_do_shortcode_data_process', false)) {
                        if (is_array($values)) {
                            $cached[$field_key] = serialize($values);
                        } else {
                            $cached[$field_key] = '';
                        }
                    }
                    break;
                default:
                    break;
            }
        }
        $this->set('cached', $cached);
        return $values;
    }

    public function filter_response_after_render($values, $field, $form_id, $is_html) {
        $cached = $this->get('cached');
        if (!$cached) {
            $cached = array();
        }
        $field_key = isset($field['attributes']['name']) ? $field['attributes']['name'] : '';
        if ($field_key) {
            switch ($field['element']) {
                case 'select_country':
                    if (!$is_html) {
                        return isset($cached[$field_key]) ? $cached[$field_key] : '';
                    }
                    break;
                case 'select':
                case 'input_checkbox':
                case 'chained_select':
                    if ($is_html) {
                        if ($field['element'] == 'select' && (!isset($field['attributes']['multiple']) || (isset($field['attributes']['multiple']) && !$field['attributes']['multiple']))) {
                            
                        } else {
                            return isset($cached[$field_key]) ? $cached[$field_key] : '';
                        }
                    }
                    break;
                case 'repeater_field':
                    if (apply_filters('e2pdf_for_do_shortcode_data_process', false)) {
                        return isset($cached[$field_key]) ? $cached[$field_key] : '';
                    }
                    break;
                default:
                    break;
            }
        }
        return $values;
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

            if (class_exists('FluentForm\App\Services\FormBuilder\ShortCodeParser')) {
                $this->filter_fields();
                if ($value === '0') { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
                } else {
                    // Hide PHP warnings due to the unfilled checkbox/radio and multiple selects produce PHP warnings
                    $value = @FluentForm\App\Services\FormBuilder\ShortCodeParser::parse(// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
                                    $value,
                                    $this->get('cached_entry')->id,
                                    $this->get('cached_entry')->data,
                                    $this->get('cached_form'),
                                    false,
                                    false
                            );
                }
                $this->unfilter_fields();
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
        $form = fluentFormApi('forms')->find($this->get('item'));
        $data = json_decode($form->form_fields, true);
        $form_fields = isset($data['fields']) ? $data['fields'] : array();
        foreach ($form_fields as $field) {
            $width = '100';
            $type = isset($field['element']) ? $field['element'] : '';
            switch ($type) {
                case 'container':
                    foreach ($field['columns'] as $column) {
                        $width = $column['width'];
                        foreach ($column['fields'] as $sub_field) {
                            $elements = $this->auto_fields($elements, $sub_field, $width);
                        }
                    }
                    break;
                case 'repeater_field':
                    $width = $width / count($field['fields']);
                    $index = 0;
                    foreach ($field['fields'] as $sub_field) {
                        $sub_field['attributes']['name'] = $field['attributes']['name'] . '.0.' . $index;
                        $sub_field['attributes']['parent'] = 'repeater_field';
                        $index++;
                        $elements = $this->auto_fields($elements, $sub_field, $width);
                    }
                    break;
                case 'tabular_grid':
                    $width = $width / (count($field['settings']['grid_columns']) + 1);
                    $sub_field = array(
                        'element' => 'hidden_html',
                    );
                    $elements = $this->auto_fields($elements, $sub_field, $width);
                    foreach ($field['settings']['grid_columns'] as $grid_column) {
                        $sub_field = array(
                            'element' => 'custom_html',
                            'settings' => array(
                                'html_codes' => $grid_column,
                            ),
                        );
                        $elements = $this->auto_fields($elements, $sub_field, $width);
                    }
                    foreach ($field['settings']['grid_rows'] as $grid_row_key => $grid_row) {
                        $sub_field = array(
                            'element' => 'custom_html',
                            'settings' => array(
                                'html_codes' => $grid_row,
                            ),
                        );
                        $elements = $this->auto_fields($elements, $sub_field, $width);
                        foreach ($field['settings']['grid_columns'] as $grid_column_key => $grid_column) {
                            $sub_field = array(
                                'element' => $field['settings']['tabular_field_type'] == 'radio' ? 'grid_radio' : 'grid_checkbox',
                                'name' => $field['attributes']['name'] . '.' . $grid_row_key,
                                'label' => $grid_column_key,
                            );
                            $elements = $this->auto_fields($elements, $sub_field, $width);
                        }
                    }
                    break;
                default:
                    $elements = $this->auto_fields($elements, $field, $width);
                    break;
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

    public function auto_fields($elements = array(), $field = array(), $width = '100') {
        $type = isset($field['element']) ? $field['element'] : '';
        switch ($type) {
            case 'input_name':
                $count = 0;
                foreach ($field['fields'] as $sub_field) {
                    if (isset($sub_field['settings']['visible']) && $sub_field['settings']['visible']) {
                        $count++;
                    }
                }
                foreach ($field['fields'] as $sub_field) {
                    if (isset($sub_field['settings']['visible']) && $sub_field['settings']['visible']) {
                        $elements[] = $this->auto_field(
                                $sub_field,
                                array(
                                    'type' => 'e2pdf-html',
                                    'block' => true,
                                    'float' => true,
                                    'properties' => array(
                                        'top' => '20',
                                        'left' => '20',
                                        'right' => '20',
                                        'width' => $width / $count . '%',
                                        'height' => 'auto',
                                        'value' => $sub_field['settings']['label'],
                                    ),
                                )
                        );
                        $elements[] = $this->auto_field(
                                $sub_field,
                                array(
                                    'type' => 'e2pdf-input',
                                    'properties' => array(
                                        'top' => '5',
                                        'width' => '100%',
                                        'height' => 'auto',
                                        'value' => '{inputs.' . $field['attributes']['name'] . '.' . $sub_field['attributes']['name'] . '}',
                                    ),
                                )
                        );
                    }
                }
                break;
            case 'custom_html':
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
                                'value' => $field['settings']['html_codes'],
                            ),
                        )
                );
                break;
            case 'input_text':
            case 'input_email':
            case 'input_password':
            case 'input_number':
            case 'input_url':
            case 'input_date':
            case 'color_picker':
            case 'rangeslider':
            case 'cpt_selection':
            case 'phone':
            case 'payment_input':
            case 'custom_payment_component':
            case 'item_quantity_component':
            case 'subscription_payment_component':
            case 'multi_payment_component':
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
                                'value' => isset($field['settings']['label']) ? $field['settings']['label'] : '',
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
                                'pass' => $type == 'input_password' ? '1' : '0',
                                'value' => '{inputs.' . $field['attributes']['name'] . '}',
                            ),
                        )
                );
                break;
            case 'textarea':
            case 'input_file':
            case 'input_image':
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
                                'value' => isset($field['settings']['label']) ? $field['settings']['label'] : '',
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
                                'value' => '{inputs.' . $field['attributes']['name'] . '}',
                            ),
                        )
                );
                break;
            case 'rich_text_input':
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
                                'value' => isset($field['settings']['label']) ? $field['settings']['label'] : '',
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
                                'value' => '{inputs.' . $field['attributes']['name'] . '}',
                            ),
                        )
                );
                break;
            case 'select_country':
            case 'select':
                $parent = isset($field['attributes']['parent']) ? $field['attributes']['parent'] : '';
                $options_tmp = array();
                if ($type == 'select_country') {
                    if (function_exists('getFluentFormCountryList')) {
                        foreach (getFluentFormCountryList() as $opt_key => $option) {
                            $options_tmp[] = $option;
                        }
                    }
                } elseif ($type == 'select') {
                    if ($parent == 'repeater_field') {
                        foreach ($field['settings']['advanced_options'] as $opt_key => $option) {
                            $options_tmp[] = $option['value'];
                        }
                    } else {
                        foreach ($field['settings']['advanced_options'] as $opt_key => $option) {
                            $options_tmp[] = $option['label'];
                        }
                    }
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
                                'value' => isset($field['settings']['label']) ? $field['settings']['label'] : '',
                            ),
                        )
                );
                if (isset($field['attributes']['multiple']) && $field['attributes']['multiple']) {
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
                                    'value' => $parent == 'repeater_field' ? '{inputs.' . $field['attributes']['name'] . '}' : '{inputs.' . $field['attributes']['name'] . '.label}',
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
                                    'value' => $parent == 'repeater_field' ? '{inputs.' . $field['attributes']['name'] . '}' : '{inputs.' . $field['attributes']['name'] . '.label}',
                                ),
                            )
                    );
                }
                break;
            case 'input_radio':
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
                                'value' => isset($field['settings']['label']) ? $field['settings']['label'] : '',
                            ),
                        )
                );

                foreach ($field['settings']['advanced_options'] as $opt_key => $option) {
                    $elements[] = $this->auto_field(
                            $field,
                            array(
                                'type' => 'e2pdf-radio',
                                'properties' => array(
                                    'top' => '5',
                                    'width' => 'auto',
                                    'height' => 'auto',
                                    'value' => '{inputs.' . $field['attributes']['name'] . '.label}',
                                    'option' => $option['label'],
                                    'group' => '{inputs.' . $field['attributes']['name'] . '.label}',
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
            case 'net_promoter_score':
            case 'ratings':
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
                                'value' => isset($field['settings']['label']) ? $field['settings']['label'] : '',
                            ),
                        )
                );

                $start = true;
                foreach ($field['options'] as $opt_key => $option) {
                    $elements[] = $this->auto_field(
                            $field,
                            array(
                                'type' => 'e2pdf-html',
                                'block' => true,
                                'float' => $start ? false : true,
                                'properties' => array(
                                    'text_align' => 'center',
                                    'top' => '20',
                                    'left' => '20',
                                    'right' => '20',
                                    'width' => $width / count($field['options']) . '%',
                                    'height' => 'auto',
                                    'value' => $option,
                                ),
                            )
                    );
                    $elements[] = $this->auto_field(
                            $field,
                            array(
                                'type' => 'e2pdf-radio',
                                'properties' => array(
                                    'top' => '5',
                                    'width' => 'auto',
                                    'height' => 'auto',
                                    'value' => '{inputs.' . $field['attributes']['name'] . '}',
                                    'option' => (string) $opt_key,
                                    'group' => '{inputs.' . $field['attributes']['name'] . '}',
                                ),
                            )
                    );
                    $start = false;
                }
                break;
            case 'hidden_html':
                $elements[] = $this->auto_field(
                        $field,
                        array(
                            'type' => 'e2pdf-html',
                            'block' => true,
                            'float' => true,
                            'hidden' => true,
                            'properties' => array(
                                'top' => '20',
                                'left' => '20',
                                'right' => '20',
                                'width' => $width . '%',
                                'height' => '0',
                                'value' => '',
                            ),
                        )
                );
                break;
            case 'grid_checkbox':
                $elements[] = $this->auto_field(
                        $field,
                        array(
                            'type' => 'e2pdf-html',
                            'block' => true,
                            'float' => true,
                            'hidden' => true,
                            'properties' => array(
                                'top' => '20',
                                'left' => '20',
                                'right' => '20',
                                'width' => $width . '%',
                                'height' => '-5',
                                'value' => '',
                            ),
                        )
                );
                $elements[] = $this->auto_field(
                        $field,
                        array(
                            'type' => 'e2pdf-checkbox',
                            'properties' => array(
                                'top' => '5',
                                'width' => 'auto',
                                'height' => 'auto',
                                'value' => '{inputs.' . $field['name'] . '}',
                                'option' => $field['label'],
                            ),
                        )
                );
                break;
            case 'grid_radio':
                $elements[] = $this->auto_field(
                        $field,
                        array(
                            'type' => 'e2pdf-html',
                            'block' => true,
                            'float' => true,
                            'hidden' => true,
                            'properties' => array(
                                'top' => '20',
                                'left' => '20',
                                'right' => '20',
                                'width' => $width . '%',
                                'height' => '-5',
                                'value' => '',
                            ),
                        )
                );
                $elements[] = $this->auto_field(
                        $field,
                        array(
                            'type' => 'e2pdf-radio',
                            'properties' => array(
                                'top' => '5',
                                'width' => 'auto',
                                'height' => 'auto',
                                'value' => '{inputs.' . $field['name'] . '}',
                                'option' => $field['label'],
                                'group' => '{inputs.' . $field['name'] . '}',
                            ),
                        )
                );
                break;
            case 'input_checkbox':
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
                                'value' => isset($field['settings']['label']) ? $field['settings']['label'] : '',
                            ),
                        )
                );
                foreach ($field['settings']['advanced_options'] as $opt_key => $option) {
                    $elements[] = $this->auto_field(
                            $field,
                            array(
                                'type' => 'e2pdf-checkbox',
                                'properties' => array(
                                    'top' => '5',
                                    'width' => 'auto',
                                    'height' => 'auto',
                                    'value' => '{inputs.' . $field['attributes']['name'] . '.label}',
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
                break;
            case 'gdpr_agreement':
            case 'terms_and_condition':
                $label = isset($field['settings']['label']) && $field['settings']['label'] ? true : false;
                $elements[] = $this->auto_field(
                        $field,
                        array(
                            'type' => 'e2pdf-html',
                            'block' => true,
                            'float' => true,
                            'hidden' => $label ? false : true,
                            'properties' => array(
                                'top' => '20',
                                'left' => '20',
                                'right' => '20',
                                'width' => $width . '%',
                                'height' => $label ? 'auto' : '-5',
                                'value' => $label ? $field['settings']['label'] : '',
                            ),
                        )
                );
                $elements[] = $this->auto_field(
                        $field,
                        array(
                            'type' => 'e2pdf-checkbox',
                            'properties' => array(
                                'top' => '5',
                                'width' => 'auto',
                                'height' => 'auto',
                                'value' => '{inputs.' . $field['attributes']['name'] . '}',
                                'option' => 'Accepted',
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
                                'value' => $field['settings']['tnc_html'],
                            ),
                        )
                );
                break;
            case 'address':
                foreach ($field['fields'] as $sub_field) {
                    if (isset($sub_field['settings']['visible']) && $sub_field['settings']['visible']) {
                        $elements[] = $this->auto_field(
                                $sub_field,
                                array(
                                    'type' => 'e2pdf-html',
                                    'block' => true,
                                    'float' => true,
                                    'properties' => array(
                                        'top' => '20',
                                        'left' => '20',
                                        'right' => '20',
                                        'width' => $width / 2 . '%',
                                        'height' => 'auto',
                                        'value' => $sub_field['settings']['label'],
                                    ),
                                )
                        );

                        if ($sub_field['element'] == 'select_country') {
                            $options_tmp = array();
                            if (function_exists('getFluentFormCountryList')) {
                                foreach (getFluentFormCountryList() as $opt_key => $option) {
                                    $options_tmp[] = $option;
                                }
                            }
                            $elements[] = $this->auto_field(
                                    $field,
                                    array(
                                        'type' => 'e2pdf-select',
                                        'properties' => array(
                                            'top' => '5',
                                            'width' => '100%',
                                            'height' => 'auto',
                                            'options' => implode("\n", $options_tmp),
                                            'value' => '{inputs.' . $field['attributes']['name'] . '.' . $sub_field['attributes']['name'] . '.label}',
                                        ),
                                    )
                            );
                        } else {
                            $elements[] = $this->auto_field(
                                    $sub_field,
                                    array(
                                        'type' => 'e2pdf-input',
                                        'properties' => array(
                                            'top' => '5',
                                            'width' => '100%',
                                            'height' => 'auto',
                                            'value' => '{inputs.' . $field['attributes']['name'] . '.' . $sub_field['attributes']['name'] . '}',
                                        ),
                                    )
                            );
                        }
                    }
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
                                'value' => isset($field['settings']['label']) ? $field['settings']['label'] : '',
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
                                'value' => '{inputs.' . $field['attributes']['name'] . '}',
                            ),
                        )
                );
                break;
            case 'chained_select':
                foreach ($field['settings']['data_source']['headers'] as $sub_field_key => $sub_field_value) {
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
                                    'width' => $width / count($field['settings']['data_source']['headers']) . '%',
                                    'height' => 'auto',
                                    'value' => $sub_field_value,
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
                                    'value' => '{inputs.' . $field['attributes']['name'] . '.' . $sub_field_value . '}',
                                ),
                            )
                    );
                }
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
        if ($this->get('cached_form') && $this->get('cached_entry') && $this->get('cached_form')->id == $this->get('cached_entry')->form_id) {
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
            echo do_shortcode('[fluentform id="' . $this->get('item') . '"]');
            $source = ob_get_clean();
            if ($source) {
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
            }
            if (ob_get_length() > 0) {
                while (@ob_end_clean()); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
            }
            if (!$source) {
                return '<div class="e2pdf-vm-error">' . __("The form source is empty or doesn't exist", 'e2pdf') . '</div>';
            } elseif (!$html) {
                return '<div class="e2pdf-vm-error">' . __('The form could not be parsed due the incorrect HTML', 'e2pdf') . '</div>';
            } else {
                $xml = new Helper_E2pdf_Xml();
                $xml->set('dom', $dom);
                $xpath = new DomXPath($dom);
                $elements = $xpath->query("//*[contains(@class, 'ff_tc_checkbox')]");
                foreach ($elements as $element) {
                    $sub_elements = $xpath->query('.//input', $element);
                    foreach ($sub_elements as $sub_element) {
                        $xml->set_node_value($sub_element, 'class', 'ff_gdpr_field');
                    }
                }
                $elements = $xpath->query("//*[contains(@class, 'ff-checkable-grids') or contains(@class, 'ff-el-ratings')]");
                foreach ($elements as $element) {
                    $sub_elements = $xpath->query('.//input', $element);
                    foreach ($sub_elements as $sub_element) {
                        $xml->set_node_value($sub_element, 'class', 'ff_grid_field');
                    }
                }
                $elements = $xpath->query("//*[contains(@data-type, 'repeater_item')]");
                foreach ($elements as $element) {
                    preg_match('/(.*?)_(\d+)_(\d+)/', $xml->get_node_value($element, 'data-name'), $matches);
                    if (isset($matches['2'])) {
                        $xml->set_node_value($element, 'name', str_replace('[]', '[' . $matches['2'] . ']', $xml->get_node_value($element, 'name')));
                    }
                    if ($element->tagName == 'select') {
                        $options = $xpath->query('.//option', $element);
                        if ($options) {
                            foreach ($options as $option) {
                                $xml->set_node_value($option, 'class', 'el-repeater-item');
                            }
                        }
                    }
                }
                $elements = $xpath->query("//*[contains(@class, 'fluentform-signature-pad-wrapper')]/parent::*");
                foreach ($elements as $element) {
                    $sub_elements = $xpath->query('.//input', $element);
                    foreach ($sub_elements as $sub_element) {
                        $xml->set_node_value($sub_element, 'style', 'width: 200px; height: 100px; text-align: center;');
                    }
                }
                $elements = $xpath->query('//select');
                foreach ($elements as $element) {
                    $sub_elements = $xpath->query(".//option[not(contains(@class, 'el-repeater-item'))]", $element);
                    if ($sub_elements) {
                        foreach ($sub_elements as $sub_element) {
                            $xml->set_node_value($sub_element, 'value', $sub_element->nodeValue);
                        }
                    }
                    if ($xml->get_node_value($element, 'multiple')) {
                        $xml->set_node_value($element, 'name', str_replace('[]', '', $xml->get_node_value($element, 'name')));
                    }
                    if ($xml->get_node_value($element, 'class') && false !== strpos($xml->get_node_value($element, 'class'), 'el-chained-select')) {
                        $xml->set_node_value($element, 'name', '{inputs.' . str_replace(array('[', ']'), array('.', ''), $xml->get_node_value($element, 'name')) . '}');
                    } elseif ($xml->get_node_value($element, 'data-name') && false !== strpos($xml->get_node_value($element, 'data-name'), 'cpt_selection')) {
                        $xml->set_node_value($element, 'name', '{inputs.' . str_replace(array('[', ']'), array('.', ''), $xml->get_node_value($element, 'name')) . '}');
                    } elseif ($xml->get_node_value($element, 'data-type') && false !== strpos($xml->get_node_value($element, 'data-type'), 'repeater_item')) {
                        $xml->set_node_value($element, 'name', '{inputs.' . str_replace(array('[', ']'), array('.', ''), $xml->get_node_value($element, 'name')) . '}');
                    } else {
                        $xml->set_node_value($element, 'name', '{inputs.' . str_replace(array('[', ']'), array('.', ''), $xml->get_node_value($element, 'name')) . '.label}');
                    }
                }
                $elements = $xpath->query('//input|//textarea');
                foreach ($elements as $element) {
                    if ($xml->get_node_value($element, 'type') == 'checkbox' || $xml->get_node_value($element, 'type') == 'radio') {
                        if ($xml->get_node_value($element, 'type') == 'checkbox') {
                            $xml->set_node_value($element, 'name', str_replace('[]', '', $xml->get_node_value($element, 'name')));
                        }
                        if ($xml->get_node_value($element, 'class') && false !== strpos($xml->get_node_value($element, 'class'), 'ff_gdpr_field')) {
                            $xml->set_node_value($element, 'value', 'Accepted');
                            $xml->set_node_value($element, 'name', '{inputs.' . str_replace(array('[', ']'), array('.', ''), $xml->get_node_value($element, 'name')) . '}');
                        } elseif ($xml->get_node_value($element, 'class') && false !== strpos($xml->get_node_value($element, 'class'), 'ff_grid_field')) {
                            $xml->set_node_value($element, 'name', '{inputs.' . str_replace(array('[', ']'), array('.', ''), $xml->get_node_value($element, 'name')) . '}');
                        } else {
                            $xml->set_node_value($element, 'value', $xml->get_node_value($element, 'aria-label'));
                            $xml->set_node_value($element, 'name', '{inputs.' . str_replace(array('[', ']'), array('.', ''), $xml->get_node_value($element, 'name')) . '.label}');
                        }
                    } else {
                        $xml->set_node_value($element, 'name', '{inputs.' . str_replace(array('[', ']'), array('.', ''), $xml->get_node_value($element, 'name')) . '}');
                    }
                }
                $submit_buttons = $xpath->query("//input[@type='submit']|//button[@type='submit']");
                foreach ($submit_buttons as $element) {
                    $element->parentNode->removeChild($element);
                }
                $remove_by_class = array(
                    'fluentform-signature-pad-wrapper',
                    'step-nav',
                );
                foreach ($remove_by_class as $key => $class) {
                    $elements = $xpath->query("//*[contains(@class, '{$class}')]");
                    foreach ($elements as $element) {
                        $element->parentNode->removeChild($element);
                    }
                }
                $remove_classes = array(
                    'fluentform-step',
                    'has-conditions',
                );
                foreach ($remove_classes as $key => $class) {
                    $elements = $xpath->query("//*[contains(@class, '{$class}')]");
                    foreach ($elements as $element) {
                        $xml->set_node_value($element, 'class', str_replace($class, '', $xml->get_node_value($element, 'class')));
                    }
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
        if (function_exists('fluentFormMix')) {
            $styles[] = fluentFormMix('css/fluent-forms-public.css');
        }
        $styles[] = plugins_url('css/extension/fluent.css?v=' . time(), $this->helper->get('plugin_file_path'));
        return $styles;
    }
}
