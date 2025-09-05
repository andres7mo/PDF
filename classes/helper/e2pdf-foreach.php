<?php

/**
 * E2pdf Shortcode If Helper
 * @copyright  Copyright 2017 https://e2pdf.com
 * @license    GPLv3
 * @version    1
 * @link       https://e2pdf.com
 * @since      1.22.00
 */
if (!defined('ABSPATH')) {
    die('Access denied.');
}

class Helper_E2pdf_Foreach {

    private $helper;

    public function __construct() {
        $this->helper = Helper_E2pdf_Helper::instance();
    }

    /**
     * [e2pdf-foreach-x] shortcode
     * @param array $atts - Attributes
     * @param string $value - Content
     */
    public function add($attributes = array(), $value = '', $foreach = 1) {
        if (false !== strpos($value, '[')) {
            $shortcode_tags = array(
                'e2pdf-foreach-' . $foreach,
            );
            preg_match_all('@\[([^<>&/\[\]\x00-\x20=]++)@', $value, $matches);
            $tagnames = array_intersect($shortcode_tags, $matches[1]);
            foreach ($matches[1] as $key => $shortcode) {
                if (strpos($shortcode, ':') !== false) {
                    $shortcode_tags[] = $shortcode;
                }
            }
            if (!empty($tagnames)) {
                preg_match_all('/' . $this->helper->load('shortcode')->get_shortcode_regex($tagnames) . '/', $value, $shortcodes);
                foreach ($shortcodes[0] as $key => $shortcode_value) {
                    $shortcode = $this->helper->load('shortcode')->get_shortcode($shortcodes, $key);
                    $atts = shortcode_parse_atts($shortcode[3]);
                    if (isset($atts['shortcode'])) {
                        $response = array();
                        $implode = isset($atts['implode']) ? $atts['implode'] : '';
                        $foreach_shortcode = str_replace('-', '_', $atts['shortcode']);
                        if ($attributes['shortcode'] == $atts['shortcode']) {
                            if (!isset($atts['id']) && isset($attributes['id'])) {
                                $atts['id'] = $attributes['id'];
                            }
                            if (!isset($atts['wc_order_id']) && isset($attributes['wc_order_id'])) {
                                $atts['wc_order_id'] = $attributes['wc_order_id'];
                            }
                        } else {
                            if ($attributes['shortcode'] == 'e2pdf-wc-order') {
                                if ($atts['shortcode'] == 'e2pdf-wc-product') {
                                    if (!isset($atts['wc_order_id']) && isset($attributes['id'])) {
                                        $atts['wc_order_id'] = $attributes['id'];
                                    }
                                }
                            } elseif ($attributes['shortcode'] == 'e2pdf-wc-cart') {
                                if ($atts['shortcode'] == 'e2pdf-wc-product') {
                                    if (!isset($atts['wc_order_id']) && isset($attributes['id'])) {
                                        $atts['wc_order_id'] = 'cart';
                                    }
                                }
                            }
                        }
                        $model_e2pdf_shortcode = new Model_E2pdf_Shortcode();
                        if (method_exists($model_e2pdf_shortcode, $foreach_shortcode)) {
                            $atts['raw'] = 'true';
                            $data = $model_e2pdf_shortcode->$foreach_shortcode($atts, '');
                            if (is_array($data) && count($data) > 0) {
                                $index = 0;
                                foreach ($data as $data_key => $data_value) {
                                    $sub_value = $this->do_shortcode($shortcode[5], $data_key, $data_value, $index, $foreach);
                                    $response[] = $this->add($attributes, $sub_value, $foreach + 1);
                                    $index++;
                                }
                            }
                        }
                    }
                    $value = str_replace($shortcode_value, implode($implode, $response), $value);
                }
            }
        }
        return $value;
    }

    /**
     * [e2pdf-foreach] inner shortcodes support
     */
    public function do_shortcode($value, $data_key, $data_value, $index, $foreach = 0) {
        if ($value) {
            $foreach_index = $foreach ? '-' . $foreach : '';
            $evenodd = $index % 2 == 0 ? '0' : '1';
            $replace = array(
                '[e2pdf-foreach-index' . $foreach_index . ']' => $index,
                '[e2pdf-foreach-counter' . $foreach_index . ']' => $index + 1,
                '[e2pdf-foreach-key' . $foreach_index . ']' => $data_key,
                '[e2pdf-foreach-value' . $foreach_index . ']' => is_array($data_value) || is_object($data_value) ? serialize($data_value) : $data_value,
                '[e2pdf-foreach-evenodd' . $foreach_index . ']' => $evenodd,
            );
            $value = str_replace(array_keys($replace), $replace, $value);
            $shortcode_tags = array(
                'e2pdf-foreach-value' . $foreach_index . '',
            );
            preg_match_all('@\[([^<>&/\[\]\x00-\x20=]++)@', $value, $matches);
            $tagnames = array_intersect($shortcode_tags, $matches[1]);
            if (!empty($tagnames)) {
                preg_match_all('/' . $this->helper->load('shortcode')->get_shortcode_regex($tagnames) . '/', $value, $shortcodes);
                foreach ($shortcodes[0] as $key => $shortcode_value) {
                    $shortcode = $this->helper->load('shortcode')->get_shortcode($shortcodes, $key);
                    $atts = shortcode_parse_atts($shortcode[3]);
                    if (isset($atts['path'])) {
                        $path = $atts['path'];
                        $post_meta = $data_value;
                        if ((is_array($post_meta) || is_object($post_meta)) && $path !== false) {
                            $path_parts = explode('.', $path);
                            $path_value = &$post_meta;
                            $found = true;
                            foreach ($path_parts as $path_part) {
                                if (is_array($path_value) && isset($path_value[$path_part])) {
                                    $path_value = &$path_value[$path_part];
                                } elseif (is_object($path_value) && isset($path_value->$path_part)) {
                                    $path_value = &$path_value->$path_part;
                                } else {
                                    $found = false;
                                    break;
                                }
                            }
                            if ($found) {
                                $post_meta = $path_value;
                            } else {
                                $post_meta = '';
                            }
                        }
                        if (is_array($post_meta) || is_object($post_meta)) {
                            $post_meta = serialize($post_meta);
                        }
                        $value = str_replace($shortcode_value, $post_meta, $value);
                    }
                }
            }
        }
        return $value;
    }
}
