<?php

/**
 * File: /extension/e2pdf-jetformbuilder.php
 *
 * @package  E2Pdf
 * @license  GPLv3
 * @link     https://e2pdf.com
 */
if (!defined('ABSPATH')) {
    die('Access denied.');
}

class Extension_E2pdf_Jetformbuilder extends Model_E2pdf_Model {

    private $options;
    private $info = array(
        'key' => 'jetformbuilder',
        'title' => 'JetFormBuilder',
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
        if (defined('E2PDF_JETFORMBUILDER_EXTENSION') || $this->helper->load('extension')->is_plugin_active('jetformbuilder/jet-form-builder.php')) {
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
                $form = get_post($this->get('item'));
                if ($form) {
                    $this->set('cached_form', jet_fb_handler()->set_form_id($this->get('item')));
                }
                break;
            case 'dataset':
                $this->set('cached_entry', false);
                if ($this->get('cached_form') && $this->get('dataset')) {
                    if (substr($this->get('dataset'), 0, 4) === 'tmp_') {
                        $transient = get_transient('e2pdf_' . $this->get('dataset'));
                        if (isset($transient['__form_id']) && ($transient['__form_id'] == $this->get('cached_form')->get_form_id())) {
                            $this->set('cached_entry', $transient);
                        }
                    } else {
                        global $wpdb;
                        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
                        $record = $wpdb->get_row($wpdb->prepare('SELECT * FROM `' . \JFB_Modules\Form_Record\Models\Record_Model::table() . '` WHERE id = %d AND form_id = %d', $this->get('dataset'), $this->get('cached_form')->get_form_id()), ARRAY_A);
                        if ($record) {
                            \JFB_Modules\Form_Record\Tools::apply_context($record);
                            $this->set('cached_entry', $record);
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
                case 'cached_post':
                    $value = array();
                    break;
                default:
                    $value = false;
                    break;
            }
        }
        return $value;
    }

    // actions
    public function load_actions() {
        add_action('jet-form-builder/send-email/send-before', array($this, 'action_jet_form_builder_send_email_send_before'));
        add_action('jet-form-builder/form-handler/before-send', array($this, 'action_jet_form_builder_form_handler_before_send'));
        add_action('jet-form-builder/form-handler/after-send', array($this, 'action_jet_form_builder_form_handler_after_send'));
        add_action('jet-form-builder/before-do-action/redirect_to_page', array($this, 'action_jet_form_builder_before_do_action_redirect_to_page'));
    }

    public function action_jet_form_builder_before_do_action_redirect_to_page($action) {
        if (!empty($action->settings['redirect_args']) && is_array($action->settings['redirect_args']) && in_array('e2pdf-hash', $action->settings['redirect_args'], true)) {
            $dataset = jet_fb_action_handler()->get_context('save_record', 'id');
            if ($dataset) {
                $hash_id = $this->helper->load('encryption')->random_md5();
                set_transient('e2pdf_hash_' . $hash_id, $dataset, apply_filters('e2pdf_hash_timeout', '1200', 'jfb_redirect'));
                jet_fb_context()->update_request($hash_id, 'e2pdf-hash');
            }
        }
    }

    // filters
    public function load_filters() {
        add_filter('jet-form-builder/before-end-form', array($this, 'filter_jet_form_builder_before_end_form'), 99);
        add_filter('jet-form-builder/send-email/message_content', array($this, 'filter_jet_form_builder_send_email_message_content'), 99, 2);
        add_filter('jet-form-builder/content-filters', array($this, 'filter_jet_form_builder_content_filter'));

        // hooks
        add_filter('jet-form-builder/page-containers/jfb-records-single', array($this, 'hook_jetformbuilder_entry_view'));
    }

    public function filter_wp_kses_allowed_html($html, $context) {
        if (is_array($context)) {
            return $html;
        }
        if ($context === 'post') {
            $html['iframe']['name'] = true;
            $html['iframe']['onload'] = true;
            $html['iframe']['style'] = true;
            $html['iframe']['class'] = true;
            $html['iframe']['width'] = true;
            $html['iframe']['height'] = true;
            $html['iframe']['src'] = true;
            $html['iframe']['preload'] = true;
            $html['iframe']['resolution'] = true;
            $html['iframe']['cursor'] = true;
            $html['iframe']['scroll'] = true;
            $html['iframe']['spread'] = true;
        }
        return $html;
    }

    public function action_jet_form_builder_form_handler_before_send() {
        foreach (jet_fb_action_handler()->get_all() as $action) {
            if (method_exists($action, 'get_id') && $action->get_id() == 'send_email') {
                if (!empty($action->settings['content'])) {
                    $content = $action->settings['content'];
                    if (is_string($content) && false !== strpos($content, '[e2pdf-')) {
                        foreach (jet_fb_action_handler()->get_all() as $sub_action) {
                            if (is_a($sub_action, '\JFB_Modules\Form_Record\Action_Types\Save_Record')) {
                                $this->set('save_record', true);
                                jet_fb_action_handler()->process_single_action($sub_action);
                                jet_fb_action_handler()->unregister_action($sub_action->_id);
                                break;
                            }
                        }
                        break;
                    }
                }
            } elseif (method_exists($action, 'get_id') && $action->get_id() == 'redirect_to_page') {
                if (!empty($action->settings['redirect_args']) && is_array($action->settings['redirect_args']) && in_array('e2pdf-hash', $action->settings['redirect_args'], true)) {
                    foreach (jet_fb_action_handler()->get_all() as $sub_action) {
                        if (is_a($sub_action, '\JFB_Modules\Form_Record\Action_Types\Save_Record')) {
                            $this->set('save_record', true);
                            jet_fb_action_handler()->process_single_action($sub_action);
                            jet_fb_action_handler()->unregister_action($sub_action->_id);
                            break;
                        }
                    }
                    break;
                }
            }
        }
    }

    public function action_jet_form_builder_form_handler_after_send($form_handler) {
        $files = $this->helper->get('jetformbuilder_attachments');
        if (is_array($files) && !empty($files)) {
            foreach ($files as $key => $file) {
                $this->helper->delete_dir(dirname($file) . '/');
            }
            $this->helper->deset('jetformbuilder_attachments');
        }

        if ($this->get('save_record')) {
            $dataset = jet_fb_action_handler()->get_context('save_record', 'id');
            if ($dataset) {
                \JFB_Modules\Form_Record\Tools::update_record($dataset);
                $args = jet_form_builder()->form_handler->get_response_args();
                (new \JFB_Modules\Form_Record\Models\Record_Model())->update(
                        array(
                            'status' => $args['status'] ?? '',
                        ),
                        array(
                            'id' => $dataset,
                        )
                );
            }
        }

        $messages = jet_form_builder()->msg_router->get_manager()->get_messages();
        foreach ($messages as $message_key => $success_message) {
            if (is_string($success_message) && false !== strpos($success_message, '[e2pdf-')) {
                $dataset = jet_fb_action_handler()->get_context('save_record', 'id');
                if ($dataset) {
                    $hash_id = $this->helper->load('encryption')->random_md5();
                    set_transient('e2pdf_hash_' . $hash_id, $dataset, apply_filters('e2pdf_hash_timeout', '1200', 'jfb_message'));
                    $form_handler->add_response_data(
                            array(
                                'e2pdf-hash' => $hash_id,
                            )
                    );
                }
                break;
            }
        }
    }

    public function action_jet_form_builder_send_email_send_before($send_email) {
        $message = $send_email->get_content();
        $attachments = $send_email->get_attachments();
        $message = preg_replace('/(\{\{)((e2pdf-download|e2pdf-view|e2pdf-save|e2pdf-attachment|e2pdf-adobesign|e2pdf-zapier)[^\}]*?)(\}\})/', '[$2]', $message);
        if (false !== strpos($message, '[')) {
            $shortcode_tags = array(
                'e2pdf-download',
                'e2pdf-save',
                'e2pdf-attachment',
                'e2pdf-adobesign',
                'e2pdf-zapier',
            );
            preg_match_all('@\[([^<>&/\[\]\x00-\x20=]++)@', $message, $matches);
            $tagnames = array_intersect($shortcode_tags, $matches[1]);
            if (!empty($tagnames)) {
                $context = jet_form_builder()->module('block-parsers')::instance()->get_context();
                $request = $context->get_request();
                preg_match_all('/' . $this->helper->load('shortcode')->get_shortcode_regex($tagnames) . '/', $message, $shortcodes);
                foreach ($shortcodes[0] as $key => $shortcode_value) {
                    $shortcode = $this->helper->load('shortcode')->get_shortcode($shortcodes, $key);
                    $atts = shortcode_parse_atts($shortcode[3]);
                    $file = false;
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
                                    $this->helper->add('jetformbuilder_attachments', $file);
                                }
                            } else {
                                $this->helper->add('jetformbuilder_attachments', $file);
                            }
                            $attachments[] = $file;
                        }
                        $message = str_replace($shortcode_value, '', $message);
                        if ($transient) {
                            delete_transient($transient);
                        }
                    } else {
                        $message = str_replace($shortcode_value, do_shortcode_tag($shortcode), $message);
                    }
                }
                $send_email->set_attachments($attachments);
                $send_email->set_content($message);
                $context->set_request($request);
                $context->apply_request();
            }
        }
    }

    public function filter_jet_form_builder_send_email_message_content($message, $send_email) {
        if (false !== strpos($message, '[')) {
            $shortcode_tags = array(
                'e2pdf-download',
                'e2pdf-save',
                'e2pdf-attachment',
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
                        if ($template->get('extension') === 'jetformbuilder') {
                            $dataset = jet_fb_action_handler()->get_context('save_record', 'id');
                            if ($dataset) {
                                $atts['dataset'] = $dataset;
                                $shortcode[3] .= ' dataset="' . $dataset . '"';
                            } elseif ($this->helper->load('shortcode')->is_attachment($shortcode, $atts)) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
                                $dataset = 'tmp_' . $this->helper->load('encryption')->random_md5();
                                set_transient('e2pdf_' . $dataset, jet_fb_context()->get_request(), 1800);
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
                    $message = str_replace($shortcode_value, '{{' . $shortcode[2] . $shortcode[3] . '}}', $message);
                }
            }
        }
        return $message;
    }

    public function filter_jet_form_builder_before_end_form($str) {
        $messages = jet_form_builder()->msg_router->get_manager()->get_messages();
        if (jet_form_builder()->msg_router->get_builder()->get_form_status() == 'success') {

            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $hash_id = isset($_GET['e2pdf-hash']) ? sanitize_text_field(wp_unslash($_GET['e2pdf-hash'])) : '';
            $dataset = get_transient('e2pdf_hash_' . $hash_id);
            if (apply_filters('e2pdf_hash_clear', true, 'jfb_message', array())) {
                delete_transient('e2pdf_hash_' . $hash_id);
            }
            if ($dataset) {
                foreach ($messages as $message_key => $success_message) {
                    if (is_string($success_message) && false !== strpos($success_message, '[')) {
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
                            add_filter('wp_kses_allowed_html', array($this, 'filter_wp_kses_allowed_html'), 10, 2);
                            preg_match_all('/' . $this->helper->load('shortcode')->get_shortcode_regex($tagnames) . '/', $success_message, $shortcodes);
                            foreach ($shortcodes[0] as $key => $shortcode_value) {
                                $shortcode = $this->helper->load('shortcode')->get_shortcode($shortcodes, $key);
                                $atts = shortcode_parse_atts($shortcode[3]);
                                if ($this->helper->load('shortcode')->is_attachment($shortcode, $atts)) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
                                } else {
                                    if (!isset($atts['dataset']) && isset($atts['id'])) {
                                        $template = new Model_E2pdf_Template();
                                        $template->load($atts['id']);
                                        if ($template->get('extension') === 'jetformbuilder') {
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
                                    $success_message = str_replace($shortcode_value, '[' . $shortcode[2] . $shortcode[3] . ']', $success_message);
                                }
                            }
                            jet_form_builder()->msg_router->get_manager()->_types[$message_key] = $success_message;
                        }
                    }
                }
            }
        }
        return $str;
    }

    // items
    public function items() {
        $items = array();
        $forms = get_posts(
                array(
                    'post_status' => 'publish',
                    'posts_per_page' => -1,
                    'post_type' => jet_form_builder()->post_type->slug(),
                )
        );
        if ($forms) {
            foreach ($forms as $key => $form) {
                $items[] = $this->item($form->ID);
            }
        }
        return $items;
    }

    // item
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
                        'post' => $item_id,
                        'action' => 'edit',
                    ), 'post.php?'
            );
        } else {
            $item->id = '';
            $item->name = '';
            $item->url = 'javascript:void(0);';
        }

        return $item;
    }

    // datasets
    public function datasets($item_id = false, $name = false) {

        $datasets = array();
        if ($item_id) {
            $record_view = (new \JFB_Modules\Form_Record\Query_Views\Record_View())->set_filters(// phpcs:ignore WordPress.Security
                    array(
                        'form' => $item_id,
                    )
            );
            $columns['id'] = true;
            $record_view->set_select(array_keys($columns));
            $entries = $record_view->query()->generate_all(ARRAY_A);

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

    // dataset actions
    public function get_dataset_actions($dataset_id = false) {
        $dataset_id = (int) $dataset_id;
        if (!$dataset_id) {
            return;
        }
        $data = new stdClass();
        $data->view = $this->helper->get_url(
                array(
                    'post_type' => jet_form_builder()->post_type->slug(),
                    'page' => 'jfb-records',
                    'item_id' => $dataset_id,
                ), 'edit.php?'
        );
        $data->delete = false;
        return $data;
    }

    // template actions
    public function get_template_actions($template = false) {
        $template = (int) $template;
        if (!$template) {
            return;
        }
        $actions = new stdClass();
        $actions->delete = false;
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

            add_filter('jet-form-builder/send-email/template-repeater', array($this, 'filter_jet_form_builder_send_email_template_repeater'), 99, 3);
            $value = $this->helper->load('field')->do_shortcodes($value, $this, $field);
            $value = jet_form_builder()->module('rich-content')::rich($value);
            remove_filter('jet-form-builder/send-email/template-repeater', array($this, 'filter_jet_form_builder_send_email_template_repeater'), 99);

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

    public function filter_jet_form_builder_send_email_template_repeater($data, $items, $macros_parser) {
        return !empty($items) ? serialize($items) : ''; // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
    }

    public function filter_jet_form_builder_content_filter($filters) {
        if (class_exists('\Jet_Form_Builder\Classes\Filters\Base_Multiple_Filter')) {
            $filters[] = hook_e2pdf_jetformbuilder_content_filter();
        }
        return $filters;
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
        $value = preg_replace('~%[^%]*%~', '', $value);
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

        if (function_exists('jet_fb_render_form')) {
            $source = jet_fb_render_form(array('form_id' => $this->get('item')));
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
        }

        if (!$source) {
            return '<div class="e2pdf-vm-error">' . __("The form source is empty or doesn't exist", 'e2pdf') . '</div>';
        } elseif (!$html) {
            return '<div class="e2pdf-vm-error">' . __('The form could not be parsed due the incorrect HTML', 'e2pdf') . '</div>';
        } else {

            $xml = $this->helper->load('xml');
            $xml->set('dom', $dom);
            $xpath = new DomXPath($dom);

            $remove_classes = array(
                'jet-form-builder-repeater__initial',
                'jet-form-builder-page--hidden',
            );

            foreach ($remove_classes as $key => $class) {
                $elements = $xpath->query("//*[contains(@class, '{$class}')]");
                foreach ($elements as $element) {
                    $xml->set_node_value($element, 'class', str_replace($class, '', $xml->get_node_value($element, 'class')));
                }
            }

            $remove_by_class = array(
                'jet-form-builder__next-page-wrap',
                'jet-form-builder-repeater__actions',
                'jet-form-builder-repeater__row-remove',
            );
            foreach ($remove_by_class as $key => $class) {
                $elements = $xpath->query("//*[contains(@class, '{$class}')]");
                foreach ($elements as $element) {
                    $element->parentNode->removeChild($element);
                }
            }

            $elements = $xpath->query('//template');
            foreach ($elements as $element) {
                $div = $dom->createElement('div');
                if ($element->hasChildNodes()) {
                    $children = [];
                    foreach ($element->childNodes as $child) {
                        $children[] = $child;
                    }
                    foreach ($children as $child) {
                        $div->appendChild($child->parentNode->removeChild($child));
                    }
                }
                $element->parentNode->replaceChild($div, $element);
            }

            $elements = $xpath->query('//input|//textarea|//select');
            foreach ($elements as $element) {
                if ($xml->get_node_value($element, 'type') == 'checkbox' || $xml->get_node_value($element, 'type') == 'file') {
                    $xml->set_node_value($element, 'name', str_replace('[]', '', $xml->get_node_value($element, 'name')));
                }
                $xml->set_node_value($element, 'name', '%' . $xml->get_node_value($element, 'name') . '%');
            }

            // Repeaters
            $elements = $xpath->query("//*[contains(@class, 'field-type-repeater-field')]");
            foreach ($elements as $element) {
                $sub_elements = $xpath->query('.//input|.//textarea|.//select', $element);
                foreach ($sub_elements as $sub_element) {
                    $xml->get_node_value($element, 'name');
                    $field_data = array();
                    preg_match('/%(.*?)\[__i__\]\[(.*?)\]%/i', $xml->get_node_value($sub_element, 'name'), $field_data);
                    if (!empty($field_data[1]) && !empty($field_data[2])) {
                        $xml->set_node_value($sub_element, 'name', '%' . $field_data[1] . '.0.' . $field_data[2] . '%');
                    } else {
                        $xml->set_node_value($sub_element, 'name', '');
                    }
                    $xml->set_node_value($sub_element, 'class', 'ff_gdpr_field');
                }
            }

            // Remove unecessary elements
            $submit_buttons = $xpath->query("//button[@type='submit']");
            foreach ($submit_buttons as $element) {
                $element->parentNode->removeChild($element);
            }

            return $dom->saveHTML();
        }
        return false;
    }

    // auto
    public function auto() {
        $response = array();
        $elements = array();

        if ($this->get('cached_form')) {
            $fields = \Jet_Form_Builder\Live_Form::instance()
                    ->set_form_id($this->get('cached_form')->get_form_id())
                    ->set_specific_data_for_render(array())
                    ->setup_fields();
            foreach ($fields as $key => $field) {
                $elements = $this->auto_fields($elements, $field);
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

    // auto fields
    public function auto_fields($elements = array(), $field = array(), $repeater = '') {

        $type = !empty($field['blockName']) ? $field['blockName'] : '';
        $label = !empty($field['attrs']['label']) ? $field['attrs']['label'] : '';
        $desc = !empty($field['attrs']['desc']) ? $field['attrs']['desc'] : '';

        if ($repeater) {
            $value = !empty($field['attrs']['name']) ? $field['attrs']['name'] : 'field_name';
            $value = '%' . $repeater . '.0.' . $value . '%';
        } else {
            $value = !empty($field['attrs']['name']) ? '%' . $field['attrs']['name'] . '%' : '%field_name%';
        }

        switch ($type) {
            case 'jet-forms/text-field':
            case 'jet-forms/color-picker-field':
            case 'jet-forms/date-field':
            case 'jet-forms/datetime-field':
            case 'jet-forms/number-field':
            case 'jet-forms/range-field':
            case 'jet-forms/calculated-field':
            case 'jet-forms/time-field':
                if ($label) {
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
                }
                $elements[] = $this->auto_field(
                        $field,
                        array(
                            'block' => $label ? false : true,
                            'type' => 'e2pdf-input',
                            'properties' => array(
                                'top' => $label ? '5' : '20',
                                'left' => $label ? '0' : '20',
                                'right' => $label ? '0' : '20',
                                'width' => '100%',
                                'height' => 'auto',
                                'value' => $value,
                            ),
                        )
                );
                if ($desc) {
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
                                    'value' => $desc,
                                ),
                            )
                    );
                }
                break;
            case 'jet-forms/checkbox-field':
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

                $field_options_from = isset($field['attrs']['field_options_from']) ? $field['attrs']['field_options_from'] : false;

                $options = array();
                if ($field_options_from == 'glossary' && isset($field['attrs']['glossary_id'])) {
                    if (function_exists('jet_engine') && jet_engine()->glossaries) {
                        $glossary = jet_engine()->glossaries->data->get_item_for_edit($field['attrs']['glossary_id']);
                        if (!empty($glossary['fields']) && is_array($glossary['fields'])) {
                            $options = $glossary['fields'];
                        }
                    }
                } elseif (isset($field['attrs']['field_options'])) {
                    $options = $field['attrs']['field_options'];
                }

                foreach ($options as $checkbox) {
                    $elements[] = $this->auto_field(
                            $field,
                            array(
                                'type' => 'e2pdf-checkbox',
                                'properties' => array(
                                    'top' => '5',
                                    'width' => 'auto',
                                    'height' => 'auto',
                                    'value' => $value,
                                    'option' => $checkbox['value'],
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
                                    'value' => $checkbox['label'],
                                ),
                            )
                    );
                }
                if ($desc) {
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
                                    'value' => $desc,
                                ),
                            )
                    );
                }
                break;
            case 'jet-forms/radio-field':
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
                foreach ($field['attrs']['field_options'] as $radio) {
                    $elements[] = $this->auto_field(
                            $field,
                            array(
                                'type' => 'e2pdf-radio',
                                'properties' => array(
                                    'top' => '5',
                                    'width' => 'auto',
                                    'height' => 'auto',
                                    'value' => $value,
                                    'option' => $radio['value'],
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
                                    'value' => $radio['label'],
                                ),
                            )
                    );
                }
                if ($desc) {
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
                                    'value' => $desc,
                                ),
                            )
                    );
                }
                break;
            case 'jet-forms/media-field':
            case 'jet-forms/textarea-field':
                if ($label) {
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
                }
                $elements[] = $this->auto_field(
                        $field,
                        array(
                            'block' => $label ? false : true,
                            'type' => 'e2pdf-textarea',
                            'properties' => array(
                                'top' => $label ? '5' : '20',
                                'left' => $label ? '0' : '20',
                                'right' => $label ? '0' : '20',
                                'width' => '100%',
                                'height' => '150',
                                'value' => $value,
                            ),
                        )
                );
                if ($desc) {
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
                                    'value' => $desc,
                                ),
                            )
                    );
                }
                break;
            case 'jet-forms/select-field':
                if ($label) {
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
                }

                $options = array();
                foreach ($field['attrs']['field_options'] as $option) {
                    $options[] = $option['value'];
                }
                $elements[] = $this->auto_field(
                        $field,
                        array(
                            'block' => $label ? false : true,
                            'type' => 'e2pdf-select',
                            'properties' => array(
                                'top' => $label ? '5' : '20',
                                'left' => $label ? '0' : '20',
                                'right' => $label ? '0' : '20',
                                'width' => '100%',
                                'height' => 'auto',
                                'options' => implode("\n", $options),
                                'value' => $value,
                                'height' => 'auto',
                            ),
                        )
                );
                if ($desc) {
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
                                    'value' => $desc,
                                ),
                            )
                    );
                }
                break;
            case 'jet-forms/wysiwyg-field':
                if ($label) {
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
                }
                $elements[] = array(
                    'block' => $label ? false : true,
                    'type' => 'e2pdf-html',
                    'properties' => array(
                        'top' => $label ? '5' : '20',
                        'left' => $label ? '0' : '20',
                        'right' => $label ? '0' : '20',
                        'width' => '100%',
                        'height' => '300',
                        'value' => $value,
                    ),
                );
                if ($desc) {
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
                                    'value' => $desc,
                                ),
                            )
                    );
                }
                break;
            case 'jet-forms/switcher':
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
                            'type' => 'e2pdf-checkbox',
                            'properties' => array(
                                'top' => '5',
                                'width' => 'auto',
                                'height' => 'auto',
                                'value' => $value,
                                'option' => 'on',
                            ),
                        )
                );
                break;
            case 'jet-forms/choices-field':
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
                foreach ($field['innerBlocks'] as $checkbox) {
                    $elements[] = $this->auto_field(
                            $field,
                            array(
                                'type' => 'e2pdf-checkbox',
                                'properties' => array(
                                    'top' => '5',
                                    'width' => 'auto',
                                    'height' => 'auto',
                                    'value' => $value,
                                    'option' => $checkbox['attrs']['value'],
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
                                    'value' => $checkbox['attrs']['value'],
                                ),
                            )
                    );
                }
                if ($desc) {
                    $elements[] = $this->auto_field(
                            $field,
                            array(
                                'type' => 'e2pdf-html',
                                'block' => true,
                                'properties' => array(
                                    'top' => '5',
                                    'width' => '100%',
                                    'height' => 'auto',
                                    'value' => $desc,
                                ),
                            )
                    );
                }
                break;
            case 'jet-forms/repeater-field':
                $repeater = !empty($field['attrs']['name']) ? $field['attrs']['name'] : '%field_name%';
                foreach ($field['innerBlocks'] as $subfield) {
                    $elements = $this->auto_fields($elements, $subfield, $repeater);
                }
                break;
            case 'jet-forms/hidden-field':
            case 'jet-forms/submit-field':
                break;
            default:
                if (!empty($field['innerBlocks'])) {
                    foreach ($field['innerBlocks'] as $subfield) {
                        $elements = $this->auto_fields($elements, $subfield);
                    }
                }
                break;
        }
        return $elements;
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
            plugins_url('css/extension/jetformbuilder.css?v=' . time(), $this->helper->get('plugin_file_path')),
        );
        return $styles;
    }

    public function hook_jetformbuilder_entry_view($containers) {
        if (class_exists('\JFB_Modules\Form_Record\Models\Record_Model') && !empty($containers[1]) && is_a($containers[1], '\Jet_Form_Builder\Admin\Single_Pages\Meta_Containers\Side_Meta_Container')) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $item_id = isset($_GET['item_id']) ? (int) $_GET['item_id'] : '0';
            global $wpdb;

            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
            $form_id = $wpdb->get_var($wpdb->prepare('SELECT form_id FROM `' . \JFB_Modules\Form_Record\Models\Record_Model::table() . '` WHERE id = %d', $item_id));
            if ($form_id) {
                $hooks = $this->helper->load('hooks')->get('jetformbuilder', 'hook_jetformbuilder_entry_view', $form_id);
                if (!empty($hooks)) {
                    if (
                            class_exists('\Jet_Form_Builder\Admin\Table_Views\Column_Advanced_Base') &&
                            class_exists('\Jet_Form_Builder\Admin\Single_Pages\Meta_Boxes\Base_List_Box') &&
                            class_exists('\JFB_Modules\Form_Record\Query_Views\Record_View') &&
                            class_exists('\Jet_Form_Builder\Exceptions\Query_Builder_Exception') &&
                            class_exists('\Jet_Form_Builder\Admin\Exceptions\Not_Found_Page_Exception')
                    ) {
                        $containers[1]->add_meta_box(hook_e2pdf_jetformbuilder_entry_view_meta_box($hooks));
                    }
                }
            }
        }
        return $containers;
    }
}

