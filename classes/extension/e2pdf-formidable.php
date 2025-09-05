<?php

/**
 * E2Pdf Formidable Extension
 * @copyright  Copyright 2017 https://e2pdf.com
 * @license    GPLv3
 * @version    1
 * @link       https://e2pdf.com
 * @since      0.00.01
 */
if (!defined('ABSPATH')) {
    die('Access denied.');
}

class Extension_E2pdf_Formidable extends Model_E2pdf_Model {

    private $options;
    private $info = array(
        'key' => 'formidable',
        'title' => 'Formidable Forms',
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
        if (defined('E2PDF_FORMIDABLE_EXTENSION') || $this->helper->load('extension')->is_plugin_active('formidable/formidable.php')) {
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
                if ($this->get('item') && $this->get('item') != '-2' && class_exists('FrmForm')) {
                    $form = FrmForm::getOne($this->get('item'));
                    if (isset($form->id)) {
                        $this->set('cached_form', $form);
                    }
                }
                break;
            case 'item1':
                $this->set('cached_form', false);
                if ($this->get('item1') && class_exists('FrmForm')) {
                    $form = FrmForm::getOne($this->get('item1'));
                    if (isset($form->id)) {
                        $this->set('cached_form', $form);
                    }
                }
                break;
            case 'item2':
                $this->set('cached_form2', false);
                if ($this->get('item2') && class_exists('FrmForm')) {
                    $form = FrmForm::getOne($this->get('item2'));
                    if (isset($form->id)) {
                        $this->set('cached_form2', $form);
                    }
                }
                break;
            case 'dataset':
                $this->set('cached_entry', false);
                if ($this->get('cached_form') && $this->get('dataset') && class_exists('FrmEntry')) {
                    if (substr($this->get('dataset'), 0, 4) === 'tmp_') {
                        $this->set('cached_entry', get_transient('e2pdf_' . $this->get('dataset')));
                    } else {
                        $entry = FrmEntry::getOne($this->get('dataset'));
                        if ($entry && isset($entry->form_id) && $entry->form_id == $this->get('cached_form')->id) {
                            $this->set('cached_entry', $entry);
                        }
                    }
                }
                break;
            case 'dataset2':
                if ($this->get('cached_form2') && $this->get('dataset2') && class_exists('FrmEntry')) {
                    $entry = FrmEntry::getOne($this->get('dataset2'));
                    if ($entry && isset($entry->form_id) && $entry->form_id == $this->get('cached_form2')->id) {
                        $this->set('cached_entry2', $entry);
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
        if (class_exists('FrmForm')) {
            $where = array(
                'is_template' => 0,
                'status' => 'published',
            );
            $forms = FrmForm::getAll($where, 'name');
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
     * @param int $item - Form ID
     * @param string $name - Entries names
     * @return array() - Entries list
     */
    public function datasets($item_id = false, $name = false) {
        $item_id = (int) $item_id;
        $datasets = array();
        if (class_exists('FrmEntry') && $item_id) {
            $where = array(
                'it.form_id' => $item_id,
            );
            $entries = FrmEntry::getAll($where, ' ORDER BY id DESC');
            if ($entries) {
                $this->set('item', $item_id);
                if (class_exists('FrmFieldsHelper') && class_exists('FrmFormsController') && method_exists('FrmFormsController', 'replace_form_name_shortcodes')) {
                    $this->set('cached_shortcodes', FrmFieldsHelper::get_shortcodes($name, $item_id));
                }
                foreach ($entries as $key => $entry) {
                    $this->set('dataset', $entry->id);
                    $entry_title = $this->render($name);
                    if (!$entry_title) {
                        $entry_title = $entry->item_key;
                    }
                    $datasets[] = array(
                        'key' => $entry->id,
                        'value' => $entry_title,
                    );
                }
                $this->set('cached_shortcodes', false);
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
                    'page' => 'formidable-entries',
                    'frm_action' => 'show',
                    'id' => $dataset_id,
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
        if (class_exists('FrmForm')) {
            $form = FrmForm::getOne($item_id);
        }
        $item = new stdClass();
        if ($form) {
            $item->id = (string) $item_id;
            $item->url = $this->helper->get_url(
                    array(
                        'page' => 'formidable',
                        'frm_action' => 'edit',
                        'id' => $item_id,
                    )
            );
            $item->name = esc_html('' === $form->name ? __('(no title)', 'formidable') : $form->name . ($form->parent_form_id ? __(' (child)', 'formidable') : ''));
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
        add_action('frm_notification', array($this, 'action_frm_notification'), 30, 3);
        add_action('check_ajax_referer', array($this, 'action_check_ajax_referer'), 10, 2);
        add_action('frm_after_create_entry', array($this, 'action_frm_default_value'), 0, 2);
        add_action('frm_after_update_entry', array($this, 'action_frm_default_value'), 0, 2);

        /* Hooks */
        add_action('frm_show_entry_sidebar', array($this, 'hook_formidable_entry_view'));
        add_action('frm_edit_entry_sidebar', array($this, 'hook_formidable_entry_edit'));
    }

    /**
     * Load filters for this extension
     */
    public function load_filters() {

        if (!get_option('e2pdf_formidable_disable_filter', '0')) {
            add_filter('frm_display_entry_content', array(new Model_E2pdf_Filter(), 'pre_filter'), 9);
            add_filter('frm_display_entry_content', array(new Model_E2pdf_Filter(), 'filter'), 25);
            add_filter('frm_display_entry_content', array($this, 'filter_frm_display_entry_content'), 30);
            add_filter('frm_content', array(new Model_E2pdf_Filter(), 'pre_filter'));
            add_filter('frm_content', array(new Model_E2pdf_Filter(), 'filter'), 25);
        }

        add_filter('frm_content', array($this, 'filter_frm_content'), 30, 3);
        add_filter('frm_image_html_array', array($this, 'filter_frm_image_html_array'), 10, 2);
        add_filter('frm_notification_attachment', array($this, 'filter_frm_notification_attachment'), 30, 3);
        add_filter('e2pdf_model_options_get_options_options', array($this, 'filter_e2pdf_model_options_get_options_options'));

        /* Replace shortcodes on Backup */
        add_filter('e2pdf_controller_templates_backup_options', array($this, 'filter_e2pdf_controller_templates_backup_options'), 10, 3);
        add_filter('e2pdf_controller_templates_backup_pages', array($this, 'filter_e2pdf_controller_templates_backup_pages'), 10, 4);
        add_filter('e2pdf_controller_templates_backup_actions', array($this, 'filter_e2pdf_controller_templates_backup_actions'), 10, 4);
        add_filter('e2pdf_controller_templates_backup_replace_shortcodes', array($this, 'filter_e2pdf_controller_templates_backup_replace_shortcodes'), 10, 4);

        /*  Replace shortcodes on Import */
        add_filter('e2pdf_controller_templates_import_options', array($this, 'filter_e2pdf_controller_templates_import_options'), 10, 3);
        add_filter('e2pdf_controller_templates_import_pages', array($this, 'filter_e2pdf_controller_templates_import_pages'), 10, 5);
        add_filter('e2pdf_controller_templates_import_actions', array($this, 'filter_e2pdf_controller_templates_import_actions'), 10, 5);
        add_filter('e2pdf_controller_templates_import_replace_shortcodes', array($this, 'filter_e2pdf_controller_templates_import_replace_shortcodes'), 10, 5);

        /* Hooks */
        add_filter('frm_row_actions', array($this, 'hook_formidable_row_actions'), 10, 2);
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
        $checkbox_separated = false;
        $foreach_wrapper_shortcodes = false;
        $foreach_inner_shortcodes = false;
        $signature = false;
        if ($this->verify()) {
            if (false !== strpos($value, '[')) {
                $value = $this->helper->load('field')->pre_shortcodes($value, $this, $field);
                if (class_exists('FrmProEntry')) {
                    $shortcode_tags = array();
                    preg_match_all('@\[([^<>&/\[\]\x00-\x20=]++)@', $value, $matches);
                    $tagnames = array_intersect($shortcode_tags, $matches[1]);
                    foreach ($matches[1] as $key => $shortcode) {
                        if (false !== strpos($shortcode, ':')) {
                            $shortcode_tags[] = $shortcode;
                        }
                    }
                    $tagnames = array_intersect($shortcode_tags, $matches[1]);
                    if (!empty($tagnames)) {
                        preg_match_all('/' . $this->helper->load('shortcode')->get_shortcode_regex($tagnames) . '/', $value, $shortcodes);
                        foreach ($shortcodes[0] as $key => $shortcode_value) {
                            $shortcode = $this->helper->load('shortcode')->get_shortcode($shortcodes, $key);
                            $shortcode_details = explode(':', $shortcode[2]);
                            $field_id = $shortcode_details['0'];
                            $child_id = $shortcode_details['1'];
                            $child_entry = 0;
                            $child_field = false;
                            $child_form = false;
                            if (class_exists('FrmField')) {
                                $child_field = FrmField::getOne($field_id);
                                if ($child_field) {
                                    $child_form = $child_field->form_id;
                                }
                            }
                            $child_entries = array();
                            if ($this->get('cached_entry')) {
                                $childs = FrmEntry::getAll(array('parent_item_id' => $this->get('cached_entry')->id), ' ORDER BY it.id ASC', '', true, false);
                                if ($childs && $child_form) {
                                    foreach ($childs as $child) {
                                        if ($child->form_id == $child_form) {
                                            $child_entries[] = $child->id;
                                        }
                                    }
                                }
                            }
                            if ($this->get('item') == '-2') {
                                if ($this->get('cached_entry2')) {
                                    $childs = FrmEntry::getAll(array('parent_item_id' => $this->get('cached_entry2')->id), ' ORDER BY it.id ASC', '', true, false);
                                    if ($childs && $child_form) {
                                        foreach ($childs as $child) {
                                            if ($child->form_id == $child_form) {
                                                $child_entries[] = $child->id;
                                            }
                                        }
                                    }
                                }
                            }
                            $new_shortcode = '';
                            if (!empty($child_entries) && count($child_entries) >= $child_id) {
                                $start = 1;
                                foreach ($child_entries as $key => $sub_entry) {
                                    if ($start == $child_id) {
                                        $child_entry = $sub_entry;
                                        break;
                                    }
                                    $start++;
                                }

                                $shortcode[2] = 'frm-field-value field_id=' . $field_id . ' entry=' . $child_entry;
                                $new_shortcode = '[' . $shortcode[2] . $shortcode[3] . ']';
                            }
                            $checkbox_separated = true;
                            $value = str_replace($shortcode_value, $new_shortcode, $value);
                        }
                    }
                }

                // Foreach Inner Shortcodes
                preg_match_all('/\[foreach[^\]]*?\]((?:(?!\[\/foreach).)+)(e2pdf-if|e2pdf-for)((?:(?!\[\/foreach).)+)\[\/foreach[^\]]*?\]/s', $value, $matches);
                if (!empty($matches[0])) {
                    foreach ($matches[0] as $match) {
                        $new_match = preg_replace('/(\[)(\/?(e2pdf-if|e2pdf-for)[^\]]*?)(\])/', '{{$2}}', $match);
                        $if_shortcodes = $this->helper->load('if')->get_shortcodes();
                        $if_shortcodes_new = array_map(
                                function ($str) {
                                    return str_replace(array('[', ']'), array('{{', '}}'), $str);
                                },
                                $if_shortcodes
                        );
                        $value = str_replace($match, str_replace($if_shortcodes, $if_shortcodes_new, $new_match), $value);
                    }
                    $foreach_inner_shortcodes = true;
                }

                $value = $this->helper->load('field')->inner_shortcodes($value, $this, $field);

                /* Default shortcodes support */
                $shortcode_tags = array(
                    'e2pdf-frm-lookup-values',
                    'e2pdf-frm-data-values',
                    'default-message',
                    'default_message',
                    'default-message2',
                    'default_message2',
                );
                preg_match_all('@\[([^<>&/\[\]\x00-\x20=]++)@', $value, $matches);
                $tagnames = array_intersect($shortcode_tags, $matches[1]);
                if (!empty($tagnames)) {
                    preg_match_all('/' . $this->helper->load('shortcode')->get_shortcode_regex($tagnames) . '/', $value, $shortcodes);
                    foreach ($shortcodes[0] as $key => $shortcode_value) {
                        $shortcode = $this->helper->load('shortcode')->get_shortcode($shortcodes, $key);
                        $atts = shortcode_parse_atts($shortcode[3]);
                        if ($shortcode[2] === 'e2pdf-frm-lookup-values' || $shortcode[2] === 'e2pdf-frm-data-values') {
                            if (!isset($atts['user_id']) && $this->get('user_id')) {
                                $shortcode[3] .= ' user_id="' . $this->get('user_id') . '"';
                                $value = str_replace($shortcode_value, '[' . $shortcode[2] . $shortcode[3] . ']', $value);
                            }
                        } elseif (($shortcode[2] === 'default-message' || $shortcode[2] === 'default_message') && $this->get('cached_entry') && class_exists('FrmEntriesHelper')) {
                            $default_message = FrmEntriesHelper::replace_default_message(
                                    $shortcode_value,
                                    array(
                                        'id' => $this->get('cached_entry')->id,
                                        'entry' => clone $this->get('cached_entry'),
                                        'plain_text' => isset($atts['is_plain_text']) && $atts['is_plain_text'] == 'true' ? true : false,
                                        'user_info' => isset($atts['user_info']) && $atts['user_info'] == 'true' ? true : false,
                                    )
                            );
                            $value = str_replace($shortcode_value, $default_message, $value);
                        } elseif (($shortcode[2] === 'default-message2' || $shortcode[2] == 'default_message2') && $this->get('cached_entry2') && class_exists('FrmEntriesHelper')) {
                            $default_message = FrmEntriesHelper::replace_default_message(
                                    str_replace(array('default-message2', 'default_message2'), array('default-message', 'default_message'), $shortcode_value),
                                    array(
                                        'id' => $this->get('cached_entry2')->id,
                                        'entry' => clone $this->get('cached_entry2'),
                                        'plain_text' => isset($atts['is_plain_text']) && $atts['is_plain_text'] == 'true' ? true : false,
                                        'user_info' => isset($atts['user_info']) && $atts['user_info'] == 'true' ? true : false,
                                    )
                            );
                            $value = str_replace($shortcode_value, $default_message, $value);
                        }
                    }
                }

                // Foreach Wrapper Shortcodes
                preg_match_all('/\[foreach[^\]]*?\]((?:(?!\[\/foreach).)+)(e2pdf-format-number|e2pdf-format-date|e2pdf-format-output|e2pdf-math)((?:(?!\[\/foreach).)+)\[\/foreach[^\]]*?\]/s', $value, $matches);
                if (!empty($matches[0])) {
                    foreach ($matches[0] as $match) {
                        $new_match = preg_replace('/(\[)(\/?(e2pdf-format-number|e2pdf-format-date|e2pdf-format-output|e2pdf-math)[^\]]*?)(\])/', '{{$2}}', $match);
                        $value = str_replace($match, $new_match, $value);
                        $foreach_wrapper_shortcodes = true;
                    }
                }
                $value = $this->helper->load('field')->wrapper_shortcodes($value, $this, $field);

                if (false !== strpos($value, '[')) {
                    if (strpos($value, '[foreach') !== false) {
                        if ($this->get('cached_entry')) {
                            $value = preg_replace('/\[foreach[^\]]*?\](.*?)\[\/foreach(*SKIP)(*FAIL)|\[id\]/is', $this->get('cached_entry')->id, $value);
                            $value = preg_replace('/\[foreach[^\]]*?\](.*?)\[\/foreach(*SKIP)(*FAIL)|\[key\]/is', $this->get('cached_entry')->item_key, $value);
                        } else {
                            $value = preg_replace('/\[foreach[^\]]*?\](.*?)\[\/foreach(*SKIP)(*FAIL)|\[id\]/is', '', $value);
                            $value = preg_replace('/\[foreach[^\]]*?\](.*?)\[\/foreach(*SKIP)(*FAIL)|\[key\]/is', '', $value);
                        }

                        if ($this->get('cached_entry2')) {
                            $value = preg_replace('/\[foreach[^\]]*?\](.*?)\[\/foreach(*SKIP)(*FAIL)|\[id2\]/is', $this->get('cached_entry2')->id, $value);
                            $value = preg_replace('/\[foreach[^\]]*?\](.*?)\[\/foreach(*SKIP)(*FAIL)|\[key2\]/is', $this->get('cached_entry2')->item_key, $value);
                        } else {
                            $value = preg_replace('/\[foreach[^\]]*?\](.*?)\[\/foreach(*SKIP)(*FAIL)|\[id2\]/is', '', $value);
                            $value = preg_replace('/\[foreach[^\]]*?\](.*?)\[\/foreach(*SKIP)(*FAIL)|\[key2\]/is', '', $value);
                        }
                    } else {
                        $replace = array(
                            '[id]' => $this->get('cached_entry') ? $this->get('cached_entry')->id : '',
                            '[key]' => $this->get('cached_entry') ? $this->get('cached_entry')->item_key : '',
                            '[id2]' => $this->get('cached_entry2') ? $this->get('cached_entry2')->id : '',
                            '[key2]' => $this->get('cached_entry2') ? $this->get('cached_entry2')->item_key : '',
                        );
                        $value = str_replace(array_keys($replace), $replace, $value);
                    }
                }
            }

            /*
             * Checkboxes separatable fix filter add
             * @since 0.01.43
             */
            if ($checkbox_separated) {
                add_filter('frm_display_value_custom', array($this, 'filter_frm_display_value_custom'), 0, 3);
            }

            /* [display-frm-data] shortcode support */
            if (false !== strpos($value, '[')) {
                preg_match_all('/(?=\[display-frm-data\b)(\[(?:[^\[\]]+|(?1))+\])/', $value, $matches);
                if (!empty($matches[1])) {
                    add_filter('frmpro_fields_replace_shortcodes', array($this, 'filter_frmpro_fields_replace_shortcodes'), 20, 4);
                    foreach ($matches[1] as $match) {
                        if ($this->get('item') == '-2') {
                            if ($this->get('cached_entry') && $this->get('cached_entry2')) {
                                $new_match = apply_filters('frm_content', $match, $this->get('cached_form'), $this->get('cached_entry'));
                                $new_match = apply_filters('frm_content', $new_match, $this->get('cached_form2'), $this->get('cached_entry2'));
                            } elseif ($this->get('cached_entry')) {
                                $new_match = apply_filters('frm_content', $match, $this->get('cached_form'), $this->get('cached_entry'));
                                if ($this->get('cached_form2')) {
                                    $new_match = apply_filters('frm_content', $new_match, $this->get('cached_form2'), $this->get('cached_entry'));
                                }
                            } elseif ($this->get('cached_entry2')) {
                                $new_match = apply_filters('frm_content', $match, $this->get('cached_form2'), $this->get('cached_entry2'));
                                if ($this->get('cached_form')) {
                                    $new_match = apply_filters('frm_content', $new_match, $this->get('cached_form'), $this->get('cached_entry2'));
                                }
                            }
                        } else {
                            $new_match = apply_filters('frm_content', $match, $this->get('cached_form'), $this->get('cached_entry'));
                        }
                        $value = str_replace($match, $new_match, $value);
                    }
                    remove_filter('frmpro_fields_replace_shortcodes', array($this, 'filter_frmpro_fields_replace_shortcodes'), 20);
                }
            }

            add_filter('frm_filter_view', array($this, 'filter_frm_filter_view'));
            $value = $this->helper->load('field')->do_shortcodes($value, $this, $field);
            remove_filter('frm_filter_view', array($this, 'filter_frm_filter_view'));

            /*
             * Checkboxes separatable fix filter remove
             * @since 0.01.43
             */
            if ($checkbox_separated) {
                remove_filter('frm_display_value_custom', array($this, 'filter_frm_display_value_custom'), 0);
            }

            /*  Bug fix for Formidable Forms Signature (2.0.1) */
            if (class_exists('FrmFieldFactory')) {
                $signature_field = FrmFieldFactory::get_field_type('signature');
                if ($signature_field->get_display_value('signature') == '') {
                    $signature = true;
                }
            }
            if ($signature) {
                remove_filter('frm_get_signature_display_value', 'FrmSigAppController::display_signature');
                remove_filter('frmpro_fields_replace_shortcodes', 'FrmSigAppController::custom_display_signature');
                add_filter('frm_keep_signature_value_array', array($this, 'filter_frm_keep_signature_value_array'), 10, 2);
            }
            add_filter('frmpro_fields_replace_shortcodes', array($this, 'filter_frmpro_fields_replace_shortcodes'), 20, 4);

            $text_signature = !empty($field['type']) && ($field['type'] == 'e2pdf-image' || $field['type'] == 'e2pdf-signature') ? true : false;
            if ($text_signature) {
                add_filter('frm_get_signature_display_value', array($this, 'filter_frm_get_signature_display_value'), 20, 3);
            }
            if ($this->get('item') == '-2') {
                if ($this->get('cached_entry') && $this->get('cached_entry2')) {
                    $value = apply_filters('frm_content', $value, $this->get('cached_form'), $this->get('cached_entry'));
                    $value = apply_filters('frm_content', $value, $this->get('cached_form2'), $this->get('cached_entry2'));
                } elseif ($this->get('cached_entry')) {
                    $value = apply_filters('frm_content', $value, $this->get('cached_form'), $this->get('cached_entry'));
                    if ($this->get('cached_form2')) {
                        $value = apply_filters('frm_content', $value, $this->get('cached_form2'), $this->get('cached_entry'));
                    }
                } elseif ($this->get('cached_entry2')) {
                    $value = apply_filters('frm_content', $value, $this->get('cached_form2'), $this->get('cached_entry2'));
                    if ($this->get('cached_form')) {
                        $value = apply_filters('frm_content', $value, $this->get('cached_form'), $this->get('cached_entry2'));
                    }
                }
            } else {
                if ($this->get('cached_shortcodes')) {
                    remove_filter('frm_content', 'FrmFormsController::filter_content', 10, 3);
                    add_filter('frm_content', array($this, 'filter_filter_content'), 10, 3);
                }
                $value = apply_filters('frm_content', $value, $this->get('cached_form'), $this->get('cached_entry'));
                if ($this->get('cached_shortcodes')) {
                    remove_filter('frm_content', array($this, 'filter_filter_content'), 10, 3);
                    add_filter('frm_content', 'FrmFormsController::filter_content', 10, 3);
                }
            }

            // Foreach Wrapper Shortcodes Reverse
            if ($foreach_wrapper_shortcodes) {
                $value = preg_replace('/(\{\{)(\/?(e2pdf-format-number|e2pdf-format-date|e2pdf-format-output|e2pdf-math)[^\}]*?)(\}\})/', '[$2]', $value);
                $value = $this->helper->load('field')->wrapper_shortcodes($value, $this, $field, true);
            }

            // Foreach Inner Shortcodes Reverse
            if ($foreach_inner_shortcodes) {
                preg_match_all('/\{\{(e2pdf-if|e2pdf-for)\}\}(.*?)+\{\{\/(e2pdf-if|e2pdf-for)\}\}/s', $value, $matches);
                if (!empty($matches[0])) {
                    foreach ($matches[0] as $match) {
                        $new_match = preg_replace('/(\{\{)(\/?(e2pdf-if|e2pdf-for)[^\}]*?)(\}\})/', '[$2]', $match);
                        $value = str_replace($match, str_replace($if_shortcodes_new, $if_shortcodes, $new_match), $value);
                    }
                }
                $value = $this->helper->load('field')->inner_shortcodes($value, $this, $field);
            }

            // Text Signature
            if ($text_signature) {
                remove_filter('frm_get_signature_display_value', array($this, 'filter_frm_get_signature_display_value'), 20);
                if ($value && false !== strpos($value, ':e2pdf-text-filter:')) {
                    $value = str_replace(':e2pdf-text-filter:', '', $value);
                    $field['properties']['text'] = '1';
                }
            }
            remove_filter('frmpro_fields_replace_shortcodes', array($this, 'filter_frmpro_fields_replace_shortcodes'), 20);
            if ($signature) {
                remove_filter('frm_keep_signature_value_array', array($this, 'filter_frm_keep_signature_value_array'));
                add_filter('frm_get_signature_display_value', 'FrmSigAppController::display_signature', 10, 3);
                add_filter('frmpro_fields_replace_shortcodes', 'FrmSigAppController::custom_display_signature', 10, 4);
            }
            $value = $this->replace_meta_shortcodes($value);
            $value = $this->helper->load('field')->render(
                    apply_filters('e2pdf_extension_render_shortcodes_pre_value', $value, $element_id, $this->get('template_id'), $this->get('item'), $this->get('dataset'), $this->get('item2'), $this->get('dataset2')),
                    $this,
                    $field
            );
        }
        return apply_filters(
                'e2pdf_extension_render_shortcodes_value', $value, $element_id, $this->get('template_id'), $this->get('item'), $this->get('dataset'), $this->get('item2'), $this->get('dataset2')
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

    public function filter_frm_lookup_is_current_user_filter_needed($is_filter_needed, $field_id, $field_options) {
        if (FrmField::is_option_true_in_array($field_options, 'lookup_filter_current_user')) {
            return true;
        }
        return $is_filter_needed;
    }

    public function filter_frm_keep_signature_value_array($keep_array, $atts) {
        return true;
    }

    /**
     * Search and update shortcodes for this extension inside content
     * Auto set of dataset id
     * @param string $content - Content
     * @param int $form - ID of form
     * @param int $dataset_id - ID of dataset
     * @return string - Content with updates shortcodes
     */
    public function filter_frm_content($content, $form, $dataset_id) {

        if (false === strpos($content, '[')) {
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
                        if ($template->get('extension') === 'formidable') {
                            $entry_id = is_object($dataset_id) ? $dataset_id->id : $dataset_id;
                            if ($entry_id) {
                                if ($template->get('item') == '-2') {
                                    if ($template->get('item1') == $form->id) {
                                        $atts['dataset'] = $entry_id;
                                        $shortcode[3] .= ' dataset="' . $entry_id . '"';
                                    } elseif (!isset($atts['dataset2']) && $template->get('item2') == $form->id) {
                                        $atts['dataset2'] = $entry_id;
                                        $shortcode[3] .= ' dataset2="' . $entry_id . '"';
                                    }
                                } else {
                                    $atts['dataset'] = $entry_id;
                                    $shortcode[3] .= ' dataset="' . $entry_id . '"';
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

    public function filter_frm_display_entry_content($content) {

        if (false === strpos($content, '[')) {
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
     * Filter for checkbox repeatable Label/Value fix
     * @param array $value - Value
     * @param obj $field - Field
     * @param array $atts - Shortcode Attributes
     * @return mixed - Updated value
     */
    public function filter_frm_display_value_custom($value, $field, $atts = array()) {
        if (class_exists('FrmProEntriesController')) {
            $defaults = array(
                'html' => 0,
                'type' => $field->type,
                'keepjs' => 0,
            );
            $atts = array_merge($defaults, $atts);
            if ($atts['type'] === 'checkbox') {
                $value = FrmProEntriesController::get_option_label_for_saved_value($value, $field, $atts);
            }
        }
        return $value;
    }

    /**
     * Filter for Signature
     * @param array $value - Value
     * @param obj $field - Field
     * @param array $atts - Shortcode Attributes
     * @return mixed - Updated value
     */
    public function filter_frm_get_signature_display_value($value, $field, $atts = array()) {
        if ($value && !empty($value['format']) && $value['format'] == 'typed' && !empty($value['content'])) {
            $value['content'] = ':e2pdf-text-filter:' . $value['content'];
        }
        return $value;
    }

    public function filter_frmpro_fields_replace_shortcodes($replace_with, $tag, $atts, $field) {

        /* Backward compatibility with Formidable Forms PRO < 5.0 */
        if (class_exists('FrmProDb') && property_exists('FrmProDb', 'plug_version') && version_compare(FrmProDb::$plug_version, '5.0', '<')) {
            if ($this->get('item') == '-2' && $this->get('cached_entry2') && $field->form_id == $this->get('cached_entry2')->form_id) {
                $atts['entry_id'] = $this->get('cached_entry2')->id;
                $replace_with = FrmProEntryMetaHelper::get_post_or_meta_value($this->get('cached_entry2'), $field, $atts);
            }
        }

        if ($field->type === 'checkbox' && is_array($replace_with)) {
            if (isset($atts['key']) && $atts['key']) {
                if (isset($replace_with[$atts['key']])) {
                    $replace_with = $replace_with[$atts['key']];
                } else {
                    $replace_with = '';
                }
            }
        }

        if ($field->type === 'radio') {
            if (isset($atts['key']) && $atts['key'] === 'other') {
                if (isset($field->options) && is_array($field->options)) {
                    $field_key = array_search($replace_with, array_column($field->options, 'value'));
                    if ($field_key !== false) {
                        $replace_with = '';
                    }
                }
            }
        }

        return $replace_with;
    }

    /**
     * Generate attachments according shortcodes in Email template
     * @param array $attachments - List of attachments
     * @param int $form - ID of form
     * @param array $args - Arguments
     * @return array - Updated list of attachments
     */
    public function filter_frm_notification_attachment($attachments, $form, $args) {
        if (!isset($args['email_key'])) {
            return $attachments;
        }
        $form_actions = FrmFormAction::get_action_for_form($form->id);
        $dataset_id = $args['entry']->id;
        $email_key = $args['email_key'];

        $shortcode_tags = array(
            'e2pdf-attachment',
            'e2pdf-save',
        );

        foreach ($form_actions as $key => $action) {
            if ($action->ID === $email_key) {
                $content = $action->post_content['email_message'];
                if (false === strpos($content, '[')) {
                    return $attachments;
                }

                remove_filter('frm_content', array($this, 'filter_frm_content'), 30);
                $content = apply_filters('frm_content', $content, $form, $args['entry']);
                add_filter('frm_content', array($this, 'filter_frm_content'), 30, 3);

                preg_match_all('@\[([^<>&/\[\]\x00-\x20=]++)@', $content, $matches);
                $tagnames = array_intersect($shortcode_tags, $matches[1]);
                if (!empty($tagnames)) {
                    preg_match_all('/' . $this->helper->load('shortcode')->get_shortcode_regex($tagnames) . '/', $content, $shortcodes);
                    foreach ($shortcodes[0] as $key => $shortcode_value) {
                        $shortcode = $this->helper->load('shortcode')->get_shortcode($shortcodes, $key);
                        $atts = shortcode_parse_atts($shortcode[3]);
                        $file = false;
                        if ($this->helper->load('shortcode')->is_attachment($shortcode, $atts)) {
                            if (!isset($atts['dataset']) && isset($atts['id'])) {
                                $template = new Model_E2pdf_Template();
                                $template->load($atts['id']);
                                if ($template->get('extension') === 'formidable') {
                                    $entry_id = is_object($dataset_id) ? $dataset_id->id : $dataset_id;
                                    if ($entry_id) {
                                        if ($template->get('item') == '-2') {
                                            if ($template->get('item1') == $form->id) {
                                                $atts['dataset'] = $entry_id;
                                                $shortcode[3] .= ' dataset="' . $entry_id . '"';
                                            } elseif (!isset($atts['dataset2']) && $template->get('item2') == $form->id) {
                                                $atts['dataset2'] = $entry_id;
                                                $shortcode[3] .= ' dataset2="' . $entry_id . '"';
                                            }
                                        } else {
                                            $atts['dataset'] = $entry_id;
                                            $shortcode[3] .= ' dataset="' . $entry_id . '"';
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
                            $transient = false;
                            if (isset($atts['use_args_entry']) && $atts['use_args_entry'] == 'true' && $atts['dataset'] == $dataset_id) {
                                $dataset = 'tmp_' . $this->helper->load('encryption')->random_md5();
                                $transient = 'e2pdf_' . $dataset;
                                set_transient($transient, $args['entry'], 1800);
                                $atts['dataset'] = $dataset;
                                $shortcode[3] .= ' dataset="' . $dataset . '"';
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
                                        $this->helper->add('formidable_attachments', $file);
                                    }
                                } else {
                                    $this->helper->add('formidable_attachments', $file);
                                }
                                $attachments[] = $file;
                            }
                            if ($transient) {
                                delete_transient($transient);
                            }
                        }
                    }
                }
            }
        }
        return $attachments;
    }

    /**
     * Decrypt protected uploaded file in a File Upload field
     * @param string $html
     * @param array $atts
     * @return string $html
     */
    public function filter_frm_image_html_array($html, $atts) {

        if (isset($atts['decrypt']) &&
                $atts['decrypt'] &&
                class_exists('FrmProFileField') &&
                class_exists('FrmProFilePayloadBuilder') &&
                class_exists('FrmAppHelper')
        ) {

            $id = $atts['media_id'];
            $permission = isset($atts['permission']) && $atts['permission'] ? true : false;

            remove_filter('wp_get_attachment_url', 'FrmProFileField::filter_attachment_url');
            remove_filter('wp_get_attachment_image_src', 'FrmProFileField::filter_attachment_image_src');

            $is_image = wp_attachment_is_image($id);
            $url = $this->get_file_url($id, $is_image ? $atts['size'] : false);

            if (!FrmProFileField::user_has_permission($id) && !$permission) {
                $frm_settings = FrmAppHelper::get_settings();
                $html = $frm_settings->admin_permission;
            } else {
                $html = $atts['show_image'] ? wp_get_attachment_image($id, $atts['size'], !$is_image) : '';

                /* If show_filename=1 is included */
                if ($atts['show_filename']) {
                    $label = $this->get_single_file_name($id);
                    if ($atts['show_image']) {
                        $html .= ' <span id="frm_media_' . absint($id) . '" class="frm_upload_label">' . $label . '</span>';
                    } else {
                        $html .= $label;
                    }
                }

                /* If neither show_image or show_filename are included, get file URL */
                if (!$html) {
                    $html = $url;
                }

                /* If add_link=1 is included */
                if ($atts['add_link'] || (!$is_image && $atts['add_link_for_non_image'])) {
                    $href = $is_image ? $this->get_file_url($id) : $url;
                    $target = !empty($atts['new_tab']) ? ' target="_blank"' : '';
                    $html = '<a href="' . esc_url($href) . '" class="frm_file_link"' . $target . '>' . $html . '</a>';
                }

                if (!empty($atts['class'])) {
                    $html = str_replace(' class="', ' class="' . esc_attr($atts['class'] . ' '), $html);
                }

                add_filter('wp_get_attachment_url', 'FrmProFileField::filter_attachment_url', 10, 2);
                add_filter('wp_get_attachment_image_src', 'FrmProFileField::filter_attachment_image_src', 10, 4);
            }
        }

        return $html;
    }

    /**
     * @param int $id
     * @param string|int[]|bool $size
     * @param array $args supported keys include "url" and "leave_size_out_of_payload"
     * @return string unprotected url to use for our specified file id and size.
     */
    public function get_file_url($id, $size = false, $args = array()) {
        $url = isset($args['url']) ? $args['url'] : false;
        $builder = new FrmProFilePayloadBuilder($id, $size, $url);
        return $builder->get_url();
    }

    /**
     * Remove Page Breaks for Visual Mapper
     * @param array $fields - List of fields
     * @return array - Updated fields
     */
    public function filter_remove_pagebreaks($fields) {
        foreach ((array) $fields as $field_key => $field) {
            if ($field->type == 'break') {
                unset($fields[$field_key]);
            }
        }
        return $fields;
    }

    public function filter_frm_match_xml_form($edit_query, $form) {
        if (isset($edit_query['created_at'])) {
            $edit_query['created_at'] = date('Y-m-d H:i:s', strtotime('now'));
        }
        return $edit_query;
    }

    public function filter_frm_show_new_entry_page() {
        return 'new';
    }

    public function filter_frm_pre_display_form($form) {
        if (!empty($form->options['single_entry'])) {
            $form->options['single_entry'] = 0;
        }
        return $form;
    }

    /**
     * Add options for Formidable extension
     * @param array $options - List of options
     * @return array - Updated options list
     */
    public function filter_e2pdf_model_options_get_options_options($options = array()) {
        $options['formidable_group'] = array(
            'name' => 'Formidable Forms',
            'action' => 'extension',
            'group' => 'formidable_group',
            'options' => array(
                array(
                    'name' => __('Auto PDF and Visual Mapper', 'e2pdf'),
                    'key' => 'e2pdf_formidable_use_keys',
                    'value' => get_option('e2pdf_formidable_use_keys', '0'),
                    'default_value' => '0',
                    'type' => 'checkbox',
                    'checkbox_value' => '1',
                    'placeholder' => __('Use Field Keys instead Field IDs', 'e2pdf'),
                ),
                array(
                    'name' => __('Filter', 'e2pdf'),
                    'key' => 'e2pdf_formidable_disable_filter',
                    'value' => get_option('e2pdf_formidable_disable_filter', '0'),
                    'default_value' => '0',
                    'type' => 'checkbox',
                    'checkbox_value' => '1',
                    'placeholder' => __('Disable Filter', 'e2pdf'),
                ),
            ),
        );
        return $options;
    }

    public function filter_e2pdf_controller_templates_import_options($options) {

        if (isset($options['item'])) {
            $options['item']['options'][] = array(
                'name' => 'Formidable Forms',
                'key' => 'options[formidable_item_new_form]',
                'value' => 1,
                'default_value' => 0,
                'type' => 'radio',
                'li' => array(
                    'class' => 'e2pdf-import-extension-option e2pdf-hide',
                ),
                'options' => array(
                    '1' => 'Recreate Web Form',
                    '0' => 'Overwrite Web Form',
                ),
            );
        }

        return $options;
    }

    public function filter_e2pdf_controller_templates_backup_options($options, $template, $extension) {

        if ($extension->loaded('formidable')) {
            $options['formidable'] = array(
                'name' => 'Formidable Forms',
                'options' => array(
                    array(
                        'name' => __('Force shortcodes to use', 'e2pdf'),
                        'key' => 'options[formidable_force_shortcodes]',
                        'value' => 0,
                        'default_value' => 0,
                        'type' => 'select',
                        'options' => array(
                            '0' => __('None', 'e2pdf'),
                            '1' => __('Fields IDs', 'e2pdf'),
                            '2' => __('Field Keys', 'e2pdf'),
                        ),
                    ),
                ),
            );
        }

        return $options;
    }

    public function filter_e2pdf_controller_templates_backup_pages($pages, $options, $template, $extension) {

        if ($extension->loaded('formidable') && (isset($options['formidable_force_shortcodes']) && $options['formidable_force_shortcodes'])) {
            $fields = array();
            if ($template->get('item') == '-2') {
                if ($template->get('item1')) {
                    $where = array('fi.form_id' => (int) $template->get('item1'));
                    $item_fields = FrmField::getAll($where, 'id ASC');
                    if ($item_fields) {
                        $fields = array_merge($fields, $item_fields);
                    }
                }
                if ($template->get('item2')) {
                    $where = array('fi.form_id' => (int) $template->get('item2'));
                    $item_fields = FrmField::getAll($where, 'id ASC');
                    if ($item_fields) {
                        $fields = array_merge($fields, $item_fields);
                    }
                }
            } else {
                $where = array('fi.form_id' => (int) $template->get('item'));
                $fields = FrmField::getAll($where, 'id ASC');
            }

            $search = array();
            $replace = array();

            if ($options['formidable_force_shortcodes'] == '1') {
                foreach ($fields as $field_key => $field) {
                    if (isset($field->field_options['form_select']) && $field->field_options['form_select']) {
                        $sub_where = array('fi.form_id' => $field->field_options['form_select']);
                        $sub_fields = FrmField::getAll($sub_where, 'id ASC');
                        foreach ($sub_fields as $sub_field_key => $sub_field) {
                            $search[] = $sub_field->field_key;
                            $replace[] = $sub_field->id;
                        }
                        $search[] = $field->field_key;
                        $replace[] = $field->id;
                    } else {
                        $search[] = $field->field_key;
                        $replace[] = $field->id;
                    }
                }
            } elseif ($options['formidable_force_shortcodes'] == '2') {
                foreach ($fields as $field_key => $field) {

                    if (isset($field->field_options['form_select']) && $field->field_options['form_select']) {
                        $sub_where = array('fi.form_id' => $field->field_options['form_select']);
                        $sub_fields = FrmField::getAll($sub_where, 'id ASC');
                        foreach ($sub_fields as $sub_field_key => $sub_field) {
                            $search[] = $sub_field->id;
                            $replace[] = $sub_field->field_key;
                        }
                        $search[] = $field->id;
                        $replace[] = $field->field_key;
                    } else {
                        $search[] = $field->id;
                        $replace[] = $field->field_key;
                    }
                }
            }

            $search = array_reverse($search);
            $replace = array_reverse($replace);
            $list = array_combine($search, $replace);
            $pages = $this->pages_replace_shortcodes($pages, $list);
        }

        return $pages;
    }

    public function filter_e2pdf_controller_templates_backup_actions($actions, $options, $template, $extension) {

        if ($extension->loaded('formidable') && (isset($options['formidable_force_shortcodes']) && $options['formidable_force_shortcodes'])) {
            $fields = array();
            if ($template->get('item') == '-2') {
                if ($template->get('item1')) {
                    $where = array('fi.form_id' => (int) $template->get('item1'));
                    $item_fields = FrmField::getAll($where, 'id ASC');
                    if ($item_fields) {
                        $fields = array_merge($fields, $item_fields);
                    }
                }
                if ($template->get('item2')) {
                    $where = array('fi.form_id' => (int) $template->get('item2'));
                    $item_fields = FrmField::getAll($where, 'id ASC');
                    if ($item_fields) {
                        $fields = array_merge($fields, $item_fields);
                    }
                }
            } else {
                $where = array('fi.form_id' => (int) $template->get('item'));
                $fields = FrmField::getAll($where, 'id ASC');
            }

            $search = array();
            $replace = array();

            if ($options['formidable_force_shortcodes'] == '1') {
                foreach ($fields as $field_key => $field) {
                    if (isset($field->field_options['form_select']) && $field->field_options['form_select']) {
                        $sub_where = array('fi.form_id' => $field->field_options['form_select']);
                        $sub_fields = FrmField::getAll($sub_where, 'id ASC');
                        foreach ($sub_fields as $sub_field_key => $sub_field) {
                            $search[] = $sub_field->field_key;
                            $replace[] = $sub_field->id;
                        }
                        $search[] = $field->field_key;
                        $replace[] = $field->id;
                    } else {
                        $search[] = $field->field_key;
                        $replace[] = $field->id;
                    }
                }
            } elseif ($options['formidable_force_shortcodes'] == '2') {
                foreach ($fields as $field_key => $field) {

                    if (isset($field->field_options['form_select']) && $field->field_options['form_select']) {
                        $sub_where = array('fi.form_id' => $field->field_options['form_select']);
                        $sub_fields = FrmField::getAll($sub_where, 'id ASC');
                        foreach ($sub_fields as $sub_field_key => $sub_field) {
                            $search[] = $sub_field->id;
                            $replace[] = $sub_field->field_key;
                        }
                        $search[] = $field->id;
                        $replace[] = $field->field_key;
                    } else {
                        $search[] = $field->id;
                        $replace[] = $field->field_key;
                    }
                }
            }

            $search = array_reverse($search);
            $replace = array_reverse($replace);
            $list = array_combine($search, $replace);
            $actions = $this->actions_replace_shortcodes($actions, $list);
        }

        return $actions;
    }

    public function filter_e2pdf_controller_templates_backup_replace_shortcodes($value, $options, $template, $extension) {

        if ($extension->loaded('formidable') && (isset($options['formidable_force_shortcodes']) && $options['formidable_force_shortcodes'])) {
            $fields = array();
            if ($template->get('item') == '-2') {
                if ($template->get('item1')) {
                    $where = array('fi.form_id' => (int) $template->get('item1'));
                    $item_fields = FrmField::getAll($where, 'id ASC');
                    if ($item_fields) {
                        $fields = array_merge($fields, $item_fields);
                    }
                }
                if ($template->get('item2')) {
                    $where = array('fi.form_id' => (int) $template->get('item2'));
                    $item_fields = FrmField::getAll($where, 'id ASC');
                    if ($item_fields) {
                        $fields = array_merge($fields, $item_fields);
                    }
                }
            } else {
                $where = array('fi.form_id' => (int) $template->get('item'));
                $fields = FrmField::getAll($where, 'id ASC');
            }

            $search = array();
            $replace = array();

            if ($options['formidable_force_shortcodes'] == '1') {
                foreach ($fields as $field_key => $field) {
                    if (isset($field->field_options['form_select']) && $field->field_options['form_select']) {
                        $sub_where = array('fi.form_id' => $field->field_options['form_select']);
                        $sub_fields = FrmField::getAll($sub_where, 'id ASC');
                        foreach ($sub_fields as $sub_field_key => $sub_field) {
                            $search[] = $sub_field->field_key;
                            $replace[] = $sub_field->id;
                        }
                        $search[] = $field->field_key;
                        $replace[] = $field->id;
                    } else {
                        $search[] = $field->field_key;
                        $replace[] = $field->id;
                    }
                }
            } elseif ($options['formidable_force_shortcodes'] == '2') {
                foreach ($fields as $field_key => $field) {

                    if (isset($field->field_options['form_select']) && $field->field_options['form_select']) {
                        $sub_where = array('fi.form_id' => $field->field_options['form_select']);
                        $sub_fields = FrmField::getAll($sub_where, 'id ASC');
                        foreach ($sub_fields as $sub_field_key => $sub_field) {
                            $search[] = $sub_field->id;
                            $replace[] = $sub_field->field_key;
                        }
                        $search[] = $field->id;
                        $replace[] = $field->field_key;
                    } else {
                        $search[] = $field->id;
                        $replace[] = $field->field_key;
                    }
                }
            }

            $search = array_reverse($search);
            $replace = array_reverse($replace);
            $list = array_combine($search, $replace);
            $value = $this->replace_shortcodes($value, $list);
        }

        return $value;
    }

    public function filter_e2pdf_controller_templates_import_pages($pages, $options, $xml, $template, $extension) {

        if ($extension->loaded('formidable') &&
                $template->get('item') &&
                (($template->get('item') != '-2' && $template->get('item') != (string) $xml->template->item) || ($template->get('item') == '-2' && ($template->get('item1') != (string) $xml->template->item1 || $template->get('item2') != (string) $xml->template->item2))) &&
                $xml->item->formidable &&
                $options['item'] &&
                class_exists('FrmXMLHelper') &&
                class_exists('FrmField')
        ) {

            $tmp = tempnam($this->helper->get('tmp_dir'), 'e2pdf');
            file_put_contents($tmp, base64_decode((string) $xml->item->formidable));
            $dom = new DOMDocument();
            $success = $dom->loadXML(file_get_contents($tmp));
            $old_ids = array();
            $old_keys = array();
            if ($success && function_exists('simplexml_import_dom')) {
                $item_xml = simplexml_import_dom($dom);
                foreach ($item_xml->form as $form_key => $form) {
                    if (($template->get('item') != '-2' && (string) $form->id == (string) $xml->template->item) || ($template->get('item') == '-2' && ((string) $form->id == (string) $xml->template->item1 || (string) $form->id == (string) $xml->template->item2))) {
                        foreach ($form->field as $field_key => $field) {
                            $field_options = @json_decode((string) $field->field_options, true); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
                            if (isset($field_options['form_select']) && $field_options['form_select']) {
                                foreach ($item_xml->form as $sub_form_key => $sub_form) {
                                    if ((string) $sub_form->id == $field_options['form_select']) {
                                        foreach ($sub_form->field as $sub_field_key => $sub_field) {
                                            $old_ids[] = (string) $sub_field->id;
                                            $old_keys[] = (string) $sub_field->field_key;
                                        }
                                    }
                                }
                                $old_ids[] = (string) $field->id;
                                $old_keys[] = (string) $field->field_key;
                            } else {
                                $old_ids[] = (string) $field->id;
                                $old_keys[] = (string) $field->field_key;
                            }
                        }
                    }
                }
            }

            $fields = array();
            if ($template->get('item') == '-2') {
                if ($template->get('item1')) {
                    $where = array('fi.form_id' => (int) $template->get('item1'));
                    $item_fields = FrmField::getAll($where, 'id ASC');
                    if ($item_fields) {
                        $fields = array_merge($fields, $item_fields);
                    }
                }

                if ($template->get('item2')) {
                    $where = array('fi.form_id' => (int) $template->get('item2'));
                    $item_fields = FrmField::getAll($where, 'id ASC');
                    if ($item_fields) {
                        $fields = array_merge($fields, $item_fields);
                    }
                }
            } else {
                $where = array('fi.form_id' => (int) $template->get('item'));
                $fields = FrmField::getAll($where, 'id ASC');
            }

            $new_ids = array();
            $new_keys = array();

            foreach ($fields as $field_key => $field) {

                if (isset($field->field_options['form_select']) && $field->field_options['form_select']) {
                    $sub_where = array('fi.form_id' => (int) $field->field_options['form_select']);
                    $sub_fields = FrmField::getAll($sub_where, 'id ASC');
                    foreach ($sub_fields as $sub_field_key => $sub_field) {
                        $new_ids[] = $sub_field->id;
                        $new_keys[] = $sub_field->field_key;
                    }
                    $new_ids[] = $field->id;
                    $new_keys[] = $field->field_key;
                } else {
                    $new_ids[] = $field->id;
                    $new_keys[] = $field->field_key;
                }
            }

            if (count($old_ids) === count($new_ids)) {
                $old_ids = array_reverse($old_ids);
                $new_ids = array_reverse($new_ids);

                $old_keys = array_reverse($old_keys);
                $new_keys = array_reverse($new_keys);

                $list_ids = array_combine($old_ids, $new_ids);
                $pages = $this->pages_replace_shortcodes($pages, $list_ids);

                $list_keys = array_combine($old_keys, $new_keys);
                $pages = $this->pages_replace_shortcodes($pages, $list_keys);
            }

            unset($dom);
            unlink($tmp);
        }

        return $pages;
    }

    public function filter_e2pdf_controller_templates_import_actions($actions, $options, $xml, $template, $extension) {

        if ($extension->loaded('formidable') &&
                $template->get('item') &&
                (($template->get('item') != '-2' && $template->get('item') != (string) $xml->template->item) || ($template->get('item') == '-2' && ($template->get('item1') != (string) $xml->template->item1 || $template->get('item2') != (string) $xml->template->item2))) &&
                $xml->item->formidable &&
                $options['item'] &&
                class_exists('FrmXMLHelper') &&
                class_exists('FrmField')
        ) {

            $tmp = tempnam($this->helper->get('tmp_dir'), 'e2pdf');
            file_put_contents($tmp, base64_decode((string) $xml->item->formidable));

            $dom = new DOMDocument();
            $success = $dom->loadXML(file_get_contents($tmp));

            $old_ids = array();
            $old_keys = array();

            if ($success && function_exists('simplexml_import_dom')) {
                $item_xml = simplexml_import_dom($dom);

                foreach ($item_xml->form as $form_key => $form) {
                    if (($template->get('item') != '-2' && (string) $form->id == (string) $xml->template->item) || ($template->get('item') == '-2' && ((string) $form->id == (string) $xml->template->item1 || (string) $form->id == (string) $xml->template->item2))) {
                        foreach ($form->field as $field_key => $field) {
                            $field_options = @json_decode((string) $field->field_options, true);
                            if (isset($field_options['form_select']) && $field_options['form_select']) {
                                foreach ($item_xml->form as $sub_form_key => $sub_form) {
                                    if ((string) $sub_form->id == $field_options['form_select']) {
                                        foreach ($sub_form->field as $sub_field_key => $sub_field) {
                                            $old_ids[] = (string) $sub_field->id;
                                            $old_keys[] = (string) $sub_field->field_key;
                                        }
                                    }
                                }
                                $old_ids[] = (string) $field->id;
                                $old_keys[] = (string) $field->field_key;
                            } else {
                                $old_ids[] = (string) $field->id;
                                $old_keys[] = (string) $field->field_key;
                            }
                        }
                    }
                }
            }

            $fields = array();
            if ($template->get('item') == '-2') {
                if ($template->get('item1')) {
                    $where = array('fi.form_id' => (int) $template->get('item1'));
                    $item_fields = FrmField::getAll($where, 'id ASC');
                    if ($item_fields) {
                        $fields = array_merge($fields, $item_fields);
                    }
                }

                if ($template->get('item2')) {
                    $where = array('fi.form_id' => (int) $template->get('item2'));
                    $item_fields = FrmField::getAll($where, 'id ASC');

                    if ($item_fields) {
                        $fields = array_merge($fields, $item_fields);
                    }
                }
            } else {
                $where = array('fi.form_id' => (int) $template->get('item'));
                $fields = FrmField::getAll($where, 'id ASC');
            }

            $new_ids = array();
            $new_keys = array();

            foreach ($fields as $field_key => $field) {

                if (isset($field->field_options['form_select']) && $field->field_options['form_select']) {
                    $sub_where = array('fi.form_id' => (int) $field->field_options['form_select']);
                    $sub_fields = FrmField::getAll($sub_where, 'id ASC');
                    foreach ($sub_fields as $sub_field_key => $sub_field) {
                        $new_ids[] = $sub_field->id;
                        $new_keys[] = $sub_field->field_key;
                    }
                    $new_ids[] = $field->id;
                    $new_keys[] = $field->field_key;
                } else {
                    $new_ids[] = $field->id;
                    $new_keys[] = $field->field_key;
                }
            }

            if (count($old_ids) === count($new_ids)) {
                $old_ids = array_reverse($old_ids);
                $new_ids = array_reverse($new_ids);

                $old_keys = array_reverse($old_keys);
                $new_keys = array_reverse($new_keys);

                $list_ids = array_combine($old_ids, $new_ids);
                $actions = $this->actions_replace_shortcodes($actions, $list_ids);

                $list_keys = array_combine($old_keys, $new_keys);
                $actions = $this->pages_replace_shortcodes($actions, $list_keys);
            }
            unset($dom);
            unlink($tmp);
        }

        return $actions;
    }

    public function filter_e2pdf_controller_templates_import_replace_shortcodes($value, $options, $xml, $template, $extension) {

        if ($extension->loaded('formidable') &&
                $template->get('item') &&
                (($template->get('item') != '-2' && $template->get('item') != (string) $xml->template->item) || ($template->get('item') == '-2' && ($template->get('item1') != (string) $xml->template->item1 || $template->get('item2') != (string) $xml->template->item2))) &&
                $xml->item->formidable &&
                $options['item'] &&
                class_exists('FrmXMLHelper') &&
                class_exists('FrmField')
        ) {

            $tmp = tempnam($this->helper->get('tmp_dir'), 'e2pdf');
            file_put_contents($tmp, base64_decode((string) $xml->item->formidable));

            $dom = new DOMDocument();
            $success = $dom->loadXML(file_get_contents($tmp));

            $old_ids = array();
            $old_keys = array();

            if ($success && function_exists('simplexml_import_dom')) {
                $item_xml = simplexml_import_dom($dom);
                foreach ($item_xml->form as $form_key => $form) {
                    if (($template->get('item') != '-2' && (string) $form->id == (string) $xml->template->item) || ($template->get('item') == '-2' && ((string) $form->id == (string) $xml->template->item1 || (string) $form->id == (string) $xml->template->item2))) {
                        foreach ($form->field as $field_key => $field) {
                            $field_options = @json_decode((string) $field->field_options, true); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
                            if (isset($field_options['form_select']) && $field_options['form_select']) {
                                foreach ($item_xml->form as $sub_form_key => $sub_form) {
                                    if ((string) $sub_form->id == $field_options['form_select']) {
                                        foreach ($sub_form->field as $sub_field_key => $sub_field) {
                                            $old_ids[] = (string) $sub_field->id;
                                            $old_keys[] = (string) $sub_field->field_key;
                                        }
                                    }
                                }
                                $old_ids[] = (string) $field->id;
                                $old_keys[] = (string) $field->field_key;
                            } else {
                                $old_ids[] = (string) $field->id;
                                $old_keys[] = (string) $field->field_key;
                            }
                        }
                    }
                }
            }

            $fields = array();
            if ($template->get('item') == '-2') {
                if ($template->get('item1')) {
                    $where = array('fi.form_id' => (int) $template->get('item1'));
                    $item_fields = FrmField::getAll($where, 'id ASC');
                    if ($item_fields) {
                        $fields = array_merge($fields, $item_fields);
                    }
                }

                if ($template->get('item2')) {
                    $where = array('fi.form_id' => (int) $template->get('item2'));
                    $item_fields = FrmField::getAll($where, 'id ASC');

                    if ($item_fields) {
                        $fields = array_merge($fields, $item_fields);
                    }
                }
            } else {
                $where = array('fi.form_id' => (int) $template->get('item'));
                $fields = FrmField::getAll($where, 'id ASC');
            }

            $new_ids = array();
            $new_keys = array();

            foreach ($fields as $field_key => $field) {
                if (isset($field->field_options['form_select']) && $field->field_options['form_select']) {
                    $sub_where = array('fi.form_id' => (int) $field->field_options['form_select']);
                    $sub_fields = FrmField::getAll($sub_where, 'id ASC');
                    foreach ($sub_fields as $sub_field_key => $sub_field) {
                        $new_ids[] = $sub_field->id;
                        $new_keys[] = $sub_field->field_key;
                    }
                    $new_ids[] = $field->id;
                    $new_keys[] = $field->field_key;
                } else {
                    $new_ids[] = $field->id;
                    $new_keys[] = $field->field_key;
                }
            }

            if (count($old_ids) === count($new_ids)) {

                $old_ids = array_reverse($old_ids);
                $new_ids = array_reverse($new_ids);

                $old_keys = array_reverse($old_keys);
                $new_keys = array_reverse($new_keys);

                $list_ids = array_combine($old_ids, $new_ids);
                $value = $this->replace_shortcodes($value, $list_ids);

                $list_keys = array_combine($old_keys, $new_keys);
                $value = $this->replace_shortcodes($value, $list_keys);
            }

            unset($dom);
            unlink($tmp);
        }
        return $value;
    }

    /**
     * Delete attachments that were sent by email
     */
    public function action_frm_notification() {

        $files = $this->helper->get('formidable_attachments');
        if (is_array($files) && !empty($files)) {
            foreach ($files as $key => $file) {
                $this->helper->delete_dir(dirname($file) . '/');
            }
            $this->helper->deset('formidable_attachments');
        }
    }

    /**
     * Fix to render E2Pdf shortcodes with beforeContent and afterContent inside Formidable Form View Preview
     */
    public function action_check_ajax_referer($action, $result) {
        if ($action == 'frm_ajax' && false !== $result && isset($_POST['action']) && $_POST['action'] == 'frm_views_process_box_preview') {
            if (isset($_POST['beforeContent']) && $_POST['beforeContent']) {
                $_POST['beforeContent'] = $this->filter_view_preview_before_after_content($_POST['beforeContent']);
            }
            if (isset($_POST['afterContent']) && $_POST['afterContent']) {
                $_POST['afterContent'] = $this->filter_view_preview_before_after_content($_POST['afterContent']);
            }
        }
    }

    public function action_frm_default_value($entry_id, $form_id) {
        global $wpdb;
        if ($form_id) {
            $fields = $wpdb->get_results($wpdb->prepare('SELECT * FROM `' . $wpdb->prefix . 'frm_fields' . '` WHERE form_id = %d AND (default_value LIKE %s OR default_value LIKE %s)', $form_id, '%[e2pdf-download%', '%[e2pdf-save%'));
            if (!empty($fields)) {
                foreach ($fields as $key => $field) {
                    $meta = FrmEntryMeta::get_entry_meta_by_field($entry_id, $field->id);
                    if ($meta === null) {
                        $new_value = $this->filter_frm_content($field->default_value, FrmForm::getOne($form_id), $entry_id);
                        $added = FrmEntryMeta::add_entry_meta($entry_id, $field->id, null, $new_value);
                        if (!$added) {
                            FrmEntryMeta::update_entry_meta($entry_id, $field->id, null, $new_value);
                        }
                    }
                }
            }
        }
    }

    /**
     * Filter "get" shortcodes with beforeContent and afterContent inside Formidable Form View Preview
     * @param type $content
     * @return type
     */
    public function filter_view_preview_before_after_content($content) {

        if (false !== strpos($content, '[get ') && (
                false !== strpos($content, 'e2pdf-download') ||
                false !== strpos($content, 'e2pdf-save') ||
                false !== strpos($content, 'e2pdf-view') ||
                false !== strpos($content, 'e2pdf-adobesign') ||
                false !== strpos($content, 'e2pdf-zapier') ||
                false !== strpos($content, 'e2pdf-attachment')
                )) {
            $shortcode_tags = array(
                'get',
            );
            preg_match_all('@\[([^<>&/\[\]\x00-\x20=]++)@', $content, $matches);
            $tagnames = array_intersect($shortcode_tags, $matches[1]);
            if (!empty($tagnames)) {
                preg_match_all('/' . $this->helper->load('shortcode')->get_shortcode_regex($tagnames) . '/', $content, $shortcodes);
                foreach ($shortcodes[0] as $key => $shortcode_value) {
                    $content = str_replace($shortcode_value, $this->convert_shortcodes($shortcode_value), $content);
                }
            }
        }
        return $content;
    }

    public function filter_filter_content($content, $form, $entry = false) {
        $content = FrmFormsController::replace_form_name_shortcodes($content, $form);
        if (!$entry) {
            return $content;
        }
        if (is_object($form)) {
            $form = $form->id;
        }
        if (!empty($entry->parent_entry)) {
            $form = $entry->parent_entry->form_id;
        }
        $shortcodes = $this->get('cached_shortcodes');
        $content = apply_filters('frm_replace_content_shortcodes', $content, $entry, $shortcodes);
        return $content;
    }

    /**
     * Auto Generate of Template for this extension
     * @return array - List of elements
     */
    public function auto() {

        $response = array();
        $elements = array();

        $form_id = $this->get('item');

        $fields = array();
        if (class_exists('FrmField')) {
            $fields = FrmField::get_all_for_form($form_id, '', 'include', 'include');
        }

        if ($fields) {
            foreach ($fields as $key => $field) {
                $field_id = get_option('e2pdf_formidable_use_keys', '0') ? $field->field_key : $field->id;

                if ($field->type === 'lookup' && isset($field->field_options['data_type'])) {
                    if ($field->field_options['data_type'] == 'select') {
                        $field->options = array(
                            '[e2pdf-frm-lookup-values id="' . $field_id . '"]',
                        );
                    } elseif ($field->field_options['data_type'] == 'radio' || $field->field_options['data_type'] == 'checkbox') {
                        $options = array();
                        $frm_filter = false;
                        if (class_exists('FrmProFieldsHelper') && method_exists('FrmProFieldsHelper', 'add_default_field_settings')) {
                            if (!has_filter('frm_default_field_options', 'FrmProFieldsHelper::add_default_field_settings')) {
                                add_filter('frm_default_field_options', 'FrmProFieldsHelper::add_default_field_settings', 10, 2);
                                $frm_filter = true;
                            }
                        }

                        $args = array();
                        $field_array = FrmAppHelper::start_field_array($field);
                        FrmFieldsHelper::prepare_new_front_field($field_array, $field, $args);
                        $field_array = array_merge($field->field_options, $field_array);
                        if (isset($field_array['options']) && is_array($field_array['options'])) {
                            $options = $field_array['options'];
                        }
                        if ($frm_filter) {
                            remove_filter('frm_default_field_options', 'FrmProFieldsHelper::add_default_field_settings');
                        }
                        $field->options = $options;
                    }
                    $field->type = $field->field_options['data_type'];
                } elseif ($field->type === 'data' && isset($field->field_options['data_type'])) {
                    if ($field->field_options['data_type'] == 'select') {
                        $field->options = array(
                            '[e2pdf-frm-data-values id="' . $field_id . '"]',
                        );
                    } elseif ($field->field_options['data_type'] == 'radio' || $field->field_options['data_type'] == 'checkbox') {
                        $options = array();
                        $frm_filter = false;
                        if (class_exists('FrmProFieldsHelper') && method_exists('FrmProFieldsHelper', 'add_default_field_settings')) {
                            if (!has_filter('frm_default_field_options', 'FrmProFieldsHelper::add_default_field_settings')) {
                                add_filter('frm_default_field_options', 'FrmProFieldsHelper::add_default_field_settings', 10, 2);
                                $frm_filter = true;
                            }
                        }

                        $args = array();
                        $field->field_options['restrict'] = '0';
                        $field_array = FrmAppHelper::start_field_array($field);
                        FrmFieldsHelper::prepare_new_front_field($field_array, $field, $args);

                        $field_array = array_merge($field->field_options, $field_array);
                        if (isset($field_array['options']) && is_array($field_array['options'])) {
                            $options = $field_array['options'];
                        }

                        if ($frm_filter) {
                            remove_filter('frm_default_field_options', 'FrmProFieldsHelper::add_default_field_settings');
                        }
                        $field->options = $options;
                    }
                    $field->type = $field->field_options['data_type'];
                }

                /*
                 * Repeatable fields Field ID modification
                 * @since 0.01.42
                 */
                if ($field->type !== 'lookup' && $field->type !== 'data' && isset($field->form_id) && $field->form_id != $form_id) {
                    $field_id = $field_id . ':1';
                }

                switch ($field->type) {
                    case 'divider':
                        if ($field->name) {
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
                                            'value' => '<h2>' . $field->name . '</h2>',
                                        ),
                                    )
                            );
                        }
                        break;
                    case 'html':
                        if ($field->description) {
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
                                            'value' => $field->description,
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
                                    'properties' => array(
                                        'top' => '20',
                                        'left' => '20',
                                        'right' => '20',
                                        'width' => '100%',
                                        'height' => 'auto',
                                        'value' => $field->name,
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
                                        'value' => '[' . $field_id . ']',
                                    ),
                                )
                        );
                        break;
                    case 'scale':
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
                                        'value' => $field->name,
                                    ),
                                )
                        );

                        if (isset($field->options) && is_array($field->options)) {
                            $start = true;
                            foreach ($field->options as $opt_key => $option) {
                                if (is_array($option)) {
                                    $elements[] = $this->auto_field(
                                            $field,
                                            array(
                                                'type' => 'e2pdf-radio',
                                                'float' => $start ? false : true,
                                                'properties' => array(
                                                    'top' => $start ? '5' : '0',
                                                    'left' => $start ? '0' : '5',
                                                    'width' => 'auto',
                                                    'height' => 'auto',
                                                    'value' => '[' . $field_id . ']',
                                                    'option' => $option['value'],
                                                    'group' => 'group_' . $field_id,
                                                ),
                                            )
                                    );
                                } else {
                                    $elements[] = $this->auto_field(
                                            $field,
                                            array(
                                                'type' => 'e2pdf-radio',
                                                'float' => $start ? false : true,
                                                'properties' => array(
                                                    'top' => $start ? '5' : '0',
                                                    'left' => $start ? '0' : '5',
                                                    'width' => 'auto',
                                                    'height' => 'auto',
                                                    'value' => '[' . $field_id . ']',
                                                    'option' => $option,
                                                    'group' => 'group_' . $field_id,
                                                ),
                                            )
                                    );
                                }

                                $start = false;
                            }

                            $start = true;
                            foreach ($field->options as $opt_key => $option) {
                                if (is_array($option)) {

                                    $elements[] = $this->auto_field(
                                            $field,
                                            array(
                                                'type' => 'e2pdf-html',
                                                'float' => $start ? false : true,
                                                'properties' => array(
                                                    'top' => $start ? '5' : '0',
                                                    'left' => $start ? '0' : '5',
                                                    'width' => 'auto',
                                                    'height' => 'auto',
                                                    'value' => $option['label'],
                                                    'text_align' => 'center',
                                                ),
                                            )
                                    );
                                } else {
                                    $elements[] = $this->auto_field(
                                            $field,
                                            array(
                                                'type' => 'e2pdf-html',
                                                'float' => $start ? false : true,
                                                'properties' => array(
                                                    'top' => $start ? '5' : '0',
                                                    'left' => $start ? '0' : '5',
                                                    'width' => 'auto',
                                                    'height' => 'auto',
                                                    'value' => $option,
                                                    'text_align' => 'center',
                                                ),
                                            )
                                    );
                                }
                                $start = false;
                            }
                        }
                        break;
                    case 'rte':
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
                                        'value' => $field->name,
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
                                        'value' => '[' . $field_id . ' wpautop=0]',
                                    ),
                                )
                        );
                        break;
                    case 'text':
                    case 'email':
                    case 'url':
                    case 'number':
                    case 'phone':
                    case 'date':
                    case 'image':
                    case 'tag':
                    case 'password':
                    case 'quiz_score':
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
                                        'value' => $field->name,
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
                                        'value' => '[' . $field_id . ']',
                                        'pass' => $field->type === 'password' ? '1' : '0',
                                    ),
                                )
                        );
                        break;

                    case 'time':
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
                                        'value' => $field->name,
                                    ),
                                )
                        );

                        $options_tmp = array();
                        if (class_exists('FrmProFieldTime')) {
                            $frm_pro_field_time = new FrmProFieldTime($field);
                            $options_tmp = $frm_pro_field_time->get_options($field->field_options);
                        }

                        if (isset($field->field_options['single_time']) && $field->field_options['single_time']) {
                            $elements[] = $this->auto_field(
                                    $field,
                                    array(
                                        'type' => 'e2pdf-select',
                                        'properties' => array(
                                            'top' => '5',
                                            'width' => '100%',
                                            'height' => 'auto',
                                            'options' => implode("\n", $options_tmp),
                                            'value' => '[' . $field_id . ']',
                                        ),
                                    )
                            );
                        } else {

                            $options_h = isset($options_tmp['H']) && is_array($options_tmp['H']) ? $options_tmp['H'] : array();
                            $options_m = isset($options_tmp['m']) && is_array($options_tmp['m']) ? $options_tmp['m'] : array();
                            $options_a = isset($options_tmp['A']) && is_array($options_tmp['A']) ? $options_tmp['A'] : array();

                            $elements[] = $this->auto_field(
                                    $field,
                                    array(
                                        'type' => 'e2pdf-select',
                                        'float' => true,
                                        'properties' => array(
                                            'top' => '5',
                                            'width' => isset($field->field_options['clock']) && $field->field_options['clock'] == '12' ? '33.3%' : '50%',
                                            'height' => 'auto',
                                            'options' => implode("\n", $options_h),
                                            'value' => isset($field->field_options['clock']) && $field->field_options['clock'] == '12' ? '[' . $field_id . ' format="g"]' : '[' . $field_id . ' format="H"]',
                                        ),
                                    )
                            );

                            $elements[] = $this->auto_field(
                                    $field,
                                    array(
                                        'type' => 'e2pdf-select',
                                        'float' => true,
                                        'properties' => array(
                                            'top' => '5',
                                            'left' => '20',
                                            'width' => isset($field->field_options['clock']) && $field->field_options['clock'] == '12' ? '33.3%' : '50%',
                                            'height' => 'auto',
                                            'options' => implode("\n", $options_m),
                                            'value' => '[' . $field_id . ' format="i"]',
                                        ),
                                    )
                            );

                            if (isset($field->field_options['clock']) && $field->field_options['clock'] == '12') {
                                $elements[] = $this->auto_field(
                                        $field,
                                        array(
                                            'type' => 'e2pdf-select',
                                            'float' => true,
                                            'properties' => array(
                                                'top' => '5',
                                                'left' => '20',
                                                'width' => '33.3%',
                                                'height' => 'auto',
                                                'options' => implode("\n", $options_a),
                                                'value' => '[' . $field_id . ' format="A"]',
                                            ),
                                        )
                                );
                            }
                        }
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
                                        'value' => $field->name,
                                    ),
                                )
                        );

                        $options_tmp = array();
                        if (isset($field->options) && is_array($field->options)) {
                            foreach ($field->options as $opt_key => $option) {
                                if (is_array($option)) {
                                    $options_tmp[] = isset($option['label']) ? $option['label'] : $option['value'];
                                } else {
                                    $options_tmp[] = $option;
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
                                        'value' => '[' . $field_id . ']',
                                        'height' => isset($field->field_options['multiple']) && $field->field_options['multiple'] ? '80' : 'auto',
                                        'multiline' => isset($field->field_options['multiple']) && $field->field_options['multiple'] ? '1' : '0',
                                    ),
                                )
                        );
                        break;
                    case 'credit_card':
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
                                        'value' => $field->name,
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
                                        'value' => '[' . $field_id . ' show="cc"]',
                                    ),
                                )
                        );

                        $elements[] = $this->auto_field(
                                $field,
                                array(
                                    'type' => 'e2pdf-select',
                                    'properties' => array(
                                        'top' => '5',
                                        'width' => '33.3%',
                                        'left' => '0',
                                        'height' => 'auto',
                                        'options' => implode("\n", range(1, 12)),
                                        'value' => '[' . $field_id . ' show="month"]',
                                    ),
                                )
                        );

                        $elements[] = $this->auto_field(
                                $field,
                                array(
                                    'type' => 'e2pdf-select',
                                    'float' => true,
                                    'properties' => array(
                                        'top' => '5',
                                        'left' => '20',
                                        'width' => '33.3%',
                                        'height' => 'auto',
                                        'options' => implode("\n", range(date('Y'), date('Y') + 10)),
                                        'value' => '[' . $field_id . ' show="year"]',
                                    ),
                                )
                        );

                        $elements[] = $this->auto_field(
                                $field,
                                array(
                                    'type' => 'e2pdf-input',
                                    'float' => true,
                                    'properties' => array(
                                        'top' => '5',
                                        'left' => '20',
                                        'width' => '33.3%',
                                        'height' => 'auto',
                                        'value' => '[' . $field_id . ' show="cvc"]',
                                    ),
                                )
                        );
                        break;
                    case 'address':
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
                                        'value' => $field->name,
                                    ),
                                )
                        );

                        if (isset($field->field_options['line1_desc']) && $field->field_options['line1_desc']) {
                            $elements[] = $this->auto_field(
                                    $field,
                                    array(
                                        'type' => 'e2pdf-html',
                                        'properties' => array(
                                            'top' => '5',
                                            'width' => '100%',
                                            'height' => 'auto',
                                            'value' => $field->field_options['line1_desc'],
                                        ),
                                    )
                            );
                        }

                        $elements[] = $this->auto_field(
                                $field,
                                array(
                                    'type' => 'e2pdf-input',
                                    'properties' => array(
                                        'top' => '5',
                                        'width' => '100%',
                                        'height' => 'auto',
                                        'value' => '[' . $field_id . ' show="line1"]',
                                    ),
                                )
                        );

                        if (isset($field->field_options['line2_desc']) && $field->field_options['line2_desc']) {
                            $elements[] = $this->auto_field(
                                    $field,
                                    array(
                                        'type' => 'e2pdf-html',
                                        'properties' => array(
                                            'top' => '5',
                                            'width' => '100%',
                                            'height' => 'auto',
                                            'value' => $field->field_options['line2_desc'],
                                        ),
                                    )
                            );
                        }

                        $elements[] = $this->auto_field(
                                $field,
                                array(
                                    'type' => 'e2pdf-input',
                                    'properties' => array(
                                        'top' => '5',
                                        'width' => '100%',
                                        'height' => 'auto',
                                        'value' => '[' . $field_id . ' show="line2"]',
                                    ),
                                )
                        );

                        if (
                                (isset($field->field_options['city_desc']) && $field->field_options['city_desc']) ||
                                (isset($field->field_options['state_desc']) && $field->field_options['state_desc'] && isset($field->field_options['address_type']) && $field->field_options['address_type'] != 'europe') ||
                                (isset($field->field_options['zip_desc']) && $field->field_options['zip_desc'])
                        ) {

                            $float = false;
                            if (isset($field->field_options['address_type']) && $field->field_options['address_type'] != 'europe') {
                                $elements[] = $this->auto_field(
                                        $field,
                                        array(
                                            'type' => 'e2pdf-html',
                                            'float' => $float,
                                            'properties' => array(
                                                'top' => '5',
                                                'left' => $float ? '20' : '0',
                                                'width' => '33.3%',
                                                'height' => 'auto',
                                                'value' => isset($field->field_options['city_desc']) && $field->field_options['city_desc'] ? $field->field_options['city_desc'] : '',
                                            ),
                                        )
                                );
                                $float = true;
                            }

                            if (isset($field->field_options['address_type']) && (
                                    $field->field_options['address_type'] == 'international' ||
                                    $field->field_options['address_type'] == 'us' ||
                                    $field->field_options['address_type'] == 'generic'
                                    )
                            ) {

                                $elements[] = $this->auto_field(
                                        $field,
                                        array(
                                            'type' => 'e2pdf-html',
                                            'float' => $float,
                                            'properties' => array(
                                                'top' => '5',
                                                'left' => $float ? '20' : '0',
                                                'width' => '33.3%',
                                                'height' => 'auto',
                                                'value' => isset($field->field_options['state_desc']) && $field->field_options['state_desc'] ? $field->field_options['state_desc'] : '',
                                            ),
                                        )
                                );
                                $float = true;
                            }

                            $elements[] = $this->auto_field(
                                    $field,
                                    array(
                                        'type' => 'e2pdf-html',
                                        'float' => $float,
                                        'properties' => array(
                                            'top' => '5',
                                            'left' => $float ? '20' : '0',
                                            'width' => '33.3%',
                                            'height' => 'auto',
                                            'value' => isset($field->field_options['zip_desc']) && $field->field_options['zip_desc'] ? $field->field_options['zip_desc'] : '',
                                        ),
                                    )
                            );
                            $float = true;

                            if (isset($field->field_options['address_type']) && $field->field_options['address_type'] == 'europe') {

                                $elements[] = $this->auto_field(
                                        $field,
                                        array(
                                            'type' => 'e2pdf-html',
                                            'float' => $float,
                                            'properties' => array(
                                                'top' => '5',
                                                'left' => $float ? '20' : '0',
                                                'width' => '33.3%',
                                                'height' => 'auto',
                                                'value' => isset($field->field_options['city_desc']) && $field->field_options['city_desc'] ? $field->field_options['city_desc'] : '',
                                            ),
                                        )
                                );
                            }
                        }

                        $float = false;
                        if (isset($field->field_options['address_type']) && $field->field_options['address_type'] != 'europe') {
                            $elements[] = $this->auto_field(
                                    $field,
                                    array(
                                        'type' => 'e2pdf-input',
                                        'float' => $float,
                                        'properties' => array(
                                            'top' => '5',
                                            'left' => $float ? '20' : '0',
                                            'width' => '33.3%',
                                            'height' => 'auto',
                                            'value' => '[' . $field_id . ' show="city"]',
                                        ),
                                    )
                            );
                            $float = true;
                        }

                        if (isset($field->field_options['address_type']) && (
                                $field->field_options['address_type'] == 'international' ||
                                $field->field_options['address_type'] == 'us' ||
                                $field->field_options['address_type'] == 'generic'
                                )
                        ) {
                            if ($field->field_options['address_type'] == 'us') {
                                $options_tmp = array();
                                if (class_exists('FrmFieldsHelper')) {
                                    $options_tmp = array_values(FrmFieldsHelper::get_us_states());
                                }

                                $elements[] = $this->auto_field(
                                        $field,
                                        array(
                                            'type' => 'e2pdf-select',
                                            'float' => $float,
                                            'properties' => array(
                                                'top' => '5',
                                                'left' => $float ? '20' : '0',
                                                'width' => '33.3%',
                                                'height' => 'auto',
                                                'options' => implode("\n", $options_tmp),
                                                'value' => '[' . $field_id . ' show="state"]',
                                            ),
                                        )
                                );
                            } else {
                                $elements[] = $this->auto_field(
                                        $field,
                                        array(
                                            'type' => 'e2pdf-input',
                                            'float' => $float,
                                            'properties' => array(
                                                'top' => '5',
                                                'left' => $float ? '20' : '0',
                                                'width' => '33.3%',
                                                'height' => 'auto',
                                                'value' => '[' . $field_id . ' show="state"]',
                                            ),
                                        )
                                );
                            }
                            $float = true;
                        }

                        $elements[] = $this->auto_field(
                                $field,
                                array(
                                    'type' => 'e2pdf-input',
                                    'float' => $float,
                                    'properties' => array(
                                        'top' => '5',
                                        'left' => $float ? '20' : '0',
                                        'width' => '33.3%',
                                        'height' => 'auto',
                                        'value' => '[' . $field_id . ' show="zip"]',
                                    ),
                                )
                        );
                        $float = true;

                        if (isset($field->field_options['address_type']) && $field->field_options['address_type'] == 'europe') {
                            $elements[] = $this->auto_field(
                                    $field,
                                    array(
                                        'type' => 'e2pdf-input',
                                        'float' => $float,
                                        'properties' => array(
                                            'top' => '5',
                                            'left' => $float ? '20' : '0',
                                            'width' => '33.3%',
                                            'height' => 'auto',
                                            'value' => '[' . $field_id . ' show="city"]',
                                        ),
                                    )
                            );
                        }

                        if (isset($field->field_options['address_type']) && ($field->field_options['address_type'] == 'international' || $field->field_options['address_type'] == 'europe')) {
                            if (isset($field->field_options['country_desc']) && $field->field_options['country_desc']) {
                                $elements[] = $this->auto_field(
                                        $field,
                                        array(
                                            'type' => 'e2pdf-html',
                                            'properties' => array(
                                                'top' => '5',
                                                'width' => '100%',
                                                'height' => 'auto',
                                                'value' => $field->field_options['country_desc'],
                                            ),
                                        )
                                );
                            }

                            $options_tmp = array();
                            if (class_exists('FrmFieldsHelper')) {
                                $options_tmp = array_values(FrmFieldsHelper::get_countries());
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
                                            'value' => '[' . $field_id . ' show="country"]',
                                        ),
                                    )
                            );
                        }
                        break;
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
                                        'value' => $field->name,
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
                                        'value' => '[' . $field_id . ' wpautop=0]',
                                    ),
                                )
                        );
                        break;
                    case 'file':
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
                                        'value' => $field->name,
                                    ),
                                )
                        );
                        if (strpos($field_id, ':') !== false) {
                            $field_id .= ' size="full" show_image="0" add_link="0"';
                        } else {
                            $field_id .= ' size="full"';
                        }
                        $elements[] = $this->auto_field(
                                $field,
                                array(
                                    'type' => 'e2pdf-textarea',
                                    'properties' => array(
                                        'top' => '5',
                                        'width' => '100%',
                                        'height' => '150',
                                        'value' => '[' . $field_id . ']',
                                    ),
                                )
                        );
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
                                        'value' => $field->name,
                                    ),
                                )
                        );

                        $image_options = false;
                        if (isset($field->field_options['image_options']) && $field->field_options['image_options']) {
                            $image_options = true;
                        }

                        $separate_value = false;
                        if (isset($field->field_options['separate_value']) && $field->field_options['separate_value']) {
                            $separate_value = true;
                        }

                        if (isset($field->options) && is_array($field->options)) {

                            foreach ($field->options as $opt_key => $option) {
                                if (is_array($option)) {
                                    $elements[] = $this->auto_field(
                                            $field,
                                            array(
                                                'type' => 'e2pdf-radio',
                                                'properties' => array(
                                                    'top' => '5',
                                                    'width' => 'auto',
                                                    'height' => 'auto',
                                                    'value' => $image_options ? '[' . $field_id . ' show="value"]' : '[' . $field_id . ']',
                                                    'option' => $image_options && $separate_value && isset($option['value']) ? $option['value'] : $option['label'],
                                                    'group' => '[' . $field_id . ']',
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
                                } else {

                                    $elements[] = $this->auto_field(
                                            $field,
                                            array(
                                                'type' => 'e2pdf-radio',
                                                'properties' => array(
                                                    'top' => '5',
                                                    'width' => 'auto',
                                                    'height' => 'auto',
                                                    'value' => '[' . $field_id . ']',
                                                    'option' => substr($opt_key, 0, 6) === 'other_' ? '[' . $field_id . ' show="value" key="other"]' : $option,
                                                    'group' => '[' . $field_id . ']',
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
                                                    'value' => $option,
                                                ),
                                            )
                                    );

                                    if (substr($opt_key, 0, 6) === 'other_') {
                                        $elements[] = $this->auto_field(
                                                $field,
                                                array(
                                                    'type' => 'e2pdf-input',
                                                    'properties' => array(
                                                        'top' => '5',
                                                        'width' => '100%',
                                                        'height' => 'auto',
                                                        'value' => '[' . $field_id . ' show="value" key="other"]',
                                                    ),
                                                )
                                        );
                                    }
                                }
                            }
                        }
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
                                        'value' => $field->name,
                                    ),
                                )
                        );

                        $image_options = false;
                        if (isset($field->field_options['image_options']) && $field->field_options['image_options']) {
                            $image_options = true;
                        }

                        $separate_value = false;
                        if (isset($field->field_options['separate_value']) && $field->field_options['separate_value']) {
                            $separate_value = true;
                        }

                        if (isset($field->options) && is_array($field->options)) {
                            foreach ($field->options as $opt_key => $option) {
                                if (is_array($option)) {
                                    $elements[] = $this->auto_field(
                                            $field,
                                            array(
                                                'type' => 'e2pdf-checkbox',
                                                'properties' => array(
                                                    'top' => '5',
                                                    'width' => 'auto',
                                                    'height' => 'auto',
                                                    'value' => $image_options ? '[' . $field_id . ' show="value"]' : '[' . $field_id . ']',
                                                    'option' => $image_options && $separate_value && isset($option['value']) ? $option['value'] : $option['label'],
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
                                } else {

                                    $elements[] = $this->auto_field(
                                            $field,
                                            array(
                                                'type' => 'e2pdf-checkbox',
                                                'properties' => array(
                                                    'top' => '5',
                                                    'width' => 'auto',
                                                    'height' => 'auto',
                                                    'value' => '[' . $field_id . ']',
                                                    'option' => substr($opt_key, 0, 6) === 'other_' ? '[' . $field_id . ' show="value" key="' . $opt_key . '"]' : $option,
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
                                                    'value' => $option,
                                                ),
                                            )
                                    );

                                    if (substr($opt_key, 0, 6) === 'other_') {
                                        $elements[] = $this->auto_field(
                                                $field,
                                                array(
                                                    'type' => 'e2pdf-input',
                                                    'properties' => array(
                                                        'top' => '5',
                                                        'width' => '100%',
                                                        'height' => 'auto',
                                                        'value' => '[' . $field_id . ' show="value" key="' . $opt_key . '"]',
                                                    ),
                                                )
                                        );
                                    }
                                }
                            }
                        }
                        break;
                    default:
                        break;
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
        if (isset($field->field_options['classes'])) {
            $classes = explode(' ', $field->field_options['classes']);
        }

        $float_classes = array(
            'frm_half',
            'frm_third',
            'frm_two_thirds',
            'frm_fourth',
            'frm_three_fourths',
            'frm_fifth',
            'frm_two_fifths',
            'frm_sixth',
            'frm_seventh',
            'frm_eighth',
        );

        $array_intersect = array_intersect($classes, $float_classes);

        if (!empty($array_intersect) && !in_array('frm_first', $classes) && isset($element['block']) && $element['block']) {
            $element['float'] = true;
        }

        $primary_class = false;
        if (!empty($array_intersect)) {
            $primary_class = end($array_intersect);
        }

        if (isset($element['block']) && $element['block']) {
            switch ($primary_class) {
                case 'frm_half':
                    $element['properties']['width'] = '50%';
                    break;
                case 'frm_third':
                    $element['properties']['width'] = '33.3%';
                    break;
                case 'frm_two_thirds':
                    $element['properties']['width'] = '66.67%';
                    break;
                case 'frm_fourth':
                    $element['properties']['width'] = '25%';
                    break;
                case 'frm_three_fourths':
                    $element['properties']['width'] = '75%';
                    break;
                case 'frm_fifth':
                    $element['properties']['width'] = '20%';
                    break;
                case 'frm_two_fifths':
                    $element['properties']['width'] = '80%';
                    break;
                case 'frm_sixth':
                    $element['properties']['width'] = '16.67%';
                    break;
                case 'frm_seventh':
                    $element['properties']['width'] = '14.29%';
                    break;
                case 'frm_eighth':
                    $element['properties']['width'] = '12.5%';
                    break;
                default:
                    break;
            }
        }

        return $element;
    }

    /**
     * Backup form action
     * @param object xml - XML object where to add params for saved item
     */
    public function backup($xml = false) {
        if (class_exists('FrmXMLController')) {

            $ids = array();
            if ($this->get('item') == '-2') {
                $ids[] = $this->get('item1');
                $ids[] = $this->get('item2');
            } else {
                $ids[] = $this->get('item');
            }

            $type = array();
            $type[] = 'forms';

            ob_start();
            FrmXMLController::generate_xml($type, compact('ids'));
            $backup = ob_get_clean();
            $xml->addChildCData('formidable', base64_encode($backup));
        }
    }

    /**
     * Import form action
     * @param object xml - XML object to parse data to import form
     */
    public function import($xml, $options = array()) {

        $updated_items = array();
        $new_form = isset($options['formidable_item_new_form']) && $options['formidable_item_new_form'] ? true : false;

        if (class_exists('FrmXMLHelper') && class_exists('FrmField') && $this->get('item')) {

            if (isset($xml->formidable) && $xml->formidable) {

                $tmp = tempnam($this->helper->get('tmp_dir'), 'e2pdf');
                file_put_contents($tmp, base64_decode((string) $xml->formidable));

                if ($new_form) {
                    add_filter('frm_match_xml_form', array($this, 'filter_frm_match_xml_form'), 10, 2);
                }
                $result = FrmXMLHelper::import_xml($tmp);

                if ($new_form) {
                    remove_filter('frm_match_xml_form', array($this, 'filter_frm_match_xml_form'));
                }
                unlink($tmp);

                FrmXMLHelper::parse_message($result, $message, $errors);

                if ($errors) {
                    return array(
                        'errors' => $errors,
                    );
                } else {
                    if ($this->get('item') == '-2') {
                        if ($this->get('item1') && isset($result['forms'][$this->get('item1')])) {
                            $updated_items[$this->get('item1')] = $result['forms'][$this->get('item1')];
                        }
                        if ($this->get('item2') && isset($result['forms'][$this->get('item2')])) {
                            $updated_items[$this->get('item2')] = $result['forms'][$this->get('item2')];
                        }
                    } elseif (isset($result['forms'][$this->get('item')])) {
                        $updated_items[$this->get('item')] = $result['forms'][$this->get('item')];
                    }
                }
            }
        }
        return $updated_items;
    }

    public function after_import($old_template_id, $new_template_id) {

        if ($this->get('item') && $old_template_id && $new_template_id && $old_template_id != $new_template_id && class_exists('FrmForm')) {
            $forms = array();
            if ($this->get('item') == '-2') {
                if ($this->get('item1')) {
                    $forms[] = $this->get('item1');
                }
                if ($this->get('item2')) {
                    $forms[] = $this->get('item2');
                }
            } else {
                $forms[] = $this->get('item');
            }

            foreach ($forms as $form_id) {
                $form = FrmForm::getOne($form_id);
                if ($form) {
                    if (isset($form->options['success_msg'])) {
                        $success_msg = $this->replace_template_id($form->options['success_msg'], $old_template_id, $new_template_id);
                        if ($success_msg != $form->options['success_msg']) {
                            $update = array(
                                'options' => array(
                                    'success_msg' => $success_msg,
                                ),
                            );
                            FrmForm::update($form_id, $update);
                        }
                    }

                    if (class_exists('FrmFormAction')) {
                        $actions = FrmFormAction::get_action_for_form($form_id, 'email');
                        foreach ($actions as $action) {
                            if (isset($action->post_content['email_message'])) {
                                $email_message = $this->replace_template_id($action->post_content['email_message'], $old_template_id, $new_template_id);
                                if ($email_message != $action->post_content['email_message']) {
                                    $action->post_content['email_message'] = $email_message;
                                    FrmFormAction::clear_cache();
                                    FrmDb::save_settings($action, 'frm_actions');
                                }
                            }
                        }
                    }
                }
            }
        }
        return false;
    }

    public function replace_template_id($message, $old_template_id, $new_template_id) {
        $old_template_id = (int) $old_template_id;
        $new_template_id = (int) $new_template_id;
        $message = preg_replace('/\[(e2pdf-download|e2pdf-view|e2pdf-save|e2pdf-attachment|e2pdf-adobesign|e2pdf-zapier)(.*) id=(|\'|")' . $old_template_id . '(|\'|")(.*)\]/i', '[${1}${2} id=${3}' . $new_template_id . '${4}${5}]', $message);
        return $message;
    }

    public function replace_meta_shortcodes($value) {
        if (false !== strpos($value, '[')) {
            $replace = array(
                '[referer]' => '',
                '[browser]' => '',
                '[referer2]' => '',
                '[browser2]' => '',
                '[entry_num]' => '',
                '[entry_num2]' => '',
            );
            if ((false !== strpos($value, '[referer]') || false !== strpos($value, '[browser]')) && $this->get('cached_entry') && isset($this->get('cached_entry')->description)) {
                if (is_serialized($this->get('cached_entry')->description)) {
                    $description = $this->helper->load('convert')->unserialize(trim($this->get('cached_entry')->description));
                } else {
                    $description = $this->get('cached_entry')->description;
                }
                if (isset($description['referrer'])) {
                    $replace['[referer]'] = @preg_match('/Referer +\d+\:[ \t]+([^\n\t]+)/', $description['referrer'], $m) ? $m[1] : $description['referrer']; // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
                }
                $replace['[browser]'] = isset($description['browser']) ? $description['browser'] : '';
            }
            if ((false !== strpos($value, '[referer2]') || false !== strpos($value, '[browser2]')) && $this->get('cached_entry2') && isset($this->get('cached_entry2')->description)) {
                if (is_serialized($this->get('cached_entry2')->description)) {
                    $description = $this->helper->load('convert')->unserialize(trim($this->get('cached_entry2')->description));
                } else {
                    $description = $this->get('cached_entry2')->description;
                }
                $replace['[referer2]'] = '';
                if (isset($description['referrer'])) {
                    $replace['[referer2]'] = @preg_match('/Referer +\d+\:[ \t]+([^\n\t]+)/', $description['referrer'], $m) ? $m[1] : $description['referrer']; // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
                }
                $replace['[browser2]'] = isset($description['browser']) ? $description['browser'] : '';
            }
            if (false !== strpos($value, '[entry_num]')) {
                $replace['[entry_num]'] = class_exists('FrmDb') && $this->get('cached_entry') ? FrmDb::get_count('frm_items', array("form_id = '{$this->get('cached_form')->id}' AND id <= '{$this->get('cached_entry')->id}' AND 1" => '1')) : '';
            }
            if (false !== strpos($value, '[entry_num2]')) {
                $replace['[entry_num2]'] = class_exists('FrmDb') && $this->get('cached_entry2') ? FrmDb::get_count('frm_items', array("form_id = '{$this->get('cached_form2')->id}' AND id <= '{$this->get('cached_entry2')->id}' AND 1" => '1')) : '';
            }
            $value = str_replace(array_keys($replace), $replace, $value);
        }
        return $value;
    }

    /**
     * Replace Old Formidable shortcodes to new values on pages
     * @param array $pages - List of pages to replace shortcodes
     * @param array $shortcodes_list - List of new/old shortcodes to replace
     * @return array - List of updated pages
     */
    public function pages_replace_shortcodes($pages = array(), $shortcodes_list = array()) {

        foreach ($pages as $page_key => $page) {
            /* Replace page actions and conditions shortcodes */
            if (isset($page['actions']) && !empty($page['actions'])) {
                foreach ($page['actions'] as $action_key => $action_value) {
                    if (isset($action_value['change'])) {
                        $pages[$page_key]['actions'][$action_key]['change'] = $this->replace_shortcodes($action_value['change'], $shortcodes_list);
                    }

                    if (isset($action_value['conditions']) && !empty($action_value['conditions'])) {
                        foreach ($action_value['conditions'] as $condition_key => $condition_value) {
                            if (isset($condition_value['if'])) {
                                $pages[$page_key]['actions'][$action_key]['conditions'][$condition_key]['if'] = $this->replace_shortcodes($condition_value['if'], $shortcodes_list);
                            }

                            if (isset($condition_value['value'])) {
                                $pages[$page_key]['actions'][$action_key]['conditions'][$condition_key]['value'] = $this->replace_shortcodes($condition_value['value'], $shortcodes_list);
                            }
                        }
                    }
                }
            }

            foreach ($page['elements'] as $element_key => $element_value) {
                $pages[$page_key]['elements'][$element_key]['value'] = $this->replace_shortcodes($element_value['value'], $shortcodes_list);

                if (isset($element_value['properties']['option'])) {
                    $pages[$page_key]['elements'][$element_key]['properties']['option'] = $this->replace_shortcodes($element_value['properties']['option'], $shortcodes_list);
                }

                if (isset($element_value['properties']['options'])) {
                    $pages[$page_key]['elements'][$element_key]['properties']['options'] = $this->replace_shortcodes($element_value['properties']['options'], $shortcodes_list);
                }

                if (isset($element_value['properties']['group'])) {
                    $pages[$page_key]['elements'][$element_key]['properties']['group'] = $this->replace_shortcodes($element_value['properties']['group'], $shortcodes_list);
                }

                /* Replace element actions and conditions shortcodes */
                if (isset($element_value['actions']) && !empty($element_value['actions'])) {
                    foreach ($element_value['actions'] as $action_key => $action_value) {
                        if (isset($action_value['change'])) {
                            $pages[$page_key]['elements'][$element_key]['actions'][$action_key]['change'] = $this->replace_shortcodes($action_value['change'], $shortcodes_list);
                        }

                        if (isset($action_value['conditions']) && !empty($action_value['conditions'])) {
                            foreach ($action_value['conditions'] as $condition_key => $condition_value) {
                                if (isset($condition_value['if'])) {
                                    $pages[$page_key]['elements'][$element_key]['actions'][$action_key]['conditions'][$condition_key]['if'] = $this->replace_shortcodes($condition_value['if'], $shortcodes_list);
                                }

                                if (isset($condition_value['value'])) {
                                    $pages[$page_key]['elements'][$element_key]['actions'][$action_key]['conditions'][$condition_key]['value'] = $this->replace_shortcodes($condition_value['value'], $shortcodes_list);
                                }
                            }
                        }
                    }
                }
            }
        }

        return $pages;
    }

    public function actions_replace_shortcodes($actions = array(), $shortcodes_list = array()) {

        foreach ($actions as $action_key => $action_value) {
            if (isset($action_value['change'])) {
                $actions[$action_key]['change'] = $this->replace_shortcodes($action_value['change'], $shortcodes_list);
            }

            if (isset($action_value['conditions']) && !empty($action_value['conditions'])) {
                foreach ($action_value['conditions'] as $condition_key => $condition_value) {
                    if (isset($condition_value['if'])) {
                        $actions[$action_key]['conditions'][$condition_key]['if'] = $this->replace_shortcodes($condition_value['if'], $shortcodes_list);
                    }

                    if (isset($condition_value['value'])) {
                        $actions[$action_key]['conditions'][$condition_key]['value'] = $this->replace_shortcodes($condition_value['value'], $shortcodes_list);
                    }
                }
            }
        }

        return $actions;
    }

    /**
     * Replace Old Formidable shortcodes to new values
     * @param string $content - Value that can contains old shortcodes
     * @param array $shortcodes_list - List of new/old shortcodes to replace
     * @return string - Updated value
     */
    public function replace_shortcodes($content = '', $shortcodes_list = array()) {
        if (false === strpos($content, '[')) {
            return $content;
        }
        foreach ($shortcodes_list as $list_key => $list_value) {
            $content = preg_replace("/(\[|\[(?:[^\]]*?)\s|\[(?:[^\]]*?)(?:field_id|e2pdf-frm-data-values id|e2pdf-frm-lookup-values id)=(?:\'|\"|)){$list_key}(\:(?:.*?)\]|\s(?:.*?)\]|(?:\'|\")(?:.*?)\]|\])/", '${1}' . $list_value . '${2}', $content);
        }
        return $content;
    }

    /**
     * Verify if item and dataset exists
     * @return bool - item and dataset exists
     */
    public function verify() {
        $verify = false;
        if ($this->get('item') == '-2') {
            if ($this->get('cached_entry')) {
                $verify = true;
            } else {
                $this->set('dataset', false);
            }
            if ($this->get('cached_entry2')) {
                $verify = true;
            } else {
                $this->set('dataset2', false);
            }
        } elseif ($this->get('cached_entry')) {
            $verify = true;
        }
        return $verify;
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

            $form = array(
                'form_key' => '',
                'name' => $template->get('title'),
                'description' => '',
                'status' => 'published',
                'options' => array(
                    'success_msg' => sprintf(__('Success. [e2pdf-download id="%s"]', 'e2pdf'), $template->get('ID')),
                ),
            );

            if ($item = FrmForm::create($form)) {
                $template->set('item', $item);

                $checkboxes = array();
                $radios = array();

                $pages = $template->get('pages');

                foreach ($pages as $page_key => $page) {
                    if (isset($page['elements']) && !empty($page['elements'])) {
                        foreach ($page['elements'] as $element_key => $element) {
                            $field_values = array();
                            if ($element['type'] == 'e2pdf-input' || ($element['type'] == 'e2pdf-signature' && !class_exists('FrmSigAppHelper'))) {
                                $field_values = FrmFieldsHelper::setup_new_vars('text', $item);
                            } elseif ($element['type'] == 'e2pdf-signature' && class_exists('FrmSigAppHelper')) {
                                $field_values = FrmFieldsHelper::setup_new_vars('signature', $item);
                            } elseif ($element['type'] == 'e2pdf-textarea') {
                                $field_values = FrmFieldsHelper::setup_new_vars('textarea', $item);
                            } elseif ($element['type'] == 'e2pdf-select') {
                                $field_values = FrmFieldsHelper::setup_new_vars('select', $item);
                                $field_values['options'] = array();
                                if (isset($element['properties']['options'])) {
                                    $field_options = explode("\n", $element['properties']['options']);
                                    foreach ($field_options as $option) {
                                        $field_values['options'][] = $option;
                                    }
                                }
                            } elseif ($element['type'] == 'e2pdf-checkbox') {
                                $field_key = array_search($element['name'], array_column($checkboxes, 'name'));
                                if ($field_key !== false) {
                                    $checkboxes[$field_key]['options'][] = $element['properties']['option'];
                                    $pages[$page_key]['elements'][$element_key]['value'] = '[' . $checkboxes[$field_key]['element_id'] . ']';
                                } else {
                                    $field_values = FrmFieldsHelper::setup_new_vars('checkbox', $item);
                                    $field_values['options'] = array();
                                }
                            } elseif ($element['type'] == 'e2pdf-radio') {
                                if (isset($element['properties']['group']) && $element['properties']['group']) {
                                    $element['name'] = $element['properties']['group'];
                                } else {
                                    $element['name'] = $element['element_id'];
                                }

                                $field_key = array_search($element['name'], array_column($radios, 'name'));
                                if ($field_key !== false) {
                                    $radios[$field_key]['options'][] = $element['properties']['option'];
                                    $pages[$page_key]['elements'][$element_key]['value'] = '[' . $radios[$field_key]['element_id'] . ']';
                                } else {
                                    $field_values = FrmFieldsHelper::setup_new_vars('radio', $item);
                                    $field_values['options'] = array();
                                }
                            }

                            if (!empty($field_values) && $field_id = FrmField::create($field_values)) {
                                $field = FrmField::getOne($field_id);
                                $labels = array();

                                if ($auto_form_shortcode) {
                                    $labels[] = get_option('e2pdf_formidable_use_keys', '0') ? '[' . $field->field_key . ']' : '[' . $field->id . ']';
                                }

                                if ($auto_form_label && $auto_form_label == 'value' && isset($element['value']) && $element['value']) {
                                    $labels[] = $element['value'];
                                } elseif ($auto_form_label && $auto_form_label == 'name' && isset($element['name']) && $element['name']) {
                                    $labels[] = $element['name'];
                                }

                                if (!empty($labels)) {
                                    $update = array(
                                        'name' => implode(' ', $labels),
                                    );
                                    FrmField::update($field_id, $update);
                                }

                                if ($element['type'] == 'e2pdf-textarea') {
                                    $pages[$page_key]['elements'][$element_key]['value'] = get_option('e2pdf_formidable_use_keys', '0') ? '[' . $field->field_key . '  wpautop=0]' : '[' . $field->id . '  wpautop=0]';
                                } else {
                                    $pages[$page_key]['elements'][$element_key]['value'] = get_option('e2pdf_formidable_use_keys', '0') ? '[' . $field->field_key . ']' : '[' . $field->id . ']';
                                }

                                if (isset($element['properties']['esig'])) {
                                    unset($pages[$page_key]['elements'][$element_key]['properties']['esig']);
                                }

                                if ($element['type'] == 'e2pdf-checkbox') {
                                    $checkboxes[] = array(
                                        'name' => $element['name'],
                                        'element_id' => $field_id,
                                        'field_id' => $field->id,
                                        'options' => array(
                                            $element['properties']['option'],
                                        ),
                                    );
                                } elseif ($element['type'] == 'e2pdf-radio') {
                                    $radios[] = array(
                                        'name' => $element['name'],
                                        'element_id' => $field_id,
                                        'field_id' => $field->id,
                                        'options' => array(
                                            $element['properties']['option'],
                                        ),
                                    );
                                }
                            }
                        }
                    }
                }

                foreach ($checkboxes as $element) {
                    $update = array(
                        'options' => $element['options'],
                    );
                    FrmField::update($element['field_id'], $update);
                }

                foreach ($radios as $element) {
                    $update = array(
                        'options' => $element['options'],
                    );
                    FrmField::update($element['field_id'], $update);
                }

                $template->set('pages', $pages);
            }
        }

        return $template;
    }

    /**
     * Init Visual Mapper data
     * @return bool|string - HTML data source for Visual Mapper
     */
    public function visual_mapper() {

        $html = '';
        $source = '';

        if ($this->get('item') && class_exists('FrmformsController')) {
            if ($this->get('cached_form') && $this->get('cached_form')->parent_form_id) {
                return '<div class="e2pdf-vm-error">' . __("Child form doesn't support Visual Mapper", 'e2pdf') . '</div>';
            }
            if (class_exists('FrmProFieldsHelper')) {
                add_filter('frm_get_paged_fields', array($this, 'filter_remove_pagebreaks'), 9);
            }
            add_filter('frm_show_new_entry_page', array($this, 'filter_frm_show_new_entry_page'), 99);
            add_filter('frm_pre_display_form', array($this, 'filter_frm_pre_display_form'));
            $source = FrmformsController::show_form($this->get('item'), '', true, true);
            if (class_exists('FrmProFieldsHelper')) {
                remove_filter('frm_get_paged_fields', array($this, 'filter_remove_pagebreaks'), 9);
            }
            remove_filter('frm_show_new_entry_page', array($this, 'filter_frm_show_new_entry_page'), 99);
            remove_filter('frm_pre_display_form', array($this, 'filter_frm_pre_display_form'));

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
                    'frm_ajax_loading',
                    'wp-editor-tools',
                    'quicktags-toolbar',
                    'frm_button_submit',
                    'frm_range_value',
                    'star-rating',
                    'frm_repeat_buttons',
                    'frm_save_draft',
                    'frm_final_submit',
                    'frm_verify',
                );
                foreach ($remove_by_class as $key => $class) {
                    $elements = $xpath->query("//*[contains(@class, '{$class}')]");
                    foreach ($elements as $element) {
                        $element->parentNode->removeChild($element);
                    }
                }

                $remove_by_tag = array(
                    'link',
                    'style',
                    'script',
                );
                foreach ($remove_by_tag as $key => $tag) {
                    $elements = $xpath->query('//' . $tag);
                    foreach ($elements as $element) {
                        $element->parentNode->removeChild($element);
                    }
                }

                $remove_classes = array(
                    'wp-editor-container',
                    'frm_logic_form',
                    'frm_pos_none',
                );

                foreach ($remove_classes as $key => $class) {
                    $elements = $xpath->query("//*[contains(@class, '{$class}')]");
                    foreach ($elements as $element) {
                        $xml->set_node_value($element, 'class', str_replace($class, '', $xml->get_node_value($element, 'class')));
                    }
                }

                $remove_styles = array(
                    'frm_toggle_container',
                );

                foreach ($remove_styles as $key => $class) {
                    $elements = $xpath->query("//*[contains(@class, '{$class}')]");
                    foreach ($elements as $element) {
                        $xml->set_node_value($element, 'style', '');
                    }
                }

                /*
                 * Metas patterns to replace field names
                 * @since 0.01.42
                 */
                $metas_pattern = array(
                    '/item_meta\[(?:.*?)\]\[0\]\[(.*?)\](?:\[\])?/i' => '$1:1',
                    '/item_meta\[(.*?)\](\[\])?/i' => '$1',
                );

                /* Replace names */
                $metas = $xpath->query("//*[contains(@name, 'item_meta')]");
                foreach ($metas as $element) {
                    $field_id = preg_replace(array_keys($metas_pattern), $metas_pattern, $xml->get_node_value($element, 'name'));
                    if (get_option('e2pdf_formidable_use_keys', '0') && class_exists('FrmField')) {
                        if (strpos($field_id, ':') !== false) {
                            $repeat_data = explode(':', $field_id);
                            if (isset($repeat_data['0'])) {
                                $field_data = FrmField::getOne($repeat_data['0']);
                                if ($field_data) {
                                    $field_id = $field_data->field_key . ':1';
                                }
                            }
                        } else {
                            $field_data = FrmField::getOne($field_id);
                            if ($field_data) {
                                $field_id = $field_data->field_key;
                            }
                        }
                    }
                    $xml->set_node_value($element, 'name', $field_id);
                }

                $frm_combo_inputs_container = $xpath->query("//*[contains(@class, 'frm_combo_inputs_container')]");
                foreach ($frm_combo_inputs_container as $element) {
                    $inputs = $xpath->query('.//input', $element);
                    $selects = $xpath->query('.//select', $element);

                    foreach ($selects as $key => $sub_element) {
                        $field_data = array();
                        preg_match('/(.*?)\[(.*?)\]/i', $xml->get_node_value($sub_element, 'name'), $field_data);
                        if (!empty($field_data) && isset($field_data[1]) && isset($field_data[2])) {
                            $field_id = $field_data[1] . ' show="' . $field_data[2] . '"';
                            $xml->set_node_value($sub_element, 'name', $field_id);
                        }
                    }

                    foreach ($inputs as $key => $sub_element) {
                        $field_data = array();
                        preg_match('/(.*?)\[(.*?)\]/i', $xml->get_node_value($sub_element, 'name'), $field_data);
                        if (!empty($field_data) && isset($field_data[1]) && isset($field_data[2])) {
                            $field_id = $field_data[1] . ' show="' . $field_data[2] . '"';
                            $xml->set_node_value($sub_element, 'name', $field_id);
                        }
                    }
                }

                $frm_dropzone = $xpath->query("//*[contains(@class, 'frm_dropzone')]/parent::*");
                foreach ($frm_dropzone as $element) {
                    $dropzone = $xpath->query(".//*[contains(@class, 'frm_dropzone')]", $element)->item(0);
                    $input = $xpath->query('.//input', $element)->item(0);
                    if ($input && $dropzone) {
                        $input_cloned = $input->cloneNode(true);

                        $xml->set_node_value($input_cloned, 'type', 'text');
                        $xml->set_node_value($input_cloned, 'value', __('File Upload', 'formidable'));
                        $xml->set_node_value($input_cloned, 'style', 'width: 100%; height: 200px; text-align: center;');

                        if (strpos($xml->get_node_value($input_cloned, 'name'), ':') !== false) {
                            $xml->set_node_value($input_cloned, 'name', $xml->get_node_value($input_cloned, 'name') . ' size="full" show_image="0" add_link="0"');
                        } else {
                            $xml->set_node_value($input_cloned, 'name', $xml->get_node_value($input_cloned, 'name') . ' size="full"');
                        }

                        $input->parentNode->replaceChild($input_cloned, $input);
                        $dropzone->parentNode->removeChild($dropzone);
                    }
                }

                /* Replace signature */
                $sigpad = $xpath->query("//*[contains(@class, 'sigPad')]");
                foreach ($sigpad as $element) {
                    $input = $xpath->query('.//input', $element)->item(0);
                    if ($input) {
                        $input_cloned = $input->cloneNode(true);

                        $field_id = preg_replace('/\[(.*?)\]/i', '', $xml->get_node_value($input_cloned, 'name'));
                        if (get_option('e2pdf_formidable_use_keys', '0') && class_exists('FrmField')) {
                            $field_data = FrmField::getOne($field_id);
                            if ($field_data) {
                                $field_id = $field_data->field_key;
                            }
                        }

                        $xml->set_node_value($input_cloned, 'style', 'width: 300px; height: 100px;');
                        $xml->set_node_value($input_cloned, 'name', $field_id);
                        $element->parentNode->replaceChild($input_cloned, $element);
                    }
                }

                /* Replace names */
                $fields = $xpath->query('//input');
                foreach ($fields as $element) {

                    $image_options = false;
                    $separate_value = false;

                    if (class_exists('FrmField') && ($xml->get_node_value($element, 'type') == 'checkbox' || $xml->get_node_value($element, 'type') == 'radio')) {

                        $field_id = $xml->get_node_value($element, 'name');
                        if (false !== strpos($xml->get_node_value($element, 'name'), ':')) {
                            $field_id = substr($xml->get_node_value($element, 'name'), 0, strpos($xml->get_node_value($element, 'name'), ':'));
                        }
                        if (preg_match('/\[(other(.*?[^\]]))\]/', $field_id)) {
                            $field_id = preg_replace('/\[(other(.*?[^\]]))\]/', '', $field_id);
                        }

                        $field_data = FrmField::getOne($field_id);

                        if (isset($field_data->field_options['image_options']) && $field_data->field_options['image_options']) {
                            $image_options = true;
                        }

                        if (isset($field_data->field_options['separate_value']) && $field_data->field_options['separate_value']) {
                            $separate_value = true;
                        }

                        if (isset($field_data->type) && $field_data->type === 'data' && isset($field_data->field_options['form_select'])) {
                            $dynamic_form_id = false;
                            $dynamic_field_id = false;

                            $dynamic_field_id = $field_data->field_options['form_select'];
                            $dynamic_field_data = FrmField::getOne($dynamic_field_id);
                            if ($dynamic_field_data) {
                                $dynamic_form_id = $dynamic_field_data->form_id;
                            }

                            $options = array();
                            if (class_exists('FrmEntry') && class_exists('FrmEntryMeta')) {
                                $where = array(
                                    'it.form_id' => $dynamic_form_id,
                                );
                                $entries_tmp = FrmEntry::getAll($where, ' ORDER BY id ASC');
                                foreach ($entries_tmp as $key => $entry) {
                                    $options[] = array(
                                        'label' => FrmEntryMeta::get_meta_value($entry, $dynamic_field_id),
                                        'value' => $key,
                                    );
                                }
                            }
                            $field_data->options = $options;
                        }

                        if (isset($field_data->options) && is_array($field_data->options)) {

                            $field_options = $field_data->options;
                            $field_value = $xml->get_node_value($element, 'value');

                            $field_key = false;
                            foreach ($field_options as $fv_key => $fv) {
                                if (isset($fv['value']) && $fv['value'] == $field_value) {
                                    $field_key = $fv_key;
                                    break;
                                }
                            }

                            if ($field_key !== false) {
                                if ($image_options && $separate_value) {
                                    $field_label = isset($field_options[$field_key]['value']) ? $field_options[$field_key]['value'] : $field_value;
                                } else {
                                    $field_label = isset($field_options[$field_key]['label']) ? $field_options[$field_key]['label'] : $field_value;
                                }
                                $xml->set_node_value($element, 'value', $field_label);
                            } elseif ($xml->get_node_value($element, 'type') == 'radio') {
                                $xml->set_node_value($element, 'value', '[' . $field_id . ' show="value" key="other"]');
                            } else {
                                $field_key = array_search($field_value, $field_options);
                                if ($field_key !== false) {
                                    $xml->set_node_value($element, 'value', '[' . $field_id . ' show="value" key="' . $field_key . '"]');
                                }
                            }
                        }
                    }

                    $name = false;
                    if ($xml->get_node_value($element, 'type') == 'text') {
                        if (preg_match('/other\[(.*?[^\]])\]\[(other(.*?[^\]]))\]/', $xml->get_node_value($element, 'name'))) {
                            $name = preg_replace('/other\[(.*?[^\]])\]\[(other(.*?[^\]]))\]/', '$1 show="value" key="$2"', $xml->get_node_value($element, 'name'));
                        } elseif (preg_match('/other\[(.*?[^\]])\]/', $xml->get_node_value($element, 'name'))) {
                            $name = preg_replace('/other\[(.*?[^\]])\]/', '$1 show="value" key="other"', $xml->get_node_value($element, 'name'));
                        }
                    } elseif ($xml->get_node_value($element, 'type') == 'checkbox') {
                        if (preg_match('/\[(other(.*?[^\]]))\]/', $xml->get_node_value($element, 'name'))) {
                            $name = preg_replace('/\[(other(.*?[^\]]))\]/', '', $xml->get_node_value($element, 'name'));
                        } else {
                            if ($image_options) {
                                $name = $xml->get_node_value($element, 'name') . ' show="value"';
                            }
                        }
                    } elseif ($xml->get_node_value($element, 'type') == 'radio') {
                        if ($image_options) {
                            $name = $xml->get_node_value($element, 'name') . ' show="value"';
                        }
                    }

                    if (!$name) {
                        $name = $xml->get_node_value($element, 'name');
                    }

                    $xml->set_node_value($element, 'name', '[' . $name . ']');
                }

                /* Replace names */
                $textareas = $xpath->query('//textarea');
                foreach ($textareas as $element) {
                    $xml->set_node_value($element, 'name', '[' . $xml->get_node_value($element, 'name') . ' wpautop=0]');
                }

                /* Replace names */
                $selects = $xpath->query('//select');
                foreach ($selects as $element) {

                    if (class_exists('FrmField')) {

                        $options = $xpath->query('.//option', $element);
                        $field_id = $xml->get_node_value($element, 'name');

                        /* Time Field names */
                        if (false !== strpos($xml->get_node_value($element, 'name'), ':')) {
                            $field_id = substr($xml->get_node_value($element, 'name'), 0, strpos($xml->get_node_value($element, 'name'), ':'));
                        }
                        $field_id = str_replace(array('[H]', '[m]', '[A]'), '', $field_id);

                        $field_data = FrmField::getOne($field_id);
                        if (isset($field_data->type) && $field_data->type === 'lookup') {
                            foreach ($options as $option) {
                                $option->parentNode->removeChild($option);
                            }
                            $wrapper = $dom->createElement('option');
                            $wrapper_atts = array(
                                'value' => '[e2pdf-frm-lookup-values id="' . $field_id . '"]',
                            );
                            foreach ($wrapper_atts as $key => $value) {
                                $attr = $dom->createAttribute($key);
                                $attr->value = $value;
                                $wrapper->appendChild($attr);
                            }
                            $element->appendChild($wrapper);
                        } elseif (isset($field_data->type) && $field_data->type === 'data') {
                            foreach ($options as $option) {
                                $option->parentNode->removeChild($option);
                            }
                            $wrapper = $dom->createElement('option');
                            $wrapper_atts = array(
                                'value' => '[e2pdf-frm-data-values id="' . $field_id . '"]',
                            );
                            foreach ($wrapper_atts as $key => $value) {
                                $attr = $dom->createAttribute($key);
                                $attr->value = $value;
                                $wrapper->appendChild($attr);
                            }
                            $element->appendChild($wrapper);
                        } elseif (isset($field_data->type) && $field_data->type === 'time') {
                            $replace = array(
                                '[m]' => ' format="i"',
                                '[A]' => ' format="A"',
                            );
                            if (isset($field_data->field_options['clock']) && $field_data->field_options['clock'] == '12') {
                                $replace['[H]'] = ' format="g"';
                            } else {
                                $replace['[H]'] = ' format="H"';
                            }
                            $xml->set_node_value($element, 'name', str_replace(array_keys($replace), $replace, $xml->get_node_value($element, 'name')));
                        }

                        if (isset($field_data->options) && is_array($field_data->options)) {
                            $field_options = $field_data->options;
                            foreach ($options as $option) {
                                $field_value = $xml->get_node_value($option, 'value');
                                foreach ($field_options as $field_option) {
                                    if (isset($field_option['value']) && $field_option['value'] === $field_value) {
                                        $field_label = isset($field_option['label']) ? $field_option['label'] : $field_value;
                                        $xml->set_node_value($option, 'value', $field_label);
                                    }
                                }
                            }
                        }
                    }

                    $xml->set_node_value($element, 'name', '[' . $xml->get_node_value($element, 'name') . ']');
                }

                /* Show Total Formatted Fields */
                $total_formatted = $xpath->query("//*[contains(@class, 'frm_total_formatted')]/parent::*");
                foreach ($total_formatted as $element) {
                    $elements = $xpath->query("//*[contains(@class, 'frm_hidden')]");
                    foreach ($elements as $element) {
                        $xml->set_node_value($element, 'class', str_replace('frm_hidden', '', $xml->get_node_value($element, 'class')));
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
     * Convert Field name to Value
     * @since 0.01.34
     * @param string $name - Field name
     * @return bool|string - Converted value or false
     */
    public function auto_map($name = false) {

        $item = $this->get('item');

        if ($item && class_exists('FrmField')) {
            $where = array(
                'fi.form_id' => (int) $item,
                array(
                    'or' => 1,
                    'fi.field_key' => $name,
                    'fi.name' => $name,
                ),
            );
            $field = FrmField::getAll($where, 'id ASC', '1');

            if ($field && is_object($field)) {
                $field_id = get_option('e2pdf_formidable_use_keys', '0') ? $field->field_key : $field->id;
                if ($field->type == 'textarea' || $field->type == 'rte') {
                    return '[' . $field_id . '  wpautop=0]';
                } else {
                    return '[' . $field_id . ']';
                }
            }
        }

        return false;
    }

    /**
     * Load additional shortcodes for this extension
     */
    public function load_shortcodes() {
        add_shortcode('e2pdf-frm-entry-values', array($this, 'shortcode_e2pdf_frm_entry_values'));
        add_shortcode('e2pdf-frm-lookup-values', array($this, 'shortcode_e2pdf_frm_lookup_values'));
        add_shortcode('e2pdf-frm-data-values', array($this, 'shortcode_e2pdf_frm_data_values'));
        add_shortcode('e2pdf-frm-repeatable', array($this, 'shortcode_e2pdf_frm_repeatable'));
    }

    /**
     * [e2pdf-frm-repeatable id="entry_id' field_id="field_id"] shortcode
     * @param array $atts - Atributes for shortcode
     * @return string - Output of shortcode
     */
    public function shortcode_e2pdf_frm_repeatable($atts = array()) {
        $id = (int) $atts['id'];
        $field_id = $atts['field_id'];

        $response = '[frm-field-value field_id="' . $field_id . '" entry="' . $id . '"]';
        return $response;
    }

    /**
     * [e2pdf-frm-entry-values id='{form_id}' field_id='{field_id}' separator=''] shortcode
     * @param array $atts - Atributes for shortcode
     * @return string - Output of shortcodes
     */
    public function shortcode_e2pdf_frm_entry_values($atts = array()) {
        $form_id = (int) $atts['id'];
        $field_id = $atts['field_id'];
        $separator = isset($atts['separator']) ? $atts['separator'] : "\r\n";

        $response = '';
        $values = array();
        if ($form_id && $field_id && class_exists('FrmEntry') && class_exists('FrmEntryMeta')) {

            $where = array(
                'it.form_id' => $form_id,
            );
            $entries_tmp = FrmEntry::getAll($where, ' ORDER BY id ASC');
            foreach ($entries_tmp as $key => $entry) {
                $values[] = FrmEntryMeta::get_meta_value($entry, $field_id);
            }
        }
        $response = implode($separator, $values);
        return $response;
    }

    /**
     * [e2pdf-frm-lookup-values id='{field_id}'] shortcode
     * @param array $atts - Atributes for shortcode
     * @return string - Output of shortcode
     */
    public function shortcode_e2pdf_frm_lookup_values($atts = array()) {

        $id = isset($atts['id']) ? $atts['id'] : false;
        $user_id = isset($atts['user_id']) ? $atts['user_id'] : '0';
        $separator = isset($atts['separator']) ? $atts['separator'] : "\r\n";

        $response = '';
        $values = array();

        if ($id && class_exists('FrmField') && class_exists('FrmAppHelper') && class_exists('FrmFieldsHelper')) {
            $field = FrmField::getOne($id);
            if ($field && $field->type === 'lookup') {

                $frm_filter = false;
                if (class_exists('FrmProFieldsHelper') && method_exists('FrmProFieldsHelper', 'add_default_field_settings')) {
                    if (!has_filter('frm_default_field_options', 'FrmProFieldsHelper::add_default_field_settings')) {
                        add_filter('frm_default_field_options', 'FrmProFieldsHelper::add_default_field_settings', 10, 2);
                        $frm_filter = true;
                    }
                }

                $args = array();
                if (is_admin()) {
                    $original_user_id = get_current_user_id();
                    wp_set_current_user($user_id);
                    if (!current_user_can('administrator')) {
                        add_filter('frm_lookup_is_current_user_filter_needed', array($this, 'filter_frm_lookup_is_current_user_filter_needed'), 10, 3);
                    }
                }

                $field_array = FrmAppHelper::start_field_array($field);
                FrmFieldsHelper::prepare_new_front_field($field_array, $field, $args);
                $field_array = array_merge($field->field_options, $field_array);
                if (isset($field_array['options']) && is_array($field_array['options'])) {
                    $values = $field_array['options'];
                }

                if (is_admin()) {
                    if (!current_user_can('administrator')) {
                        remove_filter('frm_lookup_is_current_user_filter_needed', array($this, 'filter_frm_lookup_is_current_user_filter_needed'));
                    }
                    wp_set_current_user($original_user_id);
                }
                if ($frm_filter) {
                    remove_filter('frm_default_field_options', 'FrmProFieldsHelper::add_default_field_settings');
                }
            }
        }

        $response = implode($separator, $values);
        return $response;
    }

    /**
     * [e2pdf-frm-data-values id='{field_id}'] shortcode
     * @param array $atts - Atributes for shortcode
     * @return string - Output of shortcode
     */
    public function shortcode_e2pdf_frm_data_values($atts = array()) {

        $id = isset($atts['id']) ? $atts['id'] : false;
        $user_id = isset($atts['user_id']) ? $atts['user_id'] : '0';
        $separator = isset($atts['separator']) ? $atts['separator'] : "\r\n";

        $response = '';
        $values = array();

        if ($id && class_exists('FrmField') && class_exists('FrmAppHelper') && class_exists('FrmFieldsHelper')) {
            $field = FrmField::getOne($id);
            if ($field && $field->type === 'data') {
                $frm_filter = false;
                if (class_exists('FrmProFieldsHelper') && method_exists('FrmProFieldsHelper', 'add_default_field_settings')) {
                    if (!has_filter('frm_default_field_options', 'FrmProFieldsHelper::add_default_field_settings')) {
                        add_filter('frm_default_field_options', 'FrmProFieldsHelper::add_default_field_settings', 10, 2);
                        $frm_filter = true;
                    }
                }

                $args = array();
                if (is_admin()) {
                    $original_user_id = get_current_user_id();
                    wp_set_current_user($user_id);
                    if (current_user_can('administrator')) {
                        $field->field_options['restrict'] = '0';
                    }
                }

                $field_array = FrmAppHelper::start_field_array($field);
                FrmFieldsHelper::prepare_new_front_field($field_array, $field, $args);

                $field_array = array_merge($field->field_options, $field_array);
                if (isset($field_array['options']) && is_array($field_array['options'])) {
                    $values = $field_array['options'];
                }

                if (is_admin()) {
                    wp_set_current_user($original_user_id);
                }
                if ($frm_filter) {
                    remove_filter('frm_default_field_options', 'FrmProFieldsHelper::add_default_field_settings');
                }
            }
        }

        $response = implode($separator, $values);
        return $response;
    }

    /**
     * Get styles for generating Map Field function
     * @return array - List of css files to load
     */
    public function styles($item_id = false) {

        $styles = array();
        if (class_exists('FrmStylesHelper')) {
            $uploads = FrmStylesHelper::get_upload_base();
            $saved_css_path = '/formidable/css/formidablepro.css';
            if (is_readable($uploads['basedir'] . $saved_css_path)) {
                $url = $uploads['baseurl'] . $saved_css_path;
            } else {
                $url = admin_url('admin-ajax.php?action=frmpro_css');
            }
            $styles[] = $url;
            $styles[] = plugins_url('css/extension/formidable.css?v=' . time(), $this->helper->get('plugin_file_path'));
        }
        return $styles;
    }

    public function merged_items() {
        return true;
    }

    public function hook_formidable_entry_view($entry) {
        if (!empty($entry->form_id)) {
            $hooks = $this->helper->load('hooks')->get('formidable', 'hook_formidable_entry_view', $entry->form_id);
            if (!empty($hooks)) {
                echo '<h3>' . apply_filters('e2pdf_hook_section_title', __('E2Pdf Actions', 'e2pdf'), 'hook_formidable_entry_view') . '</h3>';
                echo '<div class="inside">';
                foreach ($hooks as $hook) {
                    $action = apply_filters('e2pdf_hook_action_button',
                            array(
                                'html' => '<div class="misc-pub-section"><a class="e2pdf-download-hook" target="_blank" title="%2$s" href="%1$s"><span class="dashicons dashicons-pdf"></span> %2$s</a></div>',
                                'url' => $this->helper->get_url(
                                        array(
                                            'page' => 'e2pdf',
                                            'action' => 'export',
                                            'id' => $hook,
                                            'dataset' => $entry->id,
                                        ), 'admin.php?'
                                ),
                                'title' => 'PDF #' . $hook
                            ), 'hook_formidable_entry_view', $hook, $entry->id
                    );
                    if (!empty($action)) {
                        echo sprintf(
                                $action['html'], $action['url'], $action['title']
                        );
                    }
                }
                echo '</div>';
            }
        }
    }

    public function hook_formidable_entry_edit($entry) {
        if (!empty($entry->form_id)) {
            $hooks = $this->helper->load('hooks')->get('formidable', 'hook_formidable_entry_edit', $entry->form_id);
            if (!empty($hooks)) {
                echo '<h3>' . apply_filters('e2pdf_hook_section_title', __('E2Pdf Actions', 'e2pdf'), 'hook_formidable_entry_view') . '</h3>';
                echo '<div class="inside">';
                foreach ($hooks as $hook) {
                    $action = apply_filters('e2pdf_hook_action_button',
                            array(
                                'html' => '<div class="misc-pub-section"><a class="e2pdf-download-hook" target="_blank" title="%2$s" href="%1$s"><span class="dashicons dashicons-pdf"></span> %2$s</a></div>',
                                'url' => $this->helper->get_url(
                                        array(
                                            'page' => 'e2pdf',
                                            'action' => 'export',
                                            'id' => $hook,
                                            'dataset' => $entry->id,
                                        ), 'admin.php?'
                                ),
                                'title' => 'PDF #' . $hook
                            ), 'hook_formidable_entry_edit', $hook, $entry->id
                    );
                    if (!empty($action)) {
                        echo sprintf(
                                $action['html'], $action['url'], $action['title']
                        );
                    }
                }
                echo '</div>';
            }
        }
    }

    public function hook_formidable_row_actions($actions, $entry) {
        if (!empty($entry->form_id) && !empty($entry->id)) {
            $hooks = $this->helper->load('hooks')->get('formidable', 'hook_formidable_row_actions', $entry->form_id);
            foreach ($hooks as $hook) {
                $action = apply_filters('e2pdf_hook_action_button',
                        array(
                            'html' => '<a class="e2pdf-download-hook" target="_blank" href="%s">%s</a>',
                            'url' => $this->helper->get_url(
                                    array(
                                        'page' => 'e2pdf',
                                        'action' => 'export',
                                        'id' => $hook,
                                        'dataset' => $entry->id,
                                    ), 'admin.php?'
                            ),
                            'title' => 'PDF #' . $hook
                        ), 'hook_formidable_row_actions', $hook, $entry->id
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
