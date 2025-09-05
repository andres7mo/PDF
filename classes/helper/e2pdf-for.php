<?php

/**
 * File: /helper/e2pdf-for.php
 *
 * @package  E2Pdf
 * @license  GPLv3
 * @link     https://e2pdf.com
 */
if (!defined('ABSPATH')) {
    die('Access denied.');
}

class Helper_E2pdf_For {

    private $helper;

    public function __construct() {
        $this->helper = Helper_E2pdf_Helper::instance();
    }

    public function data($condition) {
        $unserialized = false;
        if (is_serialized($condition)) {
            $unserialized = $this->helper->load('convert')->unserialize($condition);
        }
        return is_array($unserialized) ? $unserialized : array();
    }

    public function do_shortcode($atts = array(), $value = '', $for = 0, $extension = null) {
        $result = array();
        $implode = isset($atts['implode']) ? $atts['implode'] : '';
        $for_index = $for ? '-' . $for : '';

        $tags = array(
            'e2pdf-for-data' => 'e2pdf-for-data',
            'e2pdf-for-do' => 'e2pdf-for-do',
            'e2pdf-for-else' => 'e2pdf-for-else',
        );
        /* Backward compatibility */
        if (false === strpos($value, '[e2pdf-for-data')) {
            $tags = array(
                'e2pdf-for-data' => 'e2pdf-data',
                'e2pdf-for-do' => 'e2pdf-do',
                'e2pdf-for-else' => 'e2pdf-else',
            );
        }

        add_filter('e2pdf_for_do_shortcode_data_process', array($this, 'filter_do_shortcode_data_process'));
        if ($extension && method_exists($extension, 'render')) {
            $data = $this->data($extension->render($this->helper->load('shortcode')->get_shortcode_content($tags['e2pdf-for-data'] . $for_index, $value), array(), false, true));
        } else {
            $data = $this->data($this->helper->load('shortcode')->get_shortcode_content($tags['e2pdf-for-data'] . $for_index, $value));
        }
        remove_filter('e2pdf_for_do_shortcode_data_process', array($this, 'filter_do_shortcode_data_process'));
        if (!empty($data)) {
            $index = 0;
            foreach ($data as $data_key => $data_value) {
                $do = $this->helper->load('shortcode')->get_shortcode_content($tags['e2pdf-for-do'] . $for_index, $value);
                $result[] = $this->apply($do, $data_key, $data_value, $index, $for, $extension);
                $index++;
            }
            $response = implode($implode, $result);
        } else {
            $response = $this->helper->load('shortcode')->get_shortcode_content($tags['e2pdf-for-else'] . $for_index, $value);
        }
        return $response;
    }

    public function apply($value, $data_key, $data_value, $index, $for = 0, $extension = null) {
        if ($value) {
            $for_index = $for ? '-' . $for : '';
            $evenodd = $index % 2 == 0 ? '0' : '1';
            $replace = array(
                '[e2pdf-for-index' . $for_index . ']' => $index,
                '[e2pdf-for-counter' . $for_index . ']' => $index + 1,
                '[e2pdf-for-key' . $for_index . ']' => $data_key,
                '[e2pdf-for-evenodd' . $for_index . ']' => $evenodd,
            );
            $value = str_replace(array_keys($replace), $replace, $value);
            $shortcode_tags = array(
                'e2pdf-for-value' . $for_index . '',
            );
            preg_match_all('@\[([^<>&/\[\]\x00-\x20=]++)@', $value, $matches);
            $tagnames = array_intersect($shortcode_tags, $matches[1]);
            if (!empty($tagnames)) {
                preg_match_all('/' . $this->helper->load('shortcode')->get_shortcode_regex($tagnames) . '/', $value, $shortcodes);
                foreach ($shortcodes[0] as $key => $shortcode_value) {
                    $shortcode = $this->helper->load('shortcode')->get_shortcode($shortcodes, $key);
                    $atts = shortcode_parse_atts($shortcode[3]);
                    $response = '';
                    $path = isset($atts['path']) ? $atts['path'] : false;
                    $implode = isset($atts['implode']) ? $atts['implode'] : false;

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

                    if (is_array($post_meta)) {
                        if ($implode !== false) {
                            if (!$this->helper->is_multidimensional($post_meta)) {
                                foreach ($post_meta as $post_meta_key => $post_meta_value) {
                                    $post_meta[$post_meta_key] = $this->helper->load('translator')->translate($post_meta_value);
                                }
                                $response = implode($implode, $post_meta);
                            } else {
                                $response = serialize($post_meta); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
                            }
                        } else {
                            $response = serialize($post_meta); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
                        }
                    } elseif (is_object($post_meta)) {
                        $response = serialize($post_meta); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
                    } else {
                        $response = $post_meta;
                    }
                    $value = str_replace($shortcode_value, $response, $value);
                }
            }
        }
        if (false !== strpos($value, '[e2pdf-for-' . $for + 1)) {
            $sub_shortcode_tags = array(
                'e2pdf-for-' . $for + 1 . '',
            );
            preg_match_all('@\[([^<>&/\[\]\x00-\x20=]++)@', $value, $sub_matches);
            $sub_tagnames = array_intersect($sub_shortcode_tags, $sub_matches[1]);
            if (!empty($sub_tagnames)) {
                preg_match_all('/' . $this->helper->load('shortcode')->get_shortcode_regex($sub_tagnames) . '/', $value, $sub_shortcodes);
                foreach ($sub_shortcodes[0] as $key => $sub_shortcode_value) {
                    $sub_shortcode = $this->helper->load('shortcode')->get_shortcode($sub_shortcodes, $key);
                    $atts = shortcode_parse_atts($sub_shortcode[3]);
                    $value = str_replace($sub_shortcode_value, $this->do_shortcode(is_array($atts) ? $atts : array(), $sub_shortcode[5], $for + 1, $extension), $value);
                }
            }
        }
        return $value;
    }

    public function filter_do_shortcode_data_process($status) {
        return true;
    }
}