// Hook to add a custom meta box for E2Pdf actions in JetFormBuilder entry view.
function hook_e2pdf_jetformbuilder_entry_view_meta_box($hooks = array()) {

    if (!class_exists('Hook_E2pdf_Jetformbuilder_Meta_Box')) {

        // phpcs:ignore Generic.Files.OneObjectStructurePerFile.MultipleFound
        class Hook_E2pdf_Jetformbuilder_Meta_Box extends \Jet_Form_Builder\Admin\Single_Pages\Meta_Boxes\Base_List_Box {

            protected $hooks = array();

            public function get_title(): string {
                return apply_filters('e2pdf_hook_section_title', __('E2Pdf Actions', 'e2pdf'), 'hook_jetformbuilder_entry_view');
            }

            public function get_columns(): array {
                $columns = array();
                foreach ($this->hooks as $hook) {
                    $columns['e2pdf_' . $hook] = hook_e2pdf_jetformbuilder_entry_view_column($hook);
                }
                return $columns;
            }

            public function get_list(): array {
                try {
                    return \JFB_Modules\Form_Record\Query_Views\Record_View::findById($this->get_id());
                } catch (\Jet_Form_Builder\Exceptions\Query_Builder_Exception $exception) {
                    throw new \Jet_Form_Builder\Admin\Exceptions\Not_Found_Page_Exception(
                                    esc_html($exception->getMessage()),
                                    // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                                    ...$exception->get_additional()
                            );
                }
            }

            public function set_hooks($hooks = array()) {
                $this->hooks = $hooks;
            }
        }

    }

    $meta_box = new Hook_E2pdf_Jetformbuilder_Meta_Box();
    $meta_box->set_hooks($hooks);
    return $meta_box;
}

