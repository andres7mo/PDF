<?php

/**
 * File: /model/e2pdf-extension.php
 *
 * @package  E2Pdf
 * @license  GPLv3
 * @link     https://e2pdf.com
 */
if (!defined('ABSPATH')) {
    die('Access denied.');
}

class Model_E2pdf_Extension extends Model_E2pdf_Model {

    private $extension;

    public function load($name) {
        if (in_array($name, get_option('e2pdf_disabled_extensions', array()), true)) {
            return false;
        }
        $class = 'Extension_E2pdf_' . ucfirst($name);
        if (class_exists($class)) {
            $extension = new $class();
            if ($extension->active()) {
                $this->extension = $extension;
                return true;
            }
        }
        return false;
    }

    public function loaded($extension) {
        if ($this->extension() && method_exists($this->extension(), 'info')) {
            $info = $this->extension()->info();
            if (isset($info[$extension])) {
                return true;
            }
        }
        return false;
    }

    public function info($attr = false) {
        if ($this->extension() && method_exists($this->extension(), 'info')) {
            return $this->extension()->info($attr);
        }
        return false;
    }

    public function extension() {
        return $this->extension;
    }

    public function set($attr, $value) {
        if ($this->extension() && method_exists($this->extension(), 'set')) {
            return $this->extension()->set($attr, $value);
        }
        return false;
    }

    public function get($attr) {
        if ($this->extension() && method_exists($this->extension(), 'get')) {
            return $this->extension()->get($attr);
        }
        return false;
    }

    public function verify() {
        if ($this->extension() && method_exists($this->extension(), 'verify')) {
            return $this->extension()->verify();
        }
        return false;
    }

    public function import($xml, $options = array()) {
        if ($this->extension() && method_exists($this->extension(), 'import')) {
            return $this->extension()->import($xml, $options);
        }
        return false;
    }

    public function after_import($old_template_id, $new_template_id) {
        if ($this->extension() && method_exists($this->extension(), 'after_import')) {
            return $this->extension()->after_import($old_template_id, $new_template_id);
        }
        return false;
    }

    public function backup($xml = false) {
        if ($this->extension() && method_exists($this->extension(), 'backup')) {
            return $this->extension()->backup($xml);
        }
        return false;
    }

    public function item($item_id = false) {
        if ($this->extension() && method_exists($this->extension(), 'item')) {
            return $this->extension()->item($item_id);
        }
        return false;
    }

    public function items() {
        if ($this->extension() && method_exists($this->extension(), 'items')) {
            return $this->extension()->items();
        }
        return false;
    }

    // styles
    public function styles($item = false) {
        if ($this->extension() && method_exists($this->extension(), 'styles')) {
            return $this->extension()->styles($item);
        }
        return false;
    }

    // render
    public function render($value, $field = array(), $convert_shortcodes = true, $raw = false) {
        if ($this->extension() && method_exists($this->extension(), 'render')) {
            $this->pre_render();
            $content = $this->extension()->render($value, $field, $convert_shortcodes, $raw);
            $this->after_render();
            $type = isset($field['type']) ? $field['type'] : false;
            if ($type == 'e2pdf-image' || $type == 'e2pdf-signature' || $type == 'e2pdf-qrcode' || $type == 'e2pdf-barcode' || $type == 'e2pdf-graph') {
                return $content;
            } else {
                return $this->helper->load('translator')->translate($content, 'full');
            }
        }
        return '';
    }

    // datasets
    public function datasets($item = false, $name = false) {
        if ($this->extension() && method_exists($this->extension(), 'datasets')) {
            $this->pre_render();
            $content = $this->extension()->datasets($item, $name);
            $this->after_render();
            return $content;
        }
        return false;
    }

    public function pre_render() {
        add_filter('e2pdf_pdf_render', array($this, '__return_true'), 999);
        if (class_exists('ACF')) {
            if (apply_filters('e2pdf_acf_enable_shortcodes', true)) {
                add_filter('acf/settings/enable_shortcode', array($this, '__return_true'), 999);
            }
            if (apply_filters('e2pdf_acf_allow_in_block_themes_outside_content', true)) {
                add_filter('acf/shortcode/allow_in_block_themes_outside_content', array($this, '__return_true'), 999);
            }
            if (apply_filters('e2pdf_acf_allow_in_bindings', false)) {
                add_filter('acf/load_field', array($this, 'filter_acf_allow_in_bindings'), 999);
            }
            if (!apply_filters('e2pdf_prevent_access_to_fields_on_non_public_posts', true)) {
                add_filter('acf/shortcode/prevent_access_to_fields_on_non_public_posts', array($this, '__return_false'), 999);
            }
        }
    }

    public function after_render() {
        if (class_exists('ACF')) {
            if (apply_filters('e2pdf_acf_enable_shortcodes', true)) {
                remove_filter('acf/settings/enable_shortcode', array($this, '__return_true'), 999);
            }
            if (apply_filters('e2pdf_acf_allow_in_block_themes_outside_content', true)) {
                remove_filter('acf/shortcode/allow_in_block_themes_outside_content', array($this, '__return_true'), 999);
            }
            if (apply_filters('e2pdf_acf_allow_in_bindings', false)) {
                remove_filter('acf/load_field', array($this, 'filter_acf_allow_in_bindings'), 999);
            }
            if (!apply_filters('e2pdf_prevent_access_to_fields_on_non_public_posts', true)) {
                remove_filter('acf/shortcode/prevent_access_to_fields_on_non_public_posts', array($this, '__return_false'), 999);
            }
        }
        remove_filter('e2pdf_pdf_render', array($this, '__return_true'), 999);
    }

