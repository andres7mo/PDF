<?php

/**
 * E2Pdf Gravity Forms Extension
 * @copyright  Copyright 2017 https://e2pdf.com
 * @license    GPL v2
 * @version    1
 * @link       https://e2pdf.com
 * @since      1.07.04
 */
if (!defined('ABSPATH')) {
    die('Access denied.');
}

class Extension_E2pdf_Gravity extends Model_E2pdf_Model {

    private $options;
    private $info = array(
        'key' => 'gravity',
        'title' => 'Gravity Forms',
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

        if (defined('E2PDF_GRAVITY_EXTENSION') || $this->helper->load('extension')->is_plugin_active('gravityforms/gravityforms.php')) {
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
                if ($this->get('item') && class_exists('GFFormsModel')) {
                    $this->set('cached_form', GFFormsModel::get_form_meta($this->get('item')));
                }
                break;
            case 'dataset':
                $this->set('cached_entry', array());
                if ($this->get('dataset') && class_exists('GFFormsModel')) {
                    $entry = GFFormsModel::get_entry($this->get('dataset'));
                    if ($entry && !is_wp_error($entry) && isset($entry['form_id'])) {
                        $this->set('cached_entry', $entry);
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
        if (class_exists('GFFormsModel')) {
            $forms = GFFormsModel::get_forms(null, 'title');
            if ($forms) {
                foreach ($forms as $key => $form) {
                    $items[] = $this->item($form->id);
                }
            }
        }
        return $items;
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
        if (class_exists('GFAPI') && $item_id) {
            $paging = array(
                'offset' => 0,
                'page_size' => 9999999,
            );
            $entries = GFAPI::get_entries($item_id, array(), array(), $paging);
            if ($entries) {
                $this->set('item', $item_id);
                foreach ($entries as $key => $entry) {
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
        $entry = GFFormsModel::get_entry($dataset_id);
        $item = $entry && !is_wp_error($entry) && is_array($entry) && isset($entry['form_id']) ? $entry['form_id'] : '0';
        $actions = new stdClass();
        $actions->view = $this->helper->get_url(
                array(
                    'page' => 'gf_entries',
                    'view' => 'entry',
                    'id' => $item,
                    'lid' => $dataset_id,
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
        if (class_exists('GFFormsModel')) {
            $form = GFFormsModel::get_form_meta($item_id);
        }
        $item = new stdClass();
        if ($form) {
            $item->id = (string) $item_id;
            $item->url = $this->helper->get_url(
                    array(
                        'page' => 'gf_edit_forms',
                        'id' => $item_id,
                    )
            );
            $item->name = $form['title'];
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
        add_action('gform_after_email', array($this, 'action_gform_after_email'), 30);
        add_action('gform_after_update_entry', array($this, 'action_gform_after_update_entry'), 0, 2);

        /* Hooks */
        add_action('gform_entries_first_column_actions', array($this, 'hook_gravity_row_actions'), 10, 4);
    }

    /**
     * Load filters for this extension
     */
    public function load_filters() {
        add_filter('gform_confirmation', array($this, 'filter_gform_confirmation'), 30, 4);
        add_filter('gform_notification', array($this, 'filter_gform_notification'), 30, 3);
        add_filter('gform_twilio_message', array($this, 'filter_gform_twilio_message'), 30, 4);
        add_filter('gform_entry_post_save', array($this, 'filter_gform_entry_post_save'), 0, 2);
        add_filter('gform_entry_field_value', array($this, 'filter_gform_entry_field_value'), 10, 4);
        add_filter('gform_merge_tag_filter', array($this, 'filter_gform_merge_tag_filter'), 10, 5);
        add_filter('gform_entries_field_value', array($this, 'filter_gform_entries_field_value'), 10, 4);

        /* Hooks */
        add_filter('gform_entry_detail_meta_boxes', array($this, 'hook_gravity_entry_view'), 10, 3);
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
            $gv_request = false;
            if (false !== strpos($value, '[gravityview') && function_exists('gravityview') && gravityview()->request->is_admin()) {
                $gv_request = gravityview()->request;
                gravityview()->request = new GV\Frontend_Request();
            }
            $value = $this->helper->load('field')->do_shortcodes($value, $this, $field);
            if ($gv_request !== false) {
                gravityview()->request = $gv_request;
            }
            if (class_exists('GFCommon') && $this->get('cached_form') && $this->get('cached_entry')) {
                add_filter('gp_template_paths', array($this, 'filter_gp_template_paths'), 30, 2);
                add_filter('gform_merge_tag_filter', array($this, 'filter_gform_merge_tag_filter_tags'), 30, 5);
                add_filter('gform_display_product_summary', '__return_false', 30);
                $value = GFCommon::replace_variables($value, $this->get('cached_form'), $this->get('cached_entry'), false, false, false, 'text');
                remove_filter('gform_display_product_summary', '__return_false', 30);
                remove_filter('gform_merge_tag_filter', array($this, 'filter_gform_merge_tag_filter_tags'), 30, 5);
                remove_filter('gp_template_paths', array($this, 'filter_gp_template_paths'), 30, 2);
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

        $item = $this->get('item');

        $response = array();
        $elements = array();
        $merged_tags = array();
        $form = false;

        if (class_exists('GFFormsModel')) {
            $form = GFFormsModel::get_form_meta($item);
        }

        if ($form) {
            if (class_exists('GFCommon')) {
                foreach ($form['fields'] as $field) {
                    $tags = GFCommon::get_field_merge_tags($field);
                    foreach ($tags as $tag) {
                        if (isset($tag['tag'])) {
                            if ($field->type == 'list') {
                                $field_id = preg_replace('/\{(?:.*)\:(.*)\:\}/', '${1}', $tag['tag']);
                            } else {
                                $field_id = preg_replace('/\{(?:.*)\:(.*)\}/', '${1}', $tag['tag']);
                            }
                            if ($field_id) {
                                if ($this->get('nested')) {
                                    $merged_tags[$field_id] = $this->get('nested');
                                } else {
                                    $merged_tags[$field_id] = $tag['tag'];
                                }
                            }
                        }
                    }
                }
            }

            foreach ($form['fields'] as $field) {

                if ($this->get('nested') && !in_array($field->id, $this->get('nested_fields'))) {
                    continue;
                }

                if ($field->type == 'product' || $field->type == 'shipping' || $field->type == 'option') {
                    if ($field->inputType == 'select') {
                        $field->type = 'select';
                    } elseif ($field->inputType == 'radio') {
                        $field->type = 'radio';
                    } elseif ($field->inputType == 'checkbox') {
                        $field->type = 'checkbox';
                    } elseif ($field->inputType == 'price') {
                        $field->type = 'text';
                    }
                }

                if ($field->type == 'survey') {

                    if ($field->inputType == 'rating') {
                        $field->type = 'radio';
                        foreach ($field->choices as $key => $choice) {
                            $field->choices[$key]['value'] = $choice['text'];
                        }
                    } elseif ($field->inputType == 'select') {
                        $field->type = 'select';
                        $field->enableChoiceValue = false;
                        foreach ($field->choices as $key => $choice) {
                            $field->choices[$key]['value'] = $choice['text'];
                        }
                    } elseif ($field->inputType == 'rank') {
                        $field->type = 'textarea';
                    } else {
                        $field->type = $field->inputType;
                    }
                }

                switch ($field->type) {
                    case 'text':
                    case 'number':
                    case 'date':
                    case 'time':
                    case 'phone':
                    case 'website':
                    case 'email':
                    case 'post_title':
                    case 'post_excerpt':
                    case 'post_tags':
                    case 'post_custom_field':
                    case 'quantity':
                    case 'shipping':
                    case 'total':
                        $value = isset($merged_tags[$field->id]) ? $merged_tags[$field->id] : '';

                        if ($this->get('nested')) {
                            if (substr($value, -1) == '}') {
                                $value = substr($value, 0, -1) . ':filter[' . $field->id . '],index[0]}';
                            }
                        }

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
                                        'value' => $field->label,
                                        'pass' => $field->enablePasswordInput ? '1' : '0',
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
                                        'value' => $value,
                                    ),
                                )
                        );
                        break;
                    case 'list':
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
                                        'value' => $field->label,
                                    ),
                                )
                        );

                        if ($field->enableColumns) {
                            $width = number_format(floor((100 / count($field->choices)) * 100) / 100, 2);
                            foreach ($field->choices as $key => $choice) {
                                $field_id = (int) $key + 1;
                                $value = isset($merged_tags[$field->id]) ? $merged_tags[$field->id] : '';

                                if ($this->get('nested')) {
                                    if (substr($value, -1) == '}') {
                                        $value = substr($value, 0, -1) . ':filter[' . $field->id . ':1_' . $field_id . '],index[0]}';
                                    }
                                } else {
                                    if (substr($value, -1) == '}') {
                                        $value = substr($value, 0, -1) . '1_' . $field_id . '}';
                                    }
                                }

                                $float = true;
                                if ($key == '0') {
                                    $float = false;
                                }
                                $elements[] = $this->auto_field(
                                        $field,
                                        array(
                                            'type' => 'e2pdf-html',
                                            'block' => true,
                                            'float' => $float,
                                            'properties' => array(
                                                'top' => '5',
                                                'left' => '20',
                                                'right' => '20',
                                                'width' => $width . '%',
                                                'height' => 'auto',
                                                'value' => $choice['text'],
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
                                                'value' => $value,
                                            ),
                                        )
                                );
                            }
                        } else {
                            $value = isset($merged_tags[$field->id]) ? $merged_tags[$field->id] : '';

                            if ($this->get('nested')) {
                                if (substr($value, -1) == '}') {
                                    $value = substr($value, 0, -1) . ':filter[' . $field->id . ':1],index[0]}';
                                }
                            } else {
                                if (substr($value, -1) == '}') {
                                    $value = substr($value, 0, -1) . '1}';
                                }
                            }

                            $elements[] = $this->auto_field(
                                    $field,
                                    array(
                                        'type' => 'e2pdf-input',
                                        'properties' => array(
                                            'top' => '5',
                                            'width' => '100%',
                                            'height' => 'auto',
                                            'value' => $value,
                                        ),
                                    )
                            );
                        }
                        break;
                    case 'fileupload':
                    case 'textarea':
                    case 'post_content':
                        $value = isset($merged_tags[$field->id]) ? $merged_tags[$field->id] : '';

                        if ($this->get('nested')) {
                            if (substr($value, -1) == '}') {
                                $value = substr($value, 0, -1) . ':filter[' . $field->id . '],index[0]}';
                            }
                        }

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
                                        'value' => $field->label,
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
                                        'value' => $value,
                                    ),
                                )
                        );
                        break;
                    case 'select':
                    case 'multiselect':
                    case 'post_category':
                    case 'option':
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
                                        'value' => $field->label,
                                    ),
                                )
                        );
                        $options_tmp = array();
                        if (isset($field->choices) && is_array($field->choices)) {
                            foreach ($field->choices as $opt_key => $option) {
                                $options_tmp[] = isset($option['value']) ? $option['value'] : '';
                            }
                        }

                        $value = isset($merged_tags[$field->id]) ? $merged_tags[$field->id] : '';

                        if ($this->get('nested')) {
                            if ($field->enableChoiceValue && $value) {
                                if (substr($value, -1) == '}') {
                                    $value = substr($value, 0, -1) . ':value,filter[' . $field->id . '],index[0]}';
                                }
                            } else {
                                if (substr($value, -1) == '}') {
                                    $value = substr($value, 0, -1) . ':filter[' . $field->id . '],index[0]}';
                                }
                            }
                        } else {
                            if ($field->enableChoiceValue && $value) {
                                if (substr($value, -1) == '}') {
                                    $value = substr($value, 0, -1) . ':value}';
                                }
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
                                        'value' => $value,
                                        'height' => $field->type == 'multiselect' ? '80' : 'auto',
                                        'multiline' => $field->type == 'multiselect' ? '1' : '0',
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
                                        'value' => $field->label,
                                    ),
                                )
                        );

                        if (isset($field->choices) && is_array($field->choices)) {

                            $value = isset($merged_tags[$field->id]) ? $merged_tags[$field->id] : '';

                            if ($this->get('nested')) {
                                if ($field->enableChoiceValue && $value) {
                                    if (substr($value, -1) == '}') {
                                        $value = substr($value, 0, -1) . ':value,filter[' . $field->id . '],index[0]}';
                                    }
                                } else {
                                    if (substr($value, -1) == '}') {
                                        $value = substr($value, 0, -1) . ':filter[' . $field->id . '],index[0]}';
                                    }
                                }
                            } else {
                                if ($field->enableChoiceValue && $value) {
                                    if (substr($value, -1) == '}') {
                                        $value = substr($value, 0, -1) . ':value}';
                                    }
                                }
                            }

                            foreach ($field->choices as $opt_key => $option) {
                                $elements[] = $this->auto_field(
                                        $field,
                                        array(
                                            'type' => 'e2pdf-checkbox',
                                            'properties' => array(
                                                'top' => '5',
                                                'width' => 'auto',
                                                'height' => 'auto',
                                                'value' => $value,
                                                'option' => isset($option['value']) ? $option['value'] : '',
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
                                                'value' => isset($option['text']) ? $option['text'] : '',
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
                                        'value' => $field->label,
                                    ),
                                )
                        );

                        if (isset($field->choices) && is_array($field->choices)) {

                            $value = isset($merged_tags[$field->id]) ? $merged_tags[$field->id] : '';

                            if ($this->get('nested')) {
                                if ($field->enableChoiceValue && $value) {
                                    if (substr($value, -1) == '}') {
                                        $value = substr($value, 0, -1) . ':value,filter[' . $field->id . '],index[0]}';
                                    }
                                } else {
                                    if (substr($value, -1) == '}') {
                                        $value = substr($value, 0, -1) . ':filter[' . $field->id . '],index[0]}';
                                    }
                                }
                            } else {
                                if ($field->enableChoiceValue && $value) {
                                    if (substr($value, -1) == '}') {
                                        $value = substr($value, 0, -1) . ':value}';
                                    }
                                }
                            }

                            $choices = array();
                            foreach ($field->choices as $opt_key => $option) {

                                if (!$value && isset($field->inputs) && isset($field->inputs[$opt_key]['id'])) {
                                    $value = isset($merged_tags[$field->inputs[$opt_key]['id']]) ? $merged_tags[$field->inputs[$opt_key]['id']] : '';
                                    if ($field->enableChoiceValue && $value) {
                                        if (substr($value, -1) == '}') {
                                            $value = substr($value, 0, -1) . ':value}';
                                        }
                                    }
                                }

                                if (isset($option['value'])) {
                                    $choices[] = $option['value'];
                                }

                                $elements[] = $this->auto_field(
                                        $field,
                                        array(
                                            'type' => 'e2pdf-radio',
                                            'properties' => array(
                                                'top' => '5',
                                                'width' => 'auto',
                                                'height' => 'auto',
                                                'value' => $value,
                                                'option' => isset($option['value']) ? $option['value'] : '',
                                                'group' => $value,
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
                                                'value' => isset($option['text']) ? $option['text'] : '',
                                            ),
                                        )
                                );
                            }

                            //other choice
                            if ($field->enableOtherChoice) {
                                $actions_radio = array();
                                $actions_input = array();
                                if (!empty($choices)) {
                                    $conditions = array();
                                    $conditions[1] = array(
                                        'condition' => '!=',
                                        'if' => $value,
                                        'value' => '',
                                    );

                                    foreach ($choices as $choice) {
                                        $conditions[] = array(
                                            'condition' => '!=',
                                            'if' => $value,
                                            'value' => $choice,
                                        );
                                    }

                                    $actions_radio = array(
                                        '0' => array(
                                            'order' => '0',
                                            'action' => 'change',
                                            'apply' => 'all',
                                            'change' => 'gf_other_choice',
                                            'property' => 'value',
                                            'conditions' => $conditions,
                                        ),
                                    );

                                    $actions_input = array(
                                        '0' => array(
                                            'order' => '0',
                                            'action' => 'change',
                                            'apply' => 'all',
                                            'change' => $value,
                                            'property' => 'value',
                                            'conditions' => $conditions,
                                        ),
                                    );
                                }

                                $elements[] = $this->auto_field(
                                        $field,
                                        array(
                                            'type' => 'e2pdf-radio',
                                            'properties' => array(
                                                'top' => '5',
                                                'width' => 'auto',
                                                'height' => 'auto',
                                                'value' => $value,
                                                'option' => 'gf_other_choice',
                                                'group' => $value,
                                            ),
                                            'actions' => $actions_radio,
                                        )
                                );

                                $elements[] = $this->auto_field(
                                        $field,
                                        array(
                                            'type' => 'e2pdf-input',
                                            'float' => true,
                                            'properties' => array(
                                                'left' => '5',
                                                'width' => '100%',
                                                'height' => 'auto',
                                                'value' => '',
                                            ),
                                            'actions' => $actions_input,
                                        )
                                );
                            }
                        }
                        break;
                    case 'name':
                        foreach ($field['inputs'] as $key => $input) {
                            if (isset($input->isHidden) && $input->isHidden) {
                                unset($field['inputs'][$key]);
                            }
                        }

                        $width = '100%';
                        if (count($field['inputs']) == '3') {
                            $width = '33.3%';
                        } else {
                            $width = 100 / count($field['inputs']) . '%';
                        }

                        foreach ($field['inputs'] as $key => $sub_field) {

                            $elements[] = $this->auto_field(
                                    $sub_field,
                                    array(
                                        'type' => 'e2pdf-html',
                                        'block' => true,
                                        'float' => $key == '0' ? false : true,
                                        'properties' => array(
                                            'top' => '20',
                                            'left' => '20',
                                            'right' => '20',
                                            'width' => $width,
                                            'height' => 'auto',
                                            'value' => isset($sub_field['label']) && $sub_field['label'] ? $sub_field['label'] : '',
                                        ),
                                    )
                            );

                            $value = isset($merged_tags[$sub_field['id']]) ? $merged_tags[$sub_field['id']] : '';

                            if ($this->get('nested')) {
                                if (substr($value, -1) == '}') {
                                    $value = substr($value, 0, -1) . ':filter[' . $sub_field['id'] . '],index[0]}';
                                }
                            }

                            if (isset($sub_field['choices']) && is_array($sub_field['choices'])) {

                                $options_tmp = array();
                                foreach ($sub_field['choices'] as $opt_key => $option) {
                                    $options_tmp[] = isset($option['value']) ? $option['value'] : '';
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
                                                'value' => $value,
                                                'height' => 'auto',
                                                'multiline' => '0',
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
                                                'value' => $value,
                                            ),
                                        )
                                );
                            }
                        }
                        break;
                    case 'address':
                        $index = 0;
                        foreach ($field['inputs'] as $key => $sub_field) {

                            if (isset($sub_field['isHidden']) && $sub_field['isHidden']) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
                            } else {
                                $value = isset($merged_tags[$sub_field['id']]) ? $merged_tags[$sub_field['id']] : '';
                                if ($this->get('nested')) {
                                    if (substr($value, -1) == '}') {
                                        $value = substr($value, 0, -1) . ':filter[' . $sub_field['id'] . '],index[0]}';
                                    }
                                }

                                $elements[] = $this->auto_field(
                                        $sub_field,
                                        array(
                                            'type' => 'e2pdf-html',
                                            'block' => true,
                                            'float' => $index == '0' ? false : true,
                                            'properties' => array(
                                                'top' => '20',
                                                'left' => '20',
                                                'right' => '20',
                                                'width' => $key == '0' || $key == '1' ? '100%' : '50%',
                                                'height' => 'auto',
                                                'value' => isset($sub_field['label']) && $sub_field['label'] ? $sub_field['label'] : '',
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
                                                'value' => $value,
                                            ),
                                        )
                                );
                                $index++;
                            }
                        }

