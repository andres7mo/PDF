<?php

/**
 * File: /extension/wpcf7.php
 *
 * @package  E2Pdf
 * @license  GPLv3
 * @link     https://e2pdf.com
 */
if (!defined('ABSPATH')) {
    die('Access denied.');
}

class Extension_E2pdf_Wpcf7 extends Model_E2pdf_Model {

    private $options;
    private $info = array(
        'key' => 'wpcf7',
        'title' => 'Contact Form 7',
    );

    // info
    public function info($key = false) {
        if ($key && isset($this->info[$key])) {
            return $this->info[$key];
        } else {
            return array(
                $this->info['key'] => $this->info['title'],
            );
        }
    }

    // active
    public function active() {
        if (defined('E2PDF_WPCF7_EXTENSION') || $this->helper->load('extension')->is_plugin_active('contact-form-7/wp-contact-form-7.php')) {
            return true;
        }
        return false;
    }

    // set
    public function set($key, $value) {
        if (!isset($this->options)) {
            $this->options = new stdClass();
        }
        $this->options->$key = $value;
        switch ($key) {
            case 'item':
                $this->set('cached_form', false);
                if ($this->get('item') && class_exists('WPCF7_ContactForm')) {
                    $form = WPCF7_ContactForm::get_instance($this->get('item'));
                    if ($form) {
                        $this->set('cached_form', $form);
                    }
                }
                break;
            case 'dataset':
                global $wpdb;
                $this->set('cached_submission', false);
                $this->set('cached_entry', array());
                if ($this->get('cached_form') && $this->get('dataset') && $this->get_storing_engine() !== false) {
                    if ($this->get_storing_engine() == '1') {
                        $vxcf_form = new vxcf_form();
                        $entry = $vxcf_form::get_data_object()->get_lead($this->get('dataset'));
                        if ($entry) {
                            $posted_data = array();
                            $lead = $vxcf_form::get_data_object()->attach_lead_detail($entry);
                            foreach ($lead['detail'] as $field_key => $field_value) {
                                if ($field_value) {
                                    if (0 === strpos($field_key, 'file-')) {
                                        if (is_serialized($field_value)) {
                                            $paths = $this->helper->load('convert')->unserialize(trim($field_value));
                                        } else {
                                            $paths = array();
                                        }
                                        if (is_array($paths)) {
                                            $urls = array_map(
                                                    function ($uploaded_file) {
                                                        $vxcf_form = new vxcf_form();
                                                        $upload = $vxcf_form::get_upload_dir();
                                                        return $upload['url'] . $uploaded_file;
                                                    }, $paths
                                            );
                                        } else {
                                            $urls = array();
                                        }
                                        $posted_data[$field_key] = $urls;
                                    } else {
                                        if (is_serialized($field_value)) {
                                            $posted_data[$field_key] = $this->helper->load('convert')->unserialize(trim($field_value));
                                        } else {
                                            $posted_data[$field_key] = $field_value;
                                        }
                                    }
                                } else {
                                    $posted_data[$field_key] = '';
                                }
                            }

                            $cached_entry = array(
                                'posted_data' => $posted_data,
                                'uploaded_files' => array(),
                                'meta' => array(
                                    'timestamp' => !empty($lead['created']) ? strtotime($lead['created']) : '',
                                    'remote_ip' => !empty($lead['ip']) ? $lead['ip'] : '',
                                    'remote_port' => '',
                                    'user_agent' => '',
                                    'url' => !empty($lead['url']) ? $lead['url'] : '',
                                    'unit_tag' => '',
                                    'container_post_id' => '',
                                    'current_user_id' => !empty($lead['user_id']) ? $lead['user_id'] : '0',
                                    'do_not_store' => '',
                                ),
                            );
                            $this->set('cached_entry', $cached_entry);
                        }
                    } elseif ($this->get_storing_engine() == '2') {
                        $condition = array(
                            'form_id' => array(
                                'condition' => '=',
                                'value' => $this->get('dataset'),
                                'type' => '%d',
                            ),
                        );
                        $where = $this->helper->load('db')->prepare_where($condition);
                        $cfdb = apply_filters('cfdb7_database', $wpdb);
                        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
                        $entry = $wpdb->get_row($wpdb->prepare('SELECT * FROM `' . $cfdb->prefix . 'db7_forms`' . $where['sql'] . '', $where['filter']));
                        if ($entry) {
                            $form_value = $this->helper->load('convert')->unserialize($entry->form_value);
                            $posted_data = array();
                            $upload_dir = wp_upload_dir();
                            $cfdb7_dir_url = $upload_dir['baseurl'] . '/cfdb7_uploads';
                            foreach ($form_value as $field_key => $field_value) {
                                $field_value = str_replace(
                                        array('&quot;', '&#039;', '&#047;', '&#092;'),
                                        array('"', "'", '/', '\\'), $field_value
                                );

                                if (strpos($field_key, 'cfdb7_file') !== false) {
                                    $posted_data[substr($field_key, 0, -10)] = empty($field_value) ? '' : $cfdb7_dir_url . '/' . $field_value;
                                    continue;
                                }
                                if (is_array($field_value)) {
                                    $posted_data[$field_key] = implode(', ', $field_value);
                                    continue;
                                }
                                $posted_data[$field_key] = $field_value;
                            }
                            $cached_entry = array(
                                'posted_data' => $posted_data,
                                'uploaded_files' => array(),
                                'meta' => array(
                                    'timestamp' => strtotime($entry->form_date),
                                    'remote_ip' => '',
                                    'remote_port' => '',
                                    'user_agent' => '',
                                    'url' => '',
                                    'unit_tag' => '',
                                    'container_post_id' => '',
                                    'current_user_id' => '0',
                                    'do_not_store' => '',
                                ),
                            );
                            $this->set('cached_entry', $cached_entry);
                        }
                    } elseif ($this->get_storing_engine() == '3') {
                        $condition = array(
                            'cf7_id' => array(
                                'condition' => '=',
                                'value' => $this->get('item'),
                                'type' => '%d',
                            ),
                            'data_id' => array(
                                'condition' => '=',
                                'value' => $this->get('dataset'),
                                'type' => '%d',
                            ),
                        );
                        $where = $this->helper->load('db')->prepare_where($condition);
                        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
                        $entry = $wpdb->get_results($wpdb->prepare('SELECT * FROM `' . VSZ_CF7_DATA_ENTRY_TABLE_NAME . '`' . $where['sql'] . '', $where['filter']));

                        if (!empty($entry)) {
                            $posted_data = array();
                            foreach ($entry as $k => $v) {
                                if (false !== strpos($v->name, 'checkbox')) {
                                    if ($v->value) {
                                        $checkboxes = explode(PHP_EOL, stripslashes($v->value));
                                    } else {
                                        $checkboxes = array();
                                    }
                                    $posted_data[$v->name] = implode(', ', $checkboxes);
                                } else {
                                    $posted_data[$v->name] = stripslashes($v->value);
                                }
                            }
                            $cached_entry = array(
                                'posted_data' => $posted_data,
                                'uploaded_files' => array(),
                                'meta' => array(
                                    'timestamp' => isset($posted_data['submit_time']) ? strtotime($posted_data['submit_time']) : '',
                                    'remote_ip' => isset($posted_data['submit_ip']) ? $posted_data['submit_ip'] : '',
                                    'remote_port' => '',
                                    'user_agent' => '',
                                    'url' => '',
                                    'unit_tag' => '',
                                    'container_post_id' => '',
                                    'current_user_id' => '0',
                                    'do_not_store' => '',
                                ),
                            );
                            $this->set('cached_entry', $cached_entry);
                        }
                    } else {
                        $entry = new Model_E2pdf_Dataset();
                        if ($entry->load($this->get('dataset'), $this->get('item'), 'wpcf7')) {
                            $this->set('cached_entry', $entry->get('entry'));
                        }
                    }

                    if ($this->get('cached_entry')) {
                        if (WPCF7_Submission::get_instance() == null) {

                            // Digital Signature For Contact Form 7 PHP Warning Fix
                            remove_filter('wpcf7_validate_signature', 'wpcf7_signature_validation_filter', 10, 2);
                            remove_filter('wpcf7_validate_signature*', 'wpcf7_signature_validation_filter', 10, 2);

                            $form = WPCF7_ContactForm::get_instance($this->get('item'));
                            add_action('wpcf7_before_send_mail', array($this, 'action_wpcf7_before_send_mail_abort'), 98, 2);
                            $submission = WPCF7_Submission::get_instance($form);
                            remove_action('wpcf7_before_send_mail', array($this, 'action_wpcf7_before_send_mail_abort'), 98, 2);
                        } else {
                            $submission = WPCF7_Submission::get_instance();
                        }

                        if (class_exists('ReflectionProperty')) {
                            $reflection = new ReflectionProperty(get_class($submission), 'posted_data');
                            $reflection->setAccessible(true);
                            $reflection->setValue($submission, isset($this->get('cached_entry')['posted_data']) ? $this->get('cached_entry')['posted_data'] : array());

                            $reflection = new ReflectionProperty(get_class($submission), 'uploaded_files');
                            $reflection->setAccessible(true);
                            $reflection->setValue($submission, isset($this->get('cached_entry')['uploaded_files']) ? $this->get('cached_entry')['uploaded_files'] : array());

                            // Contact Form Warning fix on $submission->__destruct()
                            if (defined('WPCF7_VERSION') && WPCF7_VERSION >= '5.8.6' && isset($this->get('cached_entry')['uploaded_files'])) {
                                foreach ((array) $this->get('cached_entry')['uploaded_files'] as $file_path) {
                                    $paths = (array) $file_path;
                                    foreach ($paths as $path) {
                                        // phpcs:disable WordPress.PHP.NoSilencedErrors.Discouraged
                                        if (!@file_exists($path)) {
                                            @mkdir(dirname($path));
                                        }
                                        // phpcs:enable
                                    }
                                }
                            }

                            $reflection = new ReflectionProperty(get_class($submission), 'meta');
                            $reflection->setAccessible(true);
                            $reflection->setValue($submission, isset($this->get('cached_entry')['meta']) ? $this->get('cached_entry')['meta'] : array());
                            $this->set('cached_submission', $submission);
                        }
                    }
                }
                break;
            default:
                break;
        }
        return true;
    }