    // convert shortcodes
    public function convert_shortcodes($value, $to = false, $html = false) {
        if ($this->extension() && method_exists($this->extension(), 'convert_shortcodes')) {
            return $this->extension()->convert_shortcodes($value, $to, $html);
        }
        return false;
    }

    // dataset actions
    public function get_dataset_actions($dataset_id = false) {
        if ($this->extension() && method_exists($this->extension(), 'get_dataset_actions')) {
            return $this->extension()->get_dataset_actions($dataset_id);
        }
        return false;
    }

    // template actions
    public function get_template_actions($template_id = false) {
        if ($this->extension() && method_exists($this->extension(), 'get_template_actions')) {
            return $this->extension()->get_template_actions($template_id);
        }
        return false;
    }

    // storing engine
    public function get_storing_engine() {
        if ($this->extension() && method_exists($this->extension(), 'get_storing_engine')) {
            return $this->extension()->get_storing_engine();
        }
        return false;
    }

    // extensions
    public function extensions($load = true) {
        $list = array();
        $extentions_path = $this->helper->get('plugin_dir') . 'classes/extension/*';
        foreach (array_filter(glob($extentions_path), 'is_file') as $file) {
            $info = pathinfo($file);
            $file_name = basename($file, '.' . $info['extension']);
            $file_name = substr($file_name, 6);

            if ($load) {
                if ($this->load($file_name)) {
                    $list = array_merge($list, $this->extension->info());
                }
            } else {
                $list[] = $file_name;
            }
        }
        return $list;
    }

    // delete item
    public function delete_item($template_id = false, $dataset_id = false) {
        if ($this->extension() && method_exists($this->extension(), 'delete_item')) {
            return $this->extension()->delete_item($template_id, $dataset_id);
        }
        return false;
    }

    // delete items
    public function delete_items($template_id = false) {
        if ($this->extension() && method_exists($this->extension(), 'delete_items')) {
            return $this->extension()->delete_items($template_id);
        }
        return false;
    }

    // visual mapper
    public function visual_mapper() {

        if ($this->extension() && method_exists($this->extension(), 'visual_mapper')) {
            if (!extension_loaded('libxml')) {
                /* translators: %s: PHP Extension */
                return sprintf(__('The PHP %s extension is required', 'e2pdf'), 'libxml');
            }
            if (!extension_loaded('Dom')) {
                /* translators: %s: PHP Extension */
                return sprintf(__('The PHP %s extension is required', 'e2pdf'), 'DOM');
            }
            if ($this->extension()->get('item') == '-2') {
                $visual_mapper = '';
                if ($this->extension()->get('item1')) {
                    $this->extension()->set('item', $this->extension()->get('item1'));

                    $output = $this->extension()->visual_mapper();
                    if ($output !== false) {
                        $visual_mapper .= $output;
                    }
                }
                if ($this->extension()->get('item2')) {
                    $this->extension()->set('item', $this->extension()->get('item2'));
                    $output = $this->extension()->visual_mapper();
                    if ($output !== false) {
                        $visual_mapper .= $output;
                    }
                }
                return $visual_mapper;
            } else {
                return $this->extension()->visual_mapper();
            }
        }
        return false;
    }

    public function auto_form($template, $data = array()) {
        if ($this->extension() && method_exists($this->extension(), 'auto_form')) {
            return $this->extension()->auto_form($template, $data);
        }
        return $template;
    }

    // auto map
    public function auto_map($name = false) {
        if ($this->extension() && method_exists($this->extension(), 'auto_map') && $name) {
            return $this->extension()->auto_map($name);
        }
        return false;
    }

    // auto
    public function auto() {
        if ($this->extension() && method_exists($this->extension(), 'auto')) {
            if ($this->extension()->get('item') == '-2') {
                if (!$this->extension()->get('item1') && !$this->extension()->get('item2')) {
                    return false;
                }
                $data = array();
                if ($this->extension()->get('item1')) {
                    $this->extension()->set('item', $this->extension()->get('item1'));
                    $data = $this->extension()->auto();
                }
                if ($this->extension()->get('item2')) {
                    $this->extension()->set('item', $this->extension()->get('item2'));

                    if (!empty($data)) {

                        $data2 = $this->extension()->auto();
                        $data['elements'] = array_merge($data['elements'], $data2['elements']);
                    } else {
                        $data = $this->extension()->auto();
                    }
                }
                return $data;
            } else {
                return $this->extension()->auto();
            }
        }
        return false;
    }

    // method
    public function method($attr = false) {
        if ($attr && $this->extension() && method_exists($this->extension(), $attr)) {
            return true;
        }
        return false;
    }

    // load actions
    public function load_actions() {
        if ($this->extension() && method_exists($this->extension(), 'load_actions')) {
            return $this->extension()->load_actions();
        }
        return false;
    }

    // load filters
    public function load_filters() {
        if ($this->extension() && method_exists($this->extension(), 'load_filters')) {
            return $this->extension()->load_filters();
        }
        return false;
    }

    // load shortcodes
    public function load_shortcodes() {
        if ($this->extension() && method_exists($this->extension(), 'load_shortcodes')) {
            return $this->extension()->load_shortcodes();
        }
        return false;
    }

    // return true
    public function __return_true() {
        return true;
    }

    // return false
    public function __return_false() {
        return false;
    }

    // filter acf allow in bidnings
    public function filter_acf_allow_in_bindings($field) {
        if (isset($field['allow_in_bindings']) && !$field['allow_in_bindings']) {
            $field['allow_in_bindings'] = 1;
        }
        return $field;
    }
}