// Generate a column definition for E2Pdf actions in JetFormBuilder entry view.
function hook_e2pdf_jetformbuilder_entry_view_column($hook = '') {

    if (!class_exists('Hook_E2pdf_Jetformbuilder_Column')) {

        // phpcs:ignore Generic.Files.OneObjectStructurePerFile.MultipleFound
        class Hook_E2pdf_Jetformbuilder_Column extends \Jet_Form_Builder\Admin\Table_Views\Column_Advanced_Base {

            protected $column = 'e2pdf';
            protected $type = self::LINK;
            protected $hook = '';

            public function get_label(): string {
                return '';
            }

            public function get_value(array $record = array()) {
                $record_id = (int) $record['id'];
                $action = apply_filters(
                        'e2pdf_hook_action_button',
                        array(
                            'html' => '<p style="padding: 6px 12px 6.5px 42px"><a class="e2pdf-download-hook" target="_blank" href="%s"><span class="dashicons dashicons-pdf"></span> %s</a></p>',
                            'url' => Helper_E2pdf_Helper::instance()->get_url(
                                    array(
                                        'page' => 'e2pdf',
                                        'action' => 'export',
                                        'id' => $this->hook,
                                        'dataset' => $record_id,
                                    ), 'admin.php?'
                            ),
                            'title' => 'PDF #' . $this->hook,
                        ), 'hook_jetformbuilder_entry_view', $this->hook, $record_id
                );
                if (!empty($action)) {
                    return array(
                        'text' => $action['title'],
                        'href' => $action['url'],
                        'target' => '_blank',
                        'type' => 'media-document',
                    );
                } else {
                    return array();
                }
            }

            public function set_hook($hook = '') {
                $this->hook = $hook;
            }
        }

    }

    $column = new Hook_E2pdf_Jetformbuilder_Column();
    $column->set_hook($hook);
    return $column;
}

