<?php

/**
 * E2pdf Files Helper
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

class Helper_E2pdf_Files {

    private $files = array();
    private $helper;

    /**
     * On init
     * Assign $_FILES array to $files
     */
    public function __construct() {
        $this->helper = Helper_E2pdf_Helper::instance();
        $this->files = $_FILES;
    }

    /**
     * Get value from $_FILES
     * 
     * @param string $key - Array key
     * 
     * @return mixed
     */
    public function get($key) {

        if (!$key) {
            if (!empty($this->files)) {
                return $this->files;
            } else {
                return false;
            }
        } else {

            if (isset($this->files[$key])) {
                return $this->files[$key];
            } else {
                return null;
            }
        }
    }

    public function get_upload_max_filesize() {
        $max_size = -1;
        if ($max_size < 0) {
            $post_max_size = $this->helper->load('convert')->to_bytes(ini_get('post_max_size'));
            if ($post_max_size > 0) {
                $max_size = $post_max_size;
            }

            $upload_max = $this->helper->load('convert')->to_bytes(ini_get('upload_max_filesize'));
            if ($upload_max > 0 && $upload_max < $max_size) {
                $max_size = $upload_max;
            }
        }
        return $this->helper->load('convert')->from_bytes($max_size);
    }

}
