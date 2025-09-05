<?php

/**
 * File: /extension/e2pdf-everest.php
 *
 * @package  E2Pdf
 * @license  GPLv3
 * @link     https://e2pdf.com
 */
if (!defined('ABSPATH')) {
    die('Access denied.');
}

class Extension_E2pdf_Everest extends Model_E2pdf_Model {

    private $options;
    private $info = array(
        'key' => 'everest',
        'title' => 'Everest Forms',
    );

    public function info($key = false) {
        if ($key && isset($this->info[$key])) {
            return $this->info[$key];
        } else {
            return array(
                $this->info['key'] => $this->info['title'],
            );
        }
    }

    public function active() {
        if (defined('E2PDF_EVEREST_EXTENSION') || $this->helper->load('extension')->is_plugin_active('everest-forms/everest-forms.php')) {
            return true;
        }
        return false;
    }

    public function set($key, $value) {
        if (!isset($this->options)) {
            $this->options = new stdClass();
        }
        $this->options->$key = $value;
        switch ($key) {
            case 'item':
                $this->set('cached_form', false);
                $this->set('cached_form_data', false);
                $form = get_post($this->get('item'));
                if (function_exists('evf_decode') && $form && !empty($form->post_content)) {
                    $this->set('cached_form', $form);
                    $this->set('cached_form_data', evf_decode($this->get('cached_form')->post_content));
                }
                break;
            case 'dataset':
                $this->set('cached_entry', false);
                $this->set('cached_fields', false);
                if (function_exists('evf_decode') && function_exists('evf_get_entry') && $this->get('cached_form') && $this->get('dataset')) {
                    $entry = evf_get_entry($this->get('dataset'), false);
                    if (!empty($entry->form_id) && $entry->form_id == $this->get('cached_form')->ID) {
                        $this->set('cached_entry', $entry);
                        $this->set('cached_fields', evf_decode($entry->fields));
                    }
                }
                break;
            default:
                break;
        }
        return true;
    }

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

    public function load_actions() {
        add_action('everest_forms_email_send_after', array($this, 'action_everest_forms_email_send_after'));
        add_action('everest_forms_entry_details_sidebar_details', array($this, 'hook_everest_entry_view'), 10);
    }

    public function load_filters() {
        add_filter('everest_forms_add_success', array($this, 'filter_everest_forms_add_success'));
        add_filter('everest_forms_email_attachments', array($this, 'filter_everest_forms_email_attachments'), 10, 2);
        add_filter('everest_forms_email_message', array($this, 'filter_everest_forms_email_message'), 10, 2);
        add_filter('everest_forms_entry_table_actions', array($this, 'hook_everest_row_actions'), 10, 2);
        add_filter('everest_forms_after_success_ajax_message', array($this, 'filter_everest_forms_after_success_ajax_message'), 99, 3);
    }

    public function items() {
        $items = array();
        $forms = get_posts(
                array(
                    'post_status' => 'publish',
                    'posts_per_page' => -1,
                    'post_type' => 'everest_form',
                )
        );
        if ($forms) {
            foreach ($forms as $key => $form) {
                $items[] = $this->item($form->ID);
            }
        }
        return $items;
    }

    public function item($item_id = false) {
        if (!$item_id && $this->get('item')) {
            $item_id = $this->get('item');
        }
        $item = new stdClass();
        $form = get_post($item_id);
        if ($form) {
            $item->id = (string) $item_id;
            $item->name = $form->post_title ? $form->post_title : $item_id;
            $item->url = $this->helper->get_url(
                    array(
                        'page' => 'evf-builder',
                        'tab' => 'fields',
                        'form_id' => $item_id,
                        'action' => 'edit',
                    ), 'admin.php?'
            );
        } else {
            $item->id = '';
            $item->name = '';
            $item->url = 'javascript:void(0);';
        }
        return $item;
    }

    public function datasets($item_id = false, $name = false) {
        $datasets = array();
        if (function_exists('evf_get_entries_ids')) {
            $entries = array_reverse(evf_get_entries_ids($item_id));
            $this->set('item', $item_id);
            foreach ($entries as $key => $entry) {
                $this->set('dataset', $entry);
                $entry_title = $this->render($name);
                if (!$entry_title) {
                    $entry_title = $entry;
                }
                $datasets[] = array(
                    'key' => $entry,
                    'value' => $entry_title,
                );
            }
        }
        return $datasets;
    }