// Hook to add a content filter
function hook_e2pdf_jetformbuilder_content_filter($hooks = array()) {
    if (!class_exists('Hook_E2pdf_Jetformbuilder_Filter')) {

        // phpcs:ignore Generic.Files.OneObjectStructurePerFile.MultipleFound
        class Hook_E2pdf_Jetformbuilder_Content_Filter extends \Jet_Form_Builder\Classes\Filters\Base_Multiple_Filter {

            public function get_id(): string {
                return 'e2pdf';
            }

            public function callback_args(): array {
                return array();
            }

            public function apply_macros($value, ...$args): string {
                if (empty($value)) {
                    return '';
                }

                $sub_args = array();
                if (!empty($args[0])) {
                    $sub_args = explode(':', $args[0]);
                }

                $filter = isset($sub_args[0]) ? $sub_args[0] : '';
                if ($filter == 'glossary_labels') {
                    if (function_exists('jet_engine_label_by_glossary') && !empty($sub_args[1])) {
                        if (!is_array($value)) {
                            $value = explode(',', (string) $value);
                        }
                        $labels = jet_engine_label_by_glossary($value, $sub_args[1]);
                        if (is_string($labels)) {
                            return $labels;
                        }
                    }
                }

                return '';
            }

            protected function apply_item($item, ...$args): string {
                return $item;
            }
        }

    }
    return new Hook_E2pdf_Jetformbuilder_Content_Filter();
}
