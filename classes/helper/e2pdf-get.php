<?php

/**
 * E2pdf Get Helper
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

class Helper_E2pdf_Get {

    private $get = array();
    private $page = null;

    /**
     * On init
     * Assign $_GET params to $get
     * @param string $url - Current url  
     */
    public function __construct($url) {
        $this->get = wp_parse_args($url);
        $this->page = reset($this->get);
        array_shift($this->get);
    }

    /**
     * Get value from $_GET
     * @param string $key - Array key
     * @return mixed - Return value by get key
     */
    public function get($key = false) {

        if (!$key) {
            if (!empty($this->get)) {
                return $this->get;
            } else {
                return array();
            }
        } else {
            if (isset($this->get[$key])) {
                return $this->get[$key];
            } else {
                return null;
            }
        }
    }

    /**
     * Get current page
     * @return string - Current page
     */
    public function get_page() {
        return $this->page;
    }
}
