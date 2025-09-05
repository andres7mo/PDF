<?php

/**
 * E2Pdf Filter Helper
 * @copyright  Copyright 2017 https://e2pdf.com
 * @license    GPLv3
 * @version    1
 * @link       https://e2pdf.com
 * @since      1.07.09
 */
if (!defined('ABSPATH')) {
    die('Access denied.');
}

class Helper_E2pdf_Filter {

    public function is_stream($file_path) {
        if (strpos($file_path, '://') > 0) {
            $wrappers = array(
                'phar',
            );
            if (function_exists('stream_get_wrappers')) {
                $wrappers = stream_get_wrappers();
            }

            foreach ($wrappers as $wrapper) {
                if (in_array($wrapper, ['http', 'https', 'file'])) {
                    continue;
                }
                if (stripos($file_path, $wrapper . '://') === 0) {
                    return true;
                }
            }
        }
        return false;
    }

    public function is_downloadable($file_path) {
        if ($file_path && in_array(strtolower(pathinfo($file_path, PATHINFO_EXTENSION)), $this->is_allowed_extensions())) {
            return true;
        }
        return false;
    }

    public function is_allowed_extensions() {
        return apply_filters('e2pdf_helper_filter_is_downloadable_allowed_extensions', array('pdf', 'jpg', 'doc', 'docx'));
    }

    /*
     * Filter Unsupported HTML Tags
     */

    public function filter_html_tags($value) {
        if ($value) {
            $tags = array(
                'script',
                'style',
            );
            foreach ($tags as $tag) {
                $value = preg_replace('#<' . $tag . '(.*?)>(.*?)</' . $tag . '>#is', '', $value);
            }
        }
        return $value;
    }

    public function filter_button_title($button_title) {
        if (false !== strpos($button_title, '<')) {
            $button_title = wp_kses_post(
                    $button_title,
                    apply_filters(
                            'e2pdf_helper_filter_button_title',
                            array(
                                'img' => array(
                                    'src' => true,
                                    'class' => true,
                                    'style' => true,
                                ),
                                'span' => array(
                                    'class' => true,
                                    'style' => true,
                                ),
                                'div' => array(
                                    'class' => true,
                                    'style' => true,
                                ),
                                'br' => array(
                                    'class' => true,
                                    'style' => true,
                                ),
                                'p' => array(
                                    'class' => true,
                                    'style' => true,
                                ),
                                'i' => array(
                                    'class' => true,
                                    'style' => true,
                                ),
                                'strong' => array(
                                    'class' => true,
                                    'style' => true,
                                ),
                                'b' => array(
                                    'class' => true,
                                    'style' => true,
                                ),
                                'em' => array(
                                    'class' => true,
                                    'style' => true,
                                ),
                            )
                    )
            );
        }
        return $button_title;
    }
}
