<?php

/**
 * E2Pdf Image Helper
 * @copyright  Copyright 2017 https://e2pdf.com
 * @license    GPLv3
 * @version    1
 * @link       https://e2pdf.com
 * @since      0.00.01
 */
if (!defined('ABSPATH')) {
    die('Access denied.');
}

class Helper_E2pdf_Image {

    private $helper;

    public function __construct() {
        $this->helper = Helper_E2pdf_Helper::instance();
    }

    /**
     * Get Base64 Encoded Image
     * @param string $value - Image path
     * @return mixed - Base64 encoded image OR FALSE
     */
    public function get_image($value, $extension = false, $field = array()) {
        $source = false;
        $protected = false;
        if ($value) {
            preg_match('/src=(?:"|\')([^"\']*)(?:"|\')/', $value, $matches);
            if (isset($matches[1])) {
                $value = $matches[1];
            }
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
                    $contents = $this->get_optimized_image($value, $field);
                    if (!$contents) {
                        $contents = @file_get_contents($value);
                    }
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
                    $contents = $this->get_optimized_image(ABSPATH . $value, $field);
                    if (!$contents) {
                        $contents = @file_get_contents(ABSPATH . $value);
                    }
                    if ($contents) {
                        $source = base64_encode($contents);
                    }
                    if ($protected) {
                        FrmProFileField::chmod(ABSPATH . $value, 0200);
                    }
                } elseif (0 === strpos($value, 'data:image/')) {
                    $value = preg_replace('/^data:image\/[^;]+;base64,/', '', $value);
                    $tmp_file = base64_decode($value, true);
                    if ($this->get_extension($tmp_file)) {
                        $source = $value;
                    }
                } elseif (0 === strpos($value, '<svg') || 0 === strpos($value, '<?xml') || (false !== strpos($value, '<svg') && false !== strpos($value, '</svg>'))) {
                    if (false === strpos($value, 'xmlns')) {
                        $value = str_replace('<svg', '<svg xmlns="http://www.w3.org/2000/svg"', $value);
                    }
                    if (0 !== strpos($value, '<svg') && 0 !== strpos($value, '<?xml')) {
                        $position = strpos($value, '<svg');
                        $value = substr($value, $position);
                    }
                    $source = base64_encode($value);
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

    public function get_optimized_image($file, $field = array()) {
        if (!empty($field['properties']['quality']) && $field['properties']['quality'] != '-1' && isset($field['width']) && isset($field['height'])) {
            $quality = (int) $field['properties']['quality'];
            $editor = wp_get_image_editor($file);
            if (is_wp_error($editor) || !$editor->get_size() || !class_exists('ReflectionProperty')) {
                return '';
            }
            $image_size = $editor->get_size();
            if (($image_size['width'] > ((int) $field['width'] * $quality)) || ($image_size['height'] > ((int) $field['height'] * $quality))) {
                $width = (int) $field['width'] * $quality;
                $height = (int) $field['height'] * $quality;
            } else {
                return '';
            }
            if (is_wp_error($editor->resize($width, $height, false))) {
                return '';
            }
            $editor->set_quality(100);
            $reflection = new ReflectionProperty(get_class($editor), 'mime_type');
            $reflection->setAccessible(true);
            $reflection->getValue($editor);
            $mime_type = $reflection->getValue($editor);

            $reflection = new ReflectionProperty(get_class($editor), 'image');
            $reflection->setAccessible(true);
            $image = $reflection->getValue($editor);

            if ($image && $editor instanceof WP_Image_Editor_GD) {
                ob_start();
                switch ($mime_type) {
                    case 'image/png':
                        imagepng($image);
                        break;
                    case 'image/gif':
                        imagegif($image);
                        break;
                    case 'image/webp':
                        if (function_exists('imagewebp')) {
                            imagewebp($image, null, 100);
                        }
                        break;
                    default:
                        imagejpeg($image, null, 100);
                        break;
                }
                $contents = ob_get_contents();
                ob_end_clean();
                return $contents;
            } elseif ($image && $editor instanceof WP_Image_Editor_Imagick) {
                $contents = $image->getImagesBlob();
                return $contents;
            }
        }
        return '';
    }

    public function get_base64_image($url) {
        if ($ext = $this->get_extension($url)) {
            $type = array_search($ext, $this->get_allowed_extensions());
            if ($type !== false && $body = $this->get_by_url($url)) {
                return 'data:' . $type . ';base64,' . base64_encode($body);
            }
        }
        return $url;
    }

    /**
     * Get image by Url
     * @param string $url - Url to image
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
            } elseif (function_exists('image_type_to_mime_type') && function_exists('getimagesizefromstring')) {
                $size = getimagesizefromstring($value);
                if (isset($size['mime'])) {
                    $mime = $size['mime'];
                }
            }
        }

        if ($mime && isset($extensions[$mime])) {
            $extension = $extensions[$mime];
        }
        return $extension;
    }
}
