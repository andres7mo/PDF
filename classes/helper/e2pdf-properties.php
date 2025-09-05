<?php

/**
 * E2Pdf Properties Helper
 * @copyright  Copyright 2017 https://e2pdf.com
 * @license    GPLv3
 * @version    1
 * @link       https://e2pdf.com
 * @since      1.08.08
 */
if (!defined('ABSPATH')) {
    die('Access denied.');
}

class Helper_E2pdf_Properties {
    
    private $helper;

    public function __construct() {
        $this->helper = Helper_E2pdf_Helper::instance();
    }

    public function apply($field = array(), $value = '') {
        if ($value) {
            if (isset($field['properties']['nl2br']) && $field['properties']['nl2br']) {
                $value = nl2br($value);
            }
            if (isset($field['properties']['preg_pattern']) && $field['properties']['preg_pattern']) {
                $value = $this->preg_replace($field['properties']['preg_pattern'], isset($field['properties']['preg_replacement']) ? $field['properties']['preg_replacement'] : '', $value);
            }
            if (isset($field['properties']['preg_match_all_pattern']) && $field['properties']['preg_match_all_pattern']) {
                $value = $this->preg_match_all($field['properties']['preg_match_all_pattern'], isset($field['properties']['preg_match_all_output']) ? $field['properties']['preg_match_all_output'] : '', $value);
            }
            if (isset($field['properties']['html_worker']) && $field['properties']['html_worker']) {
                $value = $this->html_worker($value);
            }
            if (isset($field['properties']['preload_img']) && $field['properties']['preload_img'] && false !== stripos($value, '<img')) {
                $value = preg_replace_callback(
                        '/<img[^>]+src=["\'](http(s|)[^"|^\']+)["\']/',
                        function ($matches) {
                            return str_replace($matches[1], $this->helper->load('image')->get_base64_image($matches[1]), $matches[0]);
                        },
                        $value
                );
            }
        }
        return $value;
    }

    public function preg_replace($pattern = '', $replacement = '', $value = '') {
        if ($pattern && $value) {
            $value = @preg_replace($pattern, $replacement, $value); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
        }
        return $value;
    }

    public function preg_match_all($pattern = '', $output = '', $value = '') {
        if ($pattern && $value) {
            @preg_match_all($pattern, $value, $path_value); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
            $path_parts = explode('.', $output);
            $value = '';
            if (!empty($path_value)) {
                $found = true;
                foreach ($path_parts as $path_part) {
                    if (isset($path_value[$path_part])) {
                        $path_value = &$path_value[$path_part];
                    } else {
                        $found = false;
                        break;
                    }
                }
                if ($found) {
                    if (is_array($path_value)) {
                        $value = serialize($path_value); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
                    } else {
                        $value = $path_value;
                    }
                }
            }
        }
        return $value;
    }

    public function html_worker($value) {
        if ($value) {
            $value = preg_replace('#(src|href)(=[\'"])(/)#i', '$1$2' . get_site_url() . '/', $value);
        }
        return $value;
    }

    public function css_styles() {
        $styles = array(
            'WordPress' => '
                    .alignleft img {float: left;}
                    .alignright img {float: right;}
                    .wp-block-image {display: inline;}
                    .wp-block-image figure {display: inline;}
                    .has-text-align-left {display: inline;}
                    h1 {font-size: 24px;line-height:26px;}
                    h2 {font-size: 18px;line-height:20px;}
                    h3 {font-size: 14px;line-height:16px;}
                    h4 {font-size: 12px;line-height:14px;}
                    h5 {font-size: 10px;line-height:12px;}
                    h6 {font-size: 8px;line-height:10px;}
                    h1,h2,h3,h4,h5,h6 {margin-bottom:5px;font-weight:bold;}
                    p {margin-bottom:10px;}
                    li {padding-left:5px;margin-bottom:5px;}
                    a {color: #007bff}
               ',
        );
        return apply_filters('e2pdf_helper_properties_css_styles', $styles);
    }

    public function css_style($value, $css_style = '') {
        $styles = $this->css_styles();
        if ($css_style && !empty($styles[$css_style])) {
            $value = $styles[$css_style] . $value;
        }
        return $value;
    }
}
