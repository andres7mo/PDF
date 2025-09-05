<?php

/**
 * E2pdf Shortcode Helper
 * 
 * @copyright  Copyright 2017 https://e2pdf.com
 * @license    GPLv3
 * @version    1
 * @link       https://e2pdf.com
 * @since      1.07.02
 */
if (!defined('ABSPATH')) {
    die('Access denied.');
}

class Helper_E2pdf_Shortcode {

    public function get_shortcode_regex($tagnames = null) {
        if (version_compare(get_bloginfo('version'), '4.4.0', '<')) {
            global $shortcode_tags;

            if (empty($tagnames)) {
                $tagnames = array_keys($shortcode_tags);
            }
            $tagregexp = join('|', array_map('preg_quote', $tagnames));

            return
                    '\\['
                    . '(\\[?)'
                    . "($tagregexp)"
                    . '(?![\\w-])'
                    . '('
                    . '[^\\]\\/]*'
                    . '(?:'
                    . '\\/(?!\\])'
                    . '[^\\]\\/]*'
                    . ')*?'
                    . ')'
                    . '(?:'
                    . '(\\/)'
                    . '\\]'
                    . '|'
                    . '\\]'
                    . '(?:'
                    . '('
                    . '[^\\[]*+'
                    . '(?:'
                    . '\\[(?!\\/\\2\\])'
                    . '[^\\[]*+'
                    . ')*+'
                    . ')'
                    . '\\[\\/\\2\\]'
                    . ')?'
                    . ')'
                    . '(\\]?)';
        } else {
            return get_shortcode_regex($tagnames);
        }
    }

    public function get_shortcode($shortcodes = array(), $key = '') {
        $shortcode = array();
        $shortcode[1] = $shortcodes[1][$key];
        $shortcode[2] = $shortcodes[2][$key];
        $shortcode[3] = $shortcodes[3][$key];
        $shortcode[4] = $shortcodes[4][$key];
        $shortcode[5] = $shortcodes[5][$key];
        $shortcode[6] = $shortcodes[6][$key];
        return $shortcode;
    }

    public function get_shortcode_content($shortcode_tag = '', $value = '') {
        $response = '';
        preg_match_all('@\[([^<>&/\[\]\x00-\x20=]++)@', $value, $matches);
        $tagnames = array_intersect(array($shortcode_tag), $matches[1]);
        if (!empty($tagnames)) {
            preg_match('/' . $this->get_shortcode_regex($tagnames) . '/', $value, $shortcode);
            if (isset($shortcode[5])) {
                $response = $shortcode[5];
            }
        }
        return $response;
    }

    public function apply_path_attribute($value, $path = false, $delimiter = '.') {
        if ((is_array($value) || is_object($value)) && $path !== false) {
            $keys = explode($delimiter, $path);
            $obj = &$value;
            $found = true;
            foreach ($keys as $key) {
                if (is_array($obj) && isset($obj[$key])) {
                    $obj = &$obj[$key];
                } elseif (is_object($obj) && isset($obj->$key)) {
                    $obj = &$obj->$key;
                } else {
                    $found = false;
                    break;
                }
            }
            return $found ? $obj : '';
        }
        return '';
    }

    public function apply_attachment_attribute($value, $function = 'attachment_url', $size = 'thumbnail') {
        if (is_array($value)) {
            $attachments = array();
            foreach ($value as $post_meta_part) {
                if (!is_array($post_meta_part)) {
                    if ($function == 'attachment_url') {
                        $image = wp_get_attachment_url($post_meta_part);
                    } else {
                        $image = wp_get_attachment_image_url($post_meta_part, $size);
                    }
                    if ($image) {
                        $attachments[] = $image;
                    }
                }
            }
            return $attachments;
        } else {
            if ($function == 'attachment_url') {
                $image = wp_get_attachment_url($value);
            } else {
                $image = wp_get_attachment_image_url($value, $size);
            }
            return $image ? $image : '';
        }
    }

    public function is_attachment($shortcode, $atts) {
        if (($shortcode[2] === 'e2pdf-save' && isset($atts['attachment']) && $atts['attachment'] == 'true') || $shortcode[2] === 'e2pdf-attachment') {
            return true;
        }
        return false;
    }
}