    // get
    public function get($key) {
        if (isset($this->options->$key)) {
            $value = $this->options->$key;
        } else {
            switch ($key) {
                case 'args':
                case 'cached_entry':
                    $value = array();
                    break;
                default:
                    $value = false;
                    break;
            }
        }
        return $value;
    }

    // load actions
    public function load_actions() {
        add_action('wpcf7_before_send_mail', array($this, 'action_wpcf7_before_send_mail'), 99, 2);
        add_action('wpcf7_mail_sent', array($this, 'action_wpcf7_mail_sent'));
        add_action('cfdb7_after_save_data', array($this, 'action_cfdb7_after_save_data'));
        add_action('vsz_cf7_after_insert_db', array($this, 'action_vsz_cf7_after_insert_db'), 10, 3);
    }

    // load filters
    public function load_filters() {
        add_filter('e2pdf_model_options_get_options_options', array($this, 'filter_e2pdf_model_options_get_options_options'));
        add_filter('vxcf_after_saving_addons', array($this, 'filter_vxcf_after_saving_addons'), 10, 4);
    }

    // items
    public function items() {
        $items = array();
        if (class_exists('WPCF7_ContactForm')) {
            $forms = WPCF7_ContactForm::find();
            if ($forms) {
                foreach ($forms as $key => $form) {
                    $items[] = $this->item($form->id);
                }
            }
        }
        return $items;
    }

