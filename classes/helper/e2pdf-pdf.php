<?php

/**
 * E2Pdf Pdf Helper
 * @copyright  Copyright 2017 https://e2pdf.com
 * @license    GPLv3
 * @version    1
 * @link       https://e2pdf.com
 * @since      0.00.01
 */
if (!defined('ABSPATH')) {
    die('Access denied.');
}

class Helper_E2pdf_Pdf {

    private $helper;

    public function __construct() {
        $this->helper = Helper_E2pdf_Helper::instance();
    }

    /**
     * Get Base64 Encoded Pdf
     * @param string $value - Pdf path
     * @return mixed - Base64 encoded Pdf OR FALSE
     */
    public function get_pdf($value, $extension = false) {
        $source = false;
        $protected = false;
        if ($value) {
            $value = trim($value);
            $site_url = site_url('/');
            $https = str_replace('http://', 'https://', $site_url);
            $http = str_replace('https://', 'http://', $site_url);
            if (!get_option('e2pdf_images_remote_request', '0')) {
                if (0 === strpos($value, $https)) {
                    $tmp_value = ABSPATH . substr($value, strlen($https));
                    if (@file_exists($tmp_value)) {
                        $value = $tmp_value;
                    }
                } elseif (0 === strpos($value, $http)) {
                    $tmp_value = ABSPATH . substr($value, strlen($http));
                    if (@file_exists($tmp_value)) {
                        $value = $tmp_value;
                    }
                }
            }
            if (!$source) {
                if ((0 === strpos($value, ABSPATH) || 0 === strpos($value, '/')) && @file_exists($value) && $this->get_extension($value)) {
                    if ($extension == 'formidable' && class_exists('FrmProFileField') && !@is_readable($value)) {
                        FrmProFileField::chmod($value, apply_filters('frm_protected_file_readonly_permission', 0400));
                        if (@!is_readable($value)) {
                            @chmod($value, apply_filters('frm_protected_file_readonly_permission', 0400));
                        }
                        if (@is_readable($value)) {
                            $protected = true;
                        }
                    }
                    $contents = @file_get_contents($value);
                    if ($contents) {
                        $source = base64_encode($contents);
                    }
                    if ($protected) {
                        FrmProFileField::chmod($value, 0200);
                    }
                } elseif (@file_exists(ABSPATH . $value) && $this->get_extension(ABSPATH . $value)) {
                    if ($extension == 'formidable' && class_exists('FrmProFileField') && !@is_readable(ABSPATH . $value)) {
                        FrmProFileField::chmod(ABSPATH . $value, apply_filters('frm_protected_file_readonly_permission', 0400));
                        if (@!is_readable(ABSPATH . $value)) {
                            @chmod(ABSPATH . $value, apply_filters('frm_protected_file_readonly_permission', 0400));
                        }
                        if (@is_readable(ABSPATH . $value)) {
                            $protected = true;
                        }
                    }
                    $contents = @file_get_contents(ABSPATH . $value);
                    if ($contents) {
                        $source = base64_encode($contents);
                    }
                    if ($protected) {
                        FrmProFileField::chmod(ABSPATH . $value, 0200);
                    }
                } elseif ($tmp_file = base64_decode($value, true)) {
                    if ($this->get_extension($tmp_file)) {
                        $source = $value;
                    }
                } elseif ($body = $this->get_by_url($value)) {
                    $source = base64_encode($body);
                }
            }
        }
        return $source;
    }

    /**
     * Get pdf by Url
     * @param string $url - Url to pdf
     * @return array();
     */
    public function get_by_url($url) {
        $response = wp_remote_get(
                $url,
                array(
                    'timeout' => get_option('e2pdf_images_timeout', '30'),
                    'sslverify' => false,
                )
        );
        if (wp_remote_retrieve_response_code($response) === 200) {
            return wp_remote_retrieve_body($response);
        } else {
            return '';
        }
    }

    public function get_allowed_extensions() {
        return array(
            'application/pdf' => 'pdf'
        );
    }

    public function get_extension($value = false) {
        if (!$value) {
            return false;
        }

        $extensions = $this->get_allowed_extensions();
        $extension = false;
        $mime = false;

        if (@file_exists($value)) {
            $file_extension = strtolower(pathinfo($value, PATHINFO_EXTENSION));
            if (in_array($file_extension, $extensions)) {
                return $file_extension;
            } elseif (function_exists('finfo_open') && function_exists('finfo_file')) {
                $f = finfo_open();
                $mime = @finfo_file($f, $value, FILEINFO_MIME_TYPE);
            }
        } else {
            $file_extension = strtolower(pathinfo($value, PATHINFO_EXTENSION));
            if (in_array($file_extension, $extensions)) {
                return $file_extension;
            } elseif (function_exists('finfo_open') && function_exists('finfo_buffer')) {
                $f = finfo_open();
                $mime = finfo_buffer($f, $value, FILEINFO_MIME_TYPE);
            }
        }

        if ($mime && isset($extensions[$mime])) {
            $extension = $extensions[$mime];
        }
        return $extension;
    }
}
