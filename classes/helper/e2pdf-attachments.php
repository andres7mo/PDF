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

class Helper_E2pdf_Attachments {

    private $helper;

    public function __construct() {
        $this->helper = Helper_E2pdf_Helper::instance();
    }

    /**
     * Get Base64 Encoded Pdf
     * @param string $value - Pdf path
     * @return mixed - Base64 encoded Pdf OR FALSE
     */
    public function get_file($value, $extension = null) {
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
                    if ($extension->info('key') == 'formidable' && class_exists('FrmProFileField') && !@is_readable($value)) {
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
                    if ($extension->info('key') == 'formidable' && class_exists('FrmProFileField') && !@is_readable(ABSPATH . $value)) {
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

    public function get_attachments($list, $extension = null) {
        $attachments = array();
        if ($extension) {
            $value = array_filter(array_map('trim', preg_split('/[\n\,]+/', $list, 0, PREG_SPLIT_NO_EMPTY)));
            foreach ($value as $shortcode) {
                $files = array_filter(array_map('trim', preg_split('/[\n\,]+/', $extension->render($shortcode, array()), 0, PREG_SPLIT_NO_EMPTY)));
                foreach ($files as $file) {
                    $source = $this->get_file(trim($file), $extension);
                    if ($source) {
                        $attachments[] = array(
                            'value' => $source,
                            'name' => $this->get_attachment_name($file, $extension, $shortcode),
                            'description' => $this->get_attachment_description($file, $extension, $shortcode),
                        );
                    }
                }
            }
        }
        return $attachments;
    }

    public function get_attachment_name($file, $extension = null, $shortcode = '') {
        $name = basename(trim($file));
        if (false !== strpos($name, 'index.php?gf-download')) {
            $parse_url = wp_parse_args(wp_parse_url($name, PHP_URL_QUERY));
            if (!empty($parse_url['gf-download'])) {
                $name = basename($parse_url['gf-download']);
            }
        }
        return apply_filters('e2pdf_attachments_attachment_name', $name, $extension, $file, trim($shortcode));
    }

    public function get_attachment_description($file, $extension = null, $shortcode = '') {
        return apply_filters('e2pdf_attachments_attachment_description', '', $extension, $file, trim($shortcode));
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
        return apply_filters(
                'e2pdf_pdf_allowed_attachments_extensions',
                array(
                    'image/jpeg' => 'jpg',
                    'image/jpeg2' => 'jpeg',
                    'image/png' => 'png',
                    'image/gif' => 'gif',
                    'image/svg' => 'svg',
                    'image/svg+xml' => 'svg',
                    'image/webp' => 'webp',
                    'image/x-ms-bmp' => 'bmp',
                    'image/bmp' => 'bmp',
                    'image/tif' => 'tiff',
                    'image/tiff' => 'tiff',
                    'application/pdf' => 'pdf',
                    'text/csv' => 'csv',
                    'application/msword' => 'doc',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
                    'application/vnd.ms-excel' => '.xls',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => '.xlsx',
                    'application/vnd.oasis.opendocument.text' => 'odt',
                    'application/vnd.oasis.opendocument.spreadsheet' => 'ods',
                    'application/vnd.oasis.opendocument.presentation' => '.odp',
                )
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