    // item
    public function item($item_id = false) {
        $item_id = (int) $item_id;
        if (!$item_id && $this->get('item')) {
            $item_id = $this->get('item');
        }
        $form = false;
        if (class_exists('WPCF7_ContactForm')) {
            $form = WPCF7_ContactForm::get_instance($item_id);
        }
        $item = new stdClass();
        if ($form) {
            $item->id = (string) $form->id();
            $item->url = $this->helper->get_url(
                    array(
                        'page' => 'wpcf7',
                        'post' => $item_id,
                        'action' => 'edit',
                    )
            );
            $item->name = $form->title();
        } else {
            $item->id = '';
            $item->url = 'javascript:void(0);';
            $item->name = '';
        }
        return $item;
    }

    // datasets
    public function datasets($item_id = false, $name = false) {
        global $wpdb;
        $datasets = array();
        if ($item_id) {
            if ($this->get_storing_engine() == '1') {
                $vxcf_form = new vxcf_form();
                $entries = $vxcf_form::get_data_object()->get_entries('cf_' . $item_id, 'all');
                if (!empty($entries['result'])) {
                    $this->set('item', $item_id);
                    foreach ($entries['result'] as $key => $entry) {
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
            } elseif ($this->get_storing_engine() == '2') {
                $condition = array(
                    'form_post_id' => array(
                        'condition' => '=',
                        'value' => $item_id,
                        'type' => '%s',
                    ),
                );
                $order_condition = array(
                    'orderby' => 'form_id',
                    'order' => 'desc',
                );
                $where = $this->helper->load('db')->prepare_where($condition);
                $orderby = $this->helper->load('db')->prepare_orderby($order_condition);

                $cfdb = apply_filters('cfdb7_database', $wpdb);
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
                $entries = $wpdb->get_results($wpdb->prepare('SELECT * FROM `' . $cfdb->prefix . 'db7_forms`' . $where['sql'] . $orderby . '', $where['filter']));

                if ($entries) {
                    $this->set('item', $item_id);
                    foreach ($entries as $key => $entry) {
                        $this->set('dataset', $entry->form_id);
                        $entry_title = $this->render($name);
                        if (!$entry_title) {
                            $entry_title = $entry->form_id;
                        }
                        $datasets[] = array(
                            'key' => $entry->form_id,
                            'value' => $entry_title,
                        );
                    }
                }
            } elseif ($this->get_storing_engine() == '3') {
                $condition = array(
                    'cf7_id' => array(
                        'condition' => '=',
                        'value' => $item_id,
                        'type' => '%s',
                    ),
                );
                $order_condition = array(
                    'orderby' => 'data_id',
                    'order' => 'desc',
                );
                $where = $this->helper->load('db')->prepare_where($condition);
                $orderby = $this->helper->load('db')->prepare_orderby($order_condition);
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
                $entries = $wpdb->get_results($wpdb->prepare('SELECT * FROM `' . VSZ_CF7_DATA_ENTRY_TABLE_NAME . '`' . $where['sql'] . ' GROUP BY data_id' . $orderby . '', $where['filter']));

                if ($entries) {
                    $this->set('item', $item_id);
                    foreach ($entries as $key => $entry) {
                        $this->set('dataset', $entry->data_id);
                        $entry_title = $this->render($name);
                        if (!$entry_title) {
                            $entry_title = $entry->data_id;
                        }
                        $datasets[] = array(
                            'key' => $entry->data_id,
                            'value' => $entry_title,
                        );
                    }
                }
            } else {
                $condition = array(
                    'extension' => array(
                        'condition' => '=',
                        'value' => 'wpcf7',
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
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
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
        }
        return $datasets;
    }

    // dataset actions
    public function get_dataset_actions($dataset_id = false) {
        global $wpdb;
        $dataset_id = (int) $dataset_id;
        if (!$dataset_id) {
            return;
        }
        $data = new stdClass();
        if ($this->get_storing_engine() == '1') {
            $data->view = $this->helper->get_url(
                    array(
                        'page' => 'vxcf_leads',
                        'tab' => 'entries',
                        'id' => $dataset_id,
                    )
            );
            $data->delete = false;
        } elseif ($this->get_storing_engine() == '2') {
            $condition = array(
                'form_id' => array(
                    'condition' => '=',
                    'value' => $dataset_id,
                    'type' => '%d',
                ),
            );
            $where = $this->helper->load('db')->prepare_where($condition);
            $cfdb = apply_filters('cfdb7_database', $wpdb);
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
            $entry = $wpdb->get_row($wpdb->prepare('SELECT * FROM `' . $cfdb->prefix . 'db7_forms`' . $where['sql'] . '', $where['filter']));
            $data->view = $this->helper->get_url(
                    array(
                        'page' => 'cfdb7-list.php',
                        'fid' => $entry->form_post_id,
                        'ufid' => $dataset_id,
                    )
            );
            $data->delete = false;
        } elseif ($this->get_storing_engine() == '3') {
            $data->view = false;
            $data->delete = false;
        } else {
            $data->view = false;
            $data->delete = true;
        }
        return $data;
    }

    // template actions
    public function get_template_actions($template = false) {
        $template = (int) $template;
        if (!$template) {
            return;
        }
        $actions = new stdClass();
        if ($this->get_storing_engine() == '0') {
            $actions->delete = true;
        } else {
            $actions->delete = false;
        }
        return $actions;
    }

    // render
    public function render($value, $field = array(), $convert_shortcodes = true, $raw = false) {
        $value = $this->render_shortcodes($value, $field);
        if (!$raw) {
            $value = $this->strip_shortcodes($value);
            $value = $this->convert_shortcodes($value, $convert_shortcodes, isset($field['type']) && $field['type'] == 'e2pdf-html' ? true : false);
            $value = $this->helper->load('field')->render_checkbox($value, $this, $field);
        }
        return $value;
    }

    // render shortcodes
    public function render_shortcodes($value, $field = array()) {

        $element_id = isset($field['element_id']) ? $field['element_id'] : false;

        if ($this->verify()) {
            if (false !== strpos($value, '[')) {
                $value = $this->helper->load('field')->pre_shortcodes($value, $this, $field);
                $value = $this->helper->load('field')->inner_shortcodes($value, $this, $field);
                $value = $this->helper->load('field')->wrapper_shortcodes($value, $this, $field);
            }
            $value = $this->helper->load('field')->do_shortcodes($value, $this, $field);

            add_filter('wpcf7_mail_tag_replaced_file', array($this, 'filter_wpcf7_file_mail_tag'), 99, 4);
            add_filter('wpcf7_mail_tag_replaced_file*', array($this, 'filter_wpcf7_file_mail_tag'), 99, 4);

            /**
             * Ultimate Addons for Contact Form 7 Digital Signature
             * https://wordpress.org/plugins/ultimate-addons-for-contact-form-7/
             */
            add_filter('wpcf7_mail_tag_replaced_uacf7_signature', array($this, 'filter_wpcf7_file_mail_tag'), 99, 4);
            add_filter('wpcf7_mail_tag_replaced_uacf7_signature*', array($this, 'filter_wpcf7_file_mail_tag'), 99, 4);

            if ($this->get('cached_submission')) {
                $value = htmlentities($value, ENT_NOQUOTES);
                $value = $this->get('cached_submission')->get_contact_form()->filter_message($value);
                $value = html_entity_decode($value);
            }

            remove_filter('wpcf7_mail_tag_replaced_file', array($this, 'filter_wpcf7_file_mail_tag'), 99);
            remove_filter('wpcf7_mail_tag_replaced_file*', array($this, 'filter_wpcf7_file_mail_tag'), 99);
            remove_filter('wpcf7_mail_tag_replaced_uacf7_signature', array($this, 'filter_wpcf7_file_mail_tag'), 99);
            remove_filter('wpcf7_mail_tag_replaced_uacf7_signature*', array($this, 'filter_wpcf7_file_mail_tag'), 99);

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

    // convert shortcodes
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

    // strip shortcodes
    public function strip_shortcodes($value) {
        $value = preg_replace('~(?:\[/?)[^/\]]+/?\]~s', '', $value);
        return $value;
    }

    // verify
    public function verify() {
        if ($this->get('cached_entry')) {
            return true;
        }
        return false;
    }

    // visual mapper
    public function visual_mapper() {

        $html = '';
        $source = '';

        if ($this->get('item') && class_exists('WPCF7_ContactForm')) {
            $form = WPCF7_ContactForm::get_instance($this->get('item'));
            if ($form) {
                $source = $form->form_html();
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
                    'dscf7_signature_inner',
                );
                foreach ($remove_by_class as $key => $class) {
                    $elements = $xpath->query("//*[contains(@class, '{$class}')]");
                    foreach ($elements as $element) {
                        $element->parentNode->removeChild($element);
                    }
                }

                /*
                 * Modify Digital Signature
                 * https://wordpress.org/plugins/digital-signature-for-contact-form-7/
                 */
                $signatures = $xpath->query("//*[contains(@class, 'wpcf7-signaturewpcf7-validates-as-signaturedscf7-signature')]");
                foreach ($signatures as $element) {
                    $xml->set_node_value($element, 'type', 'text');
                    $xml->set_node_value($element, 'value', __('Signature', 'e2pdf'));
                }

                $acceptances = $xpath->query("//*[contains(@class, 'wpcf7-acceptance')]");
                foreach ($acceptances as $acceptance) {
                    $element = $xpath->query('.//input', $acceptance)->item(0);
                    if ($element) {
                        $xml->set_node_value($element, 'value', 'Consented');
                    }
                }

                /*
                 * Drag and Drop Multiple File Upload – Contact Form 7
                 * https://wordpress.org/plugins/drag-and-drop-multiple-file-upload-contact-form-7/
                 */
                $drag_and_drops = $xpath->query("//*[contains(@class, 'wpcf7-drag-n-drop-file')]");
                foreach ($drag_and_drops as $element) {
                    $xml->set_node_value($element, 'name', $xml->get_node_value($element, 'data-name'));
                }

                $inputs = $xpath->query('//input|//textarea|//select');
                foreach ($inputs as $element) {
                    if ($xml->get_node_value($element, 'type') == 'checkbox') {
                        $xml->set_node_value($element, 'name', str_replace('[]', '', $xml->get_node_value($element, 'name')));
                    }
                    $xml->set_node_value($element, 'name', '[' . $xml->get_node_value($element, 'name') . ']');
                }

                // Remove unecessary elements
                $submit_buttons = $xpath->query("//input[@type='submit']");
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

    // auto
    public function auto() {

        $response = array();
        $elements = array();

        if ($this->get('cached_form')) {
            $source = $this->get('cached_form')->prop('form');
            foreach ((array) $this->get('cached_form')->scan_form_tags() as $key => $field) {
                $label = false;
                preg_match_all('/<label(?:[ \t\n]+.*?)?>(((?!<\/label>).)*)\[' . str_replace('*', '\*', $field->type) . ' ' . $field->name . '(\s|\]).*?<\/label>/s', $source, $matches);
                if (!empty($matches[1][0])) {
                    $label = trim($matches[1][0]);
                } else {
                    preg_match_all('/<label(?:[ \t\n]+.*?)?>(((?!<\/label>).)*)<\/label>(?:[\n\r\s]+)\[' . str_replace('*', '\*', $field->type) . ' ' . $field->name . '(\s|\])/', $source, $matches);
                    if (!empty($matches[1][0])) {
                        $label = trim($matches[1][0]);
                    }
                }
                switch ($field->type) {
                    case 'text':
                    case 'text*':
                    case 'email':
                    case 'email*':
                    case 'url':
                    case 'tel':
                    case 'number':
                    case 'date':
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
                                        'value' => $label ? $label : $field->name,
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
                                        'value' => '[' . $field->name . ']',
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
                                    'properties' => array(
                                        'top' => '20',
                                        'left' => '20',
                                        'right' => '20',
                                        'width' => '100%',
                                        'height' => 'auto',
                                        'value' => $label ? $label : $field->name,
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
                                        'value' => '[' . $field->name . ']',
                                    ),
                                )
                        );
                        break;
                    case 'file':
                    case 'mfile':
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
                                        'value' => $label ? $label : $field->name,
                                        'text_auto_font_size' => $field->type == 'textarea' ? '1' : '0',
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
                                        'value' => '[' . $field->name . ']',
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
                                        'value' => $label ? $label : $field->name,
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
                                        'options' => implode("\n", $field->values),
                                        'value' => '[' . $field->name . ']',
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
                                        'value' => $label ? $label : $field->name,
                                    ),
                                )
                        );
                        foreach ($field->values as $checkbox) {
                            $elements[] = $this->auto_field(
                                    $field,
                                    array(
                                        'type' => 'e2pdf-checkbox',
                                        'properties' => array(
                                            'top' => '5',
                                            'width' => 'auto',
                                            'height' => 'auto',
                                            'value' => '[' . $field->name . ']',
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
                                        'value' => $label ? $label : $field->name,
                                    ),
                                )
                        );
                        foreach ($field->values as $radio) {
                            $elements[] = $this->auto_field(
                                    $field,
                                    array(
                                        'type' => 'e2pdf-radio',
                                        'properties' => array(
                                            'top' => '5',
                                            'width' => 'auto',
                                            'height' => 'auto',
                                            'value' => '[' . $field->name . ']',
                                            'option' => $radio,
                                            'group' => '[' . $field->name . ']',
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
                                        'value' => $label ? $label : $field->name,
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
                                        'value' => '[' . $field->name . ']',
                                        'option' => 'Consented',
                                    ),
                                )
                        );
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

    // auto field
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

    // styles
    public function styles($item_id = false) {
        $styles = array(
            plugins_url('css/extension/wpcf7.css?v=' . time(), $this->helper->get('plugin_file_path')),
        );
        return $styles;
    }

    // delete item
    public function delete_item($template_id = false, $dataset_id = false) {
        global $wpdb;
        $template = new Model_E2pdf_Template();
        if ($template_id && $dataset_id && $template->load($template_id)) {
            if ($template->get('extension') === 'wpcf7' && $template->get('item')) {
                $item_id = $template->get('item');
                $where = array(
                    'ID' => $dataset_id,
                    'item' => $item_id,
                    'extension' => 'wpcf7',
                );
                $wpdb->delete($wpdb->prefix . 'e2pdf_datasets', $where);

                if ($dataset_id) {
                    $upload_dir = $this->helper->get('wpcf7_dir') . $dataset_id . '/';
                    if (is_dir($upload_dir)) {
                        $this->helper->delete_dir($upload_dir);
                    }
                }
                return true;
            }
        }
        return false;
    }

    // delete items
    public function delete_items($template_id = false) {
        global $wpdb;
        $template = new Model_E2pdf_Template();
        if ($template_id && $template->load($template_id)) {
            if ($template->get('extension') === 'wpcf7' && $template->get('item')) {
                $where = array(
                    'item' => $template->get('item'),
                    'extension' => 'wpcf7',
                );
                $condition = array(
                    'extension' => array(
                        'condition' => '=',
                        'value' => 'wpcf7',
                        'type' => '%s',
                    ),
                    'item' => array(
                        'condition' => '=',
                        'value' => $template->get('item'),
                        'type' => '%s',
                    ),
                );
                $where = $this->helper->load('db')->prepare_where($condition);
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
                $datasets = $wpdb->get_results($wpdb->prepare('SELECT ID FROM `' . $wpdb->prefix . 'e2pdf_datasets`' . $where['sql'] . '', $where['filter']));
                foreach ($datasets as $dataset) {
                    $this->delete_item($template_id, $dataset->ID);
                }
                return true;
            }
        }
        return false;
    }

    // save item
    public function save_item() {
        if ($this->get_storing_engine() == '1') {
            if ($this->get('vxcf_entry_id')) {
                $this->set('dataset', $this->get('vxcf_entry_id'));
            }
        } elseif ($this->get_storing_engine() == '2') {
            if ($this->get('cfdb7_entry_id')) {
                $this->set('dataset', $this->get('cfdb7_entry_id'));
            }
        } elseif ($this->get_storing_engine() == '3') {
            if ($this->get('vsz_cf7_entry_id')) {
                $this->set('dataset', $this->get('vsz_cf7_entry_id'));
            }
        } else {
            $posted_data = $this->get('submission')->get_posted_data();
            $uploaded_files = $this->get('submission')->uploaded_files();
            $meta = array(
                'timestamp' => $this->get('submission')->get_meta('timestamp'),
                'remote_ip' => $this->get('submission')->get_meta('remote_ip'),
                'remote_port' => $this->get('submission')->get_meta('remote_port'),
                'user_agent' => $this->get('submission')->get_meta('user_agen'),
                'url' => $this->get('submission')->get_meta('url'),
                'unit_tag' => $this->get('submission')->get_meta('unit_tag'),
                'container_post_id' => $this->get('submission')->get_meta('container_post_id'),
                'current_user_id' => $this->get('submission')->get_meta('current_user_id'),
                'do_not_store' => $this->get('submission')->get_meta('do_not_store'),
            );

            $entry = new Model_E2pdf_Dataset();
            $entry->set('extension', 'wpcf7');
            $entry->set('item', $this->get('submission')->get_contact_form()->id());
            $dataset = $entry->save();

            if ($dataset) {
                if (!empty($uploaded_files)) {
                    $upload_dir = $this->helper->get('wpcf7_dir') . $dataset . '/';
                    if ($this->helper->create_dir($upload_dir)) {
                        global $wp_filesystem;
                        if (!$wp_filesystem) {
                            if (!function_exists('WP_Filesystem')) {
                                require_once ABSPATH . '/wp-admin/includes/file.php';  // PHPCS:ignore WPThemeReview.CoreFunctionality.FileInclude.FileIncludeFound
                            }
                            WP_Filesystem();
                        }
                        foreach ($uploaded_files as $key => $field) {
                            $uploaded_files[$key] = array_map(
                                    function ($uploaded_file) use ($dataset) {
                                        $upload_dir = $this->helper->get('wpcf7_dir') . $dataset . '/';
                                        $dir = dirname($uploaded_file) . '/';
                                        $new_dir = $upload_dir . basename($dir) . '/';
                                        if (!is_dir($new_dir)) {
                                            if ($this->helper->create_dir($new_dir)) {
                                                copy_dir($dir, $new_dir);
                                            }
                                        }
                                        return $uploaded_file;
                                    }, $uploaded_files[$key]
                            );
                        }
                    }
                }
                $data = array(
                    'posted_data' => $posted_data,
                    'uploaded_files' => $uploaded_files,
                    'meta' => $meta,
                );
                $entry->set('entry', $data);
                $entry->save();

                $this->set('dataset', $dataset);
            }
        }
    }

    public function filter_vxcf_after_saving_addons($lead, $entry_id, $type, $form) {
        if ($this->get_storing_engine() == '1' && !empty($lead['__vx_entry']['form_id'])) {
            if (0 === strpos($lead['__vx_entry']['form_id'], 'cf_')) {
                $this->set('vxcf_entry_id', $entry_id);
            }
        }
        return $lead;
    }

    public function action_cfdb7_after_save_data($entry_id) {
        if ($this->get_storing_engine() == '2' && $entry_id) {
            $this->set('cfdb7_entry_id', $entry_id);
        }
    }

    public function action_vsz_cf7_after_insert_db($form, $cf7_id, $data_id) {
        if ($this->get_storing_engine() == '3' && $data_id) {
            $this->set('vsz_cf7_entry_id', $data_id);
        }
    }

    public function action_wpcf7_before_send_mail_abort($form, &$abort) {
        $abort = true;
    }

    public function action_wpcf7_before_send_mail($form, &$abort) {

        if ($abort) {
            return;
        }

        $submission = WPCF7_Submission::get_instance();
        if (!$submission) {
            return;
        }

        $this->set('dataset', false);
        $this->set('submission', $submission);

        $properties = $submission->get_contact_form()->get_properties();

        if (isset($properties['messages']['mail_sent_ok']) && $properties['messages']['mail_sent_ok']) {
            $properties['messages']['mail_sent_ok'] = $this->filter_success_message($properties['messages']['mail_sent_ok']);
        }

        if (isset($properties['mail']['active']) && $properties['mail']['active'] && isset($properties['mail']['body']) && $properties['mail']['body']) {
            $properties['mail']['body'] = $this->filter_mail_body($properties['mail']['body']);
            $properties['mail']['attachments'] = $this->filter_mail_body($properties['mail']['attachments']);
        }

        if (isset($properties['mail_2']['active']) && $properties['mail_2']['active'] && isset($properties['mail']['body']) && $properties['mail_2']['body']) {
            $properties['mail_2']['body'] = $this->filter_mail_body($properties['mail_2']['body'], 'mail_2');
            $properties['mail_2']['attachments'] = $this->filter_mail_body($properties['mail_2']['attachments'], 'mail_2');
        }

        if ($this->get('dataset')) {
            $submission->get_contact_form()->set_properties($properties);
        }
    }

    public function action_wpcf7_mail_sent($form) {
        $submission = WPCF7_Submission::get_instance();
        if (!$submission) {
            return $form;
        }

        $success_message = $submission->get_response();
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
                    $atts = shortcode_parse_atts($shortcode[3]);
                    if ($this->helper->load('shortcode')->is_attachment($shortcode, $atts)) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
                    } else {
                        $success_message = str_replace($shortcode_value, do_shortcode_tag($shortcode), $success_message);
                    }
                }
                $submission->set_response($success_message);
            }
        }

        $files = $this->helper->get('wpcf7_attachments');
        if (is_array($files) && !empty($files)) {
            foreach ($files as $key => $file) {
                $this->helper->delete_dir(dirname($file) . '/');
            }
            $this->helper->deset('wpcf7_attachments');
        }
    }

    public function filter_success_message($success_message) {
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
                    $atts = shortcode_parse_atts($shortcode[3]);
                    if ($this->helper->load('shortcode')->is_attachment($shortcode, $atts)) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
                    } else {
                        if (!isset($atts['dataset']) && isset($atts['id'])) {
                            $template = new Model_E2pdf_Template();
                            $template->load($atts['id']);

                            if ($template->get('extension') === 'wpcf7') {
                                if (!$this->get('dataset')) {
                                    $this->save_item();
                                }
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
                        $success_message = str_replace($shortcode_value, '[' . $shortcode[2] . $shortcode[3] . ']', $success_message);
                    }
                }
            }
        }
        return $success_message;
    }

    public function filter_mail_body($success_message, $tpl = 'mail') {

        if (false !== strpos($success_message, '[')) {
            $shortcode_tags = array(
                'e2pdf-download',
                'e2pdf-save',
                'e2pdf-adobesign',
                'e2pdf-zapier',
                'e2pdf-attachment',
            );
            preg_match_all('@\[([^<>&/\[\]\x00-\x20=]++)@', $success_message, $matches);
            $tagnames = array_intersect($shortcode_tags, $matches[1]);
            if (!empty($tagnames)) {
                preg_match_all('/' . $this->helper->load('shortcode')->get_shortcode_regex($tagnames) . '/', $success_message, $shortcodes);
                foreach ($shortcodes[0] as $key => $shortcode_value) {
                    $shortcode = $this->helper->load('shortcode')->get_shortcode($shortcodes, $key);
                    $atts = shortcode_parse_atts($shortcode[3]);

                    if ($this->helper->load('shortcode')->is_attachment($shortcode, $atts)) {
                        if (!isset($atts['dataset']) && isset($atts['id'])) {
                            $template = new Model_E2pdf_Template();
                            $template->load($atts['id']);
                            if ($template->get('extension') === 'wpcf7') {
                                if (!$this->get('dataset')) {
                                    $this->save_item();
                                }
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
                                    $this->helper->add('wpcf7_attachments', $file);
                                }
                            } else {
                                $this->helper->add('wpcf7_attachments', $file);
                            }
                            $this->get('submission')->add_extra_attachments($file, $tpl);
                        }
                        $success_message = str_replace($shortcode_value, '', $success_message);
                    } else {
                        if (!isset($atts['dataset']) && isset($atts['id'])) {
                            $template = new Model_E2pdf_Template();
                            $template->load($atts['id']);
                            if ($template->get('extension') === 'wpcf7') {
                                if (!$this->get('dataset')) {
                                    $this->save_item();
                                }
                                $atts['dataset'] = $this->get('dataset');
                                $shortcode[3] .= ' dataset="' . $this->get('dataset') . '"';
                            }
                        }
                        if (!isset($atts['apply'])) {
                            $shortcode[3] .= ' apply="true"';
                        }
                        $success_message = str_replace($shortcode_value, do_shortcode_tag($shortcode), $success_message);
                    }
                }
            }
        }

        return $success_message;
    }

    public function filter_wpcf7_file_mail_tag($replaced, $submitted, $html, $mail_tag) {
        $submission = WPCF7_Submission::get_instance();
        $uploaded_files = $submission->uploaded_files();
        $name = $mail_tag->field_name();

        if (!empty($uploaded_files[$name])) {
            $paths = (array) $uploaded_files[$name];
            $urls = array_map(
                    function ($uploaded_file) {
                        $wp_upload_dir = wp_upload_dir();
                        $upload_dir = $this->helper->get('wpcf7_dir') . $this->get('dataset');
                        $uploaded_file = str_replace(wpcf7_upload_tmp_dir(), $upload_dir, $uploaded_file);
                        return str_replace($wp_upload_dir['basedir'], $wp_upload_dir['baseurl'], $uploaded_file);
                    }, $paths
            );

            $replaced = wpcf7_flat_join(
                    $urls,
                    array(
                        'separator' => wp_get_list_item_separator(),
                    )
            );
        }

        return $replaced;
    }

    public function filter_e2pdf_model_options_get_options_options($options = array()) {
        $engines = array(
            '0' => 'E2Pdf',
        );

        /**
         * Contact Form Entries – Contact Form 7, WPforms and more
         * https://wordpress.org/plugins/contact-form-entries/
         */
        if (class_exists('vxcf_form')) {
            $engines['1'] = 'Contact Form Entries – Contact Form 7, WPforms and more';
        }

        /**
         * Contact Form 7 Database Addon - CFDB7
         * https://wordpress.org/plugins/contact-form-cfdb7/
         */
        if (function_exists('cfdb7_init')) {
            $engines['2'] = 'Contact Form 7 Database Addon - CFDB7';
        }

        /**
         * Advanced Contact form 7 DB
         * https://wordpress.org/plugins/advanced-cf7-db/
         */
        if (class_exists('Advanced_Cf7_Db')) {
            $engines['3'] = 'Advanced Contact form 7 DB';
        }

        $options['wpcf7_group'] = array(
            'name' => 'Contact Form 7',
            'action' => 'extension',
            'group' => 'wpcf7_group',
            'options' => array(
                array(
                    'name' => __('Storing Engine', 'e2pdf'),
                    'key' => 'e2pdf_wpcf7_storing_engine',
                    'value' => $this->get_storing_engine(),
                    'default_value' => '0',
                    'type' => 'select',
                    'options' => $engines,
                ),
            ),
        );
        return $options;
    }

    /**
     * 0 - E2Pdf (default)
     * 1 - Contact Form Entries – Contact Form 7, WPforms and more
     * https://wordpress.org/plugins/contact-form-entries/
     * 2 - Contact Form 7 Database Addon - CFDB7
     * https://wordpress.org/plugins/contact-form-cfdb7/
     * 3 - Advanced Contact form 7 DB
     * https://wordpress.org/plugins/advanced-cf7-db/)
     */
    public function get_storing_engine() {
        if ($this->get('storing_engine') !== false) {
            $storing_engine = $this->get('storing_engine');
            if ($storing_engine === '1') {
                if (class_exists('vxcf_form')) {
                    return '1';
                } else {
                    return false;
                }
            } elseif ($storing_engine === '2') {
                if (function_exists('cfdb7_init')) {
                    return '2';
                } else {
                    return false;
                }
            } elseif ($storing_engine === '3') {
                if (class_exists('Advanced_Cf7_Db')) {
                    return '3';
                } else {
                    return false;
                }
            } else {
                return '0';
            }
        } else {
            $storing_engine = get_option('e2pdf_wpcf7_storing_engine', '0');
            if ($storing_engine === '1' && class_exists('vxcf_form')) {
                return '1';
            } elseif ($storing_engine === '2' && function_exists('cfdb7_init')) {
                return '2';
            } elseif ($storing_engine === '3' && class_exists('Advanced_Cf7_Db')) {
                return '3';
            } else {
                return '0';
            }
        }
    }
}
