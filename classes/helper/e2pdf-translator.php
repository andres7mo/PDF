<?php

/**
 * E2pdf Zip Helper
 * 
 * @copyright  Copyright 2017 https://e2pdf.com
 * @license    GPLv3
 * @version    1
 * @link       https://e2pdf.com
 * @since      0.00.01
 */
if (!defined('ABSPATH')) {
    die('Access denied.');
}

class Helper_E2pdf_Translator {

    private $translator = null;

    public function __construct() {

        /**
         * Translate Multilingual sites – TranslatePress
         * https://wordpress.org/plugins/translatepress-multilingual/
         */
        if (class_exists('TRP_Translate_Press') && get_option('e2pdf_pdf_translation', '2') !== '0') {
            $this->translator = TRP_Translate_Press::get_trp_instance();
        }

        /**
         * Weglot Translate – Translate your WordPress website and go multilingual
         * https://wordpress.org/plugins/weglot/
         */
        if (class_exists('WeglotWP\Services\Translate_Service_Weglot') && get_option('e2pdf_pdf_translation', '2') !== '0' && function_exists('weglot_get_current_language') && function_exists('weglot_get_original_language')) {
            if (weglot_get_current_language() != weglot_get_original_language()) {
                $this->translator = new WeglotWP\Services\Translate_Service_Weglot();
            }
        }
    }

    public function translate($content = '', $type = 'default') {
        if ($this->translator && $content) {
            $translation = false;
            switch ($type) {
                case 'full':
                    if (get_option('e2pdf_pdf_translation', '2') === '2') {
                        $translation = true;
                    }
                    break;
                case 'partial':
                    if (get_option('e2pdf_pdf_translation', '2') === '1') {
                        $translation = true;
                    }
                    break;
                default:
                    $translation = true;
                    break;
            }

            if ($translation) {
                /**
                 * Weglot Translate – Translate your WordPress website and go multilingual
                 * https://wordpress.org/plugins/weglot/
                 */
                if (is_a($this->translator, 'WeglotWP\Services\Translate_Service_Weglot')) {
                    if (weglot_get_current_language() != weglot_get_original_language()) {
                        $content = str_replace(array('e2pdf-page-number', 'e2pdf-page-total'), array('e-2-p-d-f-p-a-g-e-n-u-m-b-e-r', 'e-2-p-d-f-p-a-g-e-t-o-t-a-l'), $content);
                        $content = $this->translator->weglot_treat_page($content);
                        $content = str_replace(array('e-2-p-d-f-p-a-g-e-n-u-m-b-e-r', 'e-2-p-d-f-p-a-g-e-t-o-t-a-l'), array('e2pdf-page-number', 'e2pdf-page-total'), $content);
                    }
                }

                /**
                 * Translate Multilingual sites – TranslatePress
                 * https://wordpress.org/plugins/translatepress-multilingual/
                 */
                if (is_a($this->translator, 'TRP_Translate_Press')) {
                    add_filter('trp_get_existing_translations', array($this, 'filter_trp_get_existing_translations'), 99, 5);
                    $content = $this->translator->get_component('translation_render')->translate_page($content);
                    remove_filter('trp_get_existing_translations', array($this, 'filter_trp_get_existing_translations'), 99);
                }
            }
        }
        return $content;
    }

    public function filter_trp_get_existing_translations($dictionary, $prepared_query, $strings_array, $language_code, $block_type) {
        global $wpdb;
        if (!is_array($strings_array) || count($strings_array) == 0) {
            return $dictionary;
        }
        if ($block_type == null) {
            $and_block_type = "";
        } else {
            $and_block_type = " AND block_type = " . $block_type;
        }
        $placeholders = array();
        $values = array();
        foreach ($strings_array as $string) {
            $wptexturized_string = wptexturize($string);
            if ($string !== $wptexturized_string) {
                $placeholders[] = '%s';
                $values[] = wptexturize($string);
            }
        }
        if (!empty($values)) {
            $query = "SELECT original,translated, status FROM `" . sanitize_text_field($this->translator->get_component('query')->get_table_name($language_code)) . "` WHERE status != " . $this->translator->get_component('query')->get_constant_not_translated() . $and_block_type . " AND translated <>'' AND original IN ";
            $query .= "( " . implode(", ", $placeholders) . " )";
            $prepared_query = $wpdb->prepare($query, $values);
            $additional_dictionary = $wpdb->get_results($prepared_query, OBJECT_K);
            if (!empty($additional_dictionary) && is_array($additional_dictionary)) {
                foreach ($additional_dictionary as $dictionary_key => $dictionary_object) {
                    if (false != strpos($dictionary_key, '&#82')) {
                        $replace = array(
                            '&#8220;' => '"',
                            '&#8221;' => '"',
                            '&#8217;' => "'",
                            '&#8242;' => "'",
                            '&#8243;' => '"',
                            '&#8216;' => "'",
                            '&#8211;' => '-',
                            '&#8212;' => '-',
                        );
                        $untexturized_dictionary_key = str_replace(array_keys($replace), $replace, $dictionary_key);
                        if ($untexturized_dictionary_key !== $dictionary_key && !isset($dictionary[$untexturized_dictionary_key]) && isset($dictionary_object->original)) {
                            $new_dicitionary_object = $dictionary_object;
                            $new_dicitionary_object->original = $untexturized_dictionary_key;
                            $dictionary[$untexturized_dictionary_key] = $new_dicitionary_object;
                        }
                    }
                }
            }
        }
        return $dictionary;
    }

    public function translate_url($url = false) {
        if ($this->translator && $url) {
            if (is_a($this->translator, 'WeglotWP\Services\Translate_Service_Weglot')) {
                if (weglot_get_current_language() != weglot_get_original_language()) {
                    $request_url_service = weglot_get_request_url_service();
                    $replaced_url = $request_url_service->create_url_object($url)->getForLanguage($request_url_service->get_current_language());
                    if ($replaced_url) {
                        $url = $replaced_url;
                    }
                }
            } else if (is_a($this->translator, 'TRP_Translate_Press')) {
                $url = $this->translator->get_component('url_converter')->get_url_for_language(null, $url, '');
            }
        }
        return $url;
    }
}
