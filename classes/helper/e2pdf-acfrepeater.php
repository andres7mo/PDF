<?php

/**
 * File: /helper/e2pdf-acfrepeater.php
 *
 * @package  E2Pdf
 * @license  GPLv3
 * @link     https://e2pdf.com
 */
if (!defined('ABSPATH')) {
    die('Access denied.');
}

class Helper_E2pdf_Acfrepeater {

    private $helper;

    public function __construct() {
        $this->helper = Helper_E2pdf_Helper::instance();
    }

    public function do_shortcode($atts = array(), $value = '', $for = 0) {
        $response = '';
        $field = isset($atts['field']) ? $atts['field'] : null;
        $post_id = isset($atts['post_id']) ? $atts['post_id'] : null;
        $index = 0;
        if (function_exists('have_rows') && have_rows($field, $post_id)) {
            while (have_rows($field, $post_id)) {
                the_row();
                $response .= $this->apply($value, $atts, $index, $for);
                $index++;
            }
        }
        return $response;
    }

    public function apply($value, $atts, $index, $for = 0) {
        if ($value) {

            $field = isset($atts['field']) ? $atts['field'] : null;
            $post_id = isset($atts['post_id']) ? $atts['post_id'] : null;

            $for_index = $for ? '-' . $for : '';
            $evenodd = $index % 2 == 0 ? '0' : '1';
            $replace = array(
                '[e2pdf-acf-repeater-index' . $for_index . ']' => $index,
                '[e2pdf-acf-repeater-counter' . $for_index . ']' => $index + 1,
                '[e2pdf-acf-repeater-evenodd' . $for_index . ']' => $evenodd,
            );
            $value = str_replace(array_keys($replace), $replace, $value);

            if (false !== strpos($value, '[e2pdf-acf-repeater-' . $for + 1)) {
                $sub_shortcode_tags = array(
                    'e2pdf-acf-repeater-' . $for + 1 . '',
                );
                preg_match_all('@\[([^<>&/\[\]\x00-\x20=]++)@', $value, $sub_matches);
                $sub_tagnames = array_intersect($sub_shortcode_tags, $sub_matches[1]);
                if (!empty($sub_tagnames)) {
                    preg_match_all('/' . $this->helper->load('shortcode')->get_shortcode_regex($sub_tagnames) . '/', $value, $sub_shortcodes);
                    foreach ($sub_shortcodes[0] as $key => $sub_shortcode_value) {
                        $sub_shortcode = $this->helper->load('shortcode')->get_shortcode($sub_shortcodes, $key);
                        $sub_atts = shortcode_parse_atts($sub_shortcode[3]);
                        if (!isset($sub_atts['post_id']) && $post_id) {
                            $sub_atts['post_id'] = $post_id;
                        }
                        $value = str_replace($sub_shortcode_value, $this->do_shortcode(is_array($sub_atts) ? $sub_atts : array(), $sub_shortcode[5], $for + 1), $value);
                    }
                }
            }
            $shortcode_tags = array(
                'acf',
            );
            preg_match_all('@\[([^<>&/\[\]\x00-\x20=]++)@', $value, $matches);
            $tagnames = array_intersect($shortcode_tags, $matches[1]);
            if (!empty($tagnames)) {
                preg_match_all('/' . $this->helper->load('shortcode')->get_shortcode_regex($tagnames) . '/', $value, $shortcodes);
                foreach ($shortcodes[0] as $key => $shortcode_value) {
                    $shortcode = $this->helper->load('shortcode')->get_shortcode($shortcodes, $key);
                    $atts = shortcode_parse_atts($shortcode[3]);
                    $repeater = isset($atts['repeater']) ? $atts['repeater'] : $field;
                    if ($shortcode[2] === 'acf') {
                        if (!isset($atts['post_id']) && $post_id) {
                            $shortcode[3] .= ' post_id="' . $post_id . '"';
                        }
                        if ($field && isset($atts['field'])) {
                            if ($repeater !== $field) {
                                $value = str_replace($shortcode_value, '[' . $shortcode[2] . $shortcode[3] . ']', $value);
                            } else {
                                if (isset($atts['repeater'])) {
                                    $shortcode[3] .= ' field="' . $field . '_' . $index . '_' . $atts['field'] . '"';
                                } else {
                                    $shortcode[3] .= ' field="' . $field . '_' . $index . '_' . $atts['field'] . '" repeater="' . $field . '"';
                                }
                                $value = str_replace($shortcode_value, '[' . $shortcode[2] . $shortcode[3] . ']', $value);
                            }
                        } else {
                            $value = str_replace($shortcode_value, '', $value);
                        }
                    }
                }
            }
        }
        return $value;
    }
}