    public function get_dataset_actions($dataset_id = false) {
        $dataset_id = (int) $dataset_id;
        if (!$dataset_id) {
            return false;
        }
        $item = evf_get_entry($dataset_id);
        $actions = new stdClass();
        $actions->view = $this->helper->get_url(
                array(
                    'page' => 'evf-entries',
                    'form_id' => !empty($item->form_id) ? $item->form_id : '0',
                    'view-entry' => $dataset_id,
                )
        );
        $actions->delete = false;
        return $actions;
    }

    public function render($value, $field = array(), $convert_shortcodes = true, $raw = false) {
        $value = $this->render_shortcodes($value, $field);
        if (!$raw) {
            $value = $this->strip_shortcodes($value);
            $value = $this->convert_shortcodes($value, $convert_shortcodes, isset($field['type']) && $field['type'] == 'e2pdf-html' ? true : false);
            $value = $this->helper->load('field')->render_checkbox($value, $this, $field);
        }
        return $value;
    }

    public function render_shortcodes($value, $field = array()) {
        $element_id = isset($field['element_id']) ? $field['element_id'] : false;
        if ($this->verify()) {
            if (false !== strpos($value, '[')) {
                $value = $this->helper->load('field')->pre_shortcodes($value, $this, $field);
                $value = $this->helper->load('field')->inner_shortcodes($value, $this, $field);
                $value = $this->helper->load('field')->wrapper_shortcodes($value, $this, $field);
            }
            $value = $this->helper->load('field')->do_shortcodes($value, $this, $field);

            add_filter('everest_forms_process_smart_tags', array($this, 'filter_everest_forms_process_smart_tags'), 0, 3);
            $value = apply_filters('everest_forms_process_smart_tags', $value, $this->get('cached_form_data'), $this->get('cached_fields'), $this->get('dataset'));
            remove_filter('everest_forms_process_smart_tags', array($this, 'filter_everest_forms_process_smart_tags'), 99);

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

    public function strip_shortcodes($value) {
        $value = preg_replace('~(?:\[/?)[^/\]]+/?\]~s', '', $value);
        $value = preg_replace('~a\:\d+\:{[^}]*}(*SKIP)(*FAIL)|{[^}]*}~', '', $value);
        return $value;
    }

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

    public function verify() {
        if ($this->get('cached_entry')) {
            return true;
        }
        return false;
    }

    // visual mapper
    public function visual_mapper() {
        $source = '';
        $html = '';
        if ($this->get('item')) {
            $source = do_shortcode('[everest_form id="' . $this->get('item') . '"]');
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
                $form = get_post($this->get('item'));
                $form_fields = array();
                if (function_exists('evf_decode') && $form && !empty($form->post_content)) {
                    $post_content = evf_decode($form->post_content);
                    if (!empty($post_content['form_fields'])) {
                        $form_fields = $post_content['form_fields'];
                    }
                }
                $xml = $this->helper->load('xml');
                $xml->set('dom', $dom);
                $xpath = new DomXPath($dom);

                $elements = $xpath->query("//*[contains(@class, 'dropzone-input')]");
                foreach ($elements as $element) {
                    $field_id = preg_replace('/everest_forms_(\d+)_([^\]\.*]+)/', '$2', $xml->get_node_value($element, 'name'));
                    if (isset($form_fields[$field_id])) {
                        $field_id = $this->get_field_id_for_smarttags($form_fields[$field_id]);
                        $xml->set_node_value($element, 'name', '{field_id="' . $field_id . '"}');
                        $parent = $element->parentNode;
                        if ($parent && strpos($parent->getAttribute('class'), 'evf-field-image-upload') !== false) {
                            $dropzone = $xpath->query(".//*[contains(@class, 'everest-forms-uploader')]", $parent)->item(0);
                            $max_file_number = (int) $xml->get_node_value($dropzone, 'data-max-file-number');
                            if ($max_file_number > 1) {
                                $xml->set_node_value($element, 'name', '[e2pdf-format-output explode=", " output="{0}"]' . $xml->get_node_value($element, 'name') . '[/e2pdf-format-output]');
                            }
                        }
                    }
                }

                $elements = $xpath->query("//*[contains(@data-field-type, 'repeater-fields')]");
                foreach ($elements as $element) {
                    $sub_element = $xpath->query(".//div[contains(@class, 'repeater_button_add_remove_label')]", $element)->item(0);
                    if ($sub_element) {
                        $input = $element->ownerDocument->createElement('input');
                        $input->setAttribute('type', 'text');
                        $field_id = $xml->get_node_value($element, 'data-repeater-field-id');
                        if (isset($form_fields[$field_id])) {
                            $field_id = $this->get_field_id_for_smarttags($form_fields[$field_id]);
                            $input->setAttribute('name', '{field_id="' . $field_id . '"}');
                            $sub_element->appendChild($input);
                        }
                    }
                }

                $elements = $xpath->query("//*[contains(@class, 'evf-signature-input') or contains(@class, 'evf-payment-price') or contains(@class, 'evf-payment-subtotal') or contains(@class, 'evf-payment-total')]");
                foreach ($elements as $element) {
                    $xml->set_node_value($element, 'type', 'text');
                }

                $elements = $xpath->query('//input|//textarea|//select');
                foreach ($elements as $element) {
                    $field_id = preg_replace('/everest_forms\[form_fields\]\[([^\]\.*]+)\].*/', '$1', $xml->get_node_value($element, 'name'));
                    if (isset($form_fields[$field_id])) {
                        $field_id = $this->get_field_id_for_smarttags($form_fields[$field_id]);
                        $xml->set_node_value($element, 'name', '{field_id="' . $field_id . '"}');
                    }
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

    public function auto() {
        $response = array();
        $elements = array();
        $fields = array();

        $form = evf()->form->get(
                $this->get('item'),
                array(
                    'content_only' => true,
                )
        );
        $fields = $form['form_fields'];

        foreach ($fields as $field) {

            $field_value = '{field_id="' . $this->get_field_id_for_smarttags($field) . '"}';
            $field_label = isset($field['label']) ? $field['label'] : '';

            switch ($field['type']) {
                case 'text':
                case 'email':
                case 'first-name':
                case 'last-name':
                case 'number':
                case 'url':
                case 'phone':
                case 'date-time':
                case 'rating':
                case 'scale-rating':
                case 'payment-quantity':
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
                                    'width' => '100%',
                                    'height' => 'auto',
                                    'value' => $field_label,
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
                                    'width' => '100%',
                                    'height' => 'auto',
                                    'value' => $field_label,
                                ),
                            )
                    );
                    $field_options = array();
                    foreach ($field['choices'] as $option) {
                        $option_value = isset($option['label']) ? $option['label'] : '';
                        $field_options[] = $option_value;
                    }
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
                    break;
                case 'yes-no':
                case 'radio':
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
                                    'width' => '100%',
                                    'height' => 'auto',
                                    'value' => $field_label,
                                ),
                            )
                    );

                    if ($field['type'] == 'yes-no') {
                        $field['choices'] = array(
                            array(
                                'label' => $field['yes_label'],
                                'value' => $field['yes_value'],
                            ),
                            array(
                                'label' => $field['no_label'],
                                'value' => $field['no_value'],
                            ),
                        );
                    }

                    foreach ($field['choices'] as $opt_key => $option) {
                        if ($field['type'] == 'yes-no') {
                            $option_value = isset($option['value']) ? $option['value'] : '';
                        } else {
                            $option_value = isset($option['label']) ? $option['label'] : '';
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
                                        'value' => $option['label'],
                                    ),
                                )
                        );
                    }
                    break;
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
                                    'width' => '100%',
                                    'height' => 'auto',
                                    'value' => $field_label,
                                ),
                            )
                    );
                    foreach ($field['choices'] as $opt_key => $option) {
                        $option_value = isset($option['label']) ? $option['label'] : '';
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
                                        'value' => $option['label'],
                                    ),
                                )
                        );
                    }
                    break;
                case 'image-upload':
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
                                    'width' => '100%',
                                    'height' => 'auto',
                                    'value' => $field_label,
                                ),
                            )
                    );

                    $max_file_number = 1;
                    if (!empty($field['max_file_number'])) {
                        $max_file_number = (int) $field['max_file_number'];
                    }

                    for ($i = 0; $i < $max_file_number; $i++) {
                        $elements[] = $this->auto_field(
                                $field,
                                array(
                                    'type' => 'e2pdf-image',
                                    'float' => true,
                                    'block' => true,
                                    'properties' => array(
                                        'top' => '5',
                                        'left' => '20',
                                        'right' => '0',
                                        'width' => '170',
                                        'height' => '150',
                                        'value' => $max_file_number > 1 ? '[e2pdf-format-output explode=", " output="{' . $i . '}"]' . $field_value . '[/e2pdf-format-output]' : $field_value,
                                    ),
                                )
                        );
                    }
                    break;
                case 'textarea':
                case 'file-upload':
                case 'address':
                case 'country':
                case 'likert':
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
                                    'width' => '100%',
                                    'height' => 'auto',
                                    'value' => $field_label,
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
                case 'privacy-policy':
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
                                    'width' => '100%',
                                    'height' => 'auto',
                                    'value' => $field_label,
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
                                    'value' => $field_value,
                                    'option' => isset($field['consent_message']) ? $field['consent_message'] : '',
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
                                    'value' => $field['consent_message'],
                                ),
                            )
                    );
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
                                    'width' => '100%',
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
                                    'value' => $field_value,
                                ),
                            )
                    );
                    break;
                case 'wysiwyg':
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
                                    'width' => '100%',
                                    'height' => 'auto',
                                    'value' => $field_label,
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
                                    'width' => '100%',
                                    'height' => 'auto',
                                    'value' => isset($field['code']) ? $field['code'] : '',
                                ),
                            )
                    );
                    break;
                case 'title':
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
                                    'width' => '100%',
                                    'height' => 'auto',
                                    'value' => $field_label,
                                ),
                            )
                    );
                    if (!empty($field['description'])) {
                        $elements[] = $this->auto_field(
                                $field,
                                array(
                                    'type' => 'e2pdf-html',
                                    'block' => true,
                                    'float' => true,
                                    'properties' => array(
                                        'top' => '5',
                                        'left' => '20',
                                        'right' => '20',
                                        'width' => '100%',
                                        'height' => 'auto',
                                        'value' => isset($field['description']) ? $field['description'] : '',
                                    ),
                                )
                        );
                    }
                    break;
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
                                    'width' => '100%',
                                    'height' => 'auto',
                                    'value' => $field_label,
                                ),
                            )
                    );
                    if (!empty($field['description'])) {
                        $elements[] = $this->auto_field(
                                $field,
                                array(
                                    'type' => 'e2pdf-html',
                                    'block' => true,
                                    'float' => true,
                                    'properties' => array(
                                        'top' => '5',
                                        'left' => '20',
                                        'right' => '20',
                                        'width' => '100%',
                                        'height' => 'auto',
                                        'value' => isset($field['description']) ? $field['description'] : '',
                                    ),
                                )
                        );
                    }
                    $elements[] = $this->auto_field(
                            $field,
                            array(
                                'type' => 'e2pdf-html',
                                'properties' => array(
                                    'top' => '5',
                                    'width' => '100%',
                                    'height' => 'auto',
                                    'value' => $field_value,
                                ),
                            )
                    );

                    break;
                case 'payment-subtotal':
                case 'payment-total':
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
                                    'width' => '100%',
                                    'height' => 'auto',
                                    'value' => $field_label,
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
                                    'value' => $field_value,
                                ),
                            )
                    );
                    break;
                case 'divider':
                case 'hidden':
                case 'repeater-fields':
                    break;
                default:
                    break;
            }
        }

        $response['page'] = array(
            'bottom' => '20',
            'top' => '20',
        );
        $response['elements'] = $elements;
        return $response;
    }

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
        return $element;
    }

    public function styles($item_id = false) {
        $styles = array();
        if (function_exists('evf')) {
            $styles[] = evf()->plugin_url() . '/assets/css/everest-forms.css?v=' . time();
        }
        $styles[] = plugins_url('css/extension/everest.css?v=' . time(), $this->helper->get('plugin_file_path'));
        return $styles;
    }

    /**
     * Functions
     */
    public function get_field_id_for_smarttags($field) {
        $field_id = isset($field['id']) ? $field['id'] : '';
        $field_label = isset($field['label']) ? $field['label'] : '';
        $field_type = isset($field['type']) ? $field['type'] : '';
        if ('fullname' !== $field_id && 'email' !== $field_id && 'subject' !== $field_id && 'message' !== $field_id) {
            $field_label = preg_split('/[\s\-\_]/', $field_label);
            foreach ($field_label as $key => $value) {
                if (0 === $key) {
                    $field_label[$key] = strtolower($value);
                } else {
                    $field_label[$key] = ucfirst($value);
                }
            }
            $field_label = implode('', $field_label);
            $field_id = $field_label . '_' . $field_id;
        } else {
            $field_id = $field_id;
        }
        if ($field_type != 'repeater-fields' && isset($field['repeater-fields']) && $field['repeater-fields'] == 'yes') {
            $field_id .= '_1';
        }
        return $field_id;
    }

    public function get_merge_tag_value($value) {
        if (isset($value['type'])) {
            switch ($value['type']) {
                case 'checkbox':
                case 'payment-checkbox':
                    $value = isset($value['label']) ? implode(', ', $value['label']) : '';
                    break;
                case 'country':
                    $value = isset($value['country_code']) ? $value['country_code'] : '';
                    break;
                case 'image-upload':
                case 'file-upload':
                    $files = [];
                    $uploads = wp_upload_dir();
                    if (!empty($value['value_raw'])) {
                        foreach ($value['value_raw'] as $files_key => $files_value) {
                            if (!is_array($files_value['value']) && false !== strpos($files_value['value'], $uploads['basedir'])) {
                                $value = trailingslashit(content_url()) . str_replace(str_replace('uploads', '', $uploads['basedir']), '', $files_value['value']);
                            }
                            if (!empty($value)) {
                                $files[] = $files_value['value'];
                            }
                        }
                    }
                    $value = implode(', ', $files);
                    break;
            }
            return $value;
        }
        return '';
    }

    /**
     * Actions
     */
    public function action_everest_forms_email_send_after() {
        $files = $this->helper->get('tmp_everest_attachments');
        if (is_array($files) && !empty($files)) {
            foreach ($files as $key => $file) {
                $this->helper->delete_dir(dirname($file) . '/');
            }
            $this->helper->deset('tmp_everest_attachments');
            $this->helper->deset('everest_attachments');
        }
    }

    public function action_everest_forms_before_template_part($template_name) {
        if ($template_name == 'notices/success.php') {
            ob_start();
        }
    }

    public function action_everest_forms_after_template_part($template_name) {
        if ($template_name == 'notices/success.php') {
            $content = ob_get_clean();
            $content = $this->filter_message($content);
            echo $content;
            remove_action('everest_forms_before_template_part', array($this, 'action_everest_forms_before_template_part'), 99);
            remove_action('everest_forms_after_template_part', array($this, 'action_everest_forms_after_template_part'), 99);
        }
    }

    /**
     * Filters
     */
    public function filter_everest_forms_after_success_ajax_message($response_data, $form_data, $entry) {
        if (!empty($response_data['message'])) {
            $response_data['message'] = $this->filter_message($response_data['message']);
        }
        return $response_data;
    }

    public function filter_everest_forms_email_attachments($attachments, $mail) {
        $files = $this->helper->get('everest_attachments');
        if (is_array($files) && !empty($files)) {
            $attachments = $this->helper->load('convert')->to_array($attachments);
            foreach ($files as $key => $file) {
                $attachments[] = $file;
            }
        }
        return $attachments;
    }

    public function filter_everest_forms_email_message($message, $mail) {
        $message = $this->filter_message($message, 'mail');
        return $message;
    }

    public function filter_everest_forms_add_success($message) {
        if (is_string($message) && false !== strpos($message, '[e2pdf-')) {
            add_action('everest_forms_before_template_part', array($this, 'action_everest_forms_before_template_part'), 99);
            add_action('everest_forms_after_template_part', array($this, 'action_everest_forms_after_template_part'), 99);
        }
        return $message;
    }

    public function filter_everest_forms_process_smart_tags($content, $form_data, $fields = '') {

        preg_match_all('/\{field_id="(.+?)"\}/', $content, $ids);

        if (!empty($ids[1]) && !empty($fields)) {

            foreach ($ids[1] as $key => $field_id) {
                $mixed_field_id = explode('_', $field_id);
                $sub_field_id = isset($mixed_field_id[1]) ? $mixed_field_id['1'] : '';

                $repeater_index = false;
                if (isset($mixed_field_id[2])) {
                    $field_id = $mixed_field_id[0] . '_' . $sub_field_id;
                    $repeater_index = $mixed_field_id[2];
                }

                if ('fullname' !== $field_id && 'email' !== $field_id && 'subject' !== $field_id && 'message' !== $field_id) {
                    $value = isset($fields[$sub_field_id]['value']) && !empty($fields[$sub_field_id]['value']) ? evf_sanitize_textarea_field($fields[$sub_field_id]['value']) : '';
                } else {
                    $value = isset($fields[$field_id]['value']) && !empty($fields[$field_id]['value']) ? evf_sanitize_textarea_field($fields[$field_id]['value']) : '';
                }
                $value = apply_filters('everest_forms_smart_tags_value', $value, $field_id, $fields, $form_data);

                // repeater
                if (!isset($fields[$sub_field_id])) {
                    if (!empty($form_data['form_fields'])) {
                        foreach ($form_data['form_fields'] as $fid => $field) {
                            if ('repeater-fields' === $field['type']) {
                                if (!empty($fields[$field['id']])) {
                                    $value = '';
                                    $repeater_rows = $fields[$field['id']]['value_raw'];
                                    if ($repeater_index) {
                                        if (isset($repeater_rows[$repeater_index]) && array_key_exists($sub_field_id, $repeater_rows[$repeater_index])) {
                                            $value = isset($repeater_rows[$repeater_index][$sub_field_id]['value']) && !empty($repeater_rows[$repeater_index][$sub_field_id]['value']) ? evf_sanitize_textarea_field($repeater_rows[$repeater_index][$sub_field_id]['value']) : '';
                                            if (isset($repeater_rows[$repeater_index][$sub_field_id]['type']) && ('image-upload' === $repeater_rows[$repeater_index][$sub_field_id]['type'] || 'file-upload' === $repeater_rows[$repeater_index][$sub_field_id]['type'])) {
                                                $value = $repeater_rows[$repeater_index][$sub_field_id];
                                            }
                                            if (isset($value['type']) && in_array($value['type'], array('country', 'checkbox', 'payment-checkbox', 'image-upload', 'file-upload'), true)) {
                                                $value = $this->get_merge_tag_value($value);
                                            }
                                        }
                                        $content = str_replace('{field_id="' . $field_id . '_' . $repeater_index . '"}', $value, $content);
                                    } else {
                                        $values = array();
                                        foreach ($repeater_rows as $row_id => $row) {
                                            if (array_key_exists($sub_field_id, $row)) {
                                                if (isset($row[$sub_field_id]['type']) && ('image-upload' === $row[$sub_field_id]['type'] || 'file-upload' === $row[$sub_field_id]['type'])) {
                                                    $value = $row[$sub_field_id];
                                                }
                                                if (isset($value['type']) && in_array($value['type'], array('country', 'checkbox', 'payment-checkbox', 'image-upload', 'file-upload'), true)) {
                                                    $values[] = $this->get_merge_tag_value($value['value']);
                                                } else {
                                                    $values[] = $row[$sub_field_id]['value'];
                                                }
                                            }
                                        }
                                        $value = implode(',', array_filter($values));
                                        $content = str_replace('{field_id="' . $field_id . '"}', $value, $content);
                                        break;
                                    }
                                }
                            }
                        }
                    }
                } else {
                    if (isset($fields[$sub_field_id]['type']) && ($fields[$sub_field_id]['type'] === 'image-upload' || $fields[$sub_field_id]['type'] === 'file-upload' || ($fields[$sub_field_id]['type'] === 'repeater-fields' && apply_filters('e2pdf_for_do_shortcode_data_process', false)))) {
                        $value = $fields[$sub_field_id];
                    }
                    if (isset($value['type'])) {
                        if ($value['type'] === 'repeater-fields') {
                            $repeaters = array();
                            if (!empty($value['value_raw']) && is_array($repeaters)) {
                                $repeaters = $value['value_raw'];
                            }
                            $content = str_replace('{field_id="' . $field_id . '"}', serialize($repeaters), $content); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
                        } elseif (in_array($value['type'], array('country', 'checkbox', 'payment-checkbox', 'image-upload', 'file-upload'), true)) {
                            $content = str_replace('{field_id="' . $field_id . '"}', $this->get_merge_tag_value($value), $content);
                        }
                    }
                }
            }
        }
        return $content;
    }

    public function filter_message($message = '', $type = 'message') {
        if (false !== strpos($message, '[')) {
            $shortcode_tags = array(
                'e2pdf-download',
                'e2pdf-save',
                'e2pdf-view',
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
                    if (!isset($atts['dataset']) && isset($atts['id'])) {
                        $template = new Model_E2pdf_Template();
                        $template->load($atts['id']);
                        if ($template->get('extension') === 'everest') {
                            $atts['dataset'] = evf()->task->entry_id;
                            $shortcode[3] .= ' dataset="' . evf()->task->entry_id . '"';
                        }
                    }
                    if (!isset($atts['apply'])) {
                        $shortcode[3] .= ' apply="true"';
                    }
                    if (!isset($atts['iframe_download'])) {
                        $shortcode[3] .= ' iframe_download="true"';
                    }
                    $file = false;
                    if ($this->helper->load('shortcode')->is_attachment($shortcode, $atts)) {
                        if ($type == 'mail') {
                            $file = do_shortcode_tag($shortcode);
                            if ($file) {
                                $tmp = false;
                                if (substr($file, 0, 4) === 'tmp:') {
                                    $file = substr($file, 4);
                                    $tmp = true;
                                }
                                if ($shortcode[2] === 'e2pdf-save' || isset($atts['pdf'])) {
                                    if ($tmp) {
                                        $this->helper->add('tmp_everest_attachments', $file);
                                    }
                                } else {
                                    $this->helper->add('tmp_everest_attachments', $file);
                                }
                                $this->helper->add('everest_attachments', $file);
                            }
                        }
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
     * Hooks
     */
    public function hook_everest_row_actions($actions, $entry) {
        if (!empty($entry->form_id)) {
            $hooks = $this->helper->load('hooks')->get('everest', 'hook_everest_row_actions', $entry->form_id);
            if (!empty($hooks)) {
                foreach ($hooks as $hook) {
                    $action = apply_filters(
                            'e2pdf_hook_action_button',
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
                                'title' => 'PDF #' . $hook,
                            ), 'hook_everest_row_actions', $hook, $entry->entry_id
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

    public function hook_everest_entry_view($entry) {
        if (!empty($entry->form_id)) {
            $hooks = $this->helper->load('hooks')->get('everest', 'hook_everest_entry_view', $entry->form_id);
            if (!empty($hooks)) {
                foreach ($hooks as $hook) {
                    $action = apply_filters(
                            'e2pdf_hook_action_button',
                            array(
                                'html' => '<p><a class="e2pdf-download-hook" target="_blank" title="%2$s" href="%1$s"><span class="dashicons dashicons-pdf"></span> %2$s</a></p>',
                                'url' => $this->helper->get_url(
                                        array(
                                            'page' => 'e2pdf',
                                            'action' => 'export',
                                            'id' => $hook,
                                            'dataset' => $entry->entry_id,
                                        ), 'admin.php?'
                                ),
                                'title' => 'PDF #' . $hook,
                            ), 'hook_everest_entry_view', $hook, $entry->entry_id
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
}