                        break;
                    case 'consent':
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
                                        'value' => $field->label,
                                    ),
                                )
                        );

                        $value = isset($merged_tags[$field->id . '.1']) ? $merged_tags[$field->id . '.1'] : '';

                        if ($this->get('nested')) {
                            if (substr($value, -1) == '}') {
                                $value = substr($value, 0, -1) . ':filter[' . $field->id . '.1],index[0]}';
                            }
                        }

                        $elements[] = $this->auto_field(
                                $field,
                                array(
                                    'type' => 'e2pdf-checkbox',
                                    'properties' => array(
                                        'top' => '5',
                                        'width' => 'auto',
                                        'height' => 'auto',
                                        'value' => $value,
                                        'option' => '1',
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
                                        'value' => $field->checkboxLabel,
                                    ),
                                )
                        );

                        break;
                    case 'post_image':
                        $value = isset($merged_tags[$field->id]) ? $merged_tags[$field->id] : '';
                        if ($this->get('nested')) {
                            if (substr($value, -1) == '}') {
                                $value = substr($value, 0, -1) . ':filter[' . $field->id . '],index[0]}';
                            }
                        }

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
                                        'value' => $field->label,
                                    ),
                                )
                        );

                        $elements[] = $this->auto_field(
                                $field,
                                array(
                                    'type' => 'e2pdf-image',
                                    'properties' => array(
                                        'top' => '5',
                                        'width' => '100',
                                        'height' => '100',
                                        'value' => $value,
                                        'dimension' => '1',
                                    ),
                                )
                        );

                        if ($field->displayTitle) {
                            $value = isset($merged_tags[$field->id]) ? $merged_tags[$field->id] : '';
                            if ($this->get('nested')) {
                                if (substr($value, -1) == '}') {
                                    $value = substr($value, 0, -1) . ':title,filter[' . $field->id . '],index[0]}';
                                }
                            } else {
                                if (substr($value, -1) == '}') {
                                    $value = substr($value, 0, -1) . ':title}';
                                }
                            }

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
                                            'value' => __('Title', 'gravityforms'),
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
                                            'value' => $value,
                                            'dimension' => '1',
                                        ),
                                    )
                            );
                        }

                        if ($field->displayCaption) {

                            $value = isset($merged_tags[$field->id]) ? $merged_tags[$field->id] : '';
                            if ($this->get('nested')) {
                                if (substr($value, -1) == '}') {
                                    $value = substr($value, 0, -1) . ':caption,filter[' . $field->id . '],index[0]}';
                                }
                            } else {
                                if (substr($value, -1) == '}') {
                                    $value = substr($value, 0, -1) . ':caption}';
                                }
                            }

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
                                            'value' => __('Caption', 'gravityforms'),
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
                                            'value' => $value,
                                            'dimension' => '1',
                                        ),
                                    )
                            );
                        }

                        if ($field->displayDescription) {

                            $value = isset($merged_tags[$field->id]) ? $merged_tags[$field->id] : '';
                            if ($this->get('nested')) {
                                if (substr($value, -1) == '}') {
                                    $value = substr($value, 0, -1) . ':description,filter[' . $field->id . '],index[0]}';
                                }
                            } else {
                                if (substr($value, -1) == '}') {
                                    $value = substr($value, 0, -1) . ':description}';
                                }
                            }

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
                                            'value' => __('Description', 'gravityforms'),
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
                                            'value' => $value && substr($value, -1) == '}' ? substr($value, 0, -1) . ':description}' : '',
                                            'dimension' => '1',
                                        ),
                                    )
                            );
                        }

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
                                        'value' => $field->content,
                                    ),
                                )
                        );
                        break;
                    case 'product':
                        if ($field->inputType != 'hiddenproduct') {
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
                                            'value' => $field->label,
                                        ),
                                    )
                            );

                            if ($field->inputType == 'singleproduct' || $field->inputType == 'calculation') {

                                if ($field->disableQuantity) {
                                    $width = '50%';
                                } else {
                                    $width = '33.3%';
                                }

                                foreach ($field['inputs'] as $key => $sub_field) {

                                    if ($field->disableQuantity && $key == '2') { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
                                    } else {

                                        $value = isset($merged_tags[$sub_field['id']]) ? $merged_tags[$sub_field['id']] : '';

                                        if ($this->get('nested')) {
                                            if (substr($value, -1) == '}') {
                                                $value = substr($value, 0, -1) . ':filter[' . $sub_field['id'] . '],index[0]}';
                                            }
                                        }

                                        $elements[] = $this->auto_field(
                                                $sub_field,
                                                array(
                                                    'type' => 'e2pdf-html',
                                                    'block' => true,
                                                    'float' => $key == '0' ? false : true,
                                                    'properties' => array(
                                                        'top' => '5',
                                                        'left' => '20',
                                                        'right' => '20',
                                                        'width' => $width,
                                                        'height' => 'auto',
                                                        'value' => isset($sub_field['label']) && $sub_field['label'] ? $sub_field['label'] : '',
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
                                                        'value' => $value,
                                                    ),
                                                )
                                        );
                                    }
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
                                    'properties' => array(
                                        'top' => '20',
                                        'left' => '20',
                                        'right' => '20',
                                        'width' => '100%',
                                        'height' => 'auto',
                                        'value' => $field->label,
                                    ),
                                )
                        );

                        $value = isset($merged_tags[$field->id]) ? $merged_tags[$field->id] : '';

                        if ($this->get('nested')) {
                            if (substr($value, -1) == '}') {
                                $value = substr($value, 0, -1) . ':filter[' . $field->id . '],index[0]}';
                            }
                        }

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
                                        'value' => $value,
                                    ),
                                )
                        );
                        break;
                    case 'form':
                        if (isset($field->gpnfForm) && $field->gpnfForm) {

                            $value = isset($merged_tags[$field->id]) ? $merged_tags[$field->id] : '';
                            if ($value) {
                                $this->set('item', $field->gpnfForm);
                                $this->set('nested', $value);

                                $nested_fields = array();
                                if (isset($field->gpnfFields) && is_array($field->gpnfFields)) {
                                    $nested_fields = $field->gpnfFields;
                                }
                                $this->set('nested_fields', $nested_fields);
                                $nested_form = $this->auto();
                                if (!empty($nested_form['elements'])) {
                                    $elements = array_merge($elements, $nested_form['elements']);
                                }
                                $this->set('item', $item);
                                $this->set('nested', '');
                            }
                        }
                        break;
                    case 'likert':
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
                                        'value' => $field->label,
                                    ),
                                )
                        );

                        if (isset($field->gsurveyLikertEnableMultipleRows) && $field->gsurveyLikertEnableMultipleRows) {

                            foreach ($field['inputs'] as $key => $sub_field) {

                                $elements[] = $this->auto_field(
                                        $field,
                                        array(
                                            'type' => 'e2pdf-html',
                                            'block' => true,
                                            'properties' => array(
                                                'top' => '5',
                                                'left' => '20',
                                                'right' => '20',
                                                'width' => '100%',
                                                'height' => 'auto',
                                                'value' => $sub_field['label'],
                                            ),
                                        )
                                );

                                if (isset($field->choices) && is_array($field->choices)) {

                                    $value = isset($merged_tags[$sub_field['id']]) ? $merged_tags[$sub_field['id']] : '';
                                    if ($field->enableChoiceValue && $value) {
                                        if (substr($value, -1) == '}') {
                                            $value = substr($value, 0, -1) . ':value}';
                                        }
                                    }

                                    $choices = array();
                                    foreach ($field->choices as $opt_key => $option) {

                                        if (!$value && isset($field->inputs) && isset($field->inputs[$opt_key]['id'])) {
                                            $value = isset($merged_tags[$field->inputs[$opt_key]['id']]) ? $merged_tags[$field->inputs[$opt_key]['id']] : '';
                                            if ($field->enableChoiceValue && $value) {
                                                if (substr($value, -1) == '}') {
                                                    $value = substr($value, 0, -1) . ':value}';
                                                }
                                            }
                                        }

                                        if (isset($option['value'])) {
                                            $choices[] = $option['value'];
                                        }

                                        $elements[] = $this->auto_field(
                                                $field,
                                                array(
                                                    'type' => 'e2pdf-radio',
                                                    'properties' => array(
                                                        'top' => '5',
                                                        'width' => 'auto',
                                                        'height' => 'auto',
                                                        'value' => $value,
                                                        'option' => isset($option['text']) ? $option['text'] : '',
                                                        'group' => $value,
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
                                                        'value' => isset($option['text']) ? $option['text'] : '',
                                                    ),
                                                )
                                        );
                                    }
                                }
                            }
                        } else {

                            if (isset($field->choices) && is_array($field->choices)) {

                                $value = isset($merged_tags[$field->id]) ? $merged_tags[$field->id] : '';
                                if ($field->enableChoiceValue && $value) {
                                    if (substr($value, -1) == '}') {
                                        $value = substr($value, 0, -1) . ':value}';
                                    }
                                }

                                $choices = array();
                                foreach ($field->choices as $opt_key => $option) {

                                    if (!$value && isset($field->inputs) && isset($field->inputs[$opt_key]['id'])) {
                                        $value = isset($merged_tags[$field->inputs[$opt_key]['id']]) ? $merged_tags[$field->inputs[$opt_key]['id']] : '';
                                        if ($field->enableChoiceValue && $value) {
                                            if (substr($value, -1) == '}') {
                                                $value = substr($value, 0, -1) . ':value}';
                                            }
                                        }
                                    }

                                    if (isset($option['value'])) {
                                        $choices[] = $option['value'];
                                    }

                                    $elements[] = $this->auto_field(
                                            $field,
                                            array(
                                                'type' => 'e2pdf-radio',
                                                'properties' => array(
                                                    'top' => '5',
                                                    'width' => 'auto',
                                                    'height' => 'auto',
                                                    'value' => $value,
                                                    'option' => isset($option['text']) ? $option['text'] : '',
                                                    'group' => $value,
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
                                                    'value' => isset($option['text']) ? $option['text'] : '',
                                                ),
                                            )
                                    );
                                }
                            }
                        }

                        break;
                    default:
                        /* Non-supported fields */
                        break;
                }
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

    /**
     * Verify if item and dataset exists
     * @return bool - item and dataset exists
     */
    public function verify() {
        if ($this->get('cached_form') && $this->get('cached_entry')) {
            if ($this->get('cached_entry')['form_id'] == $this->get('item')) {
                return true;
            }
        }
        return false;
    }

    /**
     * Create Form based on uploaded PDF
     * @param object $template - Template Object to work with
     * @param array $data - Settings to create labels/shortcodes
     * @return object - Mapped Template Object
     */
    public function auto_form($template, $data = array()) {

        if ($template->get('ID')) {

            $auto_form_label = isset($data['auto_form_label']) && $data['auto_form_label'] ? $data['auto_form_label'] : false;
            $auto_form_shortcode = isset($data['auto_form_shortcode']) ? true : false;

            if (class_exists('GFAPI')) {
                $confirmation_id = uniqid();
                $form = array(
                    'title' => $template->get('title'),
                    'fields' => array(
                    ),
                    'confirmations' => array(),
                );

                $form['confirmations'][$confirmation_id] = array(
                    'id' => $confirmation_id,
                    'name' => __('Default Confirmation', 'gravityforms'),
                    'isDefault' => true,
                    'type' => 'message',
                    /* translators: %d: E2Pdf Template ID */
                    'message' => sprintf(__('Thanks for contacting us! We will get in touch with you shortly. [e2pdf-download id="%d"]', 'e2pdf'), $template->get('ID')),
                    'url' => '',
                    'pageId' => '',
                    'queryString' => '',
                );

                $pages = $template->get('pages');
                $checkboxes = array();
                $radios = array();

                $field_id = 1;

                foreach ($pages as $page_key => $page) {
                    if (isset($page['elements']) && !empty($page['elements'])) {
                        foreach ($page['elements'] as $element_key => $element) {
                            $type = false;
                            $labels = array();
                            $label = '';

                            if ($auto_form_shortcode) {
                                $labels[] = $field_id;
                            }
                            if ($auto_form_label && $auto_form_label == 'value' && isset($element['value']) && $element['value']) {
                                $labels[] = $element['value'];
                            } elseif ($auto_form_label && $auto_form_label == 'name' && isset($element['name']) && $element['name']) {
                                $labels[] = $element['name'];
                            }

                            if ($element['type'] == 'e2pdf-input' || $element['type'] == 'e2pdf-signature') {
                                $type = 'text';
                                $label = !empty($labels) ? implode(' ', $labels) : 'Text';
                            } elseif ($element['type'] == 'e2pdf-textarea') {
                                $type = 'textarea';
                                $label = !empty($labels) ? implode(' ', $labels) : 'Textarea';
                            } elseif ($element['type'] == 'e2pdf-select') {
                                $type = 'select';
                                $label = !empty($labels) ? implode(' ', $labels) : 'Select';

                                $choices = array();
                                $field_options = array();

                                if (isset($element['properties']['options'])) {
                                    $field_options = explode("\n", $element['properties']['options']);
                                    foreach ($field_options as $option) {
                                        $choices[] = array(
                                            'text' => $option,
                                            'value' => $option,
                                        );
                                    }
                                }
                            } elseif ($element['type'] == 'e2pdf-checkbox') {
                                $field_key = array_search($element['name'], array_column($checkboxes, 'name'));
                                if ($field_key !== false) {
                                    $checkbox = array_search($checkboxes[$field_key]['id'], array_column($form['fields'], 'id'));
                                    if ($checkbox !== false) {
                                        $form['fields'][$checkbox]['choices'][] = array(
                                            'text' => $element['properties']['option'],
                                            'value' => $element['properties']['option'],
                                        );

                                        $num = count($form['fields'][$checkbox]['inputs']) + 1;
                                        $form['fields'][$checkbox]['inputs'][] = array(
                                            'id' => $field_id . '.' . $num,
                                            'label' => $element['properties']['option'],
                                        );
                                        $pages[$page_key]['elements'][$element_key]['value'] = '{' . $form['fields'][$checkbox]['label'] . ':' . $form['fields'][$checkbox]['id'] . '}';
                                    }
                                } else {
                                    $label = !empty($labels) ? implode(' ', $labels) : 'Checkbox';
                                    $type = 'checkbox';
                                    $checkboxes[] = array(
                                        'id' => $field_id,
                                        'name' => $element['name'],
                                    );
                                    $choices = array(
                                        array(
                                            'text' => $element['properties']['option'],
                                            'value' => $element['properties']['option'],
                                        ),
                                    );

                                    $inputs = array(
                                        array(
                                            'id' => $field_id . '.1',
                                            'label' => $element['properties']['option'],
                                        ),
                                    );
                                }
                            } elseif ($element['type'] == 'e2pdf-radio') {
                                if (isset($element['properties']['group']) && $element['properties']['group']) {
                                    $element['name'] = $element['properties']['group'];
                                } else {
                                    $element['name'] = $element['element_id'];
                                }

                                $field_key = array_search($element['name'], array_column($radios, 'name'));
                                if ($field_key !== false) {
                                    $radio = array_search($radios[$field_key]['id'], array_column($form['fields'], 'id'));
                                    if ($radio !== false) {
                                        $form['fields'][$radio]['choices'][] = array(
                                            'text' => $element['properties']['option'],
                                            'value' => $element['properties']['option'],
                                        );

                                        $pages[$page_key]['elements'][$element_key]['value'] = '{' . $form['fields'][$radio]['label'] . ':' . $form['fields'][$radio]['id'] . '}';
                                    }
                                } else {
                                    $label = !empty($labels) ? implode(' ', $labels) : 'Radio';
                                    $type = 'radio';
                                    $radios[] = array(
                                        'id' => $field_id,
                                        'name' => $element['name'],
                                    );
                                    $choices = array(
                                        array(
                                            'text' => $element['properties']['option'],
                                            'value' => $element['properties']['option'],
                                        ),
                                    );
                                }
                            }

                            if ($type) {
                                $field = array(
                                    'id' => $field_id,
                                    'type' => $type,
                                    'label' => $label,
                                );
                                if ($type == 'select' || $type == 'radio' || $type == 'checkbox') {
                                    $field['choices'] = $choices;
                                }

                                if ($type == 'checkbox') {
                                    $field['inputs'] = $inputs;
                                }

                                $form['fields'][] = $field;
                                $pages[$page_key]['elements'][$element_key]['value'] = '{' . $label . ':' . $field_id . '}';

                                if (isset($element['properties']['esig'])) {
                                    unset($pages[$page_key]['elements'][$element_key]['properties']['esig']);
                                }
                                $field_id++;
                            }
                        }
                    }
                }

                $item = GFAPI::add_form($form);
                if (!is_wp_error($item)) {
                    $template->set('item', $item);
                    $template->set('pages', $pages);
                }
            }
        }

        return $template;
    }

    /**
     * Init Visual Mapper data
     * @return bool|string - HTML data source for Visual Mapper
     */
    public function visual_mapper() {

        $item = $this->get('item');
        $html = '';
        $source = '';

        if ($item && function_exists('gravity_form')) {
            ob_start();
            $form = false;
            if (class_exists('GFFormsModel')) {
                $form = GFFormsModel::get_form_meta($item);
            }
            if ($form) {
                add_filter('gform_pre_render', array($this, 'filter_gform_pre_render'), 30);
                $source = gravity_form($item, true, true, false, null, false, 0, false);
                remove_filter('gform_pre_render', array($this, 'filter_gform_pre_render'), 30);
                if ($source) {
                    libxml_use_internal_errors(true);
                    $dom = new DOMDocument();
                    if ($this->get('nested')) {
                        $source = str_replace(array('<form', '</form>'), array('<div', '</div>'), $source);
                    }
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
            }
            if (ob_get_length() > 0) {
                while (@ob_end_clean()); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
            }
            if (!$source) {
                return '<div class="e2pdf-vm-error">' . __("The form source is empty or doesn't exist", 'e2pdf') . '</div>';
            } elseif (!$html) {
                return '<div class="e2pdf-vm-error">' . __('The form could not be parsed due the incorrect HTML', 'e2pdf') . '</div>';
            } else {

                $merged_tags = array();
                if (class_exists('GFCommon')) {
                    foreach ($form['fields'] as $field) {
                        $tags = GFCommon::get_field_merge_tags($field);
                        foreach ($tags as $tag) {
                            if (isset($tag['tag'])) {
                                if ($field->type == 'list') {
                                    $field_id = preg_replace('/\{(?:.*)\:(.*)\:\}/', '${1}', $tag['tag']);
                                } else {
                                    $field_id = preg_replace('/\{(?:.*)\:(.*)\}/', '${1}', $tag['tag']);
                                }
                                if ($field_id) {
                                    if ($this->get('nested')) {
                                        $merged_tags[$field_id] = $this->get('nested');
                                    } else {
                                        $merged_tags[$field_id] = $tag['tag'];
                                    }
                                }
                            }
                        }
                    }
                }

                $xml = new Helper_E2pdf_Xml();
                $xml->set('dom', $dom);
                $xpath = new DomXPath($dom);

                $remove_by_class = array(
                    'gf_progressbar_wrapper',
                    'gform_previous_button',
                    'gform_next_button',
                    'gform_button button',
                );

                if ($this->get('nested')) {
                    $remove_by_class[] = 'gform_heading';
                }

                foreach ($remove_by_class as $key => $class) {
                    $elements = $xpath->query("//*[contains(@class, '{$class}')]");
                    foreach ($elements as $element) {
                        $element->parentNode->removeChild($element);
                    }
                }

                $remove_by_tag = array(
                    'script',
                );
                foreach ($remove_by_tag as $key => $tag) {
                    $elements = $xpath->query('//' . $tag);
                    foreach ($elements as $element) {
                        $element->parentNode->removeChild($element);
                    }
                }

                /* Replace nested forms */
                $elements = $xpath->query("//*[contains(@class, 'gpnf-nested-entries-container')]");
                foreach ($elements as $element) {
                    $button = $xpath->query('./*[@data-nestedformid]', $element)->item(0);
                    $field = $xpath->query("following-sibling::input[@type='hidden']", $element)->item(0);
                    if ($button && $field) {
                        $form_id = $xml->get_node_value($button, 'data-nestedformid');
                        if ($form_id) {
                            $this->set('item', $form_id);
                        }
                        if ($field->attributes->getNamedItem('name')) {
                            $field_id = preg_replace('/input_([^\[]+)(?:.*)/', '${1}', $xml->get_node_value($field, 'name'));
                            if ($field_id) {
                                $value = isset($merged_tags[$field_id]) ? $merged_tags[$field_id] : '';
                                if (isset($merged_tags[$field_id])) {
                                    $this->set('nested', $value);
                                }
                            }
                        }

                        $nested_form = $this->visual_mapper();
                        if ($nested_form) {
                            $element->parentNode->replaceChild($dom->importNode($nested_form->documentElement, true), $element);
                        }
                        $this->set('item', $item);
                        $this->set('nested', '');
                    }
                }

                /* Replace time fields */
                $elements = $xpath->query("//*[contains(@class, 'gfield_time_hour')]");
                foreach ($elements as $element) {

                    $sub_elements = $xpath->query('.//*[self::input or self::select]', $element->parentNode);
                    foreach ($sub_elements as $sub_element) {
                        $xml->set_node_value($sub_element, 'class', $xml->get_node_value($sub_element, 'class') . ' e2pdf-no-vm');
                    }

                    $xml->set_node_value($element, 'class', $xml->get_node_value($element->parentNode, 'class') . ' e2pdf-vm-field-wrapper', true);

                    $input = $xpath->query('./input', $element)->item(0);
                    $field = $dom->createElement('input');

                    $xml->set_node_value($field, 'type', 'text');
                    $xml->set_node_value($field, 'class', 'e2pdf-vm-field');
                    $xml->set_node_value($field, 'name', $xml->get_node_value($input, 'name'));

                    $element->parentNode->appendChild($field);
                }

                /* Replace multiupload field */
                $elements = $xpath->query("//*[contains(@class, 'gform_drop_area')]");
                foreach ($elements as $element) {

                    $xml->set_node_value($element, 'class', $xml->get_node_value($element, 'class') . ' e2pdf-vm-field-wrapper', true);

                    $field_id = preg_replace('/(?:.*)_([\d]+)/', '${1}', $xml->get_node_value($element, 'id'));

                    $field = $dom->createElement('input');
                    $xml->set_node_value($field, 'type', 'text');
                    $xml->set_node_value($field, 'class', 'e2pdf-vm-field');
                    $xml->set_node_value($field, 'name', 'input_' . $field_id);

                    $element->appendChild($field);
                }

                /* Replace single product */
                $elements = $xpath->query("//*[contains(@class, 'ginput_container_singleproduct') or contains(@class, 'ginput_container_product_calculation') or contains(@class, 'ginput_container_singleshipping') or contains(@class, 'ginput_container_total') or contains(@class, 'gfield_signature_container')]");
                foreach ($elements as $element) {
                    $spans = $xpath->query('.//span', $element);
                    foreach ($spans as $key => $sub_element) {
                        $sub_element->parentNode->removeChild($sub_element);
                    }

                    $inputs = $xpath->query('.//input', $element);
                    foreach ($inputs as $key => $sub_element) {
                        $xml->set_node_value($sub_element, 'type', 'text');
                        $xml->set_node_value($sub_element, 'class', '');
                    }
                }

                $fields = array();
                foreach ($form['fields'] as $field) {
                    $fields[$field->id] = $field;
                }

                $elements = $xpath->query("//*[contains(@class, 'gsurvey-likert-choice')]");
                foreach ($elements as $element) {
                    $label = $xml->get_node_value($element, 'data-label');
                    $sub_element = $xpath->query('.//input', $element)->item(0);
                    if ($label && $sub_element) {
                        $xml->set_node_value($sub_element, 'value', $label);
                    }
                }

                $elements = $xpath->query("//*[contains(@class, 'gsurvey-rating')]");
                foreach ($elements as $element) {
                    $inputs = $xpath->query('.//input', $element);
                    foreach ($inputs as $key => $sub_element) {
                        $label = $xpath->query('.//following-sibling::label[1]', $sub_element)->item(0);
                        if ($label) {
                            $title = $xml->get_node_value($label, 'title');
                            $xml->set_node_value($sub_element, 'value', $title);
                        }
                    }
                }

                $elements = $xpath->query("//*[contains(@class, 'gsurvey-survey-field')]//select");
                foreach ($elements as $element) {
                    $inputs = $xpath->query('.//option', $element);
                    foreach ($inputs as $key => $sub_element) {
                        $label = $sub_element->nodeValue;
                        if ($label) {
                            $xml->set_node_value($sub_element, 'value', $label);
                        }
                    }
                }

                $elements = $xpath->query("//*[contains(@class, 'ginput_container_rank')]//input");
                foreach ($elements as $element) {
                    $xml->set_node_value($element, 'type', 'text');
                }

                $replace_by_types = array(
                    '//input',
                    '//textarea',
                    '//select',
                );

                foreach ($replace_by_types as $replace_by_type) {
                    $inputs = $xpath->query($replace_by_type);
                    foreach ($inputs as $element) {
                        if ($element->attributes->getNamedItem('name')) {

                            $field_id = false;
                            $field = false;

                            $sub_field_id = preg_replace('/input_([^\[]+)(?:.*)/', '${1}', $xml->get_node_value($element, 'name'));

                            if ($sub_field_id) {
                                if (substr($sub_field_id, -6) == '_valid') {
                                    $field_id = preg_replace('/(?:.*)\_([\d]+)\_valid/', '${1}', $sub_field_id);
                                } else {
                                    $field_id = preg_replace('/([\d]+)\.(?:.*)/', '${1}', $sub_field_id);
                                }
                                if (isset($fields[$field_id])) {
                                    $field = $fields[$field_id];
                                }
                            }

                            if ($field) {
                                if (
                                        $field->type == 'name' ||
                                        $field->type == 'address' ||
                                        (
                                        $field->type == 'product' &&
                                        $field->inputType &&
                                        ($field->inputType == 'singleproduct' || $field->inputType == 'calculation')
                                        )
                                ) {
                                    $value = $merged_tags[$sub_field_id];
                                    if ($this->get('nested')) {
                                        if (substr($value, -1) == '}') {
                                            $value = substr($value, 0, -1) . ':filter[' . $sub_field_id . '],index[0]}';
                                        }
                                    }
                                    $xml->set_node_value($element, 'name', $value);
                                } elseif ($field->type == 'consent') {
                                    $value = isset($merged_tags[$field_id . '.1']) ? $merged_tags[$field_id . '.1'] : '';
                                    if ($this->get('nested')) {
                                        if (substr($value, -1) == '}') {
                                            $value = substr($value, 0, -1) . ':filter[' . $field_id . '.1],index[0]}';
                                        }
                                    }
                                    $xml->set_node_value($element, 'name', $value);
                                } elseif ($field->type == 'survey') {
                                    $value = isset($merged_tags[$sub_field_id]) ? $merged_tags[$sub_field_id] : '';

                                    if (isset($field['inputType']) &&
                                            $field['inputType'] != 'text' &&
                                            $field['inputType'] != 'textarea' &&
                                            $field['inputType'] != 'select' &&
                                            $field['inputType'] != 'rank') {
                                        if (isset($field['enableChoiceValue'])) {
                                            $value = substr($value, 0, -1) . ':value}';
                                        }
                                    }
                                    $xml->set_node_value($element, 'name', $value);
                                } else {
                                    if (isset($merged_tags[$field_id])) {
                                        $value = $merged_tags[$field_id];

                                        if (substr($value, -1) == '}') {
                                            if (false !== strpos($xml->get_node_value($element->parentNode, 'class'), 'ginput_post_image_')) {
                                                if (false !== strpos($xml->get_node_value($element->parentNode, 'class'), 'ginput_post_image_title')) {
                                                    if ($this->get('nested')) {
                                                        $value = substr($value, 0, -1) . ':title,filter[' . $field_id . '],index[0]}';
                                                    } else {
                                                        $value = substr($value, 0, -1) . ':title}';
                                                    }
                                                } elseif (false !== strpos($xml->get_node_value($element->parentNode, 'class'), 'ginput_post_image_caption')) {
                                                    if ($this->get('nested')) {
                                                        $value = substr($value, 0, -1) . ':caption,filter[' . $field_id . '],index[0]}';
                                                    } else {
                                                        $value = substr($value, 0, -1) . ':caption}';
                                                    }
                                                } elseif (false !== strpos($xml->get_node_value($element->parentNode, 'class'), 'ginput_post_image_description')) {
                                                    if ($this->get('nested')) {
                                                        $value = substr($value, 0, -1) . ':description,filter[' . $field_id . '],index[0]}';
                                                    } else {
                                                        $value = substr($value, 0, -1) . ':description}';
                                                    }
                                                }
                                            } elseif ($field->type == 'list') {
                                                if ($field->enableColumns) {
                                                    $parent_class = $xml->get_node_value($element->parentNode, 'class');
                                                    $index = 1;
                                                    $td = $xpath->query("./td[contains(@class, '{$parent_class}')]/preceding-sibling::td", $element->parentNode->parentNode);
                                                    if ($td && isset($td->length)) {
                                                        $index = (int) $td->length + 1;
                                                    }
                                                    if ($this->get('nested')) {
                                                        $value = substr($value, 0, -1) . ':value,filter[' . $field_id . ':1_' . $index . '],index[0]}';
                                                    } else {
                                                        $value = substr($value, 0, -1) . '1_' . $index . '}';
                                                    }
                                                } else {
                                                    if ($this->get('nested')) {
                                                        $value = substr($value, 0, -1) . ':filter[' . $field_id . ':1],index[0]}';
                                                    } else {
                                                        $value = substr($value, 0, -1) . '1}';
                                                    }
                                                }
                                            } elseif ($field->enableChoiceValue && $value) {
                                                if ($this->get('nested')) {
                                                    $value = substr($value, 0, -1) . ':value,filter[' . $field_id . '],index[0]}';
                                                } else {
                                                    $value = substr($value, 0, -1) . ':value}';
                                                }
                                            } else {
                                                if ($this->get('nested')) {
                                                    $value = substr($value, 0, -1) . ':filter[' . $field_id . '],index[0]}';
                                                }
                                            }
                                        }
                                        $xml->set_node_value($element, 'name', $value);
                                    }
                                }
                            }
                        }
                    }
                }

                /* Replace other choice */
                $elements = $xpath->query("//*[@value='gf_other_choice']/parent::*");
                foreach ($elements as $element) {
                    $radio = $xpath->query(".//input[@type='radio']", $element)->item(0);
                    $input = $xpath->query(".//input[@type='text']", $element)->item(0);
                    if ($radio && $input) {
                        $xml->set_node_value($input, 'name', $xml->get_node_value($radio, 'name'));
                    }
                }

                if ($this->get('nested')) {
                    return $dom;
                } else {
                    if (defined('LIBXML_HTML_NOIMPLIED') && defined('LIBXML_HTML_NODEFDTD')) {
                        return str_replace(array('<html>', '</html>'), '', $dom->saveHTML());
                    } else {
                        return $dom->saveHTML();
                    }
                }
            }
        }

        return false;
    }

    /**
     * Convert Field name to Value
     * @since 0.01.34
     * @param string $name - Field name
     * @return bool|string - Converted value or false
     */
    public function auto_map($name = false) {

        $item = $this->get('item');

        $merged_tags = array();
        $form = false;
        $sub_field_id = false;

        if ($name) {
            $sub_field_id = preg_replace('/\{(?:.*)\:(.*)\:\}/', '${1}', $name);
        }

        if ($sub_field_id) {
            if (class_exists('GFFormsModel')) {
                $form = GFFormsModel::get_form_meta($item);
            }

            if ($form) {
                if (class_exists('GFCommon')) {
                    foreach ($form['fields'] as $field) {
                        $tags = GFCommon::get_field_merge_tags($field);
                        foreach ($tags as $tag) {
                            if (isset($tag['tag'])) {
                                if ($field->type == 'list') {
                                    $field_id = preg_replace('/\{(?:.*)\:(.*)\:\}/', '${1}', $tag['tag']);
                                } else {
                                    $field_id = preg_replace('/\{(?:.*)\:(.*)\}/', '${1}', $tag['tag']);
                                }
                                if ($field_id) {
                                    $merged_tags[$field_id] = $tag['tag'];
                                }
                            }
                        }
                    }
                }

                if (isset($merged_tags[$sub_field_id])) {
                    return $merged_tags[$sub_field_id];
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

    public function filter_gform_entry_field_value($display_value, $field, $lead, $form) {
        if (isset($field->defaultValue) && (false !== strpos($field->defaultValue, '[e2pdf-download') || false !== strpos($field->defaultValue, '[e2pdf-save'))) {
            $display_value = wp_specialchars_decode($display_value, ENT_QUOTES);
        }
        return $display_value;
    }

    public function filter_gform_merge_tag_filter($value, $merge_tag, $modifier, $field, $raw_value) {
        if (isset($field->defaultValue) && (false !== strpos($field->defaultValue, '[e2pdf-download') || false !== strpos($field->defaultValue, '[e2pdf-save'))) {
            return $raw_value;
        }
        return $value;
    }

    public function filter_gform_entries_field_value($value, $form_id, $field_id, $entry) {
        $field = GFAPI::get_field($form_id, $field_id);
        if (isset($field->defaultValue) && (false !== strpos($field->defaultValue, '[e2pdf-download') || false !== strpos($field->defaultValue, '[e2pdf-save'))) {
            $value = wp_specialchars_decode($value, ENT_QUOTES);
        }
        return $value;
    }

    public function filter_gp_template_paths($file_paths, $gp_template) {
        $template_dir = $gp_template->get_theme_template_dir_name();
        $e2pdf_file_paths = array(
            -100 => trailingslashit(get_stylesheet_directory()) . $template_dir . 'e2pdf/',
            -99 => trailingslashit(get_template_directory()) . $template_dir . 'e2pdf/',
        );
        $file_paths = $file_paths + $e2pdf_file_paths;
        return $file_paths;
    }

    public function filter_gform_confirmation($content, $form, $entry, $ajax) {
        return $this->filter_content($content, isset($entry['id']) ? $entry['id'] : 0, false);
    }

    public function filter_gform_twilio_message($notification, $feed, $entry, $form) {
        $content = isset($notification['body']) && $notification['body'] ? $notification['body'] : '';
        if (false === strpos($content, '[')) {
            return $notification;
        }

        $shortcode_tags = array(
            'e2pdf-download',
            'e2pdf-view',
        );
        preg_match_all('@\[([^<>&/\[\]\x00-\x20=]++)@', $content, $matches);
        $tagnames = array_intersect($shortcode_tags, $matches[1]);
        if (!empty($tagnames)) {
            preg_match_all('/' . $this->helper->load('shortcode')->get_shortcode_regex($tagnames) . '/', $content, $shortcodes);
            foreach ($shortcodes[0] as $key => $shortcode_value) {
                $shortcode = $this->helper->load('shortcode')->get_shortcode($shortcodes, $key);
                $atts = shortcode_parse_atts($shortcode[3]);
                if (!isset($atts['dataset']) && isset($atts['id'])) {
                    $template = new Model_E2pdf_Template();
                    $template->load($atts['id']);
                    if ($template->get('extension') === 'gravity') {
                        $entry_id = $entry && isset($entry['id']) ? $entry['id'] : false;
                        if ($entry_id) {
                            $atts['dataset'] = $entry_id;
                            $shortcode[3] .= ' dataset="' . $entry_id . '"';
                        }
                    }
                }
                if (!isset($atts['apply'])) {
                    $shortcode[3] .= ' apply="true"';
                }
                if (!isset($atts['filter'])) {
                    $shortcode[3] .= ' filter="true"';
                }
                if ($shortcode[2] === 'e2pdf-download' || ($shortcode[2] === 'e2pdf-view' && isset($atts['output']) && $atts['output'] == 'url')) {
                    if (class_exists('GFCommon')) {
                        $shortcode[3] = GFCommon::replace_variables($shortcode[3], $form, $entry, false, false, false, 'text');
                    }
                    $notification['body'] = str_replace($shortcode_value, do_shortcode_tag($shortcode), $notification['body']);
                }
            }
        }
        return $notification;
    }

    public function filter_gform_entry_post_save($entry, $form) {
        if (isset($form['fields'])) {
            foreach ($form['fields'] as $key => $field) {
                if (isset($field->defaultValue) && (false !== strpos($field->defaultValue, '[e2pdf-download') || false !== strpos($field->defaultValue, '[e2pdf-save'))) {
                    $value = $this->filter_content($field->defaultValue, isset($entry['id']) ? $entry['id'] : 0, true);
                    $entry[$field['id']] = $value;
                    GFAPI::update_entry_field($entry['id'], $field['id'], $value);
                }
            }
        }
        return $entry;
    }

    public function filter_gform_notification($notification, $form, $entry) {

        $content = isset($notification['message']) && $notification['message'] ? $notification['message'] : '';

        if (false === strpos($content, '[')) {
            return $notification;
        }

        /* "Conditional Shortcode" fix since 1.13.18 */
        $shortcode_tags = array(
            'gravityforms',
        );
        preg_match_all('@\[([^<>&/\[\]\x00-\x20=]++)@', $content, $matches);
        $tagnames = array_intersect($shortcode_tags, $matches[1]);
        if (!empty($tagnames)) {
            preg_match_all('/' . $this->helper->load('shortcode')->get_shortcode_regex($tagnames) . '/', $content, $shortcodes);
            foreach ($shortcodes[0] as $key => $shortcode_value) {
                $shortcode = $this->helper->load('shortcode')->get_shortcode($shortcodes, $key);
                $conditional_shortcode_tags = array(
                    'e2pdf-download',
                    'e2pdf-save',
                    'e2pdf-view',
                    'e2pdf-adobesign',
                    'e2pdf-attachment',
                    'e2pdf-zapier',
                );
                preg_match_all('@\[([^<>&/\[\]\x00-\x20=]++)@', $shortcode[5], $conditional_matches);
                $conditional_tagnames = array_intersect($conditional_shortcode_tags, $conditional_matches[1]);
                $atts = shortcode_parse_atts($shortcode[3]);
                if (isset($atts['action']) && $atts['action'] == 'conditional' && !empty($conditional_tagnames)) {
                    $shortcode[5] = '1';
                    if (class_exists('GFCommon')) {
                        $shortcode[3] = GFCommon::replace_variables($shortcode[3], $form, $entry, false, false, false, 'text');
                    }
                    $value = do_shortcode_tag($shortcode);
                    if ($value !== '1') {
                        $content = str_replace($shortcode_value, '', $content);
                    }
                }
            }
        }

        $shortcode_tags = array(
            'e2pdf-download',
            'e2pdf-save',
            'e2pdf-view',
            'e2pdf-adobesign',
            'e2pdf-attachment',
            'e2pdf-zapier',
        );
        preg_match_all('@\[([^<>&/\[\]\x00-\x20=]++)@', $content, $matches);
        $tagnames = array_intersect($shortcode_tags, $matches[1]);
        if (!empty($tagnames)) {
            preg_match_all('/' . $this->helper->load('shortcode')->get_shortcode_regex($tagnames) . '/', $content, $shortcodes);
            foreach ($shortcodes[0] as $key => $shortcode_value) {
                $shortcode = $this->helper->load('shortcode')->get_shortcode($shortcodes, $key);
                $atts = shortcode_parse_atts($shortcode[3]);
                if (!isset($atts['dataset']) && isset($atts['id'])) {
                    $template = new Model_E2pdf_Template();
                    $template->load($atts['id']);
                    if ($template->get('extension') === 'gravity') {
                        $entry_id = $entry && isset($entry['id']) ? $entry['id'] : false;
                        if ($entry_id) {
                            $atts['dataset'] = $entry_id;
                            $shortcode[3] .= ' dataset="' . $entry_id . '"';
                        }
                    }
                }
                if (!isset($atts['apply'])) {
                    $shortcode[3] .= ' apply="true"';
                }
                if (!isset($atts['filter'])) {
                    $shortcode[3] .= ' filter="true"';
                }
                $file = false;
                if ($this->helper->load('shortcode')->is_attachment($shortcode, $atts)) {
                    if (class_exists('GFCommon')) {
                        add_filter('gform_merge_tag_filter', array($this, 'filter_gform_merge_tag_filter_attributes'), 30, 5);
                        $shortcode[3] = GFCommon::replace_variables($shortcode[3], $form, $entry, false, false, false, 'text');
                        remove_filter('gform_merge_tag_filter', array($this, 'filter_gform_merge_tag_filter_attributes'), 30);
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
                                $this->helper->add('gravity_attachments', $file);
                            }
                        } else {
                            $this->helper->add('gravity_attachments', $file);
                        }
                        $notification['attachments'] = (is_array(rgget('attachments', $notification))) ? rgget('attachments', $notification) : array();
                        $notification['attachments'][] = $file;
                    }
                    $notification['message'] = str_replace($shortcode_value, '', $notification['message']);
                } else {
                    $notification['message'] = str_replace($shortcode_value, '[' . $shortcode[2] . $shortcode[3] . ']', $notification['message']);
                }
            }
        }
        return $notification;
    }

    public function filter_content($content = '', $entry_id = 0, $do_shortcode = false) {
        if (is_array($content) || false === strpos($content, '[')) {
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
                if ($this->helper->load('shortcode')->is_attachment($shortcode, $atts)) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
                } else {
                    if (!isset($atts['dataset']) && isset($atts['id'])) {
                        $template = new Model_E2pdf_Template();
                        $template->load($atts['id']);
                        if ($template->get('extension') === 'gravity') {
                            if ($entry_id) {
                                $atts['dataset'] = $entry_id;
                                $shortcode[3] .= ' dataset="' . $entry_id . '"';
                            }
                        }
                    }
                    if (!isset($atts['apply'])) {
                        $shortcode[3] .= ' apply="true"';
                    }
                    if (!isset($atts['filter'])) {
                        $shortcode[3] .= ' filter="true"';
                    }
                    if ($do_shortcode) {
                        $content = str_replace($shortcode_value, do_shortcode_tag($shortcode), $content);
                    } else {
                        $content = str_replace($shortcode_value, '[' . $shortcode[2] . $shortcode[3] . ']', $content);
                    }
                }
            }
        }
        return $content;
    }

    public function filter_gform_pre_render($form) {
        if (isset($form['fields'])) {
            foreach ($form['fields'] as $key => $field) {
                if ($field->pageNumber != '1') {
                    $field->pageNumber = '1';
                    $form['fields'][$key] = $field;
                }
            }
        }
        return $form;
    }

    public function filter_gform_merge_tag_filter_attributes($value, $merge_tag, $modifier, $field, $raw_value) {
        if ($value) {
            return esc_attr($value);
        }
        return $value;
    }

    public function filter_gform_merge_tag_filter_tags($value, $merge_tag, $modifier, $field, $raw_value) {

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

    /**
     * Delete attachments that were sent by email
     */
    public function action_gform_after_email($is_success) {

        $files = $this->helper->get('gravity_attachments');
        if (is_array($files) && !empty($files)) {
            foreach ($files as $key => $file) {
                $this->helper->delete_dir(dirname($file) . '/');
            }
            $this->helper->deset('gravity_attachments');
        }
    }

    public function action_gform_after_update_entry($form, $entry_id) {
        if (isset($form['fields'])) {
            foreach ($form['fields'] as $key => $field) {
                if (isset($field->defaultValue) && (false !== strpos($field->defaultValue, '[e2pdf-download') || false !== strpos($field->defaultValue, '[e2pdf-save'))) {
                    $entry = GFFormsModel::get_entry($entry_id);
                    if ($entry && !is_wp_error($entry) && is_array($entry)) {
                        if (!rgar($entry, $field['id'])) {
                            $value = $this->filter_content($field->defaultValue, $entry_id, true);
                            $entry[$field['id']] = $value;
                            GFAPI::update_entry_field($entry['id'], $field['id'], $value);
                        }
                    }
                }
            }
        }
    }

    /**
     * Get styles for generating Map Field function
     * @return array - List of css files to load
     */
    public function styles($item_id = false) {
        $styles = array();
        if (class_exists('GFCommon') && class_exists('GFForms')) {
            $base_url = GFCommon::get_base_url();
            $base_path = GFCommon::get_base_path();
            $version = GFForms::$version;
            $min = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
            if (file_exists($base_path . '/legacy/css/')) {
                $styles[] = $base_url . '/legacy/css/formreset' . $min . '.css?ver=' . $version;
                $styles[] = $base_url . '/legacy/css/datepicker' . $min . '.css?ver=' . $version;
                $styles[] = $base_url . '/legacy/css/formsmain' . $min . '.css?ver=' . $version;
                $styles[] = $base_url . '/legacy/css/readyclass' . $min . '.css?ver=' . $version;
                $styles[] = $base_url . '/legacy/css/browsers' . $min . '.css?ver=' . $version;
                $styles[] = $base_url . '/legacy/css/rtl' . $min . '.css?ver=' . $version;
            } else {
                $styles[] = $base_url . '/css/formreset' . $min . '.css?ver=' . $version;
                $styles[] = $base_url . '/css/datepicker' . $min . '.css?ver=' . $version;
                $styles[] = $base_url . '/css/formsmain' . $min . '.css?ver=' . $version;
                $styles[] = $base_url . '/css/readyclass' . $min . '.css?ver=' . $version;
                $styles[] = $base_url . '/css/browsers' . $min . '.css?ver=' . $version;
                $styles[] = $base_url . '/css/rtl' . $min . '.css?ver=' . $version;
            }
            $styles[] = plugins_url('css/extension/gravity.css?v=' . time(), $this->helper->get('plugin_file_path'));
        }
        return $styles;
    }

    public function hook_gravity_entry_view($metaboxes, $entry, $form) {
        if (!empty($form['id'])) {
            $hooks = $this->helper->load('hooks')->get('gravity', 'hook_gravity_entry_view', $form['id']);
            if (!empty($hooks)) {
                $metaboxes['e2pdf'] = array(
                    'title' => apply_filters('e2pdf_hook_section_title', __('E2Pdf Actions', 'e2pdf'), 'hook_gravity_entry_view'),
                    'callback' => array($this, 'hook_gravity_entry_view_callback'),
                    'context' => 'side',
                    'callback_args' => array(
                        'entry' => $entry,
                        'hooks' => $hooks,
                    ),
                );
            }
        }
        return $metaboxes;
    }

    public function hook_gravity_entry_view_callback($post, $metabox) {
        if (!empty($metabox['args']['entry']['id'])) {
            foreach ($metabox['args']['hooks'] as $hook) {
                $action = apply_filters('e2pdf_hook_action_button',
                        array(
                            'html' => '<div class="misc-pub-section"><a class="e2pdf-download-hook" target="_blank" title="%2$s" href="%1$s"><span class="dashicons dashicons-pdf"></span> %2$s</a></div>',
                            'url' => $this->helper->get_url(
                                    array(
                                        'page' => 'e2pdf',
                                        'action' => 'export',
                                        'id' => $hook,
                                        'dataset' => $metabox['args']['entry']['id'],
                                    ), 'admin.php?'
                            ),
                            'title' => 'PDF #' . $hook
                        ), 'hook_gravity_entry_view', $hook, $metabox['args']['entry']['id']
                );
                if (!empty($action)) {
                    echo sprintf(
                            $action['html'], $action['url'], $action['title']
                    );
                }
            }
        }
    }

    public function hook_gravity_row_actions($form_id, $field_id, $value, $entry) {
        if (!empty($entry['form_id']) && !empty($entry['id'])) {
            $hooks = $this->helper->load('hooks')->get('gravity', 'hook_gravity_row_actions', $entry['form_id']);
            foreach ($hooks as $hook) {
                $action = apply_filters('e2pdf_hook_action_button',
                        array(
                            'html' => '<span> | <a class="e2pdf-download-hook" target="_blank" href="%s">%s</a></span>',
                            'url' => $this->helper->get_url(
                                    array(
                                        'page' => 'e2pdf',
                                        'action' => 'export',
                                        'id' => $hook,
                                        'dataset' => $entry['id'],
                                    ), 'admin.php?'
                            ),
                            'title' => 'PDF #' . $hook
                        ), 'hook_gravity_row_actions', $hook, $entry['id']
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
