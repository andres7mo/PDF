<?php

/**
 * E2Pdf Elementor Forms Extension
 * @copyright  Copyright 2017 https://e2pdf.com
 * @license    GPLv3
 * @version    1
 * @link       https://e2pdf.com
 * @since      1.21.07
 */
if (!defined('ABSPATH')) {
    die('Access denied.');
}

class Extension_E2pdf_Elementor extends Model_E2pdf_Model {

    private $options;
    private $info = array(
        'key' => 'elementor',
        'title' => 'Elementor Forms',
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
        if (defined('E2PDF_ELEMENTOR_EXTENSION') || $this->helper->load('extension')->is_plugin_active('elementor-pro/elementor-pro.php') || $this->helper->load('extension')->is_plugin_active('pro-elements/pro-elements.php')) {
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
                    $this->set('cached_form', $this->get_form($this->get('item')));
                }
                break;
            case 'dataset':
                $this->set('cached_entry', false);
                $this->set('cached_meta', array());
                if ($this->get('cached_form') && $this->get('dataset') && class_exists('ElementorPro\Modules\Forms\Submissions\Database\Query') && class_exists('ElementorPro\Modules\Forms\Classes\Form_Record')) {
                    $this->set('cached_meta', ElementorPro\Modules\Forms\Submissions\Database\Query::get_instance()->get_submission($this->get('dataset')));
                    $post_data = array();
                    if (!empty($this->get('cached_meta')['data']['values'])) {
                        foreach ($this->get('cached_meta')['data']['values'] as $data) {
                            if (isset($data['key'])) {
                                $post_data[$data['key']] = isset($data['value']) ? $data['value'] : '';
                            }
                        }
                    }
                    $this->set('cached_entry', new ElementorPro\Modules\Forms\Classes\Form_Record($post_data, $this->get('cached_form')));
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
                case 'cached_meta':
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
     * Load actions for this extension
     */
    public function load_actions() {
        add_action('elementor_pro/forms/new_record', array($this, 'action_elementor_pro_forms_new_record'), 10, 2);
        add_action('elementor_pro/forms/actions/after_run', array($this, 'action_elementor_pro_forms_actions_after_run'));
        add_action('elementor_pro/forms/mail_sent', array($this, 'action_elementor_pro_forms_mail_sent'), 10, 2);
    }

    public function load_filters() {
        add_filter('elementor_pro/forms/wp_mail_message', array($this, 'filter_elementor_pro_forms_wp_mail_message'));
        add_filter('elementor_pro/forms/record/actions_before', array($this, 'filter_elementor_pro_forms_record_actions_before'));
        add_filter('wpnotif_filter_elementor_message', array($this, 'filter_wpnotif_filter_elementor_message'), 10, 2);
    }

    /**
     * Get items to work with
     * @return array() - List of available items
     */
    public function items() {
        $listed = array();
        $content = array();
        $postforms = $this->get_post_forms();
        foreach ($postforms as $postform) {
            $listed[] = $postform;
        }
        $snapshots = ElementorPro\Modules\Forms\Submissions\Database\Repositories\Form_Snapshot_Repository::instance()->all();
        foreach ($snapshots as $snapshot) {
            if (!in_array($snapshot->get_key(), $listed, true)) {
                $listed[] = $snapshot->get_key();
            }
        }
        foreach ($listed as $form) {
            $content[] = $this->item($form);
        }
        return $content;
    }

    public function get_post_forms() {
        global $wpdb;

        $content = array();
        $result = $wpdb->get_results(
                $wpdb->prepare(
                        'SELECT pm.post_id, pm.meta_value FROM `' . $wpdb->postmeta . '` pm INNER JOIN `' . $wpdb->posts . '` pp ON pp.ID = pm.post_id WHERE pm.meta_key = %s AND pp.post_type NOT IN ("revision")',
                        '_elementor_data'
                )
        );
        foreach ($result as $post) {
            if (is_string($post->meta_value) && !empty($post->meta_value) && false !== strpos($post->meta_value, 'form_fields')) {
                $meta = json_decode($post->meta_value, true);
                if (empty($meta)) {
                    $meta = [];
                }
                $forms = $this->parse_forms_meta($meta);
                foreach ($forms as $key => $form) {
                    $content[] = $post->post_id . '_' . $form;
                }
            }
        }

        return $content;
    }

    public function parse_forms_meta($elements) {
        $forms = array();
        foreach ($elements as $element) {
            if (isset($element['settings']['form_fields'])) {
                $forms[] = $element['id'];
            }
            if (!empty($element['elements'])) {
                $sub_forms = $this->parse_forms_meta($element['elements']);
                if (!empty($sub_forms)) {
                    $forms = array_merge($forms, $sub_forms);
                }
            }
        }
        return $forms;
    }

    public function get_form($item_id = false) {
        if ($item_id) {
            $form = false;
            list($post_id, $form_id) = explode('_', $item_id);
            $elementor = ElementorPro\Plugin::elementor();
            $document = $elementor->documents->get($post_id);
            if ($document) {
                $form = ElementorPro\Modules\Forms\Module::find_element_recursive($document->get_elements_data(), $form_id);
                if ($form && is_array($form)) {
                    $form['post_id'] = $post_id;
                } else {
                    $snapshot = ElementorPro\Modules\Forms\Submissions\Database\Repositories\Form_Snapshot_Repository::instance()->find($post_id, $form_id);
                    if ($snapshot) {
                        $fields = array();
                        foreach ($snapshot->fields as $field) {
                            $fields[] = array(
                                'custom_id' => $field['id'],
                                'field_type' => $field['type'],
                                'required' => !empty($field['required']),
                                'field_label' => $field['label'],
                                'field_options' => '',
                                'placeholder' => '',
                                '_id' => '',
                            );
                        }
                        $form = array(
                            'id' => $snapshot->id,
                            'elType' => 'widget',
                            'post_id' => $snapshot->post_id,
                            'widgetType' => 'form',
                            'settings' => array(
                                'form_name' => $snapshot->name,
                                'form_fields' => $fields,
                                'form_metadata' => array(),
                            ),
                        );
                    }
                }
            }
            if ($form) {
                foreach ($form['settings']['form_fields'] as $key => $form_field) {
                    if (!isset($form_field['field_type'])) {
                        $form['settings']['form_fields'][$key]['field_type'] = 'text';
                    }
                    if (!isset($form_field['field_label'])) {
                        $form['settings']['form_fields'][$key]['field_label'] = '';
                    }
                }
                if (!isset($form['settings']['form_metadata'])) {
                    $form['settings']['form_metadata'] = array();
                }
            }
            return $form;
        } else {
            return false;
        }
    }

    /**
     * Get item
     * @param int $item_id - Item ID
     * @return object - Item
     */
    public function item($item_id = false) {
        if (!$item_id && $this->get('item')) {
            $item_id = $this->get('item');
        }
        $form = $this->get_form($item_id);
        $item = new stdClass();
        if ($form) {
            $item->id = $item_id;
            $item->url = $this->helper->get_url(
                    array(
                        'post' => $form['post_id'],
                        'action' => 'elementor',
                    )
            );
            $post_name = '';
            if (!empty($form['post_id'])) {
                $post_name .= ' | ' . get_the_title($form['post_id']);
            }

            $item->name = $form['settings']['form_name'] . ' (' . $form['id'] . ')' . $post_name;
        } else {
            $item->id = '';
            $item->url = 'javascript:void(0);';
            $item->name = '';
        }
        return $item;
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
            $entries = ElementorPro\Modules\Forms\Submissions\Database\Query::get_instance()->get_submissions(
                    array(
                        'page' => 1,
                        'per_page' => 999999,
                        'filters' => array(
                            'form' => array(
                                'value' => $item_id,
                            ),
                        ),
                        'order' => [
                            'order' => 'desc',
                            'by' => 'id',
                        ],
                    )
            );
            if (!empty($entries['data'])) {
                $this->set('item', $item_id);
                foreach ($entries['data'] as $key => $entry) {
                    $this->set('dataset', $entry['id']);
                    $entry_title = $this->render($name);
                    if (!$entry_title) {
                        $entry_title = $entry['id'];
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
        $actions = new stdClass();
        $actions->view = $this->helper->get_url(array('page' => 'e-form-submissions')) . '#/' . $dataset_id;
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
            $value = $this->replace_meta_shortcodes($value, $this->get('cached_meta'));

            /**
             * Compatibility fix for Signature field for Elementor Forms
             * https://wordpress.org/plugins/signature-field-for-elementor-forms/
             */
            if (class_exists('Superaddons_Elementor_Signature_Field')) {
                $signatures = $this->get('cached_entry')->get_field(
                        array(
                            'type' => 'signature',
                        )
                );
                foreach ($signatures as $signature) {
                    $this->get('cached_entry')->update_field($signature['id'], 'value', $signature['raw_value']);
                }
            }

            /**
             * Compatibility fix Repeater Fields for Elementor Forms
             * https://wordpress.org/plugins/repeater-for-elementor/
             */
            if (class_exists('Rednumber_Repeater_Custom_Validation')) {
                $repeaters = $this->get('cached_entry')->get_field(
                        array(
                            'type' => 'repeater',
                        )
                );
                foreach ($repeaters as $repeater) {
                    $this->get('cached_entry')->update_field($repeater['id'], 'value', $this->repeater_field_value($repeater['raw_value']));
                }
            }

            $value = $this->get('cached_entry')->replace_setting_shortcodes($value);
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
     * Verify if item and dataset exists
     * @return bool - item and dataset exists
     */
    public function verify() {
        if (class_exists('ElementorPro\Modules\Forms\Submissions\Database\Query') && $this->get('cached_form') && $this->get('cached_meta')) {
            $post_id = isset($this->get('cached_meta')['data']['post']['id']) ? $this->get('cached_meta')['data']['post']['id'] : false;
            $form_id = isset($this->get('cached_meta')['data']['form']['element_id']) ? $this->get('cached_meta')['data']['form']['element_id'] : false;
            if ($post_id && $form_id && $this->get('item') == $post_id . '_' . $form_id) {
                return true;
            }
        }
        return false;
    }

    /**
     * Init Visual Mapper data
     * @return bool|string - HTML data source for Visual Mapper
     */
    public function visual_mapper() {

        $item = $this->get('item');
        $html = '';
        $source = '';

        if ($item) {
            ob_start();
            $form = $this->get_form($item);
            if ($form) {
                $elementor = ElementorPro\Plugin::elementor();
                $document = $elementor->documents->get($form['post_id']);
                $elementor->documents->switch_to_document($document);
                $widget = $elementor->elements_manager->create_element_instance($form);
                $widget->print_element();
                $source = ob_get_contents();
            }
            if (ob_get_length() > 0) {
                while (@ob_end_clean()); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
            }
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
            if (!$source) {
                return '<div class="e2pdf-vm-error">' . __("The form source is empty or doesn't exist", 'e2pdf') . '</div>';
            } elseif (!$html) {
                return '<div class="e2pdf-vm-error">' . __('The form could not be parsed due the incorrect HTML', 'e2pdf') . '</div>';
            } else {
                $xml = $this->helper->load('xml');
                $xml->set('dom', $dom);
                $xpath = new DomXPath($dom);
                $remove_by_class = array(
                    'dce-conditions-js-error-notice',
                    'repeater-field-button-add',
                    'elementor-field-type-submit',
                    'select-caret-down-wrapper'
                );
                foreach ($remove_by_class as $key => $class) {
                    $elements = $xpath->query("//*[contains(@class, '{$class}')]");
                    foreach ($elements as $element) {
                        $element->parentNode->removeChild($element);
                    }
                }

                $elements = $xpath->query('//input|//textarea|//select');
                foreach ($elements as $element) {
                    if ($xml->get_node_value($element, 'type') == 'checkbox' || $xml->get_node_value($element, 'type') == 'file') {
                        $xml->set_node_value($element, 'name', str_replace('[]', '', $xml->get_node_value($element, 'name')));
                    }
                    $xml->set_node_value($element, 'name', str_replace(array('form_fields[', ']'), array('[field id="', '"]'), $xml->get_node_value($element, 'name')));
                }

                $elements = $xpath->query("//input[@type='submit']");
                foreach ($elements as $element) {
                    $element->parentNode->removeChild($element);
                }

                $elements = $xpath->query("//input[@type='dce_form_signature']");
                foreach ($elements as $element) {
                    if ($xml->get_node_value($element, 'type') == 'checkbox' || $xml->get_node_value($element, 'type') == 'file') {
                        $xml->set_node_value($element, 'name', str_replace('[]', '', $xml->get_node_value($element, 'name')));
                    }
                    $xml->set_node_value($element, 'type', 'text');
                }

                $elements = $xpath->query("//*[contains(@class, 'elementor-field-repeater-data')]");
                foreach ($elements as $element) {
                    $xml->set_node_value($element, 'value', $xml->get_node_value($element, 'name'));
                    $xml->set_node_value($element, 'type', 'text');
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
     * Auto Generate of Template for this extension
     * @return array - List of elements
     */
    public function auto() {

        $response = array();
        $elements = array();

        $form = $this->get_form($this->get('item'));

        foreach ($form['settings']['form_fields'] as $key => $field) {
            $id = isset($field['custom_id']) ? $field['custom_id'] : $field['_id'];
            $field_type = isset($field['field_type']) ? $field['field_type'] : 'text';
            $label = isset($field['field_label']) ? $field['field_label'] : '';
            switch ($field_type) {
                case 'text':
                case 'email':
                case 'url':
                case 'tel':
                case 'number':
                case 'date':
                case 'time':
                case 'password':
                    $elements[] = $this->auto_field(
                            $field,
                            array(
                                'type' => 'e2pdf-html',
                                'block' => true,
                                'properties' => array(
                                    'top' => '20',
                                    'left' => '20',
                                    'right' => '20',
                                    'width' => '100%',
                                    'height' => 'auto',
                                    'value' => $label,
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
                                    'pass' => $field_type === 'password' ? '1' : '0',
                                    'value' => '[field id="' . $id . '"]',
                                ),
                            )
                    );
                    break;
                case 'upload':
                case 'textarea':
                    $elements[] = $this->auto_field(
                            $field,
                            array(
                                'type' => 'e2pdf-html',
                                'block' => true,
                                'properties' => array(
                                    'top' => '20',
                                    'left' => '20',
                                    'right' => '20',
                                    'width' => '100%',
                                    'height' => 'auto',
                                    'value' => $label,
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
                                    'height' => '150',
                                    'text_auto_font_size' => '1',
                                    'value' => '[field id="' . $id . '"]',
                                ),
                            )
                    );
                    break;
                case 'select':
                    $elements[] = $this->auto_field(
                            $field,
                            array(
                                'type' => 'e2pdf-html',
                                'block' => true,
                                'properties' => array(
                                    'top' => '20',
                                    'left' => '20',
                                    'right' => '20',
                                    'width' => '100%',
                                    'height' => 'auto',
                                    'value' => $label,
                                ),
                            )
                    );
                    $elements[] = $this->auto_field(
                            $field,
                            array(
                                'type' => 'e2pdf-select',
                                'properties' => array(
                                    'top' => '5',
                                    'width' => '100%',
                                    'height' => 'auto',
                                    'multiline' => isset($field['is_multiple']) && $field['is_multiple'] ? '1' : '0',
                                    'options' => isset($field['field_options']) ? $field['field_options'] : '',
                                    'value' => '[field id="' . $id . '"]',
                                    'height' => 'auto',
                                ),
                            )
                    );
                    break;
                case 'checkbox':
                    $elements[] = $this->auto_field(
                            $field,
                            array(
                                'type' => 'e2pdf-html',
                                'block' => true,
                                'properties' => array(
                                    'top' => '20',
                                    'left' => '20',
                                    'right' => '20',
                                    'width' => '100%',
                                    'height' => 'auto',
                                    'value' => $label,
                                ),
                            )
                    );

                    if (isset($field['field_options'])) {
                        foreach (preg_split('/(\r\n|\n|\r)/', $field['field_options']) as $checkbox) {
                            $elements[] = $this->auto_field(
                                    $field,
                                    array(
                                        'type' => 'e2pdf-checkbox',
                                        'properties' => array(
                                            'top' => '5',
                                            'width' => 'auto',
                                            'height' => 'auto',
                                            'value' => '[field id="' . $id . '"]',
                                            'option' => $checkbox,
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
                                            'value' => $checkbox,
                                        ),
                                    )
                            );
                        }
                    }
                    break;
                case 'radio':
                    $elements[] = $this->auto_field(
                            $field,
                            array(
                                'type' => 'e2pdf-html',
                                'block' => true,
                                'properties' => array(
                                    'top' => '20',
                                    'left' => '20',
                                    'right' => '20',
                                    'width' => '100%',
                                    'height' => 'auto',
                                    'value' => $label,
                                ),
                            )
                    );
                    if (isset($field['field_options'])) {
                        foreach (preg_split('/(\r\n|\n|\r)/', $field['field_options']) as $radio) {
                            $elements[] = $this->auto_field(
                                    $field,
                                    array(
                                        'type' => 'e2pdf-radio',
                                        'properties' => array(
                                            'top' => '5',
                                            'width' => 'auto',
                                            'height' => 'auto',
                                            'value' => '[field id="' . $id . '"]',
                                            'option' => $radio,
                                            'group' => '[field id="' . $id . '"]',
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
                                            'value' => $radio,
                                        ),
                                    )
                            );
                        }
                    }
                    break;
                case 'acceptance':
                    $elements[] = $this->auto_field(
                            $field,
                            array(
                                'type' => 'e2pdf-html',
                                'block' => true,
                                'properties' => array(
                                    'top' => '20',
                                    'left' => '20',
                                    'right' => '20',
                                    'width' => '100%',
                                    'height' => 'auto',
                                    'value' => isset($field['acceptance_text']) ? $field['acceptance_text'] : '',
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
                                    'value' => '[field id="' . $id . '"]',
                                    'option' => 'on',
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
                                    'value' => $label,
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
                                'properties' => array(
                                    'top' => '20',
                                    'left' => '20',
                                    'right' => '20',
                                    'width' => '100%',
                                    'height' => 'auto',
                                    'value' => isset($field['field_html']) ? $field['field_html'] : '',
                                ),
                            )
                    );
                    break;
                case 'signature':
                case 'dce_form_signature':
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
                                    'value' => '[field id="' . $id . '"]',
                                ),
                            )
                    );
                    break;
                case 'repeater':
                    if (class_exists('Rednumber_Repeater_Custom_Validation')) {
                        $elements[] = $this->auto_field(
                                $field,
                                array(
                                    'type' => 'e2pdf-html',
                                    'block' => true,
                                    'properties' => array(
                                        'top' => '20',
                                        'left' => '20',
                                        'right' => '20',
                                        'width' => '100%',
                                        'height' => 'auto',
                                        'value' => isset($field['repeater_title']) ? $field['repeater_title'] : '',
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
                                        'height' => '150',
                                        'text_auto_font_size' => '1',
                                        'value' => '[field id="' . $id . '"]',
                                    ),
                                )
                        );
                    }
                    break;
                default:
                    break;
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
     * Element generation for Auto PDF
     * @param array $field - Field options
     * @param array $element - Element options
     * @return array - Element with modified options
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
        if (isset($element['block']) && $element['block'] && isset($field['width']) && $field['width'] !== '100') {
            $element['float'] = true;
            $element['properties']['width'] = $field['width'] . '%';
        }

        return $element;
    }

    /**
     * Get styles for generating Map Field function
     * @return array - List of css files to load
     */
    public function styles($item_id = false) {
        $styles = array();
        if (defined('ELEMENTOR_ASSETS_URL')) {
            $styles[] = ELEMENTOR_ASSETS_URL . 'css/frontend.css';
        }
        if (defined('ELEMENTOR_PRO_URL')) {
            $styles[] = ELEMENTOR_PRO_URL . 'assets/css/frontend.css';
        }
        $styles[] = $this->helper->get_wp_upload_dir('baseurl') . '/elementor/css/global.css';
        $styles[] = plugins_url('css/extension/elementor.css?v=' . time(), $this->helper->get('plugin_file_path'));
        return $styles;
    }

    public function replace_meta_shortcodes($value, $submission) {
        $meta_keys = array(
            'id',
            'referer',
            'referer_title',
            'user_id',
            'user_agent',
            'user_ip',
            'user_name',
            'created_at_gmt',
            'updated_at_gmt',
            'created_at',
            'updated_at',
            'status',
        );
        $meta = array();
        foreach ($meta_keys as $meta_key) {
            if (isset($submission['data'][$meta_key])) {
                $meta['[' . $meta_key . ']'] = $submission['data'][$meta_key];
            } else {
                $meta['[' . $meta_key . ']'] = '';
            }
        }
        return str_replace(array_keys($meta), $meta, $value);
    }

    public function action_elementor_pro_forms_actions_after_run($action) {
        if ($action->get_name() == 'save-to-database' && class_exists('ReflectionProperty')) {
            $reflection = new ReflectionProperty(get_class($action), 'submission_id');
            $reflection->setAccessible(true);
            $reflection->getValue($action);
            $this->set('dataset', $reflection->getValue($action));
        }
    }

    public function action_elementor_pro_forms_new_record($record, $handler) {
        if ($handler->is_success && class_exists('ReflectionProperty')) {
            $form = $handler->get_current_form();
            $message = $this->filter_success_message($form['settings']['success_message']);
            if ($form['settings']['success_message'] != $message) {
                $form['settings']['success_message'] = $message;
                $reflection = new ReflectionProperty(get_class($handler), 'current_form');
                $reflection->setAccessible(true);
                $reflection->setValue($handler, $form);
            }
        }
    }

    public function action_elementor_pro_forms_mail_sent($settings, $record) {
        remove_filter('wp_mail', array($this, 'filter_wp_mail'), 30);
        $files = $this->helper->get('elementor_attachments');
        if (is_array($files) && !empty($files)) {
            foreach ($files as $key => $file) {
                $this->helper->delete_dir(dirname($file) . '/');
            }
            $this->helper->deset('elementor_attachments');
        }
    }

    public function filter_elementor_pro_forms_record_actions_before($record) {
        $form_settings = $record->get('form_settings');
        if (!empty($form_settings['email_content'])) {
            $form_settings['email_content'] = preg_replace('/(\[)((e2pdf-download|e2pdf-save|e2pdf-attachment|e2pdf-adobesign|e2pdf-zapier)[^\]]*?)(\])/', '{{$2}}', $form_settings['email_content']);
        }
        if (!empty($form_settings['email_content_2'])) {
            $form_settings['email_content_2'] = preg_replace('/(\[)((e2pdf-download|e2pdf-save|e2pdf-attachment|e2pdf-adobesign|e2pdf-zapier)[^\]]*?)(\])/', '{{$2}}', $form_settings['email_content_2']);
        }
        if (!empty($form_settings['dce_form_email_repeater'])) {
            foreach ($form_settings['dce_form_email_repeater'] as $key => $email_content) {
                if (!empty($email_content['dce_form_email_content'])) {
                    $form_settings['dce_form_email_repeater'][$key]['dce_form_email_content'] = preg_replace('/(\[)((e2pdf-download|e2pdf-save|e2pdf-attachment|e2pdf-adobesign|e2pdf-zapier)[^\]]*?)(\])/', '{{$2}}', $email_content['dce_form_email_content']);
                }
            }
        }
        $record->set('form_settings', $form_settings);
        return $record;
    }

    public function filter_wpnotif_filter_elementor_message($message, $settings) {
        $message = $this->filter_success_message($message);
        return $message;
    }

    public function filter_elementor_pro_forms_wp_mail_message($message) {
        $message = preg_replace('/(\{\{)((e2pdf-download|e2pdf-save|e2pdf-attachment|e2pdf-adobesign|e2pdf-zapier)[^\}]*?)(\}\})/', '[$2]', $message);
        if (false !== strpos($message, '[')) {
            $shortcode_tags = array(
                'e2pdf-download',
                'e2pdf-save',
                'e2pdf-adobesign',
                'e2pdf-zapier',
                'e2pdf-attachment',
            );
            preg_match_all('@\[([^<>&/\[\]\x00-\x20=]++)@', $message, $matches);
            $tagnames = array_intersect($shortcode_tags, $matches[1]);
            if (!empty($tagnames)) {
                preg_match_all('/' . $this->helper->load('shortcode')->get_shortcode_regex($tagnames) . '/', $message, $shortcodes);
                foreach ($shortcodes[0] as $key => $shortcode_value) {
                    $shortcode = $this->helper->load('shortcode')->get_shortcode($shortcodes, $key);
                    $atts = shortcode_parse_atts($shortcode[3]);
                    if ($this->helper->load('shortcode')->is_attachment($shortcode, $atts)) {
                        if (!isset($atts['dataset']) && isset($atts['id'])) {
                            $template = new Model_E2pdf_Template();
                            $template->load($atts['id']);
                            if ($template->get('extension') === 'elementor') {
                                $atts['dataset'] = $this->get('dataset');
                                $shortcode[3] .= ' dataset="' . $this->get('dataset') . '"';
                            }
                        }
                        if (!isset($atts['apply'])) {
                            $shortcode[3] .= ' apply="true"';
                        }
                        $file = do_shortcode_tag($shortcode);
                        if ($file) {
                            $tmp = false;
                            if (substr($file, 0, 4) === 'tmp:') {
                                $file = substr($file, 4);
                                $tmp = true;
                            }
                            if ($shortcode[2] === 'e2pdf-save' || isset($atts['pdf'])) {
                                if ($tmp) {
                                    $this->helper->add('elementor_attachments', $file);
                                }
                            } else {
                                $this->helper->add('elementor_attachments', $file);
                            }
                        }
                        $message = str_replace($shortcode_value, '', $message);
                    } else {
                        if (!isset($atts['dataset']) && isset($atts['id'])) {
                            $template = new Model_E2pdf_Template();
                            $template->load($atts['id']);

                            if ($template->get('extension') === 'elementor') {
                                $atts['dataset'] = $this->get('dataset');
                                $shortcode[3] .= ' dataset="' . $this->get('dataset') . '"';
                            }
                        }
                        if (!isset($atts['apply'])) {
                            $shortcode[3] .= ' apply="true"';
                        }
                        $message = str_replace($shortcode_value, do_shortcode_tag($shortcode), $message);
                    }
                }
                add_filter('wp_mail', array($this, 'filter_wp_mail'), 30);
            }
        }
        return $message;
    }

    public function filter_success_message($message) {
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

                            if ($template->get('extension') === 'elementor') {
                                $atts['dataset'] = $this->get('dataset');
                                $shortcode[3] .= ' dataset="' . $this->get('dataset') . '"';
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

    public function filter_wp_mail($args = array()) {
        $files = $this->helper->get('elementor_attachments');
        if (is_array($files) && !empty($files)) {
            foreach ($files as $file) {
                $args['attachments'][] = $file;
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

    function repeater_field_value($value) {
        $dom = new DOMDocument();
        if (function_exists('mb_convert_encoding')) {
            $dom->loadHTML(mb_convert_encoding($value, 'HTML-ENTITIES', 'UTF-8'));
        } else {
            $dom->loadHTML('<?xml encoding="UTF-8">' . $value);
        }
        $ol = $dom->getElementsByTagName('ol')->item(0);
        $items = [];
        if ($ol) {
            foreach ($ol->getElementsByTagName('li') as $li) {
                $nestedUl = $li->getElementsByTagName('ul')->item(0);
                if ($nestedUl) {
                    $data = [];
                    foreach ($nestedUl->getElementsByTagName('li') as $innerLi) {
                        $parts = explode(' : ', $innerLi->textContent, 2);
                        if (count($parts) === 2) {
                            $key = trim($parts[0]);
                            $value = trim($parts[1]);
                            $data[$key] = $value;
                        }
                    }
                    $items[] = $data;
                }
            }
        }
        return serialize($items);
    }
}
